<?php
/**
 * Outputs Bulk Publish View, Stage 2, for a Post Type
 *
 * @since 3.0.5
 *
 * @package WP_To_Social_Pro
 * @author  WP Zinc
 */

?>
<div class="postbox">
	<h3 class="hndle">
		<?php esc_html_e( 'Publishing...', 'wp-to-social-pro' ); ?>
	</h3>

	<div class="wpzinc-option progressbar">
		<div class="full">
			<p>
				<?php
				echo esc_html(
					sprintf(
					/* translators: %1$s: Social Media Service Name (Buffer, Hootsuite, SocialPilot), %2$s: Social Media Service Name (Buffer, Hootsuite, SocialPilot) */
						__( 'Please be patient while Post(s) are published to %1$s. This can take a while if your server is slow (inexpensive hosting), or if you have chosen a lot of Posts and Statuses. Do not navigate away from this page until this script is done or all statuses will not be sent to %2$s. You will be notified via this page when the process is completed.', 'wp-to-social-pro' ),
						$this->base->plugin->account,
						$this->base->plugin->account
					)
				);
				?>
			</p>

			<!-- Progress Bar -->
			<div id="progress-bar"></div>
		</div>
	</div>

	<!-- Log -->	
	<div id="<?php echo esc_attr( $this->base->plugin->name ); ?>-log">
		<div class="inside">
			<div class="wpzinc-option">
				<div class="full">
					<table id="log" class="widefat wp-to-social-log">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Request Sent', 'wp-to-social-pro' ); ?></th>
								<th><?php esc_html_e( 'Action', 'wp-to-social-pro' ); ?></th>
								<th><?php esc_html_e( 'Profile', 'wp-to-social-pro' ); ?></th>
								<th><?php esc_html_e( 'Status Text', 'wp-to-social-pro' ); ?></th>
								<th><?php esc_html_e( 'Result', 'wp-to-social-pro' ); ?></th>
								<th><?php esc_html_e( 'Response', 'wp-to-social-pro' ); ?></th>
								<th>
									<?php
									echo esc_html(
										sprintf(
										/* translators: Social Media Service Name (Buffer, Hootsuite, SocialPilot) */
											__( '%s: Status Created At', 'wp-to-social-pro' ),
											$this->base->plugin->account
										)
									);
									?>
								</th>
								<th>
									<?php
									echo esc_html(
										sprintf(
											/* translators: Social Media Service Name (Buffer, Hootsuite, SocialPilot) */
											__( '%s: Status Scheduled For', 'wp-to-social-pro' ),
											$this->base->plugin->account
										)
									);
									?>
								</th>
							</tr>
						</thead>
						<tbody></tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>
