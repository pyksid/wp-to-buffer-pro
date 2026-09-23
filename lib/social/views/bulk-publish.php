<?php
/**
 * Outputs Bulk Publish View
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
	$this->base->get_class( 'notices' )->set_key_prefix( $this->base->plugin->filter_name . '_' . wp_get_current_user()->ID );
	$this->base->get_class( 'notices' )->output_notices();
	?>

	<!-- Container for JS notices -->
	<div class="js-notices"></div>

	<div class="wrap-inner">
		<!-- Tabs -->
		<h2 class="nav-tab-wrapper wpzinc-horizontal-tabbed-ui">
			<?php
			// Go through all Post Types, if API is authenticated.
			if ( $this->base->get_class( 'settings' )->account_connected() ) {
				foreach ( $post_types as $public_post_type => $post_type_obj ) {
					// Work out the icon to display.
					$icon = '';
					if ( ! empty( $post_type_obj->menu_icon ) ) {
						$icon = 'dashicons ' . $post_type_obj->menu_icon;
					} elseif ( $public_post_type === 'post' || $public_post_type === 'page' ) {
							$icon = 'dashicons dashicons-admin-' . $public_post_type;
					}
					?>
					<a href="admin.php?page=<?php echo esc_attr( $this->base->plugin->name ); ?>-bulk-publish&amp;tab=post&amp;type=<?php echo esc_attr( $public_post_type ); ?>" class="nav-tab<?php echo esc_attr( $post_type === $public_post_type ? ' nav-tab-active' : '' ); ?>" title="<?php echo esc_attr( $post_type_obj->labels->name ); ?>">
						<span class="<?php echo esc_attr( $icon ); ?>"></span>
						<span class="text">
							<?php echo esc_attr( $post_type_obj->labels->name ); ?>
						</span>
					</a>
					<?php
				}
			}
			?>
		</h2>

		<!-- Form Start -->
		<form name="post" method="post" action="<?php echo ( isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '' ); ?>" id="<?php echo esc_attr( $this->base->plugin->name ); ?>">    
			<div id="poststuff">
				<div id="post-body" class="metabox-holder columns-1">
					<!-- Content -->
					<div id="post-body-content">
						<div id="normal-sortables" class="meta-box-sortables ui-sortable publishing-defaults">  
							<?php
							// Load sub view.
							require_once $this->base->plugin->folder . 'lib/social/views/bulk-publish-' . $stage . '.php';

							// Nonce.
							wp_nonce_field( $this->base->plugin->name . '-bulk-publish', $this->base->plugin->name . '_nonce' );
							?>
						</div>
						<!-- /normal-sortables -->
					</div>
					<!-- /post-body-content -->
				</div>
			</div> 
		</form>
		<!-- /form end -->		
	</div><!-- ./wrap-inner -->           
</div>
