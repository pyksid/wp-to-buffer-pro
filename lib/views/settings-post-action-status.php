<?php
/**
 * Outputs the single status configuration form.  Its values are populated by statuses.js, based
 * on the status that has been selected for editing.
 *
 * @package WP_To_Social_Pro
 * @author  WP Zinc
 */

?>
<div id="<?php echo esc_attr( $this->base->plugin->name ); ?>-status-form-container" class="hidden">
	<div id="<?php echo esc_attr( $this->base->plugin->name ); ?>-status-form" class="wp-to-social-pro-status-form">
		<div class="wpzinc-option">
			<div class="full">
				<div class="notice-inline notice-warning pinterest hidden">
					<p>
						<?php
						esc_html_e( 'You need to create at least one Pinterest Board, and then refresh the screen to choose the board to post this status to.', 'wp-to-social-pro' );
						?>
						<a href="<?php echo esc_attr( $this->base->plugin->documentation_url ); ?>/status-settings/#status--choose-a-pinterest-board" target="_blank">
							<?php echo esc_html_e( 'Click here for instructions on creating a Pinterest board.', 'wp-to-social-pro' ); ?>
						</a>
					</p>
				</div>

				<!-- Tags and Feat. Image -->
				<div class="tags-featured-image">
					<!-- Pinterest: Sub Profile -->
					<select name="<?php echo esc_attr( $this->base->plugin->name ); ?>_sub_profile" size="1" class="right"></select> 
					<input type="url" name="<?php echo esc_attr( $this->base->plugin->name ); ?>_sub_profile" placeholder="<?php esc_attr_e( 'Pinterest Board URL', 'wp-to-social-pro' ); ?>" class="right" />
				   
					<!-- Instagram: Update Type -->
					<?php
					if ( $this->base->supports( 'instagram_update_type' ) ) {
						?>
						<select name="<?php echo esc_attr( $this->base->plugin->name ); ?>_update_type" size="1" class="right">
							<option value=""><?php esc_html_e( 'Post', 'wp-to-social-pro' ); ?></option>
							<option value="story"><?php esc_html_e( 'Story', 'wp-to-social-pro' ); ?></option>
						</select>
						<?php
					}
					?>

					<!-- Image -->
					<select name="<?php echo esc_attr( $this->base->plugin->name ); ?>_image" size="1" class="right image">
						<?php
						foreach ( $this->base->get_class( 'image' )->get_featured_image_options( $post_type ) as $value => $label ) {
							?>
							<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_attr( $label ); ?></option>
							<?php
						}
						?>
					</select>

					<?php
					// Tags.
					$textarea = 'textarea.message';
					require 'settings-post-action-status-tags.php';
					?>
				</div>
			</div>

			<!-- Status Message -->
			<div class="full">
				<textarea name="<?php echo esc_attr( $this->base->plugin->name ); ?>_message" rows="3" class="widefat wpzinc-autosize-js message"></textarea>

				<?php
				// If we're editing a Post, Page or CPT, show the chararcter count.
				if ( isset( $post ) && ! empty( $post ) ) {
					?>
					<small class="characters">
						<span class="character-count"></span>
						<?php esc_html_e( 'characters', 'wp-to-social-pro' ); ?>
					</small>
					<?php
				}
				?>
			</div>

			<!-- Scheduling -->
			<div class="full">
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
						<label for="<?php echo esc_attr( $profile_id ); ?>_status_<?php echo esc_attr( $key ); ?>_days"><?php esc_html_e( 'Days, ', 'wp-to-social-pro' ); ?></label>

						<input type="number" name="<?php echo esc_attr( $this->base->plugin->name ); ?>_hours" id="hours" />
						<label for="<?php echo esc_attr( $profile_id ); ?>_status_<?php echo esc_attr( $key ); ?>_hours"><?php esc_html_e( 'Hours, ', 'wp-to-social-pro' ); ?></label>

						<input type="number" name="<?php echo esc_attr( $this->base->plugin->name ); ?>_minutes" id="minutes" />
						<label for="<?php echo esc_attr( $profile_id ); ?>_status_<?php echo esc_attr( $key ); ?>_minutes"><?php esc_html_e( 'Minutes', 'wp-to-social-pro' ); ?></label>
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

						<?php esc_html_e( 'at', 'wp-to-social-pro' ); ?>

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
						<input type="text" name="<?php echo esc_attr( $this->base->plugin->name ); ?>_schedule_custom_field_name" placeholder="<?php esc_attr_e( 'Custom Meta Field Name', 'wp-to-social-pro' ); ?>" />
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
						<input type="datetime-local" name="<?php echo esc_attr( $this->base->plugin->name ); ?>_schedule_specific" class="widefat" placeholder="<?php esc_attr_e( 'Date and Time', 'wp-to-social-pro' ); ?>" />   
					</span>
				</div>
			</div>

			<?php
			if ( $this->base->supports( 'googlebusiness' ) ) {
				?>
				<!-- Google Business Profile -->
				<div class="full conditions conditional googlebusiness hidden">
					<h3><?php esc_html_e( 'Google Business Profile', 'wp-to-social-pro' ); ?></h3>
					<p class="description">
						<?php
						echo esc_html_e( 'Optional: Define the status type (What\'s New, Offer or Event) and additional structured fields / data.', 'wp-to-social-pro' );
						?>
					</p>

					<div class="wpzinc-option no-styling">
						<div class="full">
							<table class="widefat fixed striped">
								<tbody>
									<tr>
										<td>
											<label for="googlebusiness_post_type">
												<?php esc_html_e( 'Post Type', 'wp-to-social-pro' ); ?>
											</label>
										</td>
										<td>
											<select name="<?php echo esc_attr( $this->base->plugin->name ); ?>_googlebusiness[post_type]" id="googlebusiness_post_type" size="1" class="widefat">
												<option value="whats_new"><?php esc_attr_e( 'What\'s New', 'wp-to-social-pro' ); ?></option>
												<option value="offer"><?php esc_attr_e( 'Offer', 'wp-to-social-pro' ); ?></option>
												<option value="event"><?php esc_attr_e( 'Event', 'wp-to-social-pro' ); ?></option>
											</select>
										</td>
									</tr>
									<tr class="whats_new event">
										<td>
											<label for="googlebusiness_cta">
												<?php esc_html_e( 'Call to Action', 'wp-to-social-pro' ); ?>
											</label>
										</td>
										<td>
											<select name="<?php echo esc_attr( $this->base->plugin->name ); ?>_googlebusiness[cta]" id="googlebusiness_cta" size="1" class="widefat">
												<option value="book"><?php esc_attr_e( 'Book', 'wp-to-social-pro' ); ?></option>
												<option value="order"><?php esc_attr_e( 'Order', 'wp-to-social-pro' ); ?></option>
												<option value="shop"><?php esc_attr_e( 'Shop', 'wp-to-social-pro' ); ?></option>
												<option value="learn_more"><?php esc_attr_e( 'Learn More', 'wp-to-social-pro' ); ?></option>
												<option value="signup"><?php esc_attr_e( 'Sign Up', 'wp-to-social-pro' ); ?></option>
											</select>
										</td>
									</tr>
									<tr class="offer event">
										<td>
											<label for="googlebusiness_start_date_option">
												<?php esc_html_e( 'Start Date', 'wp-to-social-pro' ); ?>
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

											<input type="text" name="<?php echo esc_attr( $this->base->plugin->name ); ?>_googlebusiness[start_date]" id="googlebusiness_start_date" placeholder="<?php esc_attr_e( 'Custom Meta Field Name', 'wp-to-social-pro' ); ?>" />
										</td>
									</tr>
									<tr class="offer event">
										<td>
											<label for="googlebusiness_end_date_option">
												<?php esc_html_e( 'End Date', 'wp-to-social-pro' ); ?>
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

											<input type="text" name="<?php echo esc_attr( $this->base->plugin->name ); ?>_googlebusiness[end_date]" id="googlebusiness_end_date" placeholder="<?php esc_attr_e( 'Custom Meta Field Name', 'wp-to-social-pro' ); ?>" />
										</td>
									</tr>
									<tr class="offer event">
										<td>
											<label for="googlebusiness_title">
												<?php esc_html_e( 'Event / Offer Title', 'wp-to-social-pro' ); ?>
											</label>
										</td>
										<td>
											<input type="text" name="<?php echo esc_attr( $this->base->plugin->name ); ?>_googlebusiness[title]" id="googlebusiness_title" class="widefat" />
										</td>
									</tr>
									<tr class="offer">
										<td>
											<label for="googlebusiness_code">
												<?php esc_html_e( 'Coupon Code', 'wp-to-social-pro' ); ?>
											</label>
										</td>
										<td>
											<input type="text" name="<?php echo esc_attr( $this->base->plugin->name ); ?>_googlebusiness[code]" id="googlebusiness_code" class="widefat" />
										</td>
									</tr>
									<tr class="offer">
										<td>
											<label for="googlebusiness_terms">
												<?php esc_html_e( 'Terms and Conditions Text', 'wp-to-social-pro' ); ?>
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
				</div>
				<?php
			}
			?>

			<!-- Text to Image -->
			<div class="full conditions text-to-image">
				<h3><?php esc_html_e( 'Text to Image', 'wp-to-social-pro' ); ?></h3>
				<p class="description">
					<?php
					esc_html_e( 'Define the text to convert to an image, which will be sent with this status.', 'wp-to-social-pro' );
					?>
				</p>

				<div class="wpzinc-option no-styling status">
					<div class="full">
						<?php
						$textarea = 'textarea.text-to-image';
						require 'settings-post-action-status-tags.php';
						?>
					</div>
					<div class="full">
						<textarea name="<?php echo esc_attr( $this->base->plugin->name ); ?>_text_to_image" rows="3" class="widefat wpzinc-autosize-js text-to-image"></textarea>
					</div>
				</div>
			</div>

			<!-- Post Conditions -->
			<div class="full conditions">
				<h3><?php esc_html_e( 'Post Conditions', 'wp-to-social-pro' ); ?></h3>
				<p class="description">
					<?php
					echo esc_html(
						sprintf(
						/* translators: Social Media Service Name (Buffer, Hootsuite, SocialPilot) */
							__( 'Optional: Define Post conditions that are required for this status to be sent to %s. All conditions must be met.', 'wp-to-social-pro' ),
							$this->base->plugin->account
						)
					);
					?>
				</p>

				<!-- Post -->
				<div class="wpzinc-option no-styling">
					<div class="full">
						<table class="widefat fixed striped">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Attribute', 'wp-to-social-pro' ); ?></th>
									<th><?php esc_html_e( 'Compare', 'wp-to-social-pro' ); ?></th>
									<th><?php esc_html_e( 'Value', 'wp-to-social-pro' ); ?></th>
									<th class="actions"><?php esc_html_e( 'Actions', 'wp-to-social-pro' ); ?></th>
								</tr>
							</thead>

							<tfoot>
								<tr>
									<th colspan="4">
										<a href="#" class="button wpzinc-add-table-row" data-table-row-selector="custom-field">
											<?php esc_html_e( 'Add Meta / Custom Field Condition', 'wp-to-social-pro' ); ?>
										</a>
									</th>
								</tr>
							</tfoot>

							<tbody>
								<tr>
									<td>
										<label for="post_title_compare" data-for="post_title_compare_index">
											<?php esc_html_e( 'Title', 'wp-to-social-pro' ); ?>
										</label>
									</td>
									<td>
										<select name="<?php echo esc_attr( $this->base->plugin->name ); ?>_post_title[compare]" id="post_title_compare" data-id="post_title_compare_index" size="1" class="widefat">
											<option value="0"><?php esc_attr_e( 'No Conditions', 'wp-to-social-pro' ); ?></option>
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
											<?php esc_html_e( 'Excerpt', 'wp-to-social-pro' ); ?>
										</label>
									</td>
									<td>
										<select name="<?php echo esc_attr( $this->base->plugin->name ); ?>_post_excerpt[compare]" id="post_excerpt_compare" data-id="post_excerpt_compare_index" size="1" class="widefat">
											<option value="0"><?php esc_html_e( 'No Conditions', 'wp-to-social-pro' ); ?></option>
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
											<?php esc_html_e( 'Content', 'wp-to-social-pro' ); ?>
										</label>
									</td>
									<td>
										<select name="<?php echo esc_attr( $this->base->plugin->name ); ?>_post_content[compare]" id="post_content_compare" data-id="post_content_compare_index" size="1" class="widefat">
											<option value="0"><?php esc_html_e( 'No Conditions', 'wp-to-social-pro' ); ?></option>
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
											<?php esc_html_e( 'Start Date', 'wp-to-social-pro' ); ?>
										</label>
									</td>
									<td>
										<select name="<?php echo esc_attr( $this->base->plugin->name ); ?>_start_date[month]" id="start_date_compare" data-id="start_date_compare_index" size="1" class="widefat">
											<option value=""><?php esc_html_e( 'Any Month', 'wp-to-social-pro' ); ?></option>
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
										<input type="number" name="<?php echo esc_attr( $this->base->plugin->name ); ?>_start_date[day]" placeholder="<?php esc_attr_e( 'e.g. 1', 'wp-to-social-pro' ); ?>" class="widefat" />    
									</td>
									<td>&nbsp;</td>
								</tr>

								<tr>
									<td>
										<label for="end_date_compare" data-for="end_date_compare_index">
											<?php esc_html_e( 'End Date', 'wp-to-social-pro' ); ?>
										</label>
									</td>
									<td>
										<select name="<?php echo esc_attr( $this->base->plugin->name ); ?>_end_date[month]" id="end_date_compare" data-id="end_date_compare_index" size="1" class="widefat">
											<option value=""><?php esc_html_e( 'Any Month', 'wp-to-social-pro' ); ?></option>
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
										<input type="number" name="<?php echo esc_attr( $this->base->plugin->name ); ?>_end_date[day]" placeholder="<?php esc_attr_e( 'e.g. 30', 'wp-to-social-pro' ); ?>" class="widefat" />    
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
										<input type="text" name="<?php echo esc_attr( $this->base->plugin->name ); ?>_custom_fields[key][]" data-name="<?php echo esc_attr( $this->base->plugin->name ); ?>_custom_fields[key][]" placeholder="<?php esc_attr_e( 'Meta Key', 'wp-to-social-pro' ); ?>" class="widefat" />
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
										<input type="text" name="<?php echo esc_attr( $this->base->plugin->name ); ?>_custom_fields[value][]" data-name="<?php echo esc_attr( $this->base->plugin->name ); ?>_custom_fields[value][]" placeholder="<?php esc_attr_e( 'Meta Value', 'wp-to-social-pro' ); ?>" class="widefat" />
									</td>
									<td>
										<a href="#" class="wpzinc-delete-table-row button small">
											<?php esc_html_e( 'Remove', 'wp-to-social-pro' ); ?>
										</a>
									</td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>

				<!-- Author Conditions -->
				<div class="full conditions">
					<h3><?php esc_html_e( 'Author Conditions', 'wp-to-social-pro' ); ?></h3>
					<p class="description">
						<?php
						echo esc_html(
							sprintf(
							/* translators: Social Media Service Name (Buffer, Hootsuite, SocialPilot) */
								__( 'Optional: Define the Post\'s Author conditions that are required for this status to be sent to %s. All conditions must be met.', 'wp-to-social-pro' ),
								$this->base->plugin->account
							)
						);
						?>
					</p>

					<div class="wpzinc-option no-styling">
						<div class="full">
							<table class="widefat fixed striped">
								<thead>
									<tr>
										<th><?php esc_html_e( 'Attribute', 'wp-to-social-pro' ); ?></th>
										<th><?php esc_html_e( 'Compare', 'wp-to-social-pro' ); ?></th>
										<th><?php esc_html_e( 'Value', 'wp-to-social-pro' ); ?></th>
										<th class="actions"><?php esc_html_e( 'Actions', 'wp-to-social-pro' ); ?></th>
									</tr>
								</thead>

								<tfoot>
									<tr>
										<th colspan="4">
											<a href="#" class="button wpzinc-add-table-row" data-table-row-selector="authors-custom-field">
												<?php esc_html_e( 'Add Custom Field Condition', 'wp-to-social-pro' ); ?>
											</a>
										</th>
									</tr>
								</tfoot>

								<tbody>
									<tr>
										<td>
											<label for="authors" data-for="authors_index">
												<?php esc_html_e( 'Author', 'wp-to-social-pro' ); ?>
											</label>
										</td>
										<td>
											<select name="<?php echo esc_attr( $this->base->plugin->name ); ?>_authors_compare" id="authors_compare" size="1" class="widefat">
												<option value="="><?php esc_html_e( 'Equals', 'wp-to-social-pro' ); ?></option>
												<option value="!="><?php esc_html_e( 'Does not Equal', 'wp-to-social-pro' ); ?></option>
											</select>
										</td>
										<td>
											<input type="text" name="<?php echo esc_attr( $this->base->plugin->name ); ?>_authors" id="authors" class="widefat wpzinc-selectize" style="width:100%;" data-action="<?php echo esc_attr( $this->base->plugin->filter_name ); ?>_search_authors" data-nonce-key="search_authors_nonce" />
										</td>
										<td>&nbsp;</td>
									</tr>
									<tr>
										<td>
											<label for="authors_roles" data-for="authors_role_index">
												<?php esc_html_e( 'Role', 'wp-to-social-pro' ); ?>
											</label>
										</td>
										<td>
											<select name="<?php echo esc_attr( $this->base->plugin->name ); ?>_authors_roles_compare" id="authors_roles_compare" size="1" class="widefat">
												<option value="="><?php esc_html_e( 'Equals', 'wp-to-social-pro' ); ?></option>
												<option value="!="><?php esc_html_e( 'Does not Equal', 'wp-to-social-pro' ); ?></option>
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
											<input type="text" name="<?php echo esc_attr( $this->base->plugin->name ); ?>_authors_custom_fields[key][]" data-name="<?php echo esc_attr( $this->base->plugin->name ); ?>_authors_custom_fields[key][]" placeholder="<?php esc_attr_e( 'Author Meta Key', 'wp-to-social-pro' ); ?>" class="widefat" />
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
											<input type="text" name="<?php echo esc_attr( $this->base->plugin->name ); ?>_authors_custom_fields[value][]" data-name="<?php echo esc_attr( $this->base->plugin->name ); ?>_authors_custom_fields[value][]" placeholder="<?php esc_attr_e( 'Author Meta Value', 'wp-to-social-pro' ); ?>" class="widefat" />
										</td>
										<td>
											<a href="#" class="wpzinc-delete-table-row button small">
												<?php esc_html_e( 'Remove', 'wp-to-social-pro' ); ?>
											</a>
										</td>
									</tr>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
