<?php
/**
 * Outputs when an error occured in Bulk Publish.
 *
 * @since 3.0.5
 *
 * @package WPZinc\Social\Pro
 * @author  WP Zinc
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<header>
	<h1>
		<?php echo esc_html( $this->base->plugin->displayName ); ?>

		<span>
			<?php esc_html_e( 'Bulk Publish', 'wpzinc-social' ); ?>
		</span>
	</h1>
</header>

<hr class="wp-header-end" />

<div class="wrap">
	<?php
	// Output notices.
	$this->base->get_class( 'notices' )->output_notices();
	?>
</div>
