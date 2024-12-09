<?php
/**
 * Event Sources management page for the ActivityPub Event Bridge.
 *
 * @package Event_Bridge_For_ActivityPub
 * @since   1.0.0
 * @license AGPL-3.0-or-later
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

\load_template(
	__DIR__ . '/admin-header.php',
	true,
	array(
		'event-sources' => 'active',
	)
);

if ( ! isset( $args ) || ! array_key_exists( 'supports_event_sources', $args ) ) {
	return;
}

if ( ! current_user_can( 'manage_options' ) ) {
	return;
}

$event_plugins_supporting_event_sources = $args['supports_event_sources'];

$selected_plugin      = \get_option( 'event_bridge_for_activitypub_plugin_used_for_event_source_feature', '' );
$event_sources_active = \get_option( 'event_bridge_for_activitypub_event_sources_active', false );
?>

<div class="event-bridge-for-activitypub-settings event-bridge-for-activitypub-settings-page hide-if-no-js">
	<div class="box">
		<h3><?php \esc_html_e( 'Configuration of the Event Sources feature', 'activitypub' ); ?></h3>
		<?php
		if ( count( $event_plugins_supporting_event_sources ) ) {
			?>
			<form method="post" action="options.php">
			<?php
			\settings_fields( 'event-bridge-for-activitypub-event-sources' );
			?>
			<table class="form-table">
			<tbody>
				<tr>
					<th scope="row">
						<label for="event_bridge_for_activitypub_event_sources_active"><?php \esc_html_e( 'Enable External Event Sources', 'event-bridge-for-activitypub' ); ?></label>
					</th>
					<td>
						<input
							type="checkbox"
							name="event_bridge_for_activitypub_event_sources_active"
							id="event_bridge_for_activitypub_event_sources_active"
							aria-describedby="event-sources-description"
							value="1"
							<?php echo \checked( $event_sources_active ); ?>
						>
						<p id="event-sources-description"><?php esc_html_e( 'Activate this feature to allow your WordPress site to fetch events from external sources via ActivityPub. Once enabled, you can add any ActivityPub account as a source of events. These events will be cached on your site and seamlessly integrated into your existing event calendar, creating a unified view of events from both internal and external sources.', 'event-bridge-for-activitypub' ); ?></p>
					</td>
				</tr>
			<?php
			if ( $event_sources_active ) {
				?>
				<tr>
					<th scope="row">
						<label for="event_bridge_for_activitypub_plugin_used_for_event_source_feature"><?php \esc_html_e( 'Event Plugin', 'event-bridge-for-activitypub' ); ?></label>
					</th>
					<td>
						<select
							name="event_bridge_for_activitypub_plugin_used_for_event_source_feature"
							id="event_bridge_for_activitypub_plugin_used_for_event_source_feature"
							value="gatherpress"
							aria-describedby="event-sources-used-plugin-description"
						>
						<?php
						foreach ( $event_plugins_supporting_event_sources as $event_plugin ) {
							echo '<option value="' . esc_attr( $event_plugin ) . '" ' . selected( $selected_plugin, $event_plugin, true ) . '>' . esc_attr( $event_plugin ) . '</option>';
						}
						?>
						</select>
						<p id="event-sources-used-plugin-description"><?php esc_html_e( 'In case you have multiple event plugins installed you might choose which event plugin is utilized.', 'event-bridge-for-activitypub' ); ?></p>
					</td>
				<tr>
				<?php
			}
			?>
			<tbody>
			</table>
			<?php
			\submit_button();
			?>
			</form>
			<?php
		} else {
			?>
			<p><?php esc_html_e( 'You do not have an Event Plugin installed that supports this feature', 'event-bridge-for-activitypub' ); ?></p>
			<p><?php esc_html_e( 'The following Event Plugins are supported:', 'event-bridge-for-activitypub' ); ?></p>
			<?php
			$plugins_supporting_event_sources = \Event_Bridge_For_ActivityPub\Setup::detect_event_plugins_supporting_event_sources();
			echo '<ul class="event_bridge_for_activitypub-list">';
			foreach ( $plugins_supporting_event_sources as $event_plugin ) {
				echo '<li>' . esc_attr( $event_plugin->get_plugin_name() ) . '</li>';
			}
			echo '</ul>';
			return;
		}
		?>
	</div>
	<?php
	if ( ! $event_sources_active ) {
		echo '</div>';
		return;
	}
	?>
</div>

<div class="wrap event_bridge_for_activitypub-admin-table-container">

		<h2> <?php esc_html_e( 'List of Event Sources', 'event-bridge-for-activitypub' ); ?> </h2>
		<!-- Button that triggers ThickBox -->
		<a href="#TB_inline?width=600&height=400&inlineId=Event_Bridge_For_ActivityPub_add_new_source" class="thickbox page-title-action">
			<?php esc_html_e( 'Add Event Source', 'event-bridge-for-activitypub' ); ?>
		</a>

	<!-- ThickBox content (hidden initially) -->
	<div id="Event_Bridge_For_ActivityPub_add_new_source" style="display:none;">
		<h2><?php esc_html_e( 'Add new ActivityPub follow', 'event-bridge-for-activitypub' ); ?> </h2>
		<p> <?php esc_html_e( 'Here you can enter either a Fediverse handle (@username@example.social), URL of an ActivityPub Account (https://example.social/user/username) or instance URL.', 'event-bridge-for-activitypub' ); ?> </p>
		<form method="post" action="options.php">
			<?php \settings_fields( 'event-bridge-for-activitypub-event-sources' ); ?>
			<input type="text" name="event_bridge_for_activitypub_event_source" id="event_bridge_for_activitypub_event_source" value="">
			<?php \submit_button( __( 'Add Event Source', 'event-bridge-for-activitypub' ) ); ?>
		</form>
	</div>
	<div class="wrap activitypub-followers-page">
		<form method="get">
			<input type="hidden" name="page" value="event-bridge-for-activitypub" />
			<input type="hidden" name="tab" value="event-sources" />
			<?php
			$table = new \Event_Bridge_For_ActivityPub\Table\Event_Sources();
			$table->prepare_items();
			$table->search_box( 'Search', 'search' );
			$table->display();
			?>
		</form>
	</div>
</div>

