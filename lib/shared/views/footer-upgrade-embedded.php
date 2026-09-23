<?php
/**
 * Outputs the upgrade reasons to upgrade to a Pro product.
 *
 * @package WPZinc\Shared
 * @author WP Zinc
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( isset( $this->base->plugin->upgrade_reasons ) && is_array( $this->base->plugin->upgrade_reasons ) && count( $this->base->plugin->upgrade_reasons ) > 0 ) {
	foreach ( $this->base->plugin->upgrade_reasons as $reasons ) {
		?>
		<div class="wpzinc-option ignore-nth-child">
			<strong><?php echo esc_html( $reasons[0] ); ?>:</strong> <?php echo esc_html( $reasons[1] ); ?>
		</div>
		<?php
	}
	?>

	<div class="wpzinc-option ignore-nth-child">
		<strong><?php esc_html_e( 'Support', 'wpzinc-social' ); ?>:</strong> <?php esc_html_e( 'Access to one on one email support', 'wpzinc-social' ); ?>
	</div>

	<div class="wpzinc-option ignore-nth-child">
		<strong><?php esc_html_e( 'Documentation', 'wpzinc-social' ); ?>:</strong> <?php esc_html_e( 'Detailed documentation on how to install and configure the plugin', 'wpzinc-social' ); ?>
	</div>

	<div class="wpzinc-option ignore-nth-child">
		<strong><?php esc_html_e( 'Updates', 'wpzinc-social' ); ?>:</strong> <?php esc_html_e( 'Receive one click update notifications, right within your WordPress Adminstration panel', 'wpzinc-social' ); ?>
	</div>

	<div class="wpzinc-option ignore-nth-child">
		<strong><?php esc_html_e( 'Seamless Upgrade', 'wpzinc-social' ); ?>:</strong> <?php esc_html_e( 'Retain all current settings when upgrading to Pro', 'wpzinc-social' ); ?>
	</div>

	<div class="wpzinc-option">
		<a href="<?php echo esc_url( $this->base->dashboard->get_upgrade_url( 'settings_footer_upgrade' ) ); ?>" class="button button-primary" rel="noopener" target="_blank">
			<?php esc_html_e( 'Upgrade Now', 'wpzinc-social' ); ?>
		</a>
	</div>
	<?php
}
