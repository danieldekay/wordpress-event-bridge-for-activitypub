<?php
/**
 * Template for Event Bridge for ActivityPub settings page.
 *
 * This template is used to display and manage settings for the Event Bridge for ActivityPub plugin.
 *
 * @package Event_Bridge_For_ActivityPub
 * @since 1.0.0
 * @license AGPL-3.0-or-later
 *
 * @param array  $args An array of arguments for the settings page.
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

\load_template(
	__DIR__ . '/admin-header.php',
	true,
	array(
		'settings' => 'active',
	)
);

use Activitypub\Activity\Extended_Object\Event;
use Event_Bridge_For_ActivityPub\Setup;

$activitypub_plugin_is_active = Setup::get_instance()->is_activitypub_plugin_active();

if ( ! isset( $args ) || ! array_key_exists( 'event_terms', $args ) ) {
	return;
}

if ( ! current_user_can( 'manage_options' ) ) {
	return;
}
\get_option( 'event_bridge_for_activitypub_event_sources_active', false );
if ( ! isset( $args ) || ! array_key_exists( 'supports_event_sources', $args ) ) {
	return;
}

$event_plugins_supporting_event_sources = $args['supports_event_sources'];

$event_sources_active   = \get_option( 'event_bridge_for_activitypub_event_sources_active', false );
$cache_retention_period = \get_option( 'event_bridge_for_activitypub_event_source_cache_retention', DAY_IN_SECONDS );

$event_terms = $args['event_terms'];

require_once EVENT_BRIDGE_FOR_ACTIVITYPUB_PLUGIN_DIR . '/includes/event-categories.php';

$selected_default_event_category = \get_option( 'event_bridge_for_activitypub_default_event_category', 'MEETING' );
$current_category_mapping        = \get_option( 'event_bridge_for_activitypub_event_category_mappings', array() );
$reminder_time_gap               = \get_option( 'event_bridge_for_activitypub_reminder_time_gap', 0 );

$reminder_time_gap_choices = array(
	0                   => __( 'Disabled', 'event-bridge-for-activitypub' ),
	HOUR_IN_SECONDS * 6 => __( '6 hours', 'event-bridge-for-activitypub' ),
	DAY_IN_SECONDS      => __( '1 day', 'event-bridge-for-activitypub' ),
	DAY_IN_SECONDS * 3  => __( '3 days', 'event-bridge-for-activitypub' ),
	WEEK_IN_SECONDS     => __( '1 week', 'event-bridge-for-activitypub' ),
);

if ( \get_option( 'event_bridge_for_activitypub_initially_activated' ) ) {
	\update_option( 'event_bridge_for_activitypub_initially_activated', '' );
}
?>

<div class="event-bridge-for-activitypub-settings event-bridge-for-activitypub-settings-page hide-if-no-js">
	<form method="post" action="options.php">
		<?php \settings_fields( 'event-bridge-for-activitypub' ); ?>
		<div class="box">
			<h2> <?php esc_html_e( 'Event Summary Text', 'event-bridge-for-activitypub' ); ?> </h2>
			<p><?php esc_html_e( 'Many Fediverse applications (e.g., Mastodon) don\'t fully support events, instead they will show a summary text along with the events title and the URL to your Website.', 'event-bridge-for-activitypub' ); ?></p>
			<p>
				<label for="event_bridge_for_activitypub_summary_type_preset">
					<input type="radio" name="event_bridge_for_activitypub_summary_type" id="event_bridge_for_activitypub_summary_type_preset" value="preset" <?php echo \checked( 'preset', \get_option( 'event_bridge_for_activitypub_summary_type', EVENT_BRIDGE_FOR_ACTIVITYPUB_DEFAULT_SUMMARY_TYPE ) ); ?> />
					<?php \esc_html_e( 'Automatic (default)', 'event-bridge-for-activitypub' ); ?>
					-
					<span class="description">
						<?php \esc_html_e( 'Let the plugin compose a summary for you.	', 'event-bridge-for-activitypub' ); ?>
					</span>
				</label>
			</p>
			<p>
				<label for="event_bridge_for_activitypub_summary_type_custom">
					<input type="radio" name="event_bridge_for_activitypub_summary_type" id="event_bridge_for_activitypub_summary_type_custom" value="custom" <?php echo \checked( 'custom', \get_option( 'event_bridge_for_activitypub_summary_type', EVENT_BRIDGE_FOR_ACTIVITYPUB_DEFAULT_SUMMARY_TYPE ) ); ?> />
					<?php \esc_html_e( 'Custom', 'event-bridge-for-activitypub' ); ?>
					-
					<span class="description">
						<?php \esc_html_e( 'For advanced users: compose your custom summary via shortcodes.', 'event-bridge-for-activitypub' ); ?>
					</span>
				</label>
			</p>
			<div id="event_bridge_for_activitypub_summary_type_custom-details">
				<textarea name="event_bridge_for_activitypub_custom_summary" id="event_bridge_for_activitypub_custom_summary" rows="10" cols="50" class="large-text"><?php echo esc_textarea( wp_kses( \get_option( 'event_bridge_for_activitypub_custom_summary', EVENT_BRIDGE_FOR_ACTIVITYPUB_SUMMARY_TEMPLATE ), 'post' ) ); ?></textarea>
				<details>
					<summary><?php esc_html_e( 'See a list Template Tags available for the summary.', 'event-bridge-for-activitypub' ); ?></summary>
					<div class="description">
						<dl>
							<dt><code>[ap_start_time icon="true" label="true"]</code><dt>
							<dd><?php \esc_html_e( 'The events title.', 'event-bridge-for-activitypub' ); ?></dd>
							<dt><code>[ap_end_time icon="true" label="true"]</code><dt>
							<dd><?php \esc_html_e( 'The events content.', 'event-bridge-for-activitypub' ); ?></dd>
							<dt><code>[ap_location icon="true" label="true"]</code><dt>
							<dd><?php \esc_html_e( 'The events location.', 'event-bridge-for-activitypub' ); ?></dd>
							<dt><code>[ap_hashtags]</code><dt>
							<dd><?php \esc_html_e( 'The events tags as hashtags.', 'event-bridge-for-activitypub' ); ?></dd>
							<dt><code>[ap_excerpt]</code><dt>
							<dd><?php \esc_html_e( 'The events excerpt (may be truncated).', 'event-bridge-for-activitypub' ); ?></dd>
							<dt><code>[ap_content]</code><dt>
							<dd><?php \esc_html_e( 'The events description.', 'event-bridge-for-activitypub' ); ?></dd>
						</dl>
					</div>
				</details>
			</div>
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="event_bridge_for_activitypub_summary_format"> <?php esc_html_e( 'Enforce plain text in summary', 'event-bridge-for-activitypub' ); ?></label>
					</th>
					<td>
						<p>
							<input type="checkbox" aria-describedby="event_bridge_for_activitypub_summary_format_description" name="event_bridge_for_activitypub_summary_format" id="event_bridge_for_activitypub_summary_format" value="plain" <?php echo \checked( 'plain', \get_option( 'event_bridge_for_activitypub_summary_type', EVENT_BRIDGE_FOR_ACTIVITYPUB_DEFAULT_SUMMARY_TYPE ) ); ?> />
							<span id="event_bridge_for_activitypub_summary_format_description">
								<?php
								$allowed_html = array(
									'code' => array(),
								);
								echo \wp_kses( __( 'Many Fediverse applications, including Mastodon before version 4.3.0, do not render summaries as HTML. Enable this option to send the summary as plain text for better compatibility (e.g., <code>&lt;ul&gt;&lt;li&gt;Item 1&lt;/li&gt;&lt;/ul&gt;</code> will be sent as \'Item 1\' without formatting).', 'event-bridge-for-activitypub' ), $allowed_html );
								?>
							</span>
						</p>
					</td>
				</tr>
			</table>
		</div>

		<?php if ( $activitypub_plugin_is_active ) { ?>
		<div class="box">
			<h2><?php \esc_html_e( 'Event Sources', 'event-bridge-for-activitypub' ); ?></h2>
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
							<p>
							<input
								type="checkbox"
								name="event_bridge_for_activitypub_event_sources_active"
								id="event_bridge_for_activitypub_event_sources_active"
								aria-describedby="event-sources-description"
								value="1"
								<?php echo \checked( $event_sources_active ); ?>
							>
							<span id="event-sources-description"><?php esc_html_e( 'Activate this feature to allow your WordPress site to fetch events from external sources via ActivityPub. Once enabled, you can add any ActivityPub account as a source of events. These events will be cached on your site and seamlessly integrated into your existing event calendar, creating a unified view of events from both internal and external sources.', 'event-bridge-for-activitypub' ); ?></span>
							</p>
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
								echo '<option value="' . esc_attr( $time ) . '" ' . selected( $cache_retention_period, $time, true ) . '>' . esc_attr( $string ) . '</option>';
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
				<p><?php esc_html_e( 'The following Event Plugins are supported:', 'event-bridge-for-activitypub' ); ?></p>
				<?php
				$plugins_supporting_event_sources = Setup::detect_event_plugins_supporting_event_sources();
				echo '<ul class="event_bridge_for_activitypub-list">';
				foreach ( $plugins_supporting_event_sources as $event_plugin ) {
					echo '<li>' . esc_attr( $event_plugin->get_plugin_name() ) . '</li>';
				}
				echo '</ul>';
			} else {
				$activitypub_plugin_data = get_plugin_data( ACTIVITYPUB_PLUGIN_FILE );

				$notice = sprintf(
					/* translators: 1: The name of the ActivityPub plugin. */
					_x(
						'In order to use this feature your have to enable the Blog-Actor in the the <a href="%1$s">%2$s settings</a>.',
						'admin notice',
						'event-bridge-for-activitypub'
					),
					admin_url( 'options-general.php?page=activitypub&tab=settings' ),
					esc_html( $activitypub_plugin_data['Name'] )
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
		<?php } ?>

		<div class="box">
			<h2> <?php esc_html_e( 'ActivityPub Event Category', 'event-bridge-for-activitypub' ); ?> </h2>
			<p id="event_bridge_for_activitypub_default_event_category_desc"> <?php esc_html_e( 'To help visitors find events more easily, the community created a set of basic event categories. Please select the category that best matches the majority of the events you organize.', 'event-bridge-for-activitypub' ); ?> </p>
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="event_bridge_for_activitypub_default_event_category"> <?php esc_html_e( 'Default Federated Event Category', 'event-bridge-for-activitypub' ); ?></label>
					</th>
					<td>
						<select aria-describedby="event_bridge_for_activitypub_default_event_category_desc" id="event_bridge_for_activitypub_default_event_category" name="event_bridge_for_activitypub_default_event_category">';
							<?php
							foreach ( EVENT_BRIDGE_FOR_ACTIVITYPUB_EVENT_CATEGORIES as $value => $label ) {
								echo '<option value="' . esc_attr( $value ) . '" ' . selected( $selected_default_event_category, $value, false ) . '>' . esc_html( $label ) . '</option>';
							}
							?>
						</select>
					</td>
				</tr>
			</table>

			<?php if ( ! empty( $event_terms ) ) : ?>
			<h3> <?php esc_html_e( 'Fine-grained Event Category Settings', 'event-bridge-for-activitypub' ); ?> </h3>
			<p id="event_bridge_for_activitypub_event_category_mapping_desc"> <?php esc_html_e( 'For any event category you have created on your WordPress site you can choose an event category which will be used in federation. This option lets you override the default selection above. ', 'event-bridge-for-activitypub' ); ?> </p>
			<table class="form-table">
				<tr>
					<th> <?php esc_html_e( 'Event category on your site', 'event-bridge-for-activitypub' ); ?> </th>
					<th> <?php esc_html_e( 'Fediverse event category', 'event-bridge-for-activitypub' ); ?> </th>
				</tr>
				<?php foreach ( $event_terms as $event_term ) { ?>
					<tr>
						<td scope="row">
							<label for="event_bridge_for_activitypub_event_category_mapping_<?php echo esc_attr( $event_term->slug ); ?>"">
								<?php echo esc_html( $event_term->name ); ?> </td>
							</label>
						<td class="select-cell">
							<select aria-describedby="event_bridge_for_activitypub_event_category_mapping_desc" id="event_bridge_for_activitypub_event_category_mapping_<?php echo esc_attr( $event_term->slug ); ?>" name="event_bridge_for_activitypub_event_category_mappings[<?php echo esc_attr( $event_term->slug ); ?>]">
								<?php
								$current_mapping_is_set = false;
								if ( ! empty( $current_category_mapping ) ) {
									$current_mapping_is_set = array_key_exists( $event_term->slug, $current_category_mapping );
								}
								if ( $current_mapping_is_set ) {
									$mapping = $current_category_mapping[ $event_term->slug ];
								} else {
									$mapping = 'DEFAULT';
								}
								if ( 'DEFAULT' === $mapping ) {
									echo '<option value="' . esc_attr( $mapping ) . '"> -- ' . esc_html( EVENT_BRIDGE_FOR_ACTIVITYPUB_EVENT_CATEGORIES[ $mapping ] ) . ' -- </option>';
								} else {
									echo '<option value="' . esc_attr( $mapping ) . '">' . esc_html( EVENT_BRIDGE_FOR_ACTIVITYPUB_EVENT_CATEGORIES[ $mapping ] ) . '</option>';
								}
								echo '<option value="DEFAULT" ' . selected( $selected_default_event_category, 'DEFAULT', false ) . '> -- ' . esc_html__( 'Default', 'event-bridge-for-activitypub' ) . ' -- </option>';
								foreach ( Event::DEFAULT_EVENT_CATEGORIES as $event_category ) {
									echo '<option value="' . esc_attr( $event_category ) . '" ' . selected( $mappings[ $event_term->slug ] ?? '', $event_category, false ) . '>' . esc_html( EVENT_BRIDGE_FOR_ACTIVITYPUB_EVENT_CATEGORIES[ $event_category ] ) . '</option>';
								}
								?>
							</select>
						</td>
					</tr>
				<?php } ?>
			</table>
			<?php endif; ?>
		</div>
		<div class="box">
			<h2> <?php esc_html_e( 'Send reminder before event starts', 'event-bridge-for-activitypub' ); ?> </h2>
			<p> <?php esc_html_e( 'Specify a time interval before the event starts to trigger a reminder. This reminder automatically boosts the event, making it reappear in users\' timelines at the defined time before the event to increase visibility just before the event begins.', 'event-bridge-for-activitypub' ); ?> </p>
			<table class="form-table">
				<tr>
					<label for="event_bridge_for_activitypub_reminder_time_gap">
						<th scope="row"> <?php esc_html_e( 'Default Time Gap for Reminders', 'event-bridge-for-activitypub' ); ?> </th>
					</label>
					<td>
					<select id="event_bridge_for_activitypub_reminder_time_gap" name="event_bridge_for_activitypub_reminder_time_gap">';
						<?php
						foreach ( $reminder_time_gap_choices as $value => $label ) {
							echo '<option value="' . esc_attr( $value ) . '" ' . selected( $reminder_time_gap, $value, false ) . '>' . esc_html( $label ) . '</option>';
						}
						?>
					</select>
					<br><br>
					<?php esc_html_e( 'This default value can be overridden for each event. Note that override is only available in the User Interface if you use the Gutenberg editor.', 'event-bridge-for-activitypub' ); ?>
					</td>
				</tr>
			</table>
		</div>
		<!-- This disables the setup wizard. -->
		<div class="hidden" aria-hidden="true">
			<input type="checkbox" id="event_bridge_for_activitypub_initially_activated" name="event_bridge_for_activitypub_initially_activated"/>
		</div>
		<?php \submit_button(); ?>
	</form>
</div>
