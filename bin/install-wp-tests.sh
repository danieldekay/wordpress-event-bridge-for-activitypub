#!/usr/bin/env bash

if [ $# -lt 3 ]; then
	echo "usage: $0 <db-name> <db-user> <db-pass> [db-host] [wp-version] [skip-database-creation] [skip-wp-install] [skip-plugins] [skip-test-suite]"
	exit 1
fi

DB_NAME=$1
DB_USER=$2
DB_PASS=$3
DB_HOST=${4-localhost}
WP_VERSION=${5-latest}
SKIP_DB_CREATE=${6-false}
SKIP_WP_INSTALL=${7-false}
SKIP_PLUGINS_INSTALL=${8-false}
SKIP_TEST_SUITE_INSTALL=${9-false}

# Initialize the plugin list
PLUGINS=""

# Parse optional --plugins argument
while [[ "$#" -gt 0 ]]; do
    case $1 in
        --plugins) PLUGINS="$2"; shift ;;
    esac
    shift
done

TMPDIR=${TMPDIR-/tmp}
TMPDIR=$(echo $TMPDIR | sed -e "s/\/$//")
WP_TESTS_DIR=${WP_TESTS_DIR-$TMPDIR/wordpress-tests-lib}
WP_CORE_DIR=${WP_CORE_DIR-$TMPDIR/wordpress}

download() {
    if [ `which curl` ]; then
        curl -s "$1" > "$2";
    elif [ `which wget` ]; then
        wget -nv -O "$2" "$1"
    fi
}

if [[ $WP_VERSION =~ ^[0-9]+\.[0-9]+\-(beta|RC)[0-9]+$ ]]; then
	WP_BRANCH=${WP_VERSION%\-*}
	WP_TESTS_TAG="branches/$WP_BRANCH"

elif [[ $WP_VERSION =~ ^[0-9]+\.[0-9]+$ ]]; then
	WP_TESTS_TAG="branches/$WP_VERSION"
elif [[ $WP_VERSION =~ [0-9]+\.[0-9]+\.[0-9]+ ]]; then
	if [[ $WP_VERSION =~ [0-9]+\.[0-9]+\.[0] ]]; then
		# version x.x.0 means the first release of the major version, so strip off the .0 and download version x.x
		WP_TESTS_TAG="tags/${WP_VERSION%??}"
	else
		WP_TESTS_TAG="tags/$WP_VERSION"
	fi
elif [[ $WP_VERSION == 'nightly' || $WP_VERSION == 'trunk' ]]; then
	WP_TESTS_TAG="trunk"
else
	# http serves a single offer, whereas https serves multiple. we only want one
	download http://api.wordpress.org/core/version-check/1.7/ /tmp/wp-latest.json
	grep '[0-9]+\.[0-9]+(\.[0-9]+)?' /tmp/wp-latest.json
	LATEST_VERSION=$(grep -o '"version":"[^"]*' /tmp/wp-latest.json | sed 's/"version":"//')
	if [[ -z "$LATEST_VERSION" ]]; then
		echo "Latest WordPress version could not be found"
		exit 1
	fi
	WP_TESTS_TAG="tags/$LATEST_VERSION"
fi
set -ex

