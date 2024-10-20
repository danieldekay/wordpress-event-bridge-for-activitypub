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

\load_template(
	__DIR__ . '/admin-header.php',
	true,
	array(
		'welcome' => 'active',
	)
);

$active_event_plugins = Setup::get_instance()->get_active_event_plugins();

global $wp_filesystem;
WP_Filesystem();

?>

<div class="activitypub-event-bridge-settings activitypub-event-bridge-settings-page hide-if-no-js">
	<div class="box">
		<h2><?php \esc_html_e( 'Status', 'activitypub-event-bridge' ); ?></h2>
		<p><?php \esc_html_e( 'The ActivityPub Event Bridge detected the following (activated) event plugins:', 'activitypub-event-bridge' ); ?></p>
		<ul class="activitypub-event-bridge-list">
			<?php foreach ( $active_event_plugins as $active_event_plugin ) { ?>
				<li>
					<strong><?php echo esc_html( $active_event_plugin->get_plugin_name() ); ?>:</strong>
					<br>
					<?php
					if ( Health_Check::test_if_event_transformer_is_used( $active_event_plugin ) ) {
						echo 'The ActivityPub Event Bridge successfully registered to the ActivityPub plugin.';
					} else {
						echo 'The ActivityPub Event Bridge could not register to the ActivityPub plugin.';
					}
					?>
				</li>
			<?php } ?>
		</ul>
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
		<p><?php esc_html_e( 'Most of the magic happens behind the scenes, but here’s how you can verify that your events are ready to be discovered:', 'activitypub-event-bridge' ); ?></p>
		<div class="activitypub-event-bridge-settings-accordion">
				<h4 class="activitypub-event-bridge-settings-accordion-heading">
				<button aria-expanded="false" class="activitypub-event-bridge-settings-accordion-trigger" aria-controls="activitypub-event-bridge-help-accordion-mastodon" type="button">
					<span class="title"><?php \esc_html_e( '1. Using Your Mastodon Account', 'activitypub-event-bridge' ); ?></span>
					<span class="icon"></span>
				</button>
			</h4>
			<div id="activitypub-event-bridge-help-accordion-mastodon" class="activitypub-event-bridge-settings-accordion-panel" hidden="hidden">
				<ol class="activitypub-event-bridge-settings-numbered-list">
					<li><?php \esc_html_e( 'Log into your Mastodon account.', 'activitypub-event-bridge' ); ?></li>
					<li><?php \esc_html_e( 'In the search bar, type or copy the full URL of one of your event pages (e.g., https://yoursite.com/events/event-name).', 'activitypub-event-bridge' ); ?></li>
					<li><?php \esc_html_e( 'If everything is set up correctly, you\'ll see a post representing your event. It should include the event\'s image, title, and a brief description.', 'activitypub-event-bridge' ); ?></li>
				</ol>
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

