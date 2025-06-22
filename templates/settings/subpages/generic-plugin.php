<?php
/**
 * Generic Event Plugin settings page for the ActivityPub Event Bridge.
 *
 * @package Event_Bridge_For_ActivityPub
 * @since   1.0.0
 * @license AGPL-3.0-or-later
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

\load_template(
	__DIR__ . '/../menu.php',
	true,
	array(
		'generic-plugin' => 'active',
	)
);

if ( ! current_user_can( 'manage_options' ) ) {
	return;
}

// Handle form submission
if ( isset( $_POST['submit'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'event_bridge_for_activitypub_generic_settings' ) ) {
	// Save settings
	update_option( 'event_bridge_for_activitypub_generic_enabled', isset( $_POST['event_bridge_for_activitypub_generic_enabled'] ) );
	update_option( 'event_bridge_for_activitypub_generic_post_type', sanitize_text_field( $_POST['event_bridge_for_activitypub_generic_post_type'] ?? 'event' ) );
	update_option( 'event_bridge_for_activitypub_generic_category_taxonomy', sanitize_text_field( $_POST['event_bridge_for_activitypub_generic_category_taxonomy'] ?? 'category' ) );
	
	// Handle field mappings
	$field_mappings = array();
	$mapping_fields = array( 'start_time', 'end_time', 'location', 'summary', 'event_link', 'event_link_label' );
	
	foreach ( $mapping_fields as $field ) {
		if ( ! empty( $_POST['field_mappings'][ $field ] ) ) {
			$field_mappings[ $field ] = array(
				'source_type' => sanitize_text_field( $_POST['field_mappings'][ $field ]['source_type'] ),
				'field_name' => sanitize_text_field( $_POST['field_mappings'][ $field ]['field_name'] ),
			);
		}
	}
	
	update_option( 'event_bridge_for_activitypub_generic_field_mappings', $field_mappings );
	
	echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'event-bridge-for-activitypub' ) . '</p></div>';
}

// Get current settings
$generic_enabled = get_option( 'event_bridge_for_activitypub_generic_enabled', false );
$generic_post_type = get_option( 'event_bridge_for_activitypub_generic_post_type', 'event' );
$generic_category_taxonomy = get_option( 'event_bridge_for_activitypub_generic_category_taxonomy', 'category' );
$field_mappings = get_option( 'event_bridge_for_activitypub_generic_field_mappings', array() );

$post_types = $args['post_types'] ?? array();
$taxonomies = $args['taxonomies'] ?? array();
?>

<div class="wrap">
	<h2><?php esc_html_e( 'Generic Event Plugin Configuration', 'event-bridge-for-activitypub' ); ?></h2>
	
	<p><?php esc_html_e( 'Configure a generic event plugin by selecting a post type and mapping fields to ActivityPub event properties.', 'event-bridge-for-activitypub' ); ?></p>
	
	<form method="post">
		<?php wp_nonce_field( 'event_bridge_for_activitypub_generic_settings' ); ?>
		
		<h3><?php esc_html_e( 'Basic Settings', 'event-bridge-for-activitypub' ); ?></h3>
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="event_bridge_for_activitypub_generic_enabled"><?php esc_html_e( 'Enable Generic Event Plugin', 'event-bridge-for-activitypub' ); ?></label>
				</th>
				<td>
					<input
						type="checkbox"
						name="event_bridge_for_activitypub_generic_enabled"
						id="event_bridge_for_activitypub_generic_enabled"
						value="1"
						<?php checked( $generic_enabled ); ?>
					>
					<p class="description"><?php esc_html_e( 'Enable the generic event plugin integration.', 'event-bridge-for-activitypub' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="event_bridge_for_activitypub_generic_post_type"><?php esc_html_e( 'Event Post Type', 'event-bridge-for-activitypub' ); ?></label>
				</th>
				<td>
					<select name="event_bridge_for_activitypub_generic_post_type" id="event_bridge_for_activitypub_generic_post_type">
						<?php foreach ( $post_types as $post_type ) : ?>
							<option value="<?php echo esc_attr( $post_type->name ); ?>" <?php selected( $generic_post_type, $post_type->name ); ?>>
								<?php echo esc_html( $post_type->label . ' (' . $post_type->name . ')' ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<p class="description"><?php esc_html_e( 'Select the post type that contains your events.', 'event-bridge-for-activitypub' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="event_bridge_for_activitypub_generic_category_taxonomy"><?php esc_html_e( 'Event Category Taxonomy', 'event-bridge-for-activitypub' ); ?></label>
				</th>
				<td>
					<select name="event_bridge_for_activitypub_generic_category_taxonomy" id="event_bridge_for_activitypub_generic_category_taxonomy">
						<?php foreach ( $taxonomies as $taxonomy ) : ?>
							<option value="<?php echo esc_attr( $taxonomy->name ); ?>" <?php selected( $generic_category_taxonomy, $taxonomy->name ); ?>>
								<?php echo esc_html( $taxonomy->label . ' (' . $taxonomy->name . ')' ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<p class="description"><?php esc_html_e( 'Select the taxonomy used for event categories.', 'event-bridge-for-activitypub' ); ?></p>
				</td>
			</tr>
		</table>
		
		<h3><?php esc_html_e( 'Field Mappings', 'event-bridge-for-activitypub' ); ?></h3>
		<p><?php esc_html_e( 'Map your event fields to ActivityPub event properties. Leave fields empty if not applicable.', 'event-bridge-for-activitypub' ); ?></p>
		
		<table class="form-table">
			<?php
			$mapping_fields = array(
				'start_time' => array(
					'label' => __( 'Start Time', 'event-bridge-for-activitypub' ),
					'description' => __( 'The event start date/time field.', 'event-bridge-for-activitypub' ),
				),
				'end_time' => array(
					'label' => __( 'End Time', 'event-bridge-for-activitypub' ),
					'description' => __( 'The event end date/time field (optional).', 'event-bridge-for-activitypub' ),
				),
				'location' => array(
					'label' => __( 'Location', 'event-bridge-for-activitypub' ),
					'description' => __( 'The event location/venue field.', 'event-bridge-for-activitypub' ),
				),
				'summary' => array(
					'label' => __( 'Summary/Description', 'event-bridge-for-activitypub' ),
					'description' => __( 'The event description or summary field (optional).', 'event-bridge-for-activitypub' ),
				),
				'event_link' => array(
					'label' => __( 'Event Link', 'event-bridge-for-activitypub' ),
					'description' => __( 'URL to the event page or registration (optional).', 'event-bridge-for-activitypub' ),
				),
				'event_link_label' => array(
					'label' => __( 'Event Link Label', 'event-bridge-for-activitypub' ),
					'description' => __( 'Label for the event link (optional).', 'event-bridge-for-activitypub' ),
				),
			);
			
			$source_types = array(
				'meta' => __( 'Post Meta Field', 'event-bridge-for-activitypub' ),
				'post_field' => __( 'Post Field', 'event-bridge-for-activitypub' ),
				'taxonomy' => __( 'Taxonomy', 'event-bridge-for-activitypub' ),
				'custom_field' => __( 'Custom Field (ACF)', 'event-bridge-for-activitypub' ),
			);
			
			foreach ( $mapping_fields as $field_key => $field_info ) :
				$current_mapping = $field_mappings[ $field_key ] ?? array();
				$current_source_type = $current_mapping['source_type'] ?? 'meta';
				$current_field_name = $current_mapping['field_name'] ?? '';
			?>
				<tr>
					<th scope="row">
						<label><?php echo esc_html( $field_info['label'] ); ?></label>
					</th>
					<td>
						<div style="margin-bottom: 10px;">
							<strong><?php esc_html_e( 'Source Type:', 'event-bridge-for-activitypub' ); ?></strong>
							<select name="field_mappings[<?php echo esc_attr( $field_key ); ?>][source_type]" style="margin-left: 10px;">
								<?php foreach ( $source_types as $type_key => $type_label ) : ?>
									<option value="<?php echo esc_attr( $type_key ); ?>" <?php selected( $current_source_type, $type_key ); ?>>
										<?php echo esc_html( $type_label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>
						<div>
							<strong><?php esc_html_e( 'Field Name:', 'event-bridge-for-activitypub' ); ?></strong>
							<input
								type="text"
								name="field_mappings[<?php echo esc_attr( $field_key ); ?>][field_name]"
								value="<?php echo esc_attr( $current_field_name ); ?>"
								placeholder="<?php esc_attr_e( 'e.g., event_start_date', 'event-bridge-for-activitypub' ); ?>"
								style="margin-left: 10px; width: 200px;"
							>
						</div>
						<p class="description"><?php echo esc_html( $field_info['description'] ); ?></p>
					</td>
				</tr>
			<?php endforeach; ?>
		</table>
		
		<h3><?php esc_html_e( 'Field Mapping Examples', 'event-bridge-for-activitypub' ); ?></h3>
		<div class="notice notice-info">
			<ul>
				<li><strong><?php esc_html_e( 'Post Meta Field:', 'event-bridge-for-activitypub' ); ?></strong> <?php esc_html_e( 'Custom meta fields like "_event_start_date" or "event_location"', 'event-bridge-for-activitypub' ); ?></li>
				<li><strong><?php esc_html_e( 'Post Field:', 'event-bridge-for-activitypub' ); ?></strong> <?php esc_html_e( 'Standard post fields like "post_title", "post_content", "post_excerpt"', 'event-bridge-for-activitypub' ); ?></li>
				<li><strong><?php esc_html_e( 'Taxonomy:', 'event-bridge-for-activitypub' ); ?></strong> <?php esc_html_e( 'Taxonomy terms like "event_category" or "event_location"', 'event-bridge-for-activitypub' ); ?></li>
				<li><strong><?php esc_html_e( 'Custom Field (ACF):', 'event-bridge-for-activitypub' ); ?></strong> <?php esc_html_e( 'ACF fields using get_field() function', 'event-bridge-for-activitypub' ); ?></li>
			</ul>
		</div>
		
		<?php submit_button(); ?>
	</form>
</div>