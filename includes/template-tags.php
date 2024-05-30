<?php
/**
 * Display helpers for template rendering.
 */

// Notification Bar to be placed after opening body tag.
add_action( 'wp_body_open', 'whx3_display_notification_bar' );

/**
 * Check for the Notification Bar, and display it.
 *
 * @return void
 *
 * @since 0.1.1
 */
function whx3_display_notification_bar() {
	$has_notice = get_field( 'whx3_notification_bar_group', 'options' );

	if ( ! $has_notice || ! $has_notice['whx3_notification_onoff'] ) {
		return;
	}
	?>

	<div class="demo-acf-notification-bar" style="margin-top:0;padding-top:15px;padding-right:30px;padding-bottom:15px;padding-left:30px;background-color:pink;color:black;">
		<div class="demo-acf-notification-bar__inner">
			<p class="demo-acf-notification-bar__content" style="font-size:80%;line-height:1;margin:0 auto;text-align:center;">
				<?php echo wp_kses_post( $has_notice['whx3_notification_message'] ); ?>
			</p>
		</div>
	</div>
	<?php
}
