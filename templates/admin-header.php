<?php
/**
 * Template for the header and navigation of the admin pages.
 *
 * @package Event_Bridge_For_ActivityPub
 * @since   1.0.0
 * @license AGPL-3.0-or-later
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

/* @var array $args Template arguments. */
$args = wp_parse_args(
	$args,
	array(
		'welcome'       => '',
		'settings'      => '',
		'event-sources' => '',
	)
);
?>

<div class="event-bridge-for-activitypub-settings-header">
	<div class="event-bridge-for-activitypub-settings-title-section">
		<h1><?php \esc_html_e( 'Event Bridge for ActivityPub', 'event-bridge-for-activitypub' ); ?></h1>
	</div>

	<nav class="event-bridge-for-activitypub-settings-tabs-wrapper" aria-label="<?php \esc_attr_e( 'Secondary menu', 'event-bridge-for-activitypub' ); ?>">
		<a href="<?php echo \esc_url( admin_url( 'options-general.php?page=event-bridge-for-activitypub' ) ); ?>" class="event-bridge-for-activitypub-settings-tab <?php echo \esc_attr( $args['welcome'] ); ?>">
			<?php \esc_html_e( 'Welcome', 'event-bridge-for-activitypub' ); ?>
		</a>

		<a href="<?php echo \esc_url( admin_url( 'options-general.php?page=event-bridge-for-activitypub&tab=settings' ) ); ?>" class="event-bridge-for-activitypub-settings-tab <?php echo \esc_attr( $args['settings'] ); ?>">
			<?php \esc_html_e( 'Settings', 'event-bridge-for-activitypub' ); ?>
		</a>

		<a href="<?php echo \esc_url( admin_url( 'options-general.php?page=event-bridge-for-activitypub&tab=event-sources' ) ); ?>" class="event-bridge-for-activitypub-settings-tab <?php echo \esc_attr( $args['event-sources'] ); ?>">
			<?php \esc_html_e( 'Event Sources', 'event-bridge-for-activitypub' ); ?>
		</a>
	</nav>
</div>
<hr class="wp-header-end">
