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

use Event_Bridge_For_ActivityPub\Setup;

\load_template(
	__DIR__ . '/../menu.php',
	true,
	array(
		'event-sources' => 'active',
	)
);

if ( ! current_user_can( 'manage_options' ) ) {
	return;
}

$activitypub_plugin_is_active = Setup::get_instance()->is_activitypub_plugin_active();

\get_option( 'event_bridge_for_activitypub_event_sources_active', false );

if ( ! isset( $args ) || ! array_key_exists( 'supports_event_sources', $args ) ) {
	return;
}

$event_plugins_supporting_event_sources = $args['supports_event_sources'];

$event_sources_active   = \get_option( 'event_bridge_for_activitypub_event_sources_active', false );
$cache_retention_period = \get_option( 'event_bridge_for_activitypub_event_source_cache_retention', DAY_IN_SECONDS );

?>
<?php if ( $activitypub_plugin_is_active ) { ?>
	<div class="activitypub-settings hide-if-no-js">
		<form method="post" action="options.php">
			<?php \settings_fields( 'event-bridge-for-activitypub_event-sources' ); ?>
			<div class="box">
				<h2><?php \esc_html_e( 'Event Sources', 'event-bridge-for-activitypub' ); ?></h2>
				<p id="event-sources-description"><?php esc_html_e( 'This feature to allows your WordPress site to fetch and display events from external sources via ActivityPub. Once enabled, you can add any ActivityPub account as a source of events. These events will be cached on your site and seamlessly integrated into your existing event calendar, creating a unified view of events from both internal and external sources.', 'event-bridge-for-activitypub' ); ?></p>
				<?php
				if ( ! \Activitypub\is_user_type_disabled( 'blog' ) && count( $event_plugins_supporting_event_sources ) ) {
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
							</td>
						</tr>
					<?php
					if ( $event_sources_active ) {
						?>
						<tr>
							<th scope="row">
								<label for="event_bridge_for_activitypub_integration_used_for_event_sources_feature"><?php \esc_html_e( 'Event Plugin', 'event-bridge-for-activitypub' ); ?></label>
							</th>
							<td>
								<select
									name="event_bridge_for_activitypub_integration_used_for_event_sources_feature"
									id="event_bridge_for_activitypub_integration_used_for_event_sources_feature"
									value="gatherpress"
									aria-describedby="event-sources-used-plugin-description"
								>
								<?php
								foreach ( $event_plugins_supporting_event_sources as $event_plugin_class_name => $event_plugin_name ) {
									echo '<option value="' . esc_attr( $event_plugin_class_name ) . '" ' . selected( $event_plugin_class_name, Setup::get_event_plugin_integration_used_for_event_sources_feature(), true ) . '>' . esc_attr( $event_plugin_name ) . '</option>';
								}
								?>
								</select>
								<p id="event-sources-used-plugin-description"><?php esc_html_e( 'In case you have multiple event plugins installed you might choose which event plugin is utilized.', 'event-bridge-for-activitypub' ); ?></p>
							</td>
						<tr>
						<tr>
							<th scope="row">
								<label for="event_bridge_for_activitypub_event_source_cache"><?php \esc_html_e( 'Retention Period for External Events', 'event-bridge-for-activitypub' ); ?></label>
							</th>
							<td>
								<select
									name="event_bridge_for_activitypub_event_source_cache_retention"
									id="event_bridge_for_activitypub_event_source_cache_retention"
									value="0"
									aria-describedby="event_bridge_for_activitypub_event-sources-cache-clear-time-frame"
								>
								<?php
								$choices = array(
									0                => __( 'Immediately', 'event-bridge-for-activitypub' ),
									DAY_IN_SECONDS   => __( 'One Day', 'event-bridge-for-activitypub' ),
									WEEK_IN_SECONDS  => __( 'One Week', 'event-bridge-for-activitypub' ),
									MONTH_IN_SECONDS => __( 'One Month', 'event-bridge-for-activitypub' ),
									YEAR_IN_SECONDS  => __( 'One Year', 'event-bridge-for-activitypub' ),
								);
								foreach ( $choices as $time => $string ) {
									echo '<option value="' . \esc_attr( $time ) . '" ' . \selected( $cache_retention_period, $time, true ) . '>' . \esc_attr( $string ) . '</option>';
								}
								?>
								</select>
								<p id="event_bridge_for_activitypub_event-sources-cache-clear-time-frame"><?php esc_html_e( 'External events from your event sources will be automatically removed from your site after the selected time period has passed since the event ended. Choose a time frame that works best for your needs.', 'event-bridge-for-activitypub' ); ?></p>
							</td>
						<tr>
						<?php
					}
					?>
					<tbody>
					</table>
					<?php
				} elseif ( ! \Activitypub\is_user_type_disabled( 'blog' ) ) {
					?>
					<div class="notice-warning"><p><?php esc_html_e( 'You do not have an Event Plugin installed that supports this feature.', 'event-bridge-for-activitypub' ); ?></p></div>
					<p><?php \esc_html_e( 'The following Event Plugins are supported:', 'event-bridge-for-activitypub' ); ?></p>
					<?php
					$plugins_supporting_event_sources = Setup::detect_event_plugins_supporting_event_sources();
					echo '<ul class="event_bridge_for_activitypub-list">';
					foreach ( $plugins_supporting_event_sources as $event_plugin ) {
						echo '<li>' . esc_attr( $event_plugin->get_plugin_name() ) . '</li>';
					}
					echo '</ul>';
				} else {
					$activitypub_plugin_data = \get_plugin_data( ACTIVITYPUB_PLUGIN_FILE );

					$notice = sprintf(
						/* translators: 1: The name of the ActivityPub plugin. */
						_x(
							'In order to use this feature your have to enable the Blog-Actor in the the <a href="%1$s">%2$s settings</a>.',
							'admin notice',
							'event-bridge-for-activitypub'
						),
						\admin_url( 'options-general.php?page=activitypub&tab=settings' ),
						\esc_html( $activitypub_plugin_data['Name'] )
					);

					$allowed_html = array(
						'a' => array(
							'href'  => true,
							'title' => true,
						),
					);
					echo '<div class="notice-warning"><p>' . \wp_kses( $notice, $allowed_html ) . '</p></div>';
				}
				?>
			</div>
			<?php \submit_button(); ?>
		</form>
	</div>
	<div class="wrap event_bridge_for_activitypub-admin-table-container">
		<br>
		<?php
		if ( \get_option( 'event_bridge_for_activitypub_event_sources_active', false ) ) {
			?>
			<!-- ThickBox content (hidden initially) -->
			<div id="Event_Bridge_For_ActivityPub_add_new_source" style="display:none;">
				<h2><?php \esc_html_e( 'Add a Trusted Event Source', 'event-bridge-for-activitypub' ); ?></h2>
				<ul class="event-bridge-for-activitypub-syntax-list" id="event_bridge_for_activitypub_add_event_source_description">
					<?php \esc_html_e( 'Use one of the following syntax:', 'event-bridge-for-activitypub' ); ?>
					<li>
						<?php \esc_html_e( 'Enter a Fediverse user handle', 'event-bridge-for-activitypub' ); ?> ( <?php esc_html_e( 'e.g.', 'event-bridge-for-activitypub' ); ?> <code>@username@example.social</code>)
					</li>
					<li>
						<?php \esc_html_e( 'ActivityPub account URL or ID', 'event-bridge-for-activitypub' ); ?> ( <?php esc_html_e( 'e.g.', 'event-bridge-for-activitypub' ); ?> <code>https://example.social/user/username</code>)
					</li>
					<li>
						<?php \esc_html_e( 'The domain or URL of a Gancio instance', 'event-bridge-for-activitypub' ); ?> ( <?php esc_html_e( 'e.g.', 'event-bridge-for-activitypub' ); ?> <code>https://demo.gancio.org</code>)
					</li>
				</ul>
				<div class="notice notice-info inline">
					<p>
						ℹ️
						<?php
						$number_of_imports = \Event_Bridge_For_ActivityPub\Outbox_Parser::MAX_EVENTS_TO_IMPORT;
						$notice            = sprintf(
							/* translators: 1: The maximum number of imported events. */
							__( 'To ensure a smooth start, up to %d upcoming events from this source will be automatically imported soon after adding it.', 'event-bridge-for-activitypub' ),
							$number_of_imports
						);
						echo esc_html( $notice );
						?>
					</p>
				</div>
				<form method="post" action="options.php">
					<?php \settings_fields( 'event-bridge-for-activitypub_add-event-source' ); ?>
					<label for="event_bridge_for_activitypub_add_event_source">
						<p>
							<?php \esc_html_e( 'Event Source (handle, URL, or instance)', 'event-bridge-for-activitypub' ); ?>:
						</p>
					</label>
					<input
						type="text"
						style="width: 100%"
						name="event_bridge_for_activitypub_add_event_source"
						id="event_bridge_for_activitypub_add_event_source"
						aria-describedby="event_bridge_for_activitypub_add_event_source_description"
						placeholder="@username@example.social or https://example.social/user/username">
					<?php \submit_button( __( 'Follow Event Source', 'event-bridge-for-activitypub' ) ); ?>
				</form>
			</div>
			<div class="wrap activitypub-followers-page">
				<!-- Table title with add new button like on post edit pages -->
				<div class="event_bridge_for_activitypub-admin-table-top">
					<h2 class="wp-heading-inline"> <?php esc_html_e( 'Manage Event Sources', 'event-bridge-for-activitypub' ); ?> </h2>
					<!-- Button that triggers ThickBox -->
					<a href="#TB_inline?width=600&height=400&inlineId=Event_Bridge_For_ActivityPub_add_new_source" class="thickbox page-title-action">
						<?php \esc_html_e( 'Add Event Source', 'event-bridge-for-activitypub' ); ?>
					</a>
				</div>
				<form method="get">
					<input type="hidden" name="page" value="activitypub" />
					<input type="hidden" name="tab" value="event-bridge-for-activitypub" />
					<input type="hidden" name="subpage" value="event-sources" />
					<?php
					$table = new \Event_Bridge_For_ActivityPub\Table\Event_Sources();
					$table->prepare_items();
					$table->search_box( 'Search', 'search' );
					$table->display();
					?>
				</form>
			</div>
		<?php } ?>
	</div>
<?php } ?>
