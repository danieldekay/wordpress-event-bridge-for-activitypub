<?php
/**
 * Template for the header and navigation of the admin pages.
 *
 * @package ActivityPub_Event_Bridge
 */

/* @var array $args Template arguments. */
$args = wp_parse_args(
	$args,
	array(
		'status'   => '',
		'settings' => '',
	)
);
?>

<div class="activitypub-event-bridge-settings-header">
	<div class="activitypub-event-bridge-settings-title-section">
		<h1><?php \esc_html_e( 'ActivityPub Event Bridge', 'activitypub-event-bridge' ); ?></h1>
	</div>

	<nav class="activitypub-event-bridge-settings-tabs-wrapper" aria-label="<?php \esc_attr_e( 'Secondary menu', 'activitypub-event-bridge' ); ?>">
		<a href="<?php echo \esc_url( admin_url( 'options-general.php?page=activitypub-event-bridge' ) ); ?>" class="activitypub-event-bridge-settings-tab <?php echo \esc_attr( $args['status'] ); ?>">
			<?php \esc_html_e( 'Status', 'activitypub-event-bridge' ); ?>
		</a>

		<a href="<?php echo \esc_url( admin_url( 'options-general.php?page=activitypub-event-bridge&tab=settings' ) ); ?>" class="activitypub-event-bridge-settings-tab <?php echo \esc_attr( $args['settings'] ); ?>">
			<?php \esc_html_e( 'Settings', 'activitypub-event-bridge' ); ?>
		</a>
	</nav>
</div>
<hr class="wp-header-end">
