<?php
/**
 * Uninstall
 *
 * @package Media from ZIP
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

$option_name  = 'mediafromzip';
$option_name2 = 'mediafromzip_generate_mail';
$option_name3 = 'mediafromzip_submit_file';

/* For Single site */
if ( ! is_multisite() ) {
	$blogusers = get_users( array( 'fields' => array( 'ID' ) ) );
	foreach ( $blogusers as $user ) {
		delete_user_option( $user->ID, $option_name, false );
		delete_user_option( $user->ID, $option_name2, false );
		delete_user_option( $user->ID, $option_name3, false );
	}
} else {
	/* For Multisite */
	global $wpdb;
	$blog_ids = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->prefix}blogs" );
	$original_blog_id = get_current_blog_id();
	foreach ( $blog_ids as $blogid ) {
		switch_to_blog( $blogid );
		$blogusers = get_users(
			array(
				'blog_id' => $blogid,
				'fields' => array( 'ID' ),
			)
		);
		foreach ( $blogusers as $user ) {
			delete_user_option( $user->ID, $option_name, false );
			delete_user_option( $user->ID, $option_name2, false );
			delete_user_option( $user->ID, $option_name3, false );
		}
	}
	switch_to_blog( $original_blog_id );

}
