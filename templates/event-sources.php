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

\load_template(
	__DIR__ . '/admin-header.php',
	true,
	array(
		'event-sources' => 'active',
	)
);
?>

<div class="wrap event_bridge_for_activitypub-admin-table-container">
	<?php if ( defined( 'ACTIVITYPUB_PLUGIN_VERSION' ) ) { ?>
		<!-- Table title with add new button like on post edit pages -->
		<div class="event_bridge_for_activitypub-admin-table-top">
			<h2 class="wp-heading-inline"> <?php esc_html_e( 'List of Event Sources', 'event-bridge-for-activitypub' ); ?> </h2>
			<!-- Button that triggers ThickBox -->
			<a href="#TB_inline?width=600&height=400&inlineId=Event_Bridge_For_ActivityPub_add_new_source" class="thickbox page-title-action">
				<?php esc_html_e( 'Add Event Source', 'event-bridge-for-activitypub' ); ?>
			</a>
		</div>

		<!-- ThickBox content (hidden initially) -->
		<div id="Event_Bridge_For_ActivityPub_add_new_source" style="display:none;">
			<h2><?php esc_html_e( 'Add new ActivityPub follow', 'event-bridge-for-activitypub' ); ?> </h2>
			<p> <?php esc_html_e( 'Here you can enter either a Fediverse handle (@username@example.social), URL of an ActivityPub Account (https://example.social/user/username) or instance URL.', 'event-bridge-for-activitypub' ); ?> </p>
			<form method="post" action="options.php">
				<?php \settings_fields( 'event-bridge-for-activitypub-event-sources' ); ?>
				<input type="text" name="event_bridge_for_activitypub_event_source" id="event_bridge_for_activitypub_event_source" value="">
				<?php \submit_button( __( 'Add Event Source', 'event-bridge-for-activitypub' ) ); ?>
			</form>
		</div>
		<div class="wrap activitypub-followers-page">
			<form method="get">
				<input type="hidden" name="page" value="event-bridge-for-activitypub" />
				<input type="hidden" name="tab" value="event-sources" />
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

