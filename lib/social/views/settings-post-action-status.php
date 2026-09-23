<?php
/**
 * Outputs the single status configuration form.  Its values are populated by statuses.js, based
 * on the status that has been selected for editing.
 *
 * @package WPZinc\Social\Pro
 * @author  WP Zinc
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div id="<?php echo esc_attr( $this->base->plugin->name ); ?>-status-form-container" class="hidden">
	<div id="<?php echo esc_attr( $this->base->plugin->name ); ?>-status-form" class="wpzinc-social-status-form">
		<div class="notice-inline notice-warning pinterest hidden full">
			<p>
				<?php
				esc_html_e( 'You need to create at least one Pinterest Board, and then refresh the screen to choose the board to post this status to.', 'wpzinc-social' );
				?>
				<a href="<?php echo esc_attr( $this->base->plugin->documentation_url ); ?>/status-settings/#buffer-pinterest" target="_blank">
					<?php echo esc_html_e( 'Click here for instructions on creating a Pinterest board.', 'wpzinc-social' ); ?>
				</a>
			</p>
		</div>

		<!-- Status Type and Text -->
		<div class="wpzinc-option status">
			<div class="full">
				<h3><?php esc_html_e( 'Status Type and Text', 'wpzinc-social' ); ?></h3>
				<p class="description">
					<?php esc_html_e( 'The type of status to create and its text.', 'wpzinc-social' ); ?>
				</p>

				<select name="<?php echo esc_attr( $this->base->plugin->name ); ?>_post_type" class="post_type" size="1">
					<?php
					foreach ( $this->base->get_class( 'common' )->get_status_post_type_options() as $status_post_type_key => $status_post_type_label ) {
						?>
						<option value="<?php echo esc_attr( $status_post_type_key ); ?>">
							<?php echo esc_attr( $status_post_type_label ); ?>
						</option>
						<?php
					}
					?>
				</select>

				<?php
				// Tags.
				$textarea = 'textarea.message';
				require 'settings-post-action-status-tags.php';
				?>

				<textarea name="<?php echo esc_attr( $this->base->plugin->name ); ?>_message" rows="3" class="widefat wpzinc-autosize-js message"></textarea>

				<?php
				// If we're editing a Post, Page or CPT, show the chararcter count.
				if ( isset( $post ) && ! empty( $post ) ) {
					?>
					<small class="characters">
						<span class="character-count"></span>
						<?php esc_html_e( 'characters', 'wpzinc-social' ); ?>
					</small>
					<?php
				}
				?>
			</div>
		</div>

		<?php
		if ( $this->base->supports( 'first_comment' ) ) {
			?>
			<!-- First Comment -->
			<div class="wpzinc-option status">
				<div class="full">
					<h3><?php esc_html_e( 'First Comment', 'wpzinc-social' ); ?></h3>
					<p class="description">
						<?php esc_html_e( 'Optional: Define the first comment to include below the status. Not all networks support this feature.', 'wpzinc-social' ); ?>
					</p>

					<?php
					// Tags.
					$textarea = 'textarea.first_comment';
					require 'settings-post-action-status-tags.php';
					?>
					<textarea name="<?php echo esc_attr( $this->base->plugin->name ); ?>_first_comment" rows="3" class="widefat wpzinc-autosize-js first_comment"></textarea>
				</div>
			</div>
			<?php
		}
		?>

		<!-- Schedule -->
		<div class="wpzinc-option status">
			<div class="full">
				<h3><?php esc_html_e( 'Schedule', 'wpzinc-social' ); ?></h3>
				<p class="description">
					<?php esc_html_e( 'When the status should be added to social media.', 'wpzinc-social' ); ?>
				</p>

				<select name="<?php echo esc_attr( $this->base->plugin->name ); ?>_schedule" size="1" class="schedule widefat">
					<?php
					foreach ( $this->base->get_class( 'common' )->get_schedule_options( $post_type, $is_post_screen ) as $schedule_option => $label ) {
						?>
						<option value="<?php echo esc_attr( $schedule_option ); ?>"><?php echo esc_attr( $label ); ?></option>
						<?php
					}
					?>
				</select> 

				<div class="schedule">
					<span class="hours_mins_secs">
						<!-- Days, Hours, Minutes -->
						<input type="number" name="<?php echo esc_attr( $this->base->plugin->name ); ?>_days" id="days" min="0" max="9999" step="1" value="" />
						<label for="<?php echo esc_attr( $profile_id ); ?>_status_<?php echo esc_attr( $key ); ?>_days"><?php esc_html_e( 'Days, ', 'wpzinc-social' ); ?></label>

						<input type="number" name="<?php echo esc_attr( $this->base->plugin->name ); ?>_hours" id="hours" />
						<label for="<?php echo esc_attr( $profile_id ); ?>_status_<?php echo esc_attr( $key ); ?>_hours"><?php esc_html_e( 'Hours, ', 'wpzinc-social' ); ?></label>

						<input type="number" name="<?php echo esc_attr( $this->base->plugin->name ); ?>_minutes" id="minutes" />
						<label for="<?php echo esc_attr( $profile_id ); ?>_status_<?php echo esc_attr( $key ); ?>_minutes"><?php esc_html_e( 'Minutes', 'wpzinc-social' ); ?></label>
					</span>

					<span class="relative">
						<select name="<?php echo esc_attr( $this->base->plugin->name ); ?>_schedule_relative_day" id="schedule_relative_day" size="1">
							<?php
							foreach ( $this->base->get_class( 'common' )->get_schedule_relative_days() as $day => $label ) {
								?>
								<option value="<?php echo esc_attr( $day ); ?>"><?php echo esc_attr( $label ); ?></option>
								<?php
							}
							?>
						</select>

						<?php esc_html_e( 'at', 'wpzinc-social' ); ?>

						<input type="time" name="<?php echo esc_attr( $this->base->plugin->name ); ?>_schedule_relative_time" id="schedule_relative_time" />
					</span>

					<span class="custom"></span>

					<span class="custom_field">
						<select name="<?php echo esc_attr( $this->base->plugin->name ); ?>_schedule_custom_field_relation" size="1">
							<?php
							foreach ( $this->base->get_class( 'common' )->get_schedule_custom_relation_options() as $schedule_option => $label ) {
								?>
								<option value="<?php echo esc_attr( $schedule_option ); ?>"><?php echo esc_attr( $label ); ?></option>
								<?php
							}
							?>
						</select> 
						<input type="text" name="<?php echo esc_attr( $this->base->plugin->name ); ?>_schedule_custom_field_name" placeholder="<?php esc_attr_e( 'Custom Meta Field Name', 'wpzinc-social' ); ?>" />
					</span>

					<?php
					/**
					 * Output Schedule settings for Integrations / Third Party Plugins
					 *
					 * @since   4.4.0
					 *
					 * @param   string  $post_type  Post Type
					 */
					do_action( $this->base->plugin->filter_name . '_output_schedule_options_form_fields', $post_type );
					?>

					<span class="specific">
						<input type="datetime-local" name="<?php echo esc_attr( $this->base->plugin->name ); ?>_schedule_specific" class="widefat" placeholder="<?php esc_attr_e( 'Date and Time', 'wpzinc-social' ); ?>" />   
					</span>
				</div>
			</div>
		</div>

		<!-- Link -->
		<div class="wpzinc-option link">
			<div class="full">
				<h3><?php esc_html_e( 'Link', 'wpzinc-social' ); ?></h3>
				<p class="description">
					<?php esc_html_e( 'The "primary" URL to use for the link preview / card. Additional links can be included in the status text above.', 'wpzinc-social' ); ?>
				</p>

				<input type="text" name="<?php echo esc_attr( $this->base->plugin->name ); ?>_url" class="widefat url" />
			</div>
		</div>

