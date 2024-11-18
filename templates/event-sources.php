<?php
/**
 * Event Sources management page for the ActivityPub Event Bridge.
 *
 * @package ActivityPub_Event_Bridge
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


$table = new \ActivityPub_Event_Bridge\Table\Event_Sources();
?>

<div class="activitypub-event-bridge-settings activitypub-event-bridge-settings-page hide-if-no-js">

	<div class="box">
		<h2> <?php esc_html_e( 'Federated event sources', 'activitypub-event-bridge' ); ?> </h2>
		<p> <?php esc_html_e( 'Here you can add any Fediverse Account.', 'activitypub-event-bridge' ); ?> </p>

	<!-- Button that triggers ThickBox -->
	<a href="#TB_inline?width=600&height=400&inlineId=activitypub_event_bridge_add_new_source" class="thickbox button button-primary">
		<?php esc_html_e( 'Add new', 'activitypub-event-bridge' ); ?>
	</a>

	<!-- ThickBox content (hidden initially) -->
	<div id="activitypub_event_bridge_add_new_source" style="display:none;">
		<h2><?php esc_html_e( 'Add new ActivityPub follow', 'activitypub-event-bridge' ); ?> </h2>
		<p> <?php esc_html_e( 'Here you can enter either a handle or instance URL.', 'activitypub-event-bridge' ); ?> </p>
	</div>
</div>

<div class="wrap activitypub-followers-page">
	<form method="get">
		<input type="hidden" name="page" value="activitypub" />
		<input type="hidden" name="tab" value="followers" />
		<?php
		$table->prepare_items();
		$table->search_box( 'Search', 'search' );
		$table->display();
		?>
	</form>
</div>
