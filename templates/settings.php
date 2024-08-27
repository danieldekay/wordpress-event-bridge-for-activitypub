<?php
/**
 * Template for ActivityPub Event Extensions settings pages.
 *
 * This template is used to display and manage settings for the ActivityPub Event Extensions plugin.
 *
 * @package ActivityPub_Event_Extensions
 * @since 1.0.0
 *
 * @param array  $args An array of arguments for the settings page.
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

use Activitypub\Activity\Extended_Object\Event;

if ( ! isset( $args ) ) {
	return;
}

if ( ! current_user_can( 'manage_options' ) ) {
	return;
}

$event_terms = $args['event_terms'];

$default_event_category_strings = array(
	'ARTS'                          => __( 'Arts', 'activitypub-event-extensions' ),
	'BOOK_CLUBS'                    => __( 'Book clubs', 'activitypub-event-extensions' ),
	'BUSINESS'                      => __( 'Business', 'activitypub-event-extensions' ),
	'CAUSES'                        => __( 'Causes', 'activitypub-event-extensions' ),
	'COMEDY'                        => __( 'Comedy', 'activitypub-event-extensions' ),
	'CRAFTS'                        => __( 'Crafts', 'activitypub-event-extensions' ),
	'FOOD_DRINK'                    => __( 'Food & Drink', 'activitypub-event-extensions' ),
	'HEALTH'                        => __( 'Health', 'activitypub-event-extensions' ),
	'MUSIC'                         => __( 'Music', 'activitypub-event-extensions' ),
	'AUTO_BOAT_AIR'                 => __( 'Auto, boat and air', 'activitypub-event-extensions' ),
	'COMMUNITY'                     => __( 'Community', 'activitypub-event-extensions' ),
	'FAMILY_EDUCATION'              => __( 'Family & Education', 'activitypub-event-extensions' ),
	'FASHION_BEAUTY'                => __( 'Fashion & Beauty', 'activitypub-event-extensions' ),
	'FILM_MEDIA'                    => __( 'Film & Media', 'activitypub-event-extensions' ),
	'GAMES'                         => __( 'Games', 'activitypub-event-extensions' ),
	'LANGUAGE_CULTURE'              => __( 'Language & Culture', 'activitypub-event-extensions' ),
	'LEARNING'                      => __( 'Learning', 'activitypub-event-extensions' ),
	'LGBTQ'                         => __( 'LGBTQ', 'activitypub-event-extensions' ),
	'MOVEMENTS_POLITICS'            => __( 'Movements and politics', 'activitypub-event-extensions' ),
	'NETWORKING'                    => __( 'Networking', 'activitypub-event-extensions' ),
	'PARTY'                         => __( 'Party', 'activitypub-event-extensions' ),
	'PERFORMING_VISUAL_ARTS'        => __( 'Performing & Visual Arts', 'activitypub-event-extensions' ),
	'PETS'                          => __( 'Pets', 'activitypub-event-extensions' ),
	'PHOTOGRAPHY'                   => __( 'Photography', 'activitypub-event-extensions' ),
	'OUTDOORS_ADVENTURE'            => __( 'Outdoors & Adventure', 'activitypub-event-extensions' ),
	'SPIRITUALITY_RELIGION_BELIEFS' => __( 'Spirituality, Religion & Beliefs', 'activitypub-event-extensions' ),
	'SCIENCE_TECH'                  => __( 'Science & Tech', 'activitypub-event-extensions' ),
	'SPORTS'                        => __( 'Sports', 'activitypub-event-extensions' ),
	'THEATRE'                       => __( 'Theatre', 'activitypub-event-extensions' ),
	'MEETING'                       => __( 'Meeting', 'activitypub-event-extensions' ), // Default value in federation.
	'DEFAULT'                       => __( 'Default', 'activitypub-event-extensions' ), // Internal default for overrides.
);

$selected_default_event_category = \get_option( 'activitypub_event_extensions_default_event_category', 'MEETING' );
$current_category_mapping        = \get_option( 'activitypub_event_extensions_event_category_mappings', array() );
?>

<div class="activitypub-settings activitypub-settings-page hide-if-no-js">
	<form method="post" action="options.php">
		<?php \settings_fields( 'activitypub-event-extensions' ); ?>

		<div class="box">

			<h2> <?php esc_html_e( 'Default ActivityPub Event Category', 'activitypub-event-extensions' ); ?> </h2>

			<p> <?php esc_html_e( 'The community defined an arbitrary set of basic event categories in order to allow events from multiple organizers to be grouped in a useful way. Please specify the category most common for your events.' ); ?> </p>

			<table class="form-table">
				<tr>
					<th scope="row"> <?php esc_html_e( 'Default Category', 'activitypub-event-extensions' ); ?> </th>
					<td>
						<select id="activitypub_event_extensions_default_event_category" name="activitypub_event_extensions_default_event_category">';
						<?php
						foreach ( $default_event_category_strings as $value => $label ) {
							echo '<option value="' . esc_attr( $value ) . '" ' . selected( $selected_default_event_category, $value, false ) . '>' . esc_html( $label ) . '</option>';
						}
						?>
						</select>
					</td>
				</tr>
			</table>

			<h2> <?php esc_html_e( 'Specific mapping of Event Categories', 'activitypub-event-extensions' ); ?> </h2>

			<p> <?php esc_html_e( 'Here you can assign each of your event categories in use to the basic category set used in ActivityPub .' ); ?> </p>

			<table class="form-table">
				<?php foreach ( $event_terms as $event_term ) { ?>
					<tr>
						<th scope="row"> <?php echo esc_html( $event_term->name ); ?> </th>
						<td>
							<select name="activitypub_event_extensions_event_category_mappings[<?php echo esc_attr( $event_term->slug ); ?>]">
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
								echo '<option value="DEFAULT" ' . selected( $selected_default_event_category, 'DEFAULT', false ) . '> -- ' . esc_html__( 'Default', 'activitypub-event-extensions' ) . ' -- </option>';
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
		<?php \submit_button(); ?>
	</form>
</div>