<?php
		// Video, where supported by the Plugin.
if ( $this->base->supports( 'video' ) ) {
	?>
			<div class="wpzinc-option video">
				<div class="full">
					<h3><?php esc_html_e( 'Video', 'wpzinc-social' ); ?></h3>
					<p class="description">
				<?php esc_html_e( 'The video to use. Choose the video defined in this Post\'s Media section, or specify a URL.', 'wpzinc-social' ); ?>
					</p>

					<table class="widefat fixed striped">
						<tbody>
							<tr>
								<td width="20%">
									<label for="<?php echo esc_attr( $this->base->plugin->name ); ?>_video">
								<?php esc_html_e( 'Video', 'wpzinc-social' ); ?>
									</label>
								</td>
								<td>
									<select id="<?php echo esc_attr( $this->base->plugin->name ); ?>_video" name="<?php echo esc_attr( $this->base->plugin->name ); ?>_video" size="1" class="video" data-conditional="<?php echo esc_attr( $this->base->plugin->name ); ?>_video_url_row" data-conditional-value="url">
								<?php
								foreach ( $this->base->get_class( 'image' )->get_status_video_options() as $video_option_value => $video_option_label ) {
									?>
											<option value="<?php echo esc_attr( $video_option_value ); ?>"><?php echo esc_attr( $video_option_label ); ?></option>
											<?php
								}
								?>
									</select>
								</td>
							</tr>
							<tr id="<?php echo esc_attr( $this->base->plugin->name ); ?>_video_url_row" class="video-url">
								<td width="20%">
									<label for="<?php echo esc_attr( $this->base->plugin->name ); ?>_video_url">
										<?php esc_html_e( 'Video URL', 'wpzinc-social' ); ?>
									</label>
								</td>
								<td>
									<input type="text" id="<?php echo esc_attr( $this->base->plugin->name ); ?>_video_url" name="<?php echo esc_attr( $this->base->plugin->name ); ?>_video_url" class="widefat video_url" />
									<p class="description">
										<?php esc_html_e( 'A direct video file URL, or a tag (such as a custom field) that outputs one.', 'wpzinc-social' ); ?>
									</p>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
			<?php
}
?>

		
		<!-- Pinterest -->
		<div class="wpzinc-option pinterest hidden">
			<div class="full">
				<h3><?php esc_html_e( 'Pinterest', 'wpzinc-social' ); ?></h3>
				<p class="description">
					<?php
					esc_html_e( 'Define the Pinterest Board for this status to be sent to.', 'wpzinc-social' );
					?>
				</p>

				<table class="widefat fixed striped">
					<tbody>
						<tr>
							<td width="20%">
								<label for="<?php echo esc_attr( $this->base->plugin->name ); ?>_pinterest_board">
									<?php esc_html_e( 'Board', 'wpzinc-social' ); ?>
								</label>
							</td>
							<td>
								<!-- Pinterest: Sub Profile -->
								<select name="<?php echo esc_attr( $this->base->plugin->name ); ?>_pinterest[board]" id="<?php echo esc_attr( $this->base->plugin->name ); ?>_pinterest_board" size="1" class="widefat"></select> 
								<input type="url" name="<?php echo esc_attr( $this->base->plugin->name ); ?>_pinterest[board]" id="<?php echo esc_attr( $this->base->plugin->name ); ?>_pinterest_board" placeholder="<?php esc_attr_e( 'Pinterest Board URL', 'wpzinc-social' ); ?>" class="widefat" />
							</td>
						</tr>

						<?php
						if ( $this->base->supports( 'pinterest_title' ) ) {
							?>
							<tr>
								<td>
									<label for="pinterest_title">
										<?php esc_html_e( 'Pin Title', 'wpzinc-social' ); ?>
									</label>
								</td>
								<td>
									<input type="text" name="<?php echo esc_attr( $this->base->plugin->name ); ?>_pinterest[title]" id="pinterest_title" placeholder="<?php esc_attr_e( 'Pin Title', 'wpzinc-social' ); ?>" class="widefat" />
									<p class="description">
										<?php esc_html_e( 'An optional title. Text Tags are supported.', 'wpzinc-social' ); ?>
									</p>
								</td>
							</tr>
							<?php
						}
						?>
					</tbody>
				</table>
			</div>
		</div>

		<?php
		if ( $this->base->supports( 'googlebusiness' ) ) {
			?>
			<!-- Google Business Profile -->
			<div class="wpzinc-option googlebusiness hidden">
				<div class="full">
					<h3><?php esc_html_e( 'Google Business Profile', 'wpzinc-social' ); ?></h3>
					<p class="description">
						<?php
						echo esc_html_e( 'Optional: Define the status type (What\'s New, Offer or Event) and additional structured fields / data.', 'wpzinc-social' );
						?>
					</p>

					<table class="widefat fixed striped">
						<tbody>
							<tr>
								<td width="20%">
									<label for="googlebusiness_post_type">
										<?php esc_html_e( 'Post Type', 'wpzinc-social' ); ?>
									</label>
								</td>
								<td>
									<select name="<?php echo esc_attr( $this->base->plugin->name ); ?>_googlebusiness[post_type]" id="googlebusiness_post_type" size="1" class="widefat">
										<option value="whats_new"><?php esc_attr_e( 'What\'s New', 'wpzinc-social' ); ?></option>
										<option value="offer"><?php esc_attr_e( 'Offer', 'wpzinc-social' ); ?></option>
										<option value="event"><?php esc_attr_e( 'Event', 'wpzinc-social' ); ?></option>
									</select>
								</td>
							</tr>
							<tr class="whats_new event">
								<td>
									<label for="googlebusiness_cta">
										<?php esc_html_e( 'Call to Action', 'wpzinc-social' ); ?>
									</label>
								</td>
								<td>
									<select name="<?php echo esc_attr( $this->base->plugin->name ); ?>_googlebusiness[cta]" id="googlebusiness_cta" size="1" class="widefat">
										<option value="book"><?php esc_attr_e( 'Book', 'wpzinc-social' ); ?></option>
										<option value="order"><?php esc_attr_e( 'Order', 'wpzinc-social' ); ?></option>
										<option value="shop"><?php esc_attr_e( 'Shop', 'wpzinc-social' ); ?></option>
										<option value="learn_more"><?php esc_attr_e( 'Learn More', 'wpzinc-social' ); ?></option>
										<option value="signup"><?php esc_attr_e( 'Sign Up', 'wpzinc-social' ); ?></option>
									</select>
								</td>
							</tr>
							<tr class="offer event">
								<td>
									<label for="googlebusiness_start_date_option">
										<?php esc_html_e( 'Start Date', 'wpzinc-social' ); ?>
									</label>
								</td>
								<td>
									<select name="<?php echo esc_attr( $this->base->plugin->name ); ?>_googlebusiness[start_date_option]" id="googlebusiness_start_date_option" size="1" class="widefat">
										<?php
										foreach ( $this->base->get_class( 'common' )->get_google_business_start_date_options( $post_type ) as $schedule_option => $label ) {
											?>
											<option value="<?php echo esc_attr( $schedule_option ); ?>"><?php echo esc_attr( $label ); ?></option>
											<?php
										}
										?>
									</select>

									<input type="text" name="<?php echo esc_attr( $this->base->plugin->name ); ?>_googlebusiness[start_date]" id="googlebusiness_start_date" placeholder="<?php esc_attr_e( 'Custom Meta Field Name', 'wpzinc-social' ); ?>" />
								</td>
							</tr>
							<tr class="offer event">
								<td>
									<label for="googlebusiness_end_date_option">
										<?php esc_html_e( 'End Date', 'wpzinc-social' ); ?>
									</label>
								</td>
								<td>
									<select name="<?php echo esc_attr( $this->base->plugin->name ); ?>_googlebusiness[end_date_option]" id="googlebusiness_end_date_option" size="1" class="widefat">
										<?php
										foreach ( $this->base->get_class( 'common' )->get_google_business_end_date_options( $post_type ) as $schedule_option => $label ) {
											?>
											<option value="<?php echo esc_attr( $schedule_option ); ?>"><?php echo esc_attr( $label ); ?></option>
											<?php
										}
										?>
									</select>

									<input type="text" name="<?php echo esc_attr( $this->base->plugin->name ); ?>_googlebusiness[end_date]" id="googlebusiness_end_date" placeholder="<?php esc_attr_e( 'Custom Meta Field Name', 'wpzinc-social' ); ?>" />
								</td>
							</tr>
							<tr class="offer event">
								<td>
									<label for="googlebusiness_title">
										<?php esc_html_e( 'Event / Offer Title', 'wpzinc-social' ); ?>
									</label>
								</td>
								<td>
									<input type="text" name="<?php echo esc_attr( $this->base->plugin->name ); ?>_googlebusiness[title]" id="googlebusiness_title" class="widefat" />
								</td>
							</tr>
							<tr class="offer">
								<td>
									<label for="googlebusiness_code">
										<?php esc_html_e( 'Coupon Code', 'wpzinc-social' ); ?>
									</label>
								</td>
								<td>
									<input type="text" name="<?php echo esc_attr( $this->base->plugin->name ); ?>_googlebusiness[code]" id="googlebusiness_code" class="widefat" />
								</td>
							</tr>
							<tr class="offer">
								<td>
									<label for="googlebusiness_terms">
										<?php esc_html_e( 'Terms and Conditions Text', 'wpzinc-social' ); ?>
									</label>
								</td>
								<td>
									<input type="text" name="<?php echo esc_attr( $this->base->plugin->name ); ?>_googlebusiness[terms]" id="googlebusiness_terms" class="widefat" />
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
			<?php
		}
		?>

		<!-- Images -->
		<div class="wpzinc-option images">
			<div class="full">
				<h3><?php esc_html_e( 'Image', 'wpzinc-social' ); ?></h3>
				<p class="description">
					<?php esc_html_e( 'The type of image(s) to use.', 'wpzinc-social' ); ?>
				</p>


				<table class="widefat fixed striped">
					<tbody>
						<tr>
							<td width="20%">
								<label for="<?php echo esc_attr( $this->base->plugin->name ); ?>_image">
									<?php esc_html_e( 'Image', 'wpzinc-social' ); ?>
								</label>
							</td>
							<td>
								<select id="<?php echo esc_attr( $this->base->plugin->name ); ?>_image" name="<?php echo esc_attr( $this->base->plugin->name ); ?>_image" size="1" class="image">
									<?php
									foreach ( $this->base->get_class( 'image' )->get_status_image_options( false, $post_type ) as $value => $image_option ) {
										?>
										<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_attr( $image_option['label'] ); ?></option>
										<?php
									}
									?>
								</select>
							</td>
						</tr>
						<tr class="additional-images" data-conditional-value="<?php echo esc_attr( implode( ',', array_keys( $this->base->get_class( 'image' )->get_status_image_options_supporting_additional_images( false, $post_type ) ) ) ); ?>">
							<td width="20%">
								<label for="<?php echo esc_attr( $this->base->plugin->name ); ?>_image_additional">
									<?php esc_html_e( 'Additional Images', 'wpzinc-social' ); ?>
								</label>
							</td>
							<td>
								<select id="<?php echo esc_attr( $this->base->plugin->name ); ?>_image_additional" name="<?php echo esc_attr( $this->base->plugin->name ); ?>_image_additional" size="1">
									<?php
									foreach ( $this->base->get_class( 'image' )->get_status_additional_image_options( false, $post_type ) as $value => $label ) {
										?>
										<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_attr( $label ); ?></option>
										<?php
									}
									?>
								</select>
								<p class="description">
									<?php
									printf(
										'<code>%s</code>: %s',
										esc_html__( 'Specified in Post settings', 'wpzinc-social' ),
										esc_html__( 'Include additional images in the status if specified in the "Featured and Additional Images" settings on the individual Post.', 'wpzinc-social' )
									);
									?>
									<br />
									<?php
									printf(
										'<code>%s</code>: %s',
										esc_html__( 'Auto populate from Post content', 'wpzinc-social' ),
										esc_html__( 'Include additional images in the status from the Post\'s content.', 'wpzinc-social' )
									);
									?>
								</p>
							</td>
						</tr>
						<tr class="additional-images" data-conditional-value="<?php echo esc_attr( implode( ',', array_keys( $this->base->get_class( 'image' )->get_status_image_options_supporting_additional_images( false, $post_type ) ) ) ); ?>">
							<td width="20%">
								<label for="<?php echo esc_attr( $this->base->plugin->name ); ?>_image_additional_limit">
									<?php esc_html_e( 'Limit', 'wpzinc-social' ); ?>
								</label>
							</td>
							<td>
								<input type="number" id="<?php echo esc_attr( $this->base->plugin->name ); ?>_image_additional_limit" name="<?php echo esc_attr( $this->base->plugin->name ); ?>_image_additional_limit" min="1" max="9 step="1" />
								<p class="description">
									<?php
									esc_html_e( 'The maximum number of images to include with this status. If this exceeds the social media platform\'s limit, the extra images will be ignored.', 'wpzinc-social' );
									?>
								</p>
							</td>
						</tr>
						<tr class="text-to-image" data-conditional-value="<?php echo esc_attr( implode( ',', array_keys( $this->base->get_class( 'image' )->get_status_image_options_supporting_text_to_image( false, $post_type ) ) ) ); ?>">
							<td width="20%">
								<label for="<?php echo esc_attr( $this->base->plugin->name ); ?>_text_to_image">
									<?php esc_html_e( 'Text to Image', 'wpzinc-social' ); ?>
								</label>
							</td>
							<td>
								<?php
								$textarea = 'textarea.text-to-image';
								require 'settings-post-action-status-tags.php';
								?>
							
								<textarea id="<?php echo esc_attr( $this->base->plugin->name ); ?>_text_to_image" name="<?php echo esc_attr( $this->base->plugin->name ); ?>_text_to_image" rows="3" class="widefat wpzinc-autosize-js text-to-image"></textarea>
								<p class="description">
									<?php
									esc_html_e( 'Define the text to convert to an image, which will be sent with this status.', 'wpzinc-social' );
									?>
								</p>
							</td>
						</tr>
						<tr class="text-to-image" data-conditional-value="<?php echo esc_attr( implode( ',', array_keys( $this->base->get_class( 'image' )->get_status_image_options_supporting_text_to_image( false, $post_type ) ) ) ); ?>">
							<td width="20%">
								<label for="<?php echo esc_attr( $this->base->plugin->name ); ?>_text_to_image_background_image">
									<?php esc_html_e( 'Background Image', 'wpzinc-social' ); ?>
								</label>
							</td>
							<td>
								<select id="<?php echo esc_attr( $this->base->plugin->name ); ?>_text_to_image_background_image" name="<?php echo esc_attr( $this->base->plugin->name ); ?>_text_to_image_type" size="1" data-conditional="text_to_image_background_image" data-conditional-value="background">
									<option value=""><?php esc_attr_e( 'Use Plugin Settings', 'wpzinc-social' ); ?></option>
									<option value="featured"><?php esc_attr_e( 'Use Post\'s Featured Image', 'wpzinc-social' ); ?></option>
									<option value="background"><?php esc_attr_e( 'Use Background Image', 'wpzinc-social' ); ?></option>
								</select>

								<div id="text_to_image_background_image" 
									class="full wpzinc-media-library-selector"
									data-input-name="text_to_image_background_image"
									data-input-url="text_to_image_background_image_url"
									data-file-type="image"
									data-output-size="small">
									<ul class="images">
										<li class="wpzinc-media-library-attachment">
											<div class="wpzinc-media-library-insert">
												<input type="hidden" name="text_to_image_background_image" value="" />
												<input type="hidden" name="text_to_image_background_image_url" value="" />
												<img src="" />
											</div>
											<a href="#" class="wpzinc-media-library-remove" title="<?php esc_attr_e( 'Remove Background Image', 'wpzinc-social' ); ?>"><?php esc_html_e( 'Remove', 'wpzinc-social' ); ?></a>
										</li>
									</ul>

									<button class="wpzinc-media-library-insert button button-secondary">
										<?php esc_html_e( 'Select Background Image', 'wpzinc-social' ); ?>
									</button>
								</div>

								<p class="description">
									<?php
									esc_html_e( 'Use Plugin Settings: Use the background image settings specified in Settings > Text to Image.', 'wpzinc-social' );
									?>
									<br />
									<?php
									esc_html_e( 'Use Post\'s Featured Image: Use the Post\'s Featured Image as the background image.', 'wpzinc-social' );
									?>
									<br />
									<?php
									esc_html_e( 'Use Background Image: Specify a background image below.', 'wpzinc-social' );
									?>
								</p>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>

		<!-- Post Conditions -->
		<div class="wpzinc-option post-conditions">
			<div class="full">
				<h3><?php esc_html_e( 'Post Conditions', 'wpzinc-social' ); ?></h3>
				<p class="description">
					<?php
					echo esc_html(
						sprintf(
							/* translators: Social Media Scheduler Name */
							__( 'Optional: Define Post conditions that are required for this status to be sent to %s. All conditions must be met.', 'wpzinc-social' ),
							$this->base->plugin->account
						)
					);
					?>
				</p>

				<table class="widefat fixed striped">
					<thead>
						<tr>
							<th width="20%"><?php esc_html_e( 'Attribute', 'wpzinc-social' ); ?></th>
							<th><?php esc_html_e( 'Compare', 'wpzinc-social' ); ?></th>
							<th><?php esc_html_e( 'Value', 'wpzinc-social' ); ?></th>
							<th class="actions"><?php esc_html_e( 'Actions', 'wpzinc-social' ); ?></th>
						</tr>
					</thead>

					<tfoot>
						<tr>
							<th colspan="4">
								<a href="#" class="button wpzinc-add-table-row" data-table-row-selector="custom-field">
									<?php esc_html_e( 'Add Meta / Custom Field Condition', 'wpzinc-social' ); ?>
								</a>
							</th>
						</tr>
					</tfoot>

					<tbody>
						<tr>
							<td width="20%">
								<label for="post_title_compare" data-for="post_title_compare_index">
									<?php esc_html_e( 'Title', 'wpzinc-social' ); ?>
								</label>
							</td>
							<td>
								<select name="<?php echo esc_attr( $this->base->plugin->name ); ?>_post_title[compare]" id="post_title_compare" data-id="post_title_compare_index" size="1" class="widefat">
									<option value="0"><?php esc_attr_e( 'No Conditions', 'wpzinc-social' ); ?></option>
									<?php
									foreach ( $this->base->get_class( 'common' )->get_comparison_operators() as $comparison_key => $label ) {
										?>
										<option value="<?php echo esc_attr( $comparison_key ); ?>"><?php echo esc_attr( $label ); ?></option>
										<?php
									}
									?>
								</select>
							</td>
							<td>
								<input type="text" name="<?php echo esc_attr( $this->base->plugin->name ); ?>_post_title[value]" class="widefat" />    
							</td>
							<td class="actions">&nbsp;</td>
						</tr>

						<tr>
							<td>
								<label for="post_excerpt_compare" data-for="post_excerpt_compare_index">
									<?php esc_html_e( 'Excerpt', 'wpzinc-social' ); ?>
								</label>
							</td>
							<td>
								<select name="<?php echo esc_attr( $this->base->plugin->name ); ?>_post_excerpt[compare]" id="post_excerpt_compare" data-id="post_excerpt_compare_index" size="1" class="widefat">
									<option value="0"><?php esc_html_e( 'No Conditions', 'wpzinc-social' ); ?></option>
									<?php
									foreach ( $this->base->get_class( 'common' )->get_custom_field_comparison_operators() as $comparison_key => $label ) {
										?>
										<option value="<?php echo esc_attr( $comparison_key ); ?>"><?php echo esc_attr( $label ); ?></option>
										<?php
									}
									?>
								</select>
							</td>
							<td>
								<input type="text" name="<?php echo esc_attr( $this->base->plugin->name ); ?>_post_excerpt[value]" class="widefat" />    
							</td>
							<td class="actions">&nbsp;</td>
						</tr>

						<tr>
							<td>
								<label for="post_content_compare" data-for="post_content_compare_index">
									<?php esc_html_e( 'Content', 'wpzinc-social' ); ?>
								</label>
							</td>
							<td>
								<select name="<?php echo esc_attr( $this->base->plugin->name ); ?>_post_content[compare]" id="post_content_compare" data-id="post_content_compare_index" size="1" class="widefat">
									<option value="0"><?php esc_html_e( 'No Conditions', 'wpzinc-social' ); ?></option>
									<?php
									foreach ( $this->base->get_class( 'common' )->get_custom_field_comparison_operators() as $comparison_key => $label ) {
										?>
										<option value="<?php echo esc_attr( $comparison_key ); ?>"><?php echo esc_attr( $label ); ?></option>
										<?php
									}
									?>
								</select>
							</td>
							<td>
								<input type="text" name="<?php echo esc_attr( $this->base->plugin->name ); ?>_post_content[value]" class="widefat" />    
							</td>
							<td class="actions">&nbsp;</td>
						</tr>

						<tr>
							<td>
								<label for="start_date_compare" data-for="start_date_compare_index">
									<?php esc_html_e( 'Start Date', 'wpzinc-social' ); ?>
								</label>
							</td>
							<td>
								<select name="<?php echo esc_attr( $this->base->plugin->name ); ?>_start_date[month]" id="start_date_compare" data-id="start_date_compare_index" size="1" class="widefat">
									<option value=""><?php esc_html_e( 'Any Month', 'wpzinc-social' ); ?></option>
									<?php
									for ( $month = 1; $month <= 12; $month++ ) {
										?>
										<option value="<?php echo esc_attr( $month ); ?>"><?php echo esc_attr( DateTime::createFromFormat( '!m', $month )->format( 'F' ) ); ?></option>
										<?php
									}
									?>
								</select>
							</td>
							<td>
								<input type="number" name="<?php echo esc_attr( $this->base->plugin->name ); ?>_start_date[day]" placeholder="<?php esc_attr_e( 'e.g. 1', 'wpzinc-social' ); ?>" class="widefat" />    
							</td>
							<td>&nbsp;</td>
						</tr>

						<tr>
							<td>
								<label for="end_date_compare" data-for="end_date_compare_index">
									<?php esc_html_e( 'End Date', 'wpzinc-social' ); ?>
								</label>
							</td>
							<td>
								<select name="<?php echo esc_attr( $this->base->plugin->name ); ?>_end_date[month]" id="end_date_compare" data-id="end_date_compare_index" size="1" class="widefat">
									<option value=""><?php esc_html_e( 'Any Month', 'wpzinc-social' ); ?></option>
									<?php
									for ( $month = 1; $month <= 12; $month++ ) {
										?>
										<option value="<?php echo esc_attr( $month ); ?>"><?php echo esc_attr( DateTime::createFromFormat( '!m', $month )->format( 'F' ) ); ?></option>
										<?php
									}
									?>
								</select>
							</td>
							<td>
								<input type="number" name="<?php echo esc_attr( $this->base->plugin->name ); ?>_end_date[day]" placeholder="<?php esc_attr_e( 'e.g. 30', 'wpzinc-social' ); ?>" class="widefat" />    
							</td>
							<td>&nbsp;</td>
						</tr>

						<?php
						/**
						 * Output condition settings for Integrations / Third Party Plugins
						 *
						 * @since   5.1.2
						 *
						 * @param   string  $post_type  Post Type
						 */
						do_action( $this->base->plugin->filter_name . '_output_condition_form_fields', $post_type );

						/**
						 * Conditions: Taxonomies
						 */
						$taxonomies = $this->base->get_class( 'common' )->get_taxonomies( $post_type );
						if ( is_array( $taxonomies ) && count( $taxonomies ) > 0 ) {
							foreach ( $taxonomies as $taxonomy_name => $details ) {
								?>
								<tr>
									<td>
										<label for="<?php echo esc_attr( $taxonomy_name ); ?>_compare" data-for="<?php echo esc_attr( $taxonomy_name ); ?>_compare_index">
											<?php echo esc_html( $details->labels->singular_name ); ?>
										</label>
									</td>
									<td>
										<select name="<?php echo esc_attr( $this->base->plugin->name ); ?>_conditions[<?php echo esc_attr( $taxonomy_name ); ?>]" id="<?php echo esc_attr( $taxonomy_name ); ?>_compare" data-id="<?php echo esc_attr( $taxonomy_name ); ?>_compare_index" size="1" class="widefat" data-conditional="terms" class="widefat">
											<?php
											foreach ( (array) $this->base->get_class( 'common' )->get_condition_options() as $value => $label ) {
												?>
												<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_attr( $label ); ?></option>
												<?php
											}
											?>
										</select>
									</td>
									<td>
										<input type="text" name="<?php echo esc_attr( $this->base->plugin->name ); ?>_terms[<?php echo esc_attr( $taxonomy_name ); ?>]" id="<?php echo esc_attr( $taxonomy_name ); ?>" class="widefat wpzinc-selectize" style="width:100%;" data-action="<?php echo esc_attr( $this->base->plugin->filter_name ); ?>_search_terms"  data-nonce-key="search_terms_nonce" data-taxonomy="<?php echo esc_attr( $taxonomy_name ); ?>" />
									</td>
									<td>&nbsp;</td>
								</tr>
								<?php
							}
						}

						/**
						 * Custom Fields
						 */
						?>
						<tr class="custom-field hide-delete-button">
							<td>
								<input type="text" name="<?php echo esc_attr( $this->base->plugin->name ); ?>_custom_fields[key][]" data-name="<?php echo esc_attr( $this->base->plugin->name ); ?>_custom_fields[key][]" placeholder="<?php esc_attr_e( 'Meta Key', 'wpzinc-social' ); ?>" class="widefat" />
							</td>
							<td>
								<select name="<?php echo esc_attr( $this->base->plugin->name ); ?>_custom_fields[compare][]" data-name="<?php echo esc_attr( $this->base->plugin->name ); ?>_custom_fields[compare][]" size="1" class="widefat">
									<?php
									foreach ( $this->base->get_class( 'common' )->get_custom_field_comparison_operators() as $comparison_key => $label ) {
										?>
										<option value="<?php echo esc_attr( $comparison_key ); ?>"><?php echo esc_attr( $label ); ?></option>
										<?php
									}
									?>
								</select>
							</td>
							<td>
								<input type="text" name="<?php echo esc_attr( $this->base->plugin->name ); ?>_custom_fields[value][]" data-name="<?php echo esc_attr( $this->base->plugin->name ); ?>_custom_fields[value][]" placeholder="<?php esc_attr_e( 'Meta Value', 'wpzinc-social' ); ?>" class="widefat" />
							</td>
							<td>
								<a href="#" class="wpzinc-delete-table-row button small">
									<?php esc_html_e( 'Remove', 'wpzinc-social' ); ?>
								</a>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>

		<!-- Author Conditions -->
		<div class="wpzinc-option author-conditions">
			<div class="full">
				<h3><?php esc_html_e( 'Author Conditions', 'wpzinc-social' ); ?></h3>
				<p class="description">
					<?php
					echo esc_html(
						sprintf(
							/* translators: Social Media Scheduler Name */
							__( 'Optional: Define the Post\'s Author conditions that are required for this status to be sent to %s. All conditions must be met.', 'wpzinc-social' ),
							$this->base->plugin->account
						)
					);
					?>
				</p>

				<table class="widefat fixed striped">
					<thead>
						<tr>
							<th width="20%"><?php esc_html_e( 'Attribute', 'wpzinc-social' ); ?></th>
							<th><?php esc_html_e( 'Compare', 'wpzinc-social' ); ?></th>
							<th><?php esc_html_e( 'Value', 'wpzinc-social' ); ?></th>
							<th class="actions"><?php esc_html_e( 'Actions', 'wpzinc-social' ); ?></th>
						</tr>
					</thead>

					<tfoot>
						<tr>
							<th colspan="4">
								<a href="#" class="button wpzinc-add-table-row" data-table-row-selector="authors-custom-field">
									<?php esc_html_e( 'Add Custom Field Condition', 'wpzinc-social' ); ?>
								</a>
							</th>
						</tr>
					</tfoot>

					<tbody>
						<tr>
							<td width="20%">
								<label for="<?php echo esc_attr( $this->base->plugin->name ); ?>_authors_compare" data-for="authors_index">
									<?php esc_html_e( 'Author', 'wpzinc-social' ); ?>
								</label>
							</td>
							<td>
								<select id="<?php echo esc_attr( $this->base->plugin->name ); ?>_authors_compare" name="<?php echo esc_attr( $this->base->plugin->name ); ?>_authors_compare" size="1" class="widefat">
									<option value="="><?php esc_html_e( 'Equals', 'wpzinc-social' ); ?></option>
									<option value="!="><?php esc_html_e( 'Does not Equal', 'wpzinc-social' ); ?></option>
								</select>
							</td>
							<td>
								<input type="text" name="<?php echo esc_attr( $this->base->plugin->name ); ?>_authors" id="authors" class="widefat wpzinc-selectize" style="width:100%;" data-action="<?php echo esc_attr( $this->base->plugin->filter_name ); ?>_search_authors" data-nonce-key="search_authors_nonce" />
							</td>
							<td>&nbsp;</td>
						</tr>
						<tr>
							<td>
								<label for="<?php echo esc_attr( $this->base->plugin->name ); ?>_authors_roles_compare" data-for="authors_role_index">
									<?php esc_html_e( 'Role', 'wpzinc-social' ); ?>
								</label>
							</td>
							<td>
								<select id="<?php echo esc_attr( $this->base->plugin->name ); ?>_authors_roles_compare" name="<?php echo esc_attr( $this->base->plugin->name ); ?>_authors_roles_compare" size="1" class="widefat">
									<option value="="><?php esc_html_e( 'Equals', 'wpzinc-social' ); ?></option>
									<option value="!="><?php esc_html_e( 'Does not Equal', 'wpzinc-social' ); ?></option>
								</select>
							</td>
							<td>
								<input type="text" name="<?php echo esc_attr( $this->base->plugin->name ); ?>_authors_roles" id="authors_roles" class="widefat wpzinc-selectize" style="width:100%;" data-action="<?php echo esc_attr( $this->base->plugin->filter_name ); ?>_search_roles" data-nonce-key="search_roles_nonce" />
							</td>
							<td class="actions">&nbsp;</td>
						</tr>

						<?php
						/**
						 * Custom Fields
						 */
						?>
						<tr class="authors-custom-field hide-delete-button">
							<td>
								<input type="text" name="<?php echo esc_attr( $this->base->plugin->name ); ?>_authors_custom_fields[key][]" data-name="<?php echo esc_attr( $this->base->plugin->name ); ?>_authors_custom_fields[key][]" placeholder="<?php esc_attr_e( 'Author Meta Key', 'wpzinc-social' ); ?>" class="widefat" />
							</td>
							<td>
								<select name="<?php echo esc_attr( $this->base->plugin->name ); ?>_authors_custom_fields[compare][]" data-name="<?php echo esc_attr( $this->base->plugin->name ); ?>_authors_custom_fields[compare][]" size="1" class="widefat">
									<?php
									foreach ( $this->base->get_class( 'common' )->get_custom_field_comparison_operators() as $comparison_key => $label ) {
										?>
										<option value="<?php echo esc_attr( $comparison_key ); ?>"><?php echo esc_attr( $label ); ?></option>
										<?php
									}
									?>
								</select>
							</td>
							<td>
								<input type="text" name="<?php echo esc_attr( $this->base->plugin->name ); ?>_authors_custom_fields[value][]" data-name="<?php echo esc_attr( $this->base->plugin->name ); ?>_authors_custom_fields[value][]" placeholder="<?php esc_attr_e( 'Author Meta Value', 'wpzinc-social' ); ?>" class="widefat" />
							</td>
							<td>
								<a href="#" class="wpzinc-delete-table-row button small">
									<?php esc_html_e( 'Remove', 'wpzinc-social' ); ?>
								</a>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>