install_wp() {
	if [ "$SKIP_WP_INSTALL" = "true" ]; then
        echo "Skipping WordPress installation."
        return 0
    fi

	if [ -d $WP_CORE_DIR ]; then
		return;
	fi

	mkdir -p $WP_CORE_DIR

	if [[ $WP_VERSION == 'nightly' || $WP_VERSION == 'trunk' ]]; then
		mkdir -p $TMPDIR/wordpress-trunk
		rm -rf $TMPDIR/wordpress-trunk/*
		svn export --quiet https://core.svn.wordpress.org/trunk $TMPDIR/wordpress-trunk/wordpress
		mv $TMPDIR/wordpress-trunk/wordpress/* $WP_CORE_DIR
	else
		if [ $WP_VERSION == 'latest' ]; then
			local ARCHIVE_NAME='latest'
		elif [[ $WP_VERSION =~ [0-9]+\.[0-9]+ ]]; then
			# https serves multiple offers, whereas http serves single.
			download https://api.wordpress.org/core/version-check/1.7/ $TMPDIR/wp-latest.json
			if [[ $WP_VERSION =~ [0-9]+\.[0-9]+\.[0] ]]; then
				# version x.x.0 means the first release of the major version, so strip off the .0 and download version x.x
				LATEST_VERSION=${WP_VERSION%??}
			else
				# otherwise, scan the releases and get the most up to date minor version of the major release
				local VERSION_ESCAPED=`echo $WP_VERSION | sed 's/\./\\\\./g'`
				LATEST_VERSION=$(grep -o '"version":"'$VERSION_ESCAPED'[^"]*' $TMPDIR/wp-latest.json | sed 's/"version":"//' | head -1)
			fi
			if [[ -z "$LATEST_VERSION" ]]; then
				local ARCHIVE_NAME="wordpress-$WP_VERSION"
			else
				local ARCHIVE_NAME="wordpress-$LATEST_VERSION"
			fi
		else
			local ARCHIVE_NAME="wordpress-$WP_VERSION"
		fi
		download https://wordpress.org/${ARCHIVE_NAME}.tar.gz  $TMPDIR/wordpress.tar.gz
		tar --strip-components=1 -zxmf $TMPDIR/wordpress.tar.gz -C $WP_CORE_DIR
	fi

	download https://raw.githubusercontent.com/markoheijnen/wp-mysqli/master/db.php $WP_CORE_DIR/wp-content/db.php
}

install_test_suite() {
	if [ "$SKIP_TEST_SUITE_INSTALL" = "true" ]; then
        echo "Skipping test suite installation."
        return 0
    fi

	# portable in-place argument for both GNU sed and Mac OSX sed
	if [[ $(uname -s) == 'Darwin' ]]; then
		local ioption='-i.bak'
	else
		local ioption='-i'
	fi

	# set up testing suite if it doesn't yet exist
	if [ ! -d $WP_TESTS_DIR ]; then
		# set up testing suite
		mkdir -p $WP_TESTS_DIR
		rm -rf $WP_TESTS_DIR/{includes,data}
		svn export --quiet --ignore-externals https://develop.svn.wordpress.org/${WP_TESTS_TAG}/tests/phpunit/includes/ $WP_TESTS_DIR/includes
		svn export --quiet --ignore-externals https://develop.svn.wordpress.org/${WP_TESTS_TAG}/tests/phpunit/data/ $WP_TESTS_DIR/data
	fi

	if [ ! -f wp-tests-config.php ]; then
		download https://develop.svn.wordpress.org/${WP_TESTS_TAG}/wp-tests-config-sample.php "$WP_TESTS_DIR"/wp-tests-config.php
		# remove all forward slashes in the end
		WP_CORE_DIR=$(echo $WP_CORE_DIR | sed "s:/\+$::")
		sed $ioption "s:dirname( __FILE__ ) . '/src/':'$WP_CORE_DIR/':" "$WP_TESTS_DIR"/wp-tests-config.php
		sed $ioption "s:__DIR__ . '/src/':'$WP_CORE_DIR/':" "$WP_TESTS_DIR"/wp-tests-config.php
		sed $ioption "s/youremptytestdbnamehere/$DB_NAME/" "$WP_TESTS_DIR"/wp-tests-config.php
		sed $ioption "s/yourusernamehere/$DB_USER/" "$WP_TESTS_DIR"/wp-tests-config.php
		sed $ioption "s/yourpasswordhere/$DB_PASS/" "$WP_TESTS_DIR"/wp-tests-config.php
		sed $ioption "s|localhost|${DB_HOST}|" "$WP_TESTS_DIR"/wp-tests-config.php
	fi

}

recreate_db() {
	shopt -s nocasematch
	if [[ $1 =~ ^(y|yes)$ ]]
	then
		mysqladmin drop $DB_NAME -f --user="$DB_USER" --password="$DB_PASS"$EXTRA
		create_db
		echo "Recreated the database ($DB_NAME)."
	else
		echo "Leaving the existing database ($DB_NAME) in place."
	fi
	shopt -u nocasematch
}

create_db() {
	mysqladmin create $DB_NAME --user="$DB_USER" --password="$DB_PASS"$EXTRA
}

install_db() {

	if [ ${SKIP_DB_CREATE} = "true" ]; then
		return 0
	fi

	# parse DB_HOST for port or socket references
	local PARTS=(${DB_HOST//\:/ })
	local DB_HOSTNAME=${PARTS[0]};
	local DB_SOCK_OR_PORT=${PARTS[1]};
	local EXTRA=""

	if ! [ -z $DB_HOSTNAME ] ; then
		if [ $(echo $DB_SOCK_OR_PORT | grep -e '^[0-9]\{1,\}$') ]; then
			EXTRA=" --host=$DB_HOSTNAME --port=$DB_SOCK_OR_PORT --protocol=tcp"
		elif ! [ -z $DB_SOCK_OR_PORT ] ; then
			EXTRA=" --socket=$DB_SOCK_OR_PORT"
		elif ! [ -z $DB_HOSTNAME ] ; then
			EXTRA=" --host=$DB_HOSTNAME --protocol=tcp"
		fi
	fi

	# create database
	if [ $(mysql --user="$DB_USER" --password="$DB_PASS"$EXTRA --execute='show databases;' | grep ^$DB_NAME$) ]
	then
		echo "Reinstalling will delete the existing test database ($DB_NAME)"
		recreate_db yes
	else
		create_db
	fi
}

install_wp_plugin() {
    PLUGIN_NAME=$1

	mkdir -p "$WP_CORE_DIR/wp-content/plugins/"

	if [ -d "$WP_CORE_DIR/wp-content/plugins/$PLUGIN_NAME" ]; then
		return;
	fi

	# Get the latest tag.
	if [ -z "$2" ]; then
		LATEST_TAG=$(svn log https://plugins.svn.wordpress.org/$PLUGIN_NAME/tags --limit 1 | awk 'NR == 4 { print $4 }')
		PLUGIN_VERSION=$LATEST_TAG
	else
		PLUGIN_VERSION=$2
	fi

	if [ -n "$PLUGIN_VERSION" ]; then
		PLUGIN_FILE="$PLUGIN_NAME.$PLUGIN_VERSION.zip"
	else
		PLUGIN_FILE="$PLUGIN_NAME.zip"
	fi

	URL="https://downloads.wordpress.org/plugin/$PLUGIN_FILE"

    # Check if the plugin file already exists
    if ! test -f "$TMPDIR/$PLUGIN_FILE"; then
        download $URL "$TMPDIR/$PLUGIN_FILE"
    fi

    # Unzip the plugin into the WordPress must-use plugins directory
    unzip -q -o "$TMPDIR/$PLUGIN_FILE" -d "$WP_CORE_DIR/wp-content/plugins/"
}

install_wp_plugin_mec() {
	mkdir -p "$WP_CORE_DIR/wp-content/plugins/"

	if [ -d "$WP_CORE_DIR/wp-content/plugins/modern-events-calendar-lite" ]; then
		return;
	fi

	PLUGIN_VERSION="v7.15.0"

    URL="https://codeberg.org/Event-Federation/wordpress-modern-events-calendar-lite"

	git clone $URL "$WP_CORE_DIR/wp-content/plugins/modern-events-calendar-lite"
}

install_activitypub_plugin() {
	# We also need it's test classes, therefore we use the git repository.
	mkdir -p "$WP_CORE_DIR/wp-content/plugins/"

	ACTIVITYPUB_PLUGIN_VERSION="5.8.0"

	if [ -d "$WP_CORE_DIR/wp-content/plugins/activitypub" ]; then
	    git -C "$WP_CORE_DIR/wp-content/plugins/activitypub" fetch --tags
	    git -C "$WP_CORE_DIR/wp-content/plugins/activitypub" checkout $ACTIVITYPUB_PLUGIN_VERSION
		return;
	fi

    URL="https://github.com/Automattic/wordpress-activitypub"

	git clone $URL "$WP_CORE_DIR/wp-content/plugins/activitypub"
	git -C "$WP_CORE_DIR/wp-content/plugins/activitypub" checkout $ACTIVITYPUB_PLUGIN_VERSION
}

install_wp_plugins() {
	if [ "$SKIP_PLUGINS_INSTALL" = "true" ]; then
        echo "Skipping WordPress plugin installation."
        return 0
    fi
	# Install the one and only ActivityPub plugin (greetings @pfefferle).
	install_activitypub_plugin
	# Install (not-activate) all supported event plugins.
	install_wp_plugin the-events-calendar "6.11.2"
	install_wp_plugin very-simple-event-list "18.1"
	install_wp_plugin gatherpress
	install_wp_plugin eventprime-event-calendar-management
	install_wp_plugin events-manager "6.6.4.4"
	install_wp_plugin wp-event-manager "3.1.47"
	install_wp_plugin wp-event-solution "4.0.26"
	install_wp_plugin event-organiser "3.12.8"
	install_wp_plugin eventon-lite "2.4"
	# Mec is not installable via wordpress.org, we use our own mirror.
	install_wp_plugin_mec
}

install_wp
install_wp_plugins
install_test_suite
install_db
