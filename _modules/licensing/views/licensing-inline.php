<?php
/**
 * Outputs the licensing screen form.
 *
 * @package LicensingUpdateManager
 * @author WP Zinc
 */

?>
<div id="post-body" class="metabox-holder columns-2">
	<!-- Content -->
	<div id="post-body-content">

		<!-- Form Start -->
		<form name="post" method="post" action="<?php echo esc_attr( $_SERVER['REQUEST_URI'] ); ?>">
			<div id="normal-sortables" class="meta-box-sortables ui-sortable">                        
				<div class="postbox">
					<h3 class="hndle"><?php esc_html_e( 'License Key', $this->base->plugin->name ); // phpcs:ignore WordPress.WP.I18n ?></h3>

					<?php
					// If the license key is defined in wp-config as a constant, just display it here and don't offer the option to edit.
					if ( $this->base->licensing->is_license_key_a_constant() ) {
						?>
						<div class="wpzinc-option">
							<div class="full">
								<input type="password" name="ignored" value="****************************************" class="widefat" disabled="disabled" />
							</div>
						</div>
						<?php
					} else {
						// Get from options table.
						$license_key = get_option( $this->base->licensing->plugin->name . '_licenseKey' );
						$input_type  = ( $this->base->licensing->check_license_key_valid( false ) ? 'password' : 'text' );
						?>
						<div class="wpzinc-option">
							<div class="full">
								<input type="<?php echo esc_attr( $input_type ); ?>" name="<?php echo esc_attr( $this->base->licensing->plugin->name ); ?>[licenseKey]" value="<?php echo esc_attr( $license_key ); ?>" class="widefat" />
							</div>
						</div>
						<div class="wpzinc-option">
							<input type="submit" name="submit" value="<?php esc_attr_e( 'Save' ); // phpcs:ignore WordPress.WP.I18n ?>" class="button button-primary" /> 
						</div>
						<?php
					}
					?>
				</div>
				<!-- /postbox -->
			</div>
			<!-- /normal-sortables -->
		</form>
		<!-- /form end -->

	</div>
	<!-- /post-body-content -->

	<!-- Sidebar -->
	<div id="postbox-container-1" class="postbox-container">
		<!-- About -->
		<div class="postbox">
			<h3 class="hndle"><?php esc_html_e( 'Version' ); // phpcs:ignore WordPress.WP.I18n ?></h3>

			<div class="wpzinc-option">
				<?php echo esc_html( $this->base->licensing->plugin->version ); ?>
			</div>
		</div>

		<!-- Support -->
		<div class="postbox">
			<h3 class="hndle"><span><?php esc_html_e( 'Help' ); // phpcs:ignore WordPress.WP.I18n ?></span></h3>

			<div class="wpzinc-option">
				<a href="<?php echo esc_attr( isset( $this->base->licensing->plugin->documentation_url ) ? $this->base->licensing->plugin->documentation_url : '#' ); ?>" class="button" rel="noopener" target="_blank">
					<?php esc_html_e( 'Documentation' ); // phpcs:ignore WordPress.WP.I18n ?>
				</a>
				<a href="<?php echo esc_attr( isset( $this->base->licensing->plugin->support_url ) ? $this->base->licensing->plugin->support_url : '#' ); ?>" class="button button-secondary" rel="noopener" target="_blank">
					<?php esc_html_e( 'Help' ); // phpcs:ignore WordPress.WP.I18n ?>
				</a>
			</div>
		</div>
	</div>
	<!-- /postbox-container -->
</div>
