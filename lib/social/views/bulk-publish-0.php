<?php
/**
 * Outputs Bulk Publish View for a Post Type
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
<!-- Post Type -->
<div id="<?php echo esc_attr( $post_type ); ?>-panel" class="panel">

	<!-- Post Selection Tool -->
	<div id="post-selection" class="postbox">
		<h3 class="hndle">
			<?php
			echo esc_html(
				sprintf(
					/* translators: Post Type Name */
					__( 'Search %1$s to Send to Social Media', 'wpzinc-social' ),
					$post_types[ $post_type ]->labels->name
				)
			);
			?>
		</h3>

		<div class="posts">
			<!-- Post Date -->
			<div class="wpzinc-option">
				<div class="left">
					<label for="start_date"><?php esc_html_e( 'Published Date', 'wpzinc-social' ); ?></label>
				</div>
				<div class="right">
					<?php esc_html_e( 'Between', 'wpzinc-social' ); ?>
					<input type="date" name="<?php echo esc_attr( $this->base->plugin->name ); ?>[start_date]" value="<?php echo esc_attr( $params['start_date'] ); ?>" id="start_date" />
					<?php esc_html_e( 'and', 'wpzinc-social' ); ?>
					<input type="date" name="<?php echo esc_attr( $this->base->plugin->name ); ?>[end_date]" value="<?php echo esc_attr( $params['end_date'] ); ?>" />
				</div>
			</div>

			<!-- Post Author -->
			<div class="wpzinc-option">
				<div class="left">
					<label for="authors"><?php esc_html_e( 'Authors', 'wpzinc-social' ); ?></label>
				</div>
				<div class="right">
					<input type="text" name="<?php echo esc_attr( $this->base->plugin->name ); ?>[authors]" id="authors" class="widefat wpzinc-selectize" style="width:100%;" data-action="<?php echo esc_attr( $this->base->plugin->filter_name ); ?>_search_authors" data-nonce-key="search_authors_nonce" />
				</div>
			</div>

			<!-- Meta -->
			<div class="wpzinc-option">
				<div class="left">
					<label for="custom_field_meta_key"><?php esc_html_e( 'Meta / Custom Fields', 'wpzinc-social' ); ?></label>
				</div>
				<div class="right">
					<table class="widefat fixed striped">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Key', 'wpzinc-social' ); ?></th>
								<th><?php esc_html_e( 'Compare', 'wpzinc-social' ); ?></th>
								<th><?php esc_html_e( 'Value', 'wpzinc-social' ); ?></th>
								<th><?php esc_html_e( 'Actions', 'wpzinc-social' ); ?></th>
							</tr>
						</thead>

						<tfoot>
							<tr>
								<th colspan="4">
									<a href="#" class="wpzinc-add-table-row button" data-table-row-selector="custom-field">
										<?php esc_html_e( 'Add Meta / Custom Field Condition', 'wpzinc-social' ); ?>
									</a>
								</th>
							</tr>
						</tfoot>

						<tbody>
							<?php
							if ( $params['meta'] ) {
								foreach ( $params['meta'] as $meta ) {
									?>
									<tr class="custom-field hide-delete-button">
										<td>
											<input type="text" name="<?php echo esc_attr( $this->base->plugin->name ); ?>[meta][key][]" id="custom_field_meta_key" placeholder="<?php esc_attr_e( 'Meta Key', 'wpzinc-social' ); ?>" value="<?php echo esc_attr( $meta['key'] ); ?>" class="widefat" />
										</td>
										<td>
											<select name="<?php echo esc_attr( $this->base->plugin->name ); ?>[meta][compare][]" size="1">
												<?php
												foreach ( $custom_field_comparison_operators as $key => $label ) {
													?>
													<option value="<?php echo esc_attr( $key ); ?>"<?php selected( $key, $meta['compare'] ); ?>><?php echo esc_attr( $label ); ?></option>
													<?php
												}
												?>
											</select>
										</td>
										<td>
											<input type="text" name="<?php echo esc_attr( $this->base->plugin->name ); ?>[meta][value][]" placeholder="<?php esc_attr_e( 'Meta Value', 'wpzinc-social' ); ?>" value="<?php echo esc_attr( $meta['value'] ); ?>" class="widefat" />
										</td>
										<td>
											<a href="#" class="wpzinc-delete-table-row button small">
												<?php esc_html_e( 'Remove', 'wpzinc-social' ); ?>
											</a>
										</td>
									</tr>
									<?php
								}
							}
							?>
							<tr class="custom-field hide-delete-button">
								<td>
									<input type="text" name="<?php echo esc_attr( $this->base->plugin->name ); ?>[meta][key][]" id="custom_field_meta_key" placeholder="<?php esc_attr_e( 'Meta Key', 'wpzinc-social' ); ?>" class="widefat" />
								</td>
								<td>
									<select name="<?php echo esc_attr( $this->base->plugin->name ); ?>[meta][compare][]" size="1">
										<?php
										foreach ( $custom_field_comparison_operators as $key => $label ) {
											?>
											<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_attr( $label ); ?></option>
											<?php
										}
										?>
									</select>
								</td>
								<td>
									<input type="text" name="<?php echo esc_attr( $this->base->plugin->name ); ?>[meta][value][]" placeholder="<?php esc_attr_e( 'Meta Value', 'wpzinc-social' ); ?>" class="widefat" />
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

			<!-- Search -->
			<div class="wpzinc-option">
				<div class="left">
					<label for="s"><?php esc_html_e( 'Search Terms', 'wpzinc-social' ); ?></label>
				</div>
				<div class="right">
					<input type="text" name="<?php echo esc_attr( $this->base->plugin->name ); ?>[s]" id="s" value="<?php echo esc_attr( $params['s'] ); ?>" class="widefat" />

					<p class="description">
						<?php
						echo esc_html(
							sprintf(
							/* translators: Post Type Name */
								__( 'Will return all %s where the Title or Content contains this value.', 'wpzinc-social' ),
								$post_types[ $post_type ]->labels->name
							)
						);
						?>
					</p>
				</div>
			</div>

			<!-- Taxonomies -->
			<?php
			// Output taxonomies.
			foreach ( $taxonomies as $taxonomy_name => $details ) {
				?>
				<div class="wpzinc-option">
					<div class="left">
						<label for="<?php echo esc_attr( $taxonomy_name ); ?>"><?php echo esc_html( $details->labels->singular_name ); ?></label>
					</div>

					<div class="right">
						<input type="text" name="<?php echo esc_attr( $this->base->plugin->name ); ?>[taxonomies][<?php echo esc_attr( $taxonomy_name ); ?>]" size="1" multiple="multiple" id="<?php echo esc_attr( $taxonomy_name ); ?>" class="widefat wpzinc-selectize" style="width:100%;" data-taxonomy="<?php echo esc_attr( $taxonomy_name ); ?>" data-action="<?php echo esc_attr( $this->base->plugin->filter_name ); ?>_search_terms" data-nonce-key="search_terms_nonce">
					</div>
				</div>
				<?php
			} // Close loop
			?>

			<!-- Order By and Order -->
			<div class="wpzinc-option">
				<div class="left">
					<label for="orderby"><?php esc_html_e( 'Order By', 'wpzinc-social' ); ?></label>
				</div>
				<div class="right">
					<select name="<?php echo esc_attr( $this->base->plugin->name ); ?>[orderby]" id="orderby" size="1">
						<?php
						foreach ( $orderby as $key => $label ) {
							?>
							<option value="<?php echo esc_attr( $key ); ?>"<?php selected( $key, $params['orderby'] ); ?>><?php echo esc_attr( $label ); ?></option>
							<?php
						}
						?>
					</select>

					<p class="description">
						<?php
						echo esc_html(
							sprintf(
							/* translators: Social Media Scheduler Name */
								__( 'Defines how to order the Posts that will be added to your %s queue.', 'wpzinc-social' ),
								$this->base->plugin->account
							)
						);
						?>
					</p>
				</div>
			</div>

			<div class="wpzinc-option">
				<div class="left">
					<label for="order"><?php esc_html_e( 'Order', 'wpzinc-social' ); ?></label>
				</div>
				<div class="right">
					<select name="<?php echo esc_attr( $this->base->plugin->name ); ?>[order]" id="order" size="1">
						<?php
						foreach ( $order as $key => $label ) {
							?>
							<option value="<?php echo esc_attr( $key ); ?>"<?php selected( $key, $params['order'] ); ?>><?php echo esc_attr( $label ); ?></option>
							<?php
						}
						?>
					</select>

					<p class="description">
						<?php
						echo esc_html(
							sprintf(
							/* translators: Social Media Scheduler Name */
								__( 'Defines the order in which Posts will be added to your %s queue.', 'wpzinc-social' ),
								$this->base->plugin->account
							)
						);
						?>
					</p>
				</div>
			</div>
		</div>
	</div>
</div>
<!-- /post_type -->

<!-- Buttons -->
<input type="hidden" name="stage" value="1" />
<input type="submit" name="submit" value="<?php esc_attr_e( 'Choose Posts', 'wpzinc-social' ); ?>" class="button button-primary" />
