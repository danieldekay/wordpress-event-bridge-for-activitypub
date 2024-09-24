FROM php:8.1.29-alpine

RUN mkdir /app

WORKDIR /app

# Install Git, NPM & needed libraries
RUN apk update \
    && apk add bash git nodejs npm gettext subversion mysql mysql-client zip \
    && rm -f /var/cache/apk/*

RUN docker-php-ext-install mysqli

# Install Xdebug
RUN apk add --no-cache $PHPIZE_DEPS \
    && apk add --update linux-headers \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apk del --purge $PHPIZE_DEPS \
    && echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.mode=debug" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.idekey=VSCODE" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# Install Composer
RUN EXPECTED_CHECKSUM=$(curl -s https://composer.github.io/installer.sig) \
    && curl https://getcomposer.org/installer -o composer-setup.php \
    && ACTUAL_CHECKSUM="$(php -r "echo hash_file('sha384', 'composer-setup.php');")" \
    && if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]; then >&2 echo 'ERROR: Invalid installer checksum'; rm composer-setup.php; exit 1; fi \
    && php composer-setup.php --quiet \
    && php -r "unlink('composer-setup.php');" \
    && mv composer.phar /usr/local/bin/composer

RUN curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar && \
    chmod +x wp-cli.phar && \
    mv wp-cli.phar /usr/local/bin/wp

COPY composer.json composer.lock /app/
RUN composer install --no-scripts --no-autoloader
RUN composer global require yoast/phpunit-polyfills:"^3.0" --dev
ENV PATH="/root/.composer/vendor/bin:${PATH}"

RUN chmod +x -R ./
