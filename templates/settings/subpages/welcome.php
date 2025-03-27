<?php
/**
 * Status/Welcome page for the Event Bridge for ActivityPub admin interface.
 *
 * @package Event_Bridge_For_ActivityPub
 * @since   1.0.0
 * @license AGPL-3.0-or-later
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

use Event_Bridge_For_ActivityPub\Setup;
use Event_Bridge_For_ActivityPub\Admin\General_Admin_Notices;
use Event_Bridge_For_ActivityPub\Admin\Settings_Page;
use Event_Bridge_For_ActivityPub\Admin\Health_Check;

\load_template(
	__DIR__ . '/../menu.php',
	true,
	array(
		'welcome' => 'active',
	)
);

$active_event_plugins                   = Setup::get_instance()->get_active_event_plugins();
$activitypub_plugin_is_active           = Setup::get_instance()->is_activitypub_plugin_active();
$event_bridge_for_activitypub_status_ok = true;
$example_event_post                     = Health_Check::get_most_recent_event_posts();

if ( empty( $example_event_post ) ) {
	$example_event_post          = 'https://yoursite.com/events/event-name';
	$example_event_post_is_dummy = true;
} else {
	$example_event_post          = \get_permalink( $example_event_post[0] );
	$example_event_post_is_dummy = false;
}

global $wp_filesystem;
WP_Filesystem();

?>

<div class="activitypub-settings event-bridge-for-activitypub-settings event-bridge-for-activitypub-settings-page hide-if-no-js">
	<div class="box">
		<h2><?php \esc_html_e( 'Status', 'event-bridge-for-activitypub' ); ?></h2>
		<p><?php \esc_html_e( 'The Event Bridge for ActivityPub detected the following (activated) event plugins:', 'event-bridge-for-activitypub' ); ?></p>
			<?php
			if ( ! $activitypub_plugin_is_active ) {
				$notice = General_Admin_Notices::get_admin_notice_activitypub_plugin_not_enabled();
				echo '<p>⚠' . \wp_kses( $notice, General_Admin_Notices::ALLOWED_HTML ) . '</p>';
			} elseif ( empty( $active_event_plugins ) ) {
				$notice = General_Admin_Notices::get_admin_notice_no_supported_event_plugin_active();
				echo '<p>⚠' . \wp_kses( $notice, General_Admin_Notices::ALLOWED_HTML ) . '</p>';
			}
			?>
			<?php foreach ( $active_event_plugins as $active_event_plugin ) { ?>
			<h3><?php echo esc_html( $active_event_plugin->get_plugin_name() ); ?>:</h3>
			<ul class="event-bridge-for-activitypub-list">
				<li>
					<?php
					if ( in_array( $active_event_plugin::get_post_type(), get_option( 'activitypub_support_post_types', array() ), true ) ) {
						echo '&#9989 ';
						$status_message_post_type_enabled = sprintf(
							/* translators: 1: the name of the event plugin a admin notice is shown. 2: The name of the ActivityPub plugin. */
							_x(
								'The ActivityPub support for the event post type of the plugin <i>%2$s</i> is enabled in the <a href="%3$s">%1$s settings</a>.',
								'admin notice',
								'event-bridge-for-activitypub'
							),
							esc_html( get_plugin_data( ACTIVITYPUB_PLUGIN_FILE )['Name'] ),
							esc_html( $active_event_plugin->get_plugin_name() ),
							admin_url( 'options-general.php?page=activitypub&tab=event-bridge-for-activitypub&subpage=settings' )
						);
					} else {
						$event_bridge_for_activitypub_status_ok = false;
						echo '&#10060 ';
						$status_message_post_type_enabled = sprintf(
							/* translators: 1: the name of the event plugin a admin notice is shown. 2: The name of the ActivityPub plugin. */
							_x(
								'The post type for events of the plugin <i>%2$s</i> is <b>not enabled</b> in the <a href="%3$s">%1$s settings</a>.',
								'admin notice',
								'event-bridge-for-activitypub'
							),
							esc_html( get_plugin_data( ACTIVITYPUB_PLUGIN_FILE )['Name'] ),
							esc_html( $active_event_plugin->get_plugin_name() ),
							admin_url( 'options-general.php?page=activitypub&tab=event-bridge-for-activitypub&subpage=settings' )
						);
					}
					$allowed_html = array(
						'a' => array(
							'href'  => true,
							'title' => true,
						),
						'b' => array(),
						'i' => array(),
					);
					echo \wp_kses( $status_message_post_type_enabled, $allowed_html );
					?>
				</li>
				<li>
					<?php
					if ( Health_Check::test_if_event_transformer_is_used( $active_event_plugin ) ) {
						echo '&#9989 ';
						esc_html_e( 'The Event Bridge for ActivityPub successfully registered to the ActivityPub plugin.', 'event-bridge-for-activitypub' );
					} else {
						$event_bridge_for_activitypub_status_ok = false;
						echo '&#10060 ';
						esc_html_e( 'The Event Bridge for ActivityPub could not register to the ActivityPub plugin.', 'event-bridge-for-activitypub' );
					}
					?>
				</li>
			</ul>
		<?php } ?>
	</div>

	<?php if ( get_option( 'event_bridge_for_activitypub_initially_activated', true ) ) : ?>
		<a href="<?php echo esc_url( admin_url( 'options-general.php?page=' . Settings_Page::SETTINGS_SLUG ) . '&tab=settings' ); ?>" role="button">
			<button class="button button-primary">
				<strong>→</strong> <?php \esc_html_e( 'Continue your setup', 'event-bridge-for-activitypub' ); ?>
			</button>
		</a>

	<?php else : ?>

	<div class="box">
		<h2><?php \esc_html_e( 'How to Check if It\'s Working', 'event-bridge-for-activitypub' ); ?></h2>
		<?php
		if ( ! $event_bridge_for_activitypub_status_ok ) {
			echo '<div class="notice-warning"><p>' . \esc_html__( 'Please fix the status issues above first.', 'event-bridge-for-activitypub' ) . '</p></div>';
		}
		?>
		<p><?php esc_html_e( 'Most of the magic happens behind the scenes, but here is how you can verify that your events are ready to be discovered:', 'event-bridge-for-activitypub' ); ?></p>
		<div class="event-bridge-for-activitypub-settings-accordion">
			<h4 class="event-bridge-for-activitypub-settings-accordion-heading">
				<button aria-expanded="false" class="event-bridge-for-activitypub-settings-accordion-trigger" aria-controls="event-bridge-for-activitypub-help-accordion-mastodon" type="button">
					<span class="title">
						1. 
						<img src="<?php echo esc_url( plugins_url( '/assets/img/mastodon.svg', EVENT_BRIDGE_FOR_ACTIVITYPUB_PLUGIN_FILE ) ); ?>" alt="Mastodon Icon" class="event-bridge-for-activitypub-settings-inline-icon">
						<?php \esc_html_e( 'Using Your Mastodon Account', 'event-bridge-for-activitypub' ); ?>
					</span>
					<span class="icon"></span>
				</button>
			</h4>
			<div id="event-bridge-for-activitypub-help-accordion-mastodon" class="event-bridge-for-activitypub-settings-accordion-panel" hidden="hidden">
				<div class="notice notice-warning inline"><p>ℹ <?php \esc_html_e( 'Note that Mastodon can receive ActivityPub Event objects but does not yet support updating them. This means that if Mastodon has already received an event, it will always display the first version it encountered.', 'event-bridge-for-activitypub' ); ?> <?php esc_html_e( 'See the related tracking issue:', 'event-bridge-for-activitypub' ); ?> <a href="https://github.com/mastodon/mastodon/issues/31114" target="_blank">https://github.com/mastodon/mastodon/issues/31114</a></p></div>
				<ol class="event-bridge-for-activitypub-settings-numbered-list">
					<li><?php \esc_html_e( 'Log into your Mastodon account.', 'event-bridge-for-activitypub' ); ?></li>
					<li>
						<?php \esc_html_e( 'In the search bar, type or copy the full URL of one of your event pages. For example:', 'event-bridge-for-activitypub' ); ?>
						<code class="event-bridge-for-activitypub-settings-example-url"><?php echo \esc_url( $example_event_post ); ?></code>
					</li>
					<li><?php \esc_html_e( 'If everything is set up correctly, you\'ll see a post representing your event. It should include the event\'s image, title, and a brief description.', 'event-bridge-for-activitypub' ); ?></li>
				</ol>
			</div>
			<h4 class="event-bridge-for-activitypub-settings-accordion-heading">
				<button aria-expanded="false" class="event-bridge-for-activitypub-settings-accordion-trigger" aria-controls="event-bridge-for-activitypub-help-accordion-mobilizon" type="button">
					<span class="title">
						2. 
						<img src="<?php echo esc_url( plugins_url( '/assets/img/mobilizon.svg', EVENT_BRIDGE_FOR_ACTIVITYPUB_PLUGIN_FILE ) ); ?>" alt="Mastodon Icon" class="event-bridge-for-activitypub-settings-inline-icon">
						<?php \esc_html_e( 'Using Your Mobilizon Account', 'event-bridge-for-activitypub' ); ?>
					</span>
					<span class="icon"></span>
				</button>
			</h4>
			<div id="event-bridge-for-activitypub-help-accordion-mobilizon" class="event-bridge-for-activitypub-settings-accordion-panel" hidden="hidden">
				<div class="notice notice-error inline"><p>⚠️ <?php \esc_html_e( 'Note that Mobilizon has significant interoperability issues (at least up to version 5.1).', 'event-bridge-for-activitypub' ); ?> <?php esc_html_e( 'See the related tracking issue:', 'event-bridge-for-activitypub' ); ?> <a href="https://framagit.org/framasoft/mobilizon/-/issues/1669" target="_blank">https://framagit.org/framasoft/mobilizon/-/issues/1669</a></p></div>
				<!-- <ol class="event-bridge-for-activitypub-settings-numbered-list">
					<li><?php \esc_html_e( 'Log into your Mobilizon account.', 'event-bridge-for-activitypub' ); ?></li>
					<li>
						<?php \esc_html_e( 'In the search bar, type or copy the full URL of one of your event pages. For example:', 'event-bridge-for-activitypub' ); ?>
						<code class="event-bridge-for-activitypub-settings-example-url"><?php echo \esc_url( $example_event_post ); ?></code>
					</li>
					<li><?php \esc_html_e( 'If everything is set up correctly, you\'ll see a full representation of your WordPress event. This will include the event\'s banner image, title, complete description, start and end times, categories, tags, and whether it\'s an online event.', 'event-bridge-for-activitypub' ); ?></li>
				</ol> -->
			</div>
			<h4 class="event-bridge-for-activitypub-settings-accordion-heading">
				<button aria-expanded="false" class="event-bridge-for-activitypub-settings-accordion-trigger" aria-controls="event-bridge-for-activitypub-help-accordion-fediverse" type="button">
					<span class="title">
						3. 
						<img src="<?php echo esc_url( plugins_url( '/assets/img/fediverse.svg', EVENT_BRIDGE_FOR_ACTIVITYPUB_PLUGIN_FILE ) ); ?>" alt="Mastodon Icon" class="event-bridge-for-activitypub-settings-inline-icon">
						<?php \esc_html_e( 'Using Any Other Fediverse Application', 'event-bridge-for-activitypub' ); ?>
					</span>
					<span class="icon"></span>
				</button>
			</h4>
			<div id="event-bridge-for-activitypub-help-accordion-fediverse" class="event-bridge-for-activitypub-settings-accordion-panel" hidden="hidden">
				<p><?php \esc_html_e( 'Of course any other application in the Fediverse should work as well. Most applications support importing external content via searching for the contents full URL.', 'event-bridge-for-activitypub' ); ?></p>
				<ol class="event-bridge-for-activitypub-settings-numbered-list">
					<li><?php \esc_html_e( 'Log into your account on any Fediverse app.', 'event-bridge-for-activitypub' ); ?></li>
					<li>
						<?php \esc_html_e( 'In the search bar, type or copy the full URL of one of your event pages. For example:', 'event-bridge-for-activitypub' ); ?>
						<code class="event-bridge-for-activitypub-settings-example-url"><?php echo \esc_url( $example_event_post ); ?></code>
					</li>
					<li><?php \esc_html_e( 'If the application which your are using natively supports ActivityPub events, you should see a representation of your WordPress event. If your application is supports receiving ActivityPub events you will get a post which summarizes the event. Keep in mind that some apps may not support events at all.', 'event-bridge-for-activitypub' ); ?></li>
				</ol>
			</div>
			<h4 class="event-bridge-for-activitypub-settings-accordion-heading">
				<button aria-expanded="false" class="event-bridge-for-activitypub-settings-accordion-trigger" aria-controls="event-bridge-for-activitypub-help-accordion-advanced" type="button">
					<span class="title">
						4. 
						<img src="<?php echo esc_url( plugins_url( '/assets/img/activitypub.svg', EVENT_BRIDGE_FOR_ACTIVITYPUB_PLUGIN_FILE ) ); ?>" alt="Mastodon Icon" class="event-bridge-for-activitypub-settings-inline-icon">
						<?php \esc_html_e( 'Advanced: Verifying the ActivityStreams JSON', 'event-bridge-for-activitypub' ); ?></span>
					<span class="icon"></span>
				</button>
			</h4>
			<div id="event-bridge-for-activitypub-help-accordion-advanced" class="event-bridge-for-activitypub-settings-accordion-panel" hidden="hidden">
				<p>
					<?php
					// Assume $event_url contains the dynamic URL, and '?activitypub' is appended to it.
					$activitypub_url = esc_url( $example_event_post . '?activitypub' );

					// Prepare the activitypub part wrapped in a <code> element.
					$activitypub_query = '<nobr><code>' . esc_html( '?activitypub' ) . '</code></nobr>';

					$activitypub_url_html = '<a href="' . esc_url( $activitypub_url ) . '" target="_blank">' . esc_html( $activitypub_url ) . '</a>';

					// Translator comment to explain the placeholder.
					/* translators: %1$s is the <code>?activitypub</code> string, and %2$s is the full URL of an example event */
					$raw_string = sprintf( __( 'For more technical users, you can inspect how your event is converted into an ActivityPub object. Simply append %1$s to the end of 	any single event pages URL to view the raw ActivityStreams JSON data (e.g., %2$s).', 'event-bridge-for-activitypub' ), $activitypub_query, $activitypub_url_html );

					// Allowed HTML tags in the string (only <code> and <a>).
					$allowed_html = array(
						'a'    => array(
							'href'   => array(),
							'target' => array(),
						),
						'nobr' => array(),
						'code' => array(),
					);

					// Output the formatted string with the allowed HTML elements.
					echo wp_kses( $raw_string, $allowed_html );
					?>
				</p>
			</div>
		</div>
	</div>

	<div class="box">
		<h2><?php \esc_html_e( 'Acknowledgement', 'event-bridge-for-activitypub' ); ?></h2>
		<p><a href="https://NLnet.nl"><img src="<?php echo esc_url( plugins_url( '/assets/img/acknowledgement-NLnet.svg', EVENT_BRIDGE_FOR_ACTIVITYPUB_PLUGIN_FILE ) ); ?>" alt="" class="logo-center"></a> <a href="https://NLnet.nl/NGI0"><img src="<?php echo esc_url( plugins_url( '/assets/img/acknowledgement-NGI0Entrust.svg', EVENT_BRIDGE_FOR_ACTIVITYPUB_PLUGIN_FILE ) ); ?>" alt="" class="logo-center"> </a></p>
		<p>The development of this plugin was funded through the <a href="https://NLnet.nl/entrust">NGI0 Entrust</a> Fund, a fund established by <a href="https://nlnet.nl">NLnet</a> with financial support from the European Commission's <a href="https://ngi.eu">Next Generation Internet</a> programme, under the aegis of <a href="https://commission.europa.eu/about-european-commission/departments-and-executive-agencies/communications-networks-content-and-technology_en">DG Communications Networks, Content and Technology</a> under grant agreement N<sup>o</sup> 101069594.</p>
	</div>
	<?php endif; ?>

</div>



