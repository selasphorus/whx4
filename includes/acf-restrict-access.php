<?php
/**
 * Limit capabilities of editing ACF fields,
 * post types, taxonomies and more in WP Admin.
 */

/**
 * @link https://www.advancedcustomfields.com/resources/how-to-hide-acf-menu-from-clients/
 */
add_filter( 'acf/settings/show_admin', 'whx4_show_acf_admin' );
/**
 * Filters the settings to pass to the block editor for all editor type.
 *
 * @link https://developer.wordpress.org/reference/hooks/block_editor_settings_all/
 */
add_filter( 'block_editor_settings_all', 'whx4_restrict_locking_ui', 10, 2 );

/**
 * Allow access to ACF screens by WP user role
 * AND a list of allowed email domains.
 *
 * @link https://developer.wordpress.org/reference/functions/current_user_can/
 *
 * @return boolean $show Whether to show the ACF admin.
 *
 * @since 0.1.2
 */
function whx4_show_acf_admin() {
	// If our user can manage site options.
	if ( current_user_can( 'manage_options' ) ) {
	
		$user = wp_get_current_user();

		// Make sure we have a WP_User object and email address.
		if ( $user && isset( $user->user_email ) ) {
			
			// Compare current logged in user's email with our allow list.
			//if ( in_array( $email_domain, $allowed_email_domains, true ) ) {
			if ( $user->user_email == "birdhive@gmail.com" || $user->user_email == "alphameric@protonmail.com" ) {
				return true;
			}
		}
	}
}

/**
 * Restrict access to the locking UI to designated email domains.
 *
 * @param array $settings Default editor settings.
 *
 * @since 0.1.3
 */
function whx4_restrict_locking_ui( $settings ) {
	$settings['canLockBlocks'] = whx4_show_acf_admin();

	return $settings;
}
