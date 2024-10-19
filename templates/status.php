<?php
/**
 * Status page for the ActivityPub Event Bridge.
 *
 * @package ActivityPub_Event_Bridge
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

use ActivityPub_Event_Bridge\Setup;

\load_template(
	__DIR__ . '/admin-header.php',
	true,
	array(
		'status' => 'active',
	)
);

$active_event_plugins = Setup::get_instance()->get_active_event_plugins();

global $wp_filesystem;
WP_Filesystem();

?>

<div class="activitypub-event-bridge-settings activitypub-event-bridge-settings-page hide-if-no-js">
	<div class="box">
		<h2><?php \esc_html_e( 'Welcome', 'activitypub-event-bridge' ); ?></h2>
		<p><?php \esc_html_e( 'The ActivityPub Event Bridge detected the following (activated) event plugins:', 'activitypub-event-bridge' ); ?></p>
		<ul class="activitypub-event-bridge-list">
			<?php foreach ( $active_event_plugins as $active_event_plugin ) { ?>
				<li><?php echo esc_html( $active_event_plugin->get_plugin_name() ); ?> </li>
			<?php } ?>
		</ul>
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
</div>

