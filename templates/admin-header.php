<!-- TODO css classes?
currently reusing activitypub classes which is kinda nice, because it has a consistent theme then, but also it cloud break if activitypub changes something
-->
<div class="activitypub-settings-header">
	<div class="activitypub-settings-title-section">
		<h1><?php \esc_html_e( 'Activitypub Events Plugin', $args['slug'] ); ?></h1>
	</div>

	<nav class="activitypub-settings-tabs-wrapper" aria-label="<?php \esc_attr_e( 'Secondary menu', $args['slug'] ); ?>">
        <!-- todo loop through settings pages of Extractors -->
		<?php foreach ( $args['options'] as $tabslug => $plugin ) { ?>
        <a href="<?php echo \esc_url_raw( admin_url( 'options-general.php?page='. $args['slug'] .'&tab=' . $tabslug ) ); ?>" class="activitypub-settings-tab <?php echo \esc_attr( $plugin['active'] ? 'active' : '' ); ?>">
			<?php \esc_html_e( $plugin['name'], $args['slug'] ); ?> <!-- Todo better name handling -->
        </a>
		<?php } ?>



	</nav>
</div>
<hr class="wp-header-end">
