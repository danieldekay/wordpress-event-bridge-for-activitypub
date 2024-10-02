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

$default_event_category_strings = array(
	'ARTS'                          => __( 'Arts', 'activitypub-event-bridge' ),
	'BOOK_CLUBS'                    => __( 'Book clubs', 'activitypub-event-bridge' ),
	'BUSINESS'                      => __( 'Business', 'activitypub-event-bridge' ),
	'CAUSES'                        => __( 'Causes', 'activitypub-event-bridge' ),
	'COMEDY'                        => __( 'Comedy', 'activitypub-event-bridge' ),
	'CRAFTS'                        => __( 'Crafts', 'activitypub-event-bridge' ),
	'FOOD_DRINK'                    => __( 'Food & Drink', 'activitypub-event-bridge' ),
	'HEALTH'                        => __( 'Health', 'activitypub-event-bridge' ),
	'MUSIC'                         => __( 'Music', 'activitypub-event-bridge' ),
	'AUTO_BOAT_AIR'                 => __( 'Auto, boat and air', 'activitypub-event-bridge' ),
	'COMMUNITY'                     => __( 'Community', 'activitypub-event-bridge' ),
	'FAMILY_EDUCATION'              => __( 'Family & Education', 'activitypub-event-bridge' ),
	'FASHION_BEAUTY'                => __( 'Fashion & Beauty', 'activitypub-event-bridge' ),
	'FILM_MEDIA'                    => __( 'Film & Media', 'activitypub-event-bridge' ),
	'GAMES'                         => __( 'Games', 'activitypub-event-bridge' ),
	'LANGUAGE_CULTURE'              => __( 'Language & Culture', 'activitypub-event-bridge' ),
	'LEARNING'                      => __( 'Learning', 'activitypub-event-bridge' ),
	'LGBTQ'                         => __( 'LGBTQ', 'activitypub-event-bridge' ),
	'MOVEMENTS_POLITICS'            => __( 'Movements and politics', 'activitypub-event-bridge' ),
	'NETWORKING'                    => __( 'Networking', 'activitypub-event-bridge' ),
	'PARTY'                         => __( 'Party', 'activitypub-event-bridge' ),
	'PERFORMING_VISUAL_ARTS'        => __( 'Performing & Visual Arts', 'activitypub-event-bridge' ),
	'PETS'                          => __( 'Pets', 'activitypub-event-bridge' ),
	'PHOTOGRAPHY'                   => __( 'Photography', 'activitypub-event-bridge' ),
	'OUTDOORS_ADVENTURE'            => __( 'Outdoors & Adventure', 'activitypub-event-bridge' ),
	'SPIRITUALITY_RELIGION_BELIEFS' => __( 'Spirituality, Religion & Beliefs', 'activitypub-event-bridge' ),
	'SCIENCE_TECH'                  => __( 'Science & Tech', 'activitypub-event-bridge' ),
	'SPORTS'                        => __( 'Sports', 'activitypub-event-bridge' ),
	'THEATRE'                       => __( 'Theatre', 'activitypub-event-bridge' ),
	'MEETING'                       => __( 'Meeting', 'activitypub-event-bridge' ), // Default value in federation.
	'DEFAULT'                       => __( 'Default', 'activitypub-event-bridge' ), // Internal default for overrides.
);

$selected_default_event_category = \get_option( 'activitypub_event_bridge_default_event_category', 'MEETING' );
$current_category_mapping        = \get_option( 'activitypub_event_bridge_event_category_mappings', array() );
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
						foreach ( $default_event_category_strings as $value => $label ) {
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
									echo '<option value="' . esc_attr( $mapping ) . '"> -- ' . esc_html( $default_event_category_strings[ $mapping ] ) . ' -- </option>';
								} else {
									echo '<option value="' . esc_attr( $mapping ) . '">' . esc_html( $default_event_category_strings[ $mapping ] ) . '</option>';
								}
								echo '<option value="DEFAULT" ' . selected( $selected_default_event_category, 'DEFAULT', false ) . '> -- ' . esc_html__( 'Default', 'activitypub-event-bridge' ) . ' -- </option>';
								foreach ( Event::DEFAULT_EVENT_CATEGORIES as $event_category ) {
									echo '<option value="' . esc_attr( $event_category ) . '" ' . selected( $mappings[ $event_term->slug ] ?? '', $event_category, false ) . '>' . esc_html( $default_event_category_strings[ $event_category ] ) . '</option>';
								}
								?>
							</select>
						</td>
					</tr>
				<?php } ?>
			</table>
		</div>
		<?php endif; ?>
		<?php \submit_button(); ?>
	</form>
</div>
