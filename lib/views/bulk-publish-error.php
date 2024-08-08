<?php
/**
 * Outputs when an error occured in Bulk Publish.
 *
 * @since 3.0.5
 *
 * @package WP_To_Social_Pro
 * @author  WP Zinc
 */

?>
<div class="wrap">
	<h1 class="wp-heading-inline">
		<?php echo esc_html( $this->base->plugin->displayName ); ?>

		<span>
			<?php esc_html_e( 'Bulk Publish', 'wp-to-social-pro' ); ?>
		</span>
	</h1>

	<?php
	// Output notices.
	$this->base->get_class( 'notices' )->output_notices();
	?>
</div>
