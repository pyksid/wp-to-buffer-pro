<?php
/**
 * Outputs the UI on Posts to allow multiple images to be expressly
 * defined for use when sending a status update.
 *
 * @since   3.2.6
 *
 * @package WPZinc\Social\Pro
 * @author  WP Zinc
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div class="wpzinc-option">
	<div class="full wpzinc-media-library-selector"
			data-input-name="<?php echo esc_attr( $this->base->plugin->name ); ?>[additional_images][]"
			data-file-type="image"
			data-output-size="small"
			data-multiple="<?php echo esc_attr( $this->base->supports( 'additional_images' ) ? 'true' : 'false' ); ?>"
			data-limit="10">
		<ul class="images">
			<?php
			// Output any existing selected images.
			foreach ( $images as $i => $image ) {
				// Skip if no image defined.
				if ( ! $image['thumbnail_url'] ) {
					continue;
				}
				?>
				<li class="wpzinc-media-library-attachment">
					<div class="wpzinc-media-library-insert">
						<input type="hidden" name="<?php echo esc_attr( $this->base->plugin->name ); ?>[additional_images][]" value="<?php echo esc_attr( ! $image['id'] ? '' : $image['id'] ); ?>" />
						<img src="<?php echo esc_attr( ! $image['thumbnail_url'] ? '' : $image['thumbnail_url'] ); ?>" />
					</div>
					<a href="#" class="wpzinc-media-library-remove" title="<?php esc_attr_e( 'Remove', 'wpzinc-social' ); ?>"><?php esc_html_e( 'Remove', 'wpzinc-social' ); ?></a>
				</li>
				<?php
			}
			?>
		</ul>

		<button class="wpzinc-media-library-insert button button-secondary">
			<?php
			if ( $this->base->supports( 'additional_images' ) ) {
				esc_html_e( 'Select Images', 'wpzinc-social' );
			} else {
				esc_html_e( 'Select Image', 'wpzinc-social' );
			}
			?>
		</button>
	</div>

	<?php
	// Output description depending on whether additional images are supported, or just the featured image.
	if ( $this->base->supports( 'additional_images' ) ) {
		?>
		<p class="description">
			<?php
			echo esc_html(
				sprintf(
				/* translators: Post Type Singular */
					__( 'Images defined here will only be used where a status\' type is set to "Image".', 'wpzinc-social' ),
					$post_type_object->labels->singular_name
				)
			);
			?>
		</p>
		<p class="description">
			<?php
			esc_html_e( 'Drag and drop images to reorder. The number of additional images included in a status will depend on the social network. Refer to the ', 'wpzinc-social' );
			?>
			<a href="<?php echo esc_attr( $this->base->plugin->documentation_url ); ?>/featured-image-settings/" target="_blank"><?php esc_html_e( 'Documentation', 'wpzinc-social' ); ?></a>
		</p>
		<?php
	} else {
		?>
		<p class="description">
			<?php
			echo esc_html(
				sprintf(
					/* translators: Post Type Singular */
					__( 'Image defined here will only be used where a status\' type is set to "Image".', 'wpzinc-social' ),
					$post_type_object->labels->singular_name
				)
			);
			?>
		</p>
		<?php
	}
	?>
</div>

<?php
// Video selector, where supported by the Plugin.
if ( $this->base->supports( 'video' ) ) {
	?>
	<div class="wpzinc-option">
		<div class="full wpzinc-media-library-selector"
				data-input-name="<?php echo esc_attr( $this->base->plugin->name ); ?>[video]"
				data-file-type="video"
				data-output-size="small"
				data-multiple="false"
				data-limit="1">
			<ul class="video">
				<?php
				// Output the existing selected video, if one is defined.
				if ( $video && $video['id'] ) {
					?>
					<li class="wpzinc-media-library-attachment">
						<a href="#" class="wpzinc-media-library-insert">
							<input type="hidden" name="<?php echo esc_attr( $this->base->plugin->name ); ?>[video]" value="<?php echo esc_attr( $video['id'] ); ?>" />
							<?php echo esc_html( $video['filename'] ); ?>
						</a>
						<a href="#" class="wpzinc-media-library-remove" title="<?php esc_attr_e( 'Remove', 'wpzinc-social' ); ?>"><?php esc_html_e( 'Remove', 'wpzinc-social' ); ?></a>
					</li>
					<?php
				}
				?>
			</ul>

			<button class="wpzinc-media-library-insert button button-secondary">
				<?php esc_html_e( 'Select Video', 'wpzinc-social' ); ?>
			</button>
		</div>

		<p class="description">
			<?php esc_html_e( 'The video defined here is used where a status\' type is set to "Video" and its source is "Media Library". MP4 only.', 'wpzinc-social' ); ?>
		</p>
	</div>
	<?php
}
?>
