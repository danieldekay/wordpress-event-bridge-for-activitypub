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

