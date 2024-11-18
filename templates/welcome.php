<?php
/**
 * Status page for the ActivityPub Event Bridge.
 *
 * @package ActivityPub_Event_Bridge
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

use ActivityPub_Event_Bridge\Setup;
use ActivityPub_Event_Bridge\Admin\Settings_Page;
use ActivityPub_Event_Bridge\Admin\Health_Check;
use ActivityPub_Event_Bridge\Admin\General_Admin_Notices;

\load_template(
	__DIR__ . '/admin-header.php',
	true,
	array(
		'welcome' => 'active',
	)
);

$active_event_plugins               = Setup::get_instance()->get_active_event_plugins();

if ( empty( $active_event_plugins ) ) {
	return;
}

$activitypub_event_bridge_status_ok = true;
$example_event_post                 = Health_Check::get_most_recent_event_posts();

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

<div class="activitypub-event-bridge-settings activitypub-event-bridge-settings-page hide-if-no-js">
	<div class="box">
		<h2><?php \esc_html_e( 'Status', 'activitypub-event-bridge' ); ?></h2>
		<p><?php \esc_html_e( 'The ActivityPub Event Bridge detected the following (activated) event plugins:', 'activitypub-event-bridge' ); ?></p>
			<?php foreach ( $active_event_plugins as $active_event_plugin ) { ?>
			<h3><?php echo esc_html( $active_event_plugin->get_plugin_name() ); ?>:</h3>
			<ul class="activitypub-event-bridge-list">
				<li>
					<?php
					if ( in_array( $active_event_plugin::get_post_type(), get_option( 'activitypub_support_post_types', array() ), true ) ) {
						echo '&#9989 ';
						$status_message_post_type_enabled = sprintf(
							/* translators: 1: the name of the event plugin a admin notice is shown. 2: The name of the ActivityPub plugin. */
							_x(
								'The ActivityPub support for the event post type of the plugin <i>%2$s</i> is enabled in the <a href="%3$s">%1$s settings</a>.',
								'admin notice',
								'activitypub-event-bridge'
							),
							esc_html( get_plugin_data( ACTIVITYPUB_PLUGIN_FILE )['Name'] ),
							esc_html( $active_event_plugin->get_plugin_name() ),
							admin_url( 'options-general.php?page=activitypub&tab=settings' )
						);
					} else {
						$activitypub_event_bridge_status_ok = false;
						echo '&#10060 ';
						$status_message_post_type_enabled = sprintf(
							/* translators: 1: the name of the event plugin a admin notice is shown. 2: The name of the ActivityPub plugin. */
							_x(
								'The post type for events of the plugin <i>%2$s</i> is <b>not enabled</b> in the <a href="%3$s">%1$s settings</a>.',
								'admin notice',
								'activitypub-event-bridge'
							),
							esc_html( get_plugin_data( ACTIVITYPUB_PLUGIN_FILE )['Name'] ),
							esc_html( $active_event_plugin->get_plugin_name() ),
							admin_url( 'options-general.php?page=activitypub&tab=settings' )
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
						esc_html_e( 'The ActivityPub Event Bridge successfully registered to the ActivityPub plugin.', 'activitypub-event-bridge' );
					} else {
						$activitypub_event_bridge_status_ok = false;
						echo '&#10060 ';
						esc_html_e( 'The ActivityPub Event Bridge could not register to the ActivityPub plugin.', 'activitypub-event-bridge' );
					}
					?>
				</li>
			</ul>
		<?php } ?>
	</div>

	<?php if ( get_option( 'activitypub_event_bridge_initially_activated', true ) ) : ?>
		<a href="<?php echo esc_url( admin_url( 'options-general.php?page=' . Settings_Page::SETTINGS_SLUG ) . '&tab=settings' ); ?>" role="button">
			<button class="button button-primary">
				<strong>→</strong> <?php \esc_html_e( 'Continue your setup', 'activitypub-event-bridge' ); ?>
			</button>
		</a>

	<?php else : ?>

	<div class="box">
		<h2><?php \esc_html_e( 'How to Check if It\'s Working', 'activitypub-event-bridge' ); ?></h2>
		<?php
		if ( ! $activitypub_event_bridge_status_ok ) {
			echo '<div class="notice-warning"><p>' . \esc_html__( 'Please fix the status issues above first.', 'activitypub-event-bridge' ) . '</p></div>';
		}
		?>
		<p><?php esc_html_e( 'Most of the magic happens behind the scenes, but here is how you can verify that your events are ready to be discovered:', 'activitypub-event-bridge' ); ?></p>
		<div class="activitypub-event-bridge-settings-accordion">
			<h4 class="activitypub-event-bridge-settings-accordion-heading">
				<button aria-expanded="false" class="activitypub-event-bridge-settings-accordion-trigger" aria-controls="activitypub-event-bridge-help-accordion-mastodon" type="button">
					<span class="title">
						1. 
						<img src="<?php echo esc_url( plugins_url( '/assets/img/mastodon.svg', ACTIVITYPUB_EVENT_BRIDGE_PLUGIN_FILE ) ); ?>" alt="Mastodon Icon" class="activitypub-event-bridge-settings-inline-icon">
						<?php \esc_html_e( 'Using Your Mastodon Account', 'activitypub-event-bridge' ); ?>
					</span>
					<span class="icon"></span>
				</button>
			</h4>
			<div id="activitypub-event-bridge-help-accordion-mastodon" class="activitypub-event-bridge-settings-accordion-panel" hidden="hidden">
				<ol class="activitypub-event-bridge-settings-numbered-list">
					<li><?php \esc_html_e( 'Log into your Mastodon account.', 'activitypub-event-bridge' ); ?></li>
					<li>
						<?php \esc_html_e( 'In the search bar, type or copy the full URL of one of your event pages. For example:', 'activitypub-event-bridge' ); ?>
						<code class="activitypub-event-bridge-settings-example-url"><?php echo \esc_url( $example_event_post ); ?></code>
					</li>
					<li><?php \esc_html_e( 'If everything is set up correctly, you\'ll see a post representing your event. It should include the event\'s image, title, and a brief description.', 'activitypub-event-bridge' ); ?></li>
				</ol>
			</div>
			<h4 class="activitypub-event-bridge-settings-accordion-heading">
				<button aria-expanded="false" class="activitypub-event-bridge-settings-accordion-trigger" aria-controls="activitypub-event-bridge-help-accordion-mobilizon" type="button">
					<span class="title">
						2. 
						<img src="<?php echo esc_url( plugins_url( '/assets/img/mobilizon.svg', ACTIVITYPUB_EVENT_BRIDGE_PLUGIN_FILE ) ); ?>" alt="Mastodon Icon" class="activitypub-event-bridge-settings-inline-icon">
						<?php \esc_html_e( 'Using Your Mobilizon Account', 'activitypub-event-bridge' ); ?>
					</span>
					<span class="icon"></span>
				</button>
			</h4>
			<div id="activitypub-event-bridge-help-accordion-mobilizon" class="activitypub-event-bridge-settings-accordion-panel" hidden="hidden">
				<ol class="activitypub-event-bridge-settings-numbered-list">
					<li><?php \esc_html_e( 'Log into your Mobilizon account.', 'activitypub-event-bridge' ); ?></li>
					<li>
						<?php \esc_html_e( 'In the search bar, type or copy the full URL of one of your event pages. For example:', 'activitypub-event-bridge' ); ?>
						<code class="activitypub-event-bridge-settings-example-url"><?php echo \esc_url( $example_event_post ); ?></code>
					</li>
					<li><?php \esc_html_e( 'If everything is set up correctly, you\'ll see a full representation of your WordPress event. This will include the event\'s banner image, title, complete description, start and end times, categories, tags, and whether it\'s an online event.', 'activitypub-event-bridge' ); ?></li>
				</ol>
			</div>
			<h4 class="activitypub-event-bridge-settings-accordion-heading">
				<button aria-expanded="false" class="activitypub-event-bridge-settings-accordion-trigger" aria-controls="activitypub-event-bridge-help-accordion-fediverse" type="button">
					<span class="title">
						3. 
						<img src="<?php echo esc_url( plugins_url( '/assets/img/fediverse.svg', ACTIVITYPUB_EVENT_BRIDGE_PLUGIN_FILE ) ); ?>" alt="Mastodon Icon" class="activitypub-event-bridge-settings-inline-icon">
						<?php \esc_html_e( 'Using Any Other Fediverse Application', 'activitypub-event-bridge' ); ?>
					</span>
					<span class="icon"></span>
				</button>
			</h4>
			<div id="activitypub-event-bridge-help-accordion-fediverse" class="activitypub-event-bridge-settings-accordion-panel" hidden="hidden">
				<p><?php \esc_html_e( 'Of course any other application in the Fediverse should work as well. Most applications support importing external content via searching for the contents full URL.', 'activitypub-event-bridge' ); ?></p>
				<ol class="activitypub-event-bridge-settings-numbered-list">
					<li><?php \esc_html_e( 'Log into your account on any Fediverse app.', 'activitypub-event-bridge' ); ?></li>
					<li>
						<?php \esc_html_e( 'In the search bar, type or copy the full URL of one of your event pages. For example:', 'activitypub-event-bridge' ); ?>
						<code class="activitypub-event-bridge-settings-example-url"><?php echo \esc_url( $example_event_post ); ?></code>
					</li>
					<li><?php \esc_html_e( 'If the application which your are using natively supports ActivityPub events, you should see a representation of your WordPress event. If your application is supports receiving ActivityPub events you will get a post which summarizes the event. Keep in mind that some apps may not support events at all.', 'activitypub-event-bridge' ); ?></li>
				</ol>
			</div>
			<h4 class="activitypub-event-bridge-settings-accordion-heading">
				<button aria-expanded="false" class="activitypub-event-bridge-settings-accordion-trigger" aria-controls="activitypub-event-bridge-help-accordion-advanced" type="button">
					<span class="title">
						4. 
						<img src="<?php echo esc_url( plugins_url( '/assets/img/activitypub.svg', ACTIVITYPUB_EVENT_BRIDGE_PLUGIN_FILE ) ); ?>" alt="Mastodon Icon" class="activitypub-event-bridge-settings-inline-icon">
						<?php \esc_html_e( 'Advanced: Verifying the ActivityStreams JSON', 'activitypub-event-bridge' ); ?></span>
					<span class="icon"></span>
				</button>
			</h4>
			<div id="activitypub-event-bridge-help-accordion-advanced" class="activitypub-event-bridge-settings-accordion-panel" hidden="hidden">
				<p>
					<?php
					// Assume $event_url contains the dynamic URL, and '?activitypub' is appended to it.
					$activitypub_url = esc_url( $example_event_post . '?activitypub' );

					// Prepare the activitypub part wrapped in a <code> element.
					$activitypub_query = '<nobr><code>' . esc_html( '?activitypub' ) . '</code></nobr>';

					$activitypub_url_html = '<a href="' . esc_url( $activitypub_url ) . '" target="_blank">' . esc_html( $activitypub_url ) . '</a>';

					// Translator comment to explain the placeholder.
					/* translators: %1$s is the <code>?activitypub</code> string, and %2$s is the full URL of an example event */
					$raw_string = sprintf( __( 'For more technical users, you can inspect how your event is converted into an ActivityPub object. Simply append %1$s to the end of 	any single event pages URL to view the raw ActivityStreams JSON data (e.g., %2$s).', 'activitypub-event-bridge' ), $activitypub_query, $activitypub_url_html );

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
		<h2><?php \esc_html_e( 'Changelog', 'activitypub-event-bridge' ); ?></h2>
		<pre>
			<?php
			$changelog = $wp_filesystem->get_contents( ACTIVITYPUB_EVENT_BRIDGE_PLUGIN_DIR . '/CHANGELOG.md' );
			echo esc_html( substr( $changelog, strpos( $changelog, "\n", 180 ) + 1 ) );
			?>
		</pre>
	</div>
	<?php endif; ?>

</div>

