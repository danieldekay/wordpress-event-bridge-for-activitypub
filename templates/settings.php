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
$reminder_time_gap               = \get_option( 'activitypub_event_bridge_reminder_time_gap', 0 );

$reminder_time_gap_choices = array(
	0      => __( 'Disabled', 'activitypub-event-bridge' ),
	21600  => __( '6 hours', 'activitypub-event-bridge' ),
	86400  => __( '1 day', 'activitypub-event-bridge' ),
	259200 => __( '3 days', 'activitypub-event-bridge' ),
	604800 => __( '1 week', 'activitypub-event-bridge' ),
)
?>

<div class="activitypub-settings-header">
	<div class="activitypub-settings-title-section">
		<h1><?php \esc_html_e( 'ActivityPub Event Bridge', 'activitypub-event-bridge' ); ?></h1>
	</div>
</div>
<hr class="wp-header-end">

<div class="activitypub-settings activitypub-settings-page activitypub-event-bridge-settings-page hide-if-no-js">
	<form method="post" action="options.php">
		<?php \settings_fields( 'activitypub-event-bridge' ); ?>

		<div class="box">
			<h2> <?php esc_html_e( 'Default ActivityPub Event Category', 'activitypub-event-bridge' ); ?> </h2>
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
		</div>

		<?php if ( ! empty( $event_terms ) ) : ?>
		<div class="box">
			<h2> <?php esc_html_e( 'Advanced Event Category Settings', 'activitypub-event-bridge' ); ?> </h2>
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
		</div>
		<?php endif; ?>
		<div class="box">
			<h2> <?php esc_html_e( 'Send reminder before event', 'activitypub-event-bridge' ); ?> </h2>
			<p> <?php esc_html_e( 'Specify a time interval before the event starts to trigger a reminder. This reminder automatically boosts the event, making it reappear in users\' timelines at the defined time before the event to increase visibility just before the event begins.', 'activitypub-event-bridge' ); ?> </p>
			<select id="activitypub_event_bridge_reminder_time_gap" name="activitypub_event_bridge_reminder_time_gap">';
						<?php
						foreach ( $reminder_time_gap_choices as $value => $label ) {
							echo '<option value="' . esc_attr( $value ) . '" ' . selected( $reminder_time_gap, $value, false ) . '>' . esc_html( $label ) . '</option>';
						}
						?>
			</select>
		</div>
		<?php \submit_button(); ?>
	</form>
</div>
