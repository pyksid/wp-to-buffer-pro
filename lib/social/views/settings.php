<?php
/**
 * Outputs the settings screen.
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
			<?php esc_html_e( 'Settings', 'wpzinc-social' ); ?>
		</span>
	</h1>
</header>

<div class="wrap">
	<?php
	// Output notices.
	$this->base->get_class( 'notices' )->set_key_prefix( $this->base->plugin->filter_name . '_' . wp_get_current_user()->ID );
	$this->base->get_class( 'notices' )->output_notices();
	?>

	<!-- Container for JS notices -->
	<div class="js-notices"></div>

	<div class="wrap-inner">
		<!-- Notices -->
		<hr class="wp-header-end" />

		<!-- Tabs -->
		<h2 class="nav-tab-wrapper wpzinc-horizontal-tabbed-ui">
			<!-- Settings -->
			<a href="admin.php?page=<?php echo esc_attr( $this->base->plugin->name ); ?>-settings" class="nav-tab<?php echo esc_attr( $tab === 'auth' ? ' nav-tab-active' : '' ) . ( $this->base->get_class( 'settings' )->account_connected() ? ' enabled' : ' error' ); ?>" title="<?php esc_attr_e( 'Settings', 'wpzinc-social' ); ?>">
				<span class="dashicons dashicons-lock"></span> 
				<?php
				if ( $this->base->get_class( 'settings' )->account_connected() ) {
					?>
					<span class="dashicons dashicons-yes"></span>
					<?php
				} else {
					?>
					<span class="dashicons dashicons-warning"></span>
					<?php
				}
				?>
				<span class="text">
					<?php esc_html_e( 'Settings', 'wpzinc-social' ); ?>
				</span>
			</a>

			<!-- Public Post Types -->
			<?php
			// Go through all Post Types, if authenticated.
			if ( $this->base->get_class( 'settings' )->account_connected() ) {
				foreach ( $post_types as $public_post_type => $post_type_obj ) {
					// Work out the icon to display.
					$icon = '';
					if ( ! empty( $post_type_obj->menu_icon ) ) {
						$icon = 'dashicons ' . $post_type_obj->menu_icon;
					} elseif ( $public_post_type === 'post' || $public_post_type === 'page' ) {
							$icon = 'dashicons dashicons-admin-' . $public_post_type;
					}

					// Determine if the Post Type is set to post.
					$is_post_type_enabled = $this->base->get_class( 'settings' )->is_post_type_enabled( $public_post_type );
					?>
					<a href="admin.php?page=<?php echo esc_attr( $this->base->plugin->name ); ?>-settings&amp;tab=post&amp;type=<?php echo esc_attr( $public_post_type ); ?>" class="nav-tab<?php echo esc_attr( $post_type === $public_post_type ? ' nav-tab-active' : '' ) . ( $is_post_type_enabled ? ' enabled' : '' ); ?>" title="<?php echo esc_attr( $post_type_obj->labels->name ); ?>" data-post-type="<?php echo esc_attr( $public_post_type ); ?>">
						<span class="<?php echo esc_attr( $icon ); ?>"></span>
						<span class="dashicons dashicons-yes"></span>
						<span class="text">
							<?php echo esc_attr( $post_type_obj->labels->name ); ?>
						</span>
					</a>
					<?php
				}
			}
			?>

			<!-- Documentation -->
			<a href="<?php echo esc_attr( $documentation_url ); ?>" class="nav-tab last documentation" title="<?php esc_html_e( 'Documentation', 'wpzinc-social' ); ?>" target="_blank">
				<span class="text">
					<?php esc_html_e( 'Documentation', 'wpzinc-social' ); ?>
				</span>
				<span class="text-mobile">
					<?php esc_html_e( 'Docs', 'wpzinc-social' ); ?>
				</span>
				<span class="dashicons dashicons-admin-page"></span>
			</a>
		</h2>

		<!-- Form Start -->
		<?php
		// id is deliberate; to ensure CSS, JS etc. works for all versions.
		?>
		<form name="post" method="post" action="<?php echo ( isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '' ); ?>" id="<?php echo esc_attr( $this->base->plugin->name ); ?>" class="wpzinc-social">      
			<div id="poststuff">
				<div id="post-body" class="metabox-holder columns-1">
					<!-- Content -->
					<div id="post-body-content">
						<div id="normal-sortables" class="meta-box-sortables ui-sortable publishing-defaults">  
							<?php
							// Load sub view.
							require_once $this->base->plugin->folder . 'lib/social/views/settings-' . $tab . '.php';
							?>
						</div>
						<!-- /normal-sortables -->

						<?php
						if ( ! $disable_save_button ) {
							?>
							<!-- Save -->
							<div>
								<?php wp_nonce_field( $this->base->plugin->name, $this->base->plugin->name . '_nonce' ); ?>
								<input type="submit" name="submit" value="<?php esc_attr_e( 'Save', 'wpzinc-social' ); ?>" class="button button-primary" />
							</div>
							<?php
						}
						?>
					</div>
					<!-- /post-body-content -->
				</div>
			</div> 
		</form>
		<!-- /form end -->		
	</div><!-- ./wrap-inner -->         
</div>
