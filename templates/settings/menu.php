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
		'welcome'        => '',
		'settings'       => '',
		'event-sources'  => '',
		'generic-plugin' => '',
	)
);
?>

<div class="event-bridge-for-activitypub-settings-header">
	<!-- <div class="event-bridge-for-activitypub-settings-title-section">
		<h1><?php \esc_html_e( 'Event Bridge for ActivityPub', 'event-bridge-for-activitypub' ); ?></h1>
	</div> -->

	<nav class="event-bridge-for-activitypub-settings-tabs-wrapper" aria-label="<?php \esc_attr_e( 'Tertiary menu', 'event-bridge-for-activitypub' ); ?>">
		<a href="<?php echo \esc_url( admin_url( 'options-general.php?page=activitypub&tab=event-bridge-for-activitypub' ) ); ?>" class="event-bridge-for-activitypub-settings-tab <?php echo \esc_attr( $args['welcome'] ); ?>">
			<?php \esc_html_e( 'Overview & Status', 'event-bridge-for-activitypub' ); ?>
		</a>

		<a href="<?php echo \esc_url( admin_url( 'options-general.php?page=activitypub&tab=event-bridge-for-activitypub&subpage=settings' ) ); ?>" class="event-bridge-for-activitypub-settings-tab <?php echo \esc_attr( $args['settings'] ); ?>">
			<?php \esc_html_e( 'Event Bridge Settings', 'event-bridge-for-activitypub' ); ?>
		</a>

		<a href="<?php echo \esc_url( admin_url( 'options-general.php?page=activitypub&tab=event-bridge-for-activitypub&subpage=event-sources' ) ); ?>" class="event-bridge-for-activitypub-settings-tab <?php echo \esc_attr( $args['event-sources'] ); ?>">
			<?php \esc_html_e( 'Federated Event Sources', 'event-bridge-for-activitypub' ); ?>
		</a>

		<a href="<?php echo \esc_url( admin_url( 'options-general.php?page=activitypub&tab=event-bridge-for-activitypub&subpage=generic-plugin' ) ); ?>" class="event-bridge-for-activitypub-settings-tab <?php echo \esc_attr( $args['generic-plugin'] ); ?>">
			<?php \esc_html_e( 'Generic Event Plugin', 'event-bridge-for-activitypub' ); ?>
		</a>
	</nav>
</div>
