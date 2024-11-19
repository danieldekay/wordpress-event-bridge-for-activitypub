<?php
/**
 * Template for ActivityPub Event Bridge settings page.
 *
 * This template is used to display and manage settings for the ActivityPub Event Bridge plugin.
 *
 * @package ActivityPub_Event_Bridge
 * @since 1.0.0
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

if ( ! isset( $args ) || ! array_key_exists( 'event_terms', $args ) ) {
	return;
}

if ( ! current_user_can( 'manage_options' ) ) {
	return;
}

$event_terms = $args['event_terms'];

require_once ACTIVITYPUB_EVENT_BRIDGE_PLUGIN_DIR . '/includes/event-categories.php';

$selected_default_event_category = \get_option( 'activitypub_event_bridge_default_event_category', 'MEETING' );
$current_category_mapping        = \get_option( 'activitypub_event_bridge_event_category_mappings', array() );
?>

<div class="activitypub-event-bridge-settings activitypub-event-bridge-settings-page hide-if-no-js">
	<form method="post" action="options.php">
		<?php \settings_fields( 'activitypub-event-bridge' ); ?>
		<div class="box">
			<h2> <?php esc_html_e( 'Event Summary Text', 'activitypub-event-bridge' ); ?> </h2>
			<p><?php esc_html_e( 'Many Fediverse applications (e.g., Mastodon) don\'t fully support events, instead they will show a summary text along with the events title and the URL to your Website.', 'activitypub-event-bridge' ); ?></p>
			<p>
				<label for="activitypub_summary_type_preset">
					<input type="radio" name="activitypub_summary_type" id="activitypub_summary_type_preset" value="preset" <?php echo \checked( 'preset', \get_option( 'activitypub_summary_type', ACTIVITYPUB_EVENT_BRIDGE_DEFAULT_SUMMARY_TYPE ) ); ?> />
					<?php \esc_html_e( 'Automatic (default)', 'activitypub' ); ?>
					-
					<span class="description">
						<?php \esc_html_e( 'Let the plugin compose a summary for you.	', 'activitypub-event-bridge' ); ?>
					</span>
				</label>
			</p>
			<p>
				<label for="activitypub_summary_type_custom">
					<input type="radio" name="activitypub_summary_type" id="activitypub_summary_type_custom" value="custom" <?php echo \checked( 'custom', \get_option( 'activitypub_summary_type', ACTIVITYPUB_EVENT_BRIDGE_DEFAULT_SUMMARY_TYPE ) ); ?> />
					<?php \esc_html_e( 'Custom', 'activitypub-event-bridge' ); ?>
					-
					<span class="description">
						<?php \esc_html_e( 'For advanced users: compose your custom summary via shortcodes.', 'activitypub-event-bridge' ); ?>
					</span>
				</label>
			</p>
			<div id="activitypub_summary_type_custom-details">
				<textarea name="activitypub_event_bridge_custom_summary" id="activitypub_event_bridge_custom_summary" rows="10" cols="50" class="large-text" placeholder="<?php echo wp_kses( ACTIVITYPUB_EVENT_BRIDGE_CUSTOM_SUMMARY, 'post' ); ?>"><?php echo esc_textarea( wp_kses( \get_option( 'activitypub_event_bridge_custom_summary', ACTIVITYPUB_EVENT_BRIDGE_CUSTOM_SUMMARY ), 'post' ) ); ?></textarea>
				<details>
					<summary><?php esc_html_e( 'See a list Template Tags available for the summary.', 'activitypub' ); ?></summary>
					<div class="description">
						<dl>
							<dt><code>[ap_start_time]</code><dt>
							<dd><?php \esc_html_e( 'The events title.', 'activitypub-event-bridge' ); ?></dd>
							<dt><code>[ap_end_time]</code><dt>
							<dd><?php \esc_html_e( 'The events content.', 'activitypub-event-bridge' ); ?></dd>
							<dt><code>[ap_location]</code><dt>
							<dd><?php \esc_html_e( 'The events location.', 'activitypub-event-bridge' ); ?></dd>
							<dt><code>[ap_hashtags]</code><dt>
							<dd><?php \esc_html_e( 'The events tags as hashtags.', 'activitypub-event-bridge' ); ?></dd>
							<dt><code>[ap_excerpt]</code><dt>
							<dd><?php \esc_html_e( 'The events excerpt (may be truncated).', 'activitypub-event-bridge' ); ?></dd>
							<dt><code>[ap_content]</code><dt>
							<dd><?php \esc_html_e( 'The events description.', 'activitypub-event-bridge' ); ?></dd>
						</dl>
					</div>
				</details>
			</div>
		</div>

		<div class="box">
			<h2> <?php esc_html_e( 'ActivityPub Event Category', 'activitypub-event-bridge' ); ?> </h2>
			<p> <?php esc_html_e( 'To help visitors find events more easily, the community created a set of basic event categories. Please select the category that best matches the majority of the events you organize.', 'activitypub-event-bridge' ); ?> </p>
			<table class="form-table">
				<tr>
					<th scope="row"> <?php esc_html_e( 'Default Category', 'activitypub-event-bridge' ); ?> </th>
					<td>
						<select id="activitypub_event_bridge_default_event_category" name="activitypub_event_bridge_default_event_category">';
						<?php
						foreach ( ACTIVITYPUB_EVENT_BRIDGE_EVENT_CATEGORIES as $value => $label ) {
							echo '<option value="' . esc_attr( $value ) . '" ' . selected( $selected_default_event_category, $value, false ) . '>' . esc_html( $label ) . '</option>';
						}
						?>
						</select>
					</td>
				</tr>
			</table>

			<?php if ( ! empty( $event_terms ) ) : ?>
			<h3> <?php esc_html_e( 'Advanced Event Category Settings', 'activitypub-event-bridge' ); ?> </h3>
			<p> <?php esc_html_e( 'Take more control by adjusting how your event categories are mapped to the basic category set used in ActivityPub. This option lets you override the default selection above, ensuring more accurate categorization and better visibility for your events.', 'activitypub-event-bridge' ); ?> </p>
			<table class="form-table">
				<?php foreach ( $event_terms as $event_term ) { ?>
					<tr>
						<th scope="row"> <?php echo esc_html( $event_term->name ); ?> </th>
						<td>
							<select name="activitypub_event_bridge_event_category_mappings[<?php echo esc_attr( $event_term->slug ); ?>]">
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
									echo '<option value="' . esc_attr( $mapping ) . '"> -- ' . esc_html( ACTIVITYPUB_EVENT_BRIDGE_EVENT_CATEGORIES[ $mapping ] ) . ' -- </option>';
								} else {
									echo '<option value="' . esc_attr( $mapping ) . '">' . esc_html( ACTIVITYPUB_EVENT_BRIDGE_EVENT_CATEGORIES[ $mapping ] ) . '</option>';
								}
								echo '<option value="DEFAULT" ' . selected( $selected_default_event_category, 'DEFAULT', false ) . '> -- ' . esc_html__( 'Default', 'activitypub-event-bridge' ) . ' -- </option>';
								foreach ( Event::DEFAULT_EVENT_CATEGORIES as $event_category ) {
									echo '<option value="' . esc_attr( $event_category ) . '" ' . selected( $mappings[ $event_term->slug ] ?? '', $event_category, false ) . '>' . esc_html( ACTIVITYPUB_EVENT_BRIDGE_EVENT_CATEGORIES[ $event_category ] ) . '</option>';
								}
								?>
							</select>
						</td>
					</tr>
				<?php } ?>
			</table>
			<?php endif; ?>
		</div>
		<!-- This disables the setup wizard. -->
		<div class="hidden">
			<input type="checkbox" id="activitypub_event_bridge_initially_activated" name="activitypub_event_bridge_initially_activated"/>
		</div>
		<?php \submit_button(); ?>
	</form>
</div>
