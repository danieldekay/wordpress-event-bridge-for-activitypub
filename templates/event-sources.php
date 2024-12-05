<?php
/**
 * Event Sources management page for the ActivityPub Event Bridge.
 *
 * @package Event_Bridge_For_ActivityPub
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


$table = new \Event_Bridge_For_ActivityPub\Table\Event_Sources();
?>

<div class="event-bridge-for-activitypub-settings event-bridge-for-activitypub-settings-page hide-if-no-js">

	<div class="box">
		<h2> <?php esc_html_e( 'Federated event sources', 'event-bridge-for-activitypub' ); ?> </h2>
		<p> <?php esc_html_e( 'Here you can add any Fediverse Account.', 'event-bridge-for-activitypub' ); ?> </p>

	<!-- Button that triggers ThickBox -->
	<a href="#TB_inline?width=600&height=400&inlineId=Event_Bridge_For_ActivityPub_add_new_source" class="thickbox button button-primary">
		<?php esc_html_e( 'Add new', 'event-bridge-for-activitypub' ); ?>
	</a>

	<!-- ThickBox content (hidden initially) -->
	<div id="Event_Bridge_For_ActivityPub_add_new_source" style="display:none;">
		<h2><?php esc_html_e( 'Add new ActivityPub follow', 'event-bridge-for-activitypub' ); ?> </h2>
		<p> <?php esc_html_e( 'Here you can enter either a handle or instance URL.', 'event-bridge-for-activitypub' ); ?> </p>
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
