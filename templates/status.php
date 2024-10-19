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

?>

<div class="activitypub-event-bridge-settings activitypub-event-bridge-settings-page hide-if-no-js">
	<div class="box">
		<h2><?php \esc_html_e( 'Detected Event Plugins', 'activitypub-event-bridge' ); ?></h2>
		<?php foreach ( $active_event_plugins as $active_event_plugin ) { ?>
			<h3> <?php echo esc_html( $active_event_plugin->get_plugin_name() ); ?> </h3>
		<?php } ?>
	</div>
</div>
