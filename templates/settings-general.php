<?php
// Is this check necessary? it's already enforced by admin_menu()
// it's "recommended" by https://developer.wordpress.org/plugins/administration-menus/sub-menus/
if ( ! current_user_can( 'manage_options' ) ) {
	return;
}

\load_template(
	__DIR__ . '/admin-header.php',
	true,
	$args
);
?>