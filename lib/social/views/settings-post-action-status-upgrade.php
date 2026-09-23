<?php
/**
 * Outputs an upgrade notice below the table of statuses for an action (publish,update,repost,bulk publish),
 * when the Free version of the Plugin is used.
 *
 * @package WPZinc\Social\Pro
 * @author  WP Zinc
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div class="wpzinc-option highlight">
	<div class="full">
		<h4>
			<?php
			echo esc_html(
				sprintf(
				/* translators: Social Media Scheduler Name */
					__( 'Want to Publish multiple Status Updates to %s Pro?', 'wpzinc-social' ),
					$this->base->plugin->account
				)
			);
			?>
		</h4>

		<p>
			<?php
			echo esc_html(
				sprintf(
				/* translators: Plugin Name */
					__( 'Define additional unique statuses, each with publishing conditions and custom scheduling, per social network with %s.', 'wpzinc-social' ),
					$this->base->plugin->displayName
				)
			);
			?>
		</p>

		<a href="<?php echo esc_attr( $this->base->dashboard->get_upgrade_url( 'settings_inline_upgrade' ) ); ?>" class="button button-primary" target="_blank"><?php esc_html_e( 'Upgrade', 'wpzinc-social' ); ?></a>
	</div>
</div>
