<?php
/**
 * Text to Image class
 *
 * @package WP_To_Social_Pro
 * @author WP Zinc
 */

/**
 * Creates images from text and an optional background image or color.
 *
 * @package WP_To_Social_Pro
 * @author  WP Zinc
 * @version 4.2.0
 */
class WP_To_Social_Pro_Text_To_Image {

	/**
	 * Holds the image created from imagecreatetruecolor
	 *
	 * @since   4.2.0
	 *
	 * @var     resource
	 */
	protected $im = false;

	/**
	 * Holds the image mime type
	 *
	 * @since   4.2.0
	 *
	 * @var     string
	 */
	protected $mime = 'image/png';

	/**
	 * Holds the stroke size
	 *
	 * @since   4.2.0
	 *
	 * @var     int
	 */
	protected $stroke_size = 0;

	/**
	 * Holds the stroke color RGBA values
	 *
	 * @since   4.2.0
	 *
	 * @var     int
	 */
	protected $stroke_color = array(
		'r' => 0,
		'g' => 0,
		'b' => 0,
		'a' => 0,
	);

	/**
	 * Holds the font size
	 *
	 * @since   4.2.0
	 *
	 * @var     int
	 */
	protected $text_size = 12;

	/**
	 * Holds the font color RGBA values
	 *
	 * @since   4.2.0
	 *
	 * @var     int
	 */
	protected $text_color = array(
		'r' => 0,
		'g' => 0,
		'b' => 0,
		'a' => 0,
	);

	/**
	 * Holds the text horizontal and vertical alignment
	 *
	 * @since   4.2.0
	 *
	 * @var     int
	 */
	protected $text_align = array(
		'x' => 'center',
		'y' => 'center',
	);

	/**
	 * Determines if text should be wrapped onto newlines if it overflows
	 *
	 * @since   4.2.0
	 *
	 * @var     bool
	 */
	protected $text_wrapping_overflow = true;

	/**
	 * Holds the text line height
	 *
	 * @since   4.2.0
	 *
	 * @var     decimal
	 */
	protected $line_height = 1.25;

	/**
	 * Holds the text baseline alignment
	 *
	 * @since   4.2.0
	 *
	 * @var     decimal
	 */
	protected $baseline = 0.2;

	/**
	 * Holds the text font face
	 *
	 * @since   4.2.0
	 *
	 * @var     int
	 */
	protected $font_face = null;

	/**
	 * Holds the text shadow
	 *
	 * @since   4.2.0
	 *
	 * @var     int
	 */
	protected $text_shadow = false;

	/**
	 * Holds the text background color
	 *
	 * @since   4.2.0
	 *
	 * @var     int
	 */
	protected $text_background_color = false;

	/**
	 * Holds the text box dimensions and inner padding
	 *
	 * @since   4.2.0
	 *
	 * @var     int
	 */
	protected $box = array(
		'x'      => 0,
		'y'      => 0,
		'width'  => 100,
		'height' => 100,
	);

	/**
	 * Creates a new image of the specified dimensions with optional background color, ready for text
	 * to then be applied
	 *
	 * @since   4.2.0
	 *
	 * @param   int   $width              Image Width.
	 * @param   int   $height             Image Height.
	 * @param   mixed $background_color   (string) HEX, (array) RGBA, (bool) false.
	 */
	public function create( $width, $height, $background_color ) {

		// Convert hex to rgba.
		if ( ! is_array( $background_color ) ) {
			$background_color = $this->hex_to_rgba( $background_color );
		}

		$this->im = imagecreatetruecolor( $width, $height );
		imagefill( $this->im, 0, 0, imagecolorallocate( $this->im, $background_color['r'], $background_color['g'], $background_color['b'] ) );

	}

	/**
	 * Load an existing image, ready for text to then be applied
	 *
	 * @since   4.2.0
	 *
	 * @param   int $attachment_id  Attachment ID.
	 * @return  mixed                   WP_Error | array (width,height)
	 */
	public function load( $attachment_id ) {

		// Load image from WordPress.
		$image = wp_get_attachment_image_src( $attachment_id, 'full' );

		// Bail if image could not be found.
		if ( ! $image ) {
			return new WP_Error( 'wp_to_social_pro_load_attachment_missing', __( 'Could not find the background image.', 'wp-to-social-pro' ) );
		}

		// Load as a JPEG or PNG.
		$this->mime = get_post_mime_type( $attachment_id );
		if ( ! $this->mime ) {
			return new WP_Error( 'wp_to_social_pro_load_attachment_missing', __( 'Could not determine MIME type of the background image.', 'wp-to-social-pro' ) );
		}
		switch ( $this->mime ) {
			case 'image/png':
				$background_image = imagecreatefrompng( $image[0] );
				break;

			case 'image/jpeg':
				$background_image = imagecreatefromjpeg( $image[0] );
				break;

			default:
				return new WP_Error( 'wp_to_social_pro_load_attachment_missing', __( 'Unsupported MIME type for the background image.', 'wp-to-social-pro' ) );
		}

		// Create blank image matching required width and height.
		$this->im = imagecreatetruecolor( $image[1], $image[2] );

		// Copy background image to new image.
		// We can't just return $background_image as the resource, because when we want to define the text color
		// later on, imagecolorallocate() will fail as it's constrained by the background image color pallete.
		imagecopyresampled( $this->im, $background_image, 0, 0, 0, 0, $image[1], $image[2], $image[1], $image[2] );

		// Return width and height of image.
		return array(
			$image[1],
			$image[2],
		);

	}

	/**
	 * Utility function to add centered text in the given font face, size and color
	 *
	 * @since   4.2.0
	 *
	 * @param   string $text                   Text.
	 * @param   string $font_face              Path and Filename to Font File.
	 * @param   int    $text_size              Font Size, in pixels.
	 * @param   mixed  $text_color             (string) HEX, (array) RGBA, (bool) false.
	 * @param   mixed  $text_background_color  (string) HEX, (array) RGBA, (bool) false.
	 * @param   int    $width                  Text Width (should not exceed the image's width from create() or load()).
	 * @param   int    $height                 Text Height (should not exceed the image's height from create() or load()).
	 * @param   int    $padding                Padding to apply between the width and height and the text.
	 */
	public function add_text( $text, $font_face, $text_size, $text_color, $text_background_color, $width, $height, $padding ) {

		$this->set_font_face( $font_face );
		$this->set_text_size( $text_size );
		$this->set_text_color( $text_color );
		$this->set_text_background_color( $text_background_color );
		$this->set_text_box( $padding, $padding, $width - ( $padding * 2 ), $height - ( $padding * 2 ) );
		$this->draw( $text );

	}

	/**
	 * Output the generated image into the browser
	 *
	 * @since   4.2.0
	 */
	public function output() {

		header( 'Content-Type: ' . $this->mime );

		switch ( $this->mime ) {

			case 'image/png':
				imagepng( $this->im );
				break;

			case 'image/jpeg':
				imagejpeg( $this->im );
				break;

		}

		die();

	}

	/**
	 * Save the generated image to a temporary filename.
	 *
	 * @since   4.2.0
	 *
	 * @return  string  Image Destination Path and Filename
	 */
	public function save_tmp() {

		// Define temporary destination.
		$destination = get_temp_dir() . 'wp-to-social-pro-text-to-image-' . bin2hex( random_bytes( 5 ) );

		// Save Image to Destination Path and File.
		switch ( $this->mime ) {

			case 'image/png':
				imagepng( $this->im, $destination );
				break;

			case 'image/jpeg':
				imagejpeg( $this->im, $destination );
				break;

		}

		// Return Destination Path and File.
		return $destination;

	}

	/**
	 * Sets the text color
	 *
	 * @since   4.2.0
	 *
	 * @param   mixed $color  Color (array (r,g,b,a) or hex).
	 */
	public function set_text_color( $color ) {

		if ( ! $color ) {
			return false;
		}

		// Convert hex to rgba.
		if ( ! is_array( $color ) ) {
			$color = $this->hex_to_rgba( $color );
		}

		$this->text_color = $color;

	}

	/**
	 * Sets the font face to the given font file's path and filename
	 *
	 * @since   4.2.0
	 *
	 * @param   string $path   Path and filename.
	 */
	public function set_font_face( $path ) {

		$this->font_face = $path;

	}

	/**
	 * Sets the font size, in pixels
	 *
	 * @since   4.2.0
	 *
	 * @param   int $pixels     Font Size.
	 */
	public function set_text_size( $pixels ) {

		$this->text_size = $pixels;

	}

	/**
	 * Sets the text stroke color
	 *
	 * @since   4.2.0
	 *
	 * @param   mixed $color  array (r,g,b,a) or hex.
	 */
	public function set_stroke_color( $color ) {

		if ( ! $color ) {
			return false;
		}

		// Convert hex to rgba.
		if ( ! is_array( $color ) ) {
			$color = $this->hex_to_rgba( $color );
		}

		$this->stroke_color = $color;

	}

	/**
	 * Sets the stroke size, in pixels
	 *
	 * @since   4.2.0
	 *
	 * @param   int $pixels     Stroke Size.
	 */
	public function set_stroke_size( $pixels ) {

		$this->stroke_size = $pixels;

	}

	/**
	 * Sets the text shadow
	 *
	 * @since   4.2.0
	 *
	 * @param   mixed $color  Text Color (Hex string or RGBA array).
	 * @param   int   $x      Relative shadow position in pixels. Positive values move shadow to right, negative to left.
	 * @param   int   $y      Relative shadow position in pixels. Positive values move shadow to bottom, negative to up.
	 */
	public function set_text_shadow( $color, $x, $y ) {

		if ( ! $color ) {
			return;
		}

		// Convert hex to rgba.
		if ( ! is_array( $color ) ) {
			$color = $this->hex_to_rgba( $color );
		}

		$this->text_shadow = array(
			'color' => $color,
			'x'     => $x,
			'y'     => $y,
		);

	}

	/**
	 * Sets the background color
	 *
	 * @since   4.2.0
	 *
	 * @param   mixed $color  Text Color (Hex string or RGBA array).
	 */
	public function set_text_background_color( $color ) {

		if ( ! $color ) {
			return;
		}

		// Convert hex to rgba.
		if ( ! is_array( $color ) ) {
			$color = $this->hex_to_rgba( $color );
		}

		$this->text_background_color = $color;

	}

	/**
	 * Sets the text line height
	 *
	 * @since   4.2.0
	 *
	 * @param   decimal $line_height  Height of the single text line, in percents, proportionally to font size.
	 */
	public function set_line_height( $line_height ) {

		$this->line_height = $line_height;

	}

	/**
	 * Sets the text line height
	 *
	 * @since   4.2.0
	 *
	 * @param   decimal $baseline  Position of baseline, in percents, proportionally to line height measuring from the bottom.
	 */
	public function set_baseline( $baseline ) {

		$this->baseline = $baseline;

	}

	/**
	 * Sets the text alignment
	 *
	 * @since   4.2.0
	 *
	 * @param   string $x  Horizontal alignment (left, center, right).
	 * @param   string $y  Vertical alignment (top, center, bottom).
	 */
	public function set_text_alignment( $x = 'left', $y = 'top' ) {

		$this->text_align = array(
			'x' => $x,
			'y' => $y,
		);

	}

	/**
	 * Defines the text box size and padding
	 *
	 * @param   int $x      Padding, in pixels from left edge of image.
	 * @param   int $y      Padding, in pixels from top edge of image.
	 * @param   int $width  Width of texbox in pixels.
	 * @param   int $height Height of textbox in pixels.
	 */
	public function set_text_box( $x, $y, $width, $height ) {

		$this->box = array(
			'x'      => $x,
			'y'      => $y,
			'width'  => $width,
			'height' => $height,
		);

	}

	/**
	 * Draws the given text on the image
	 *
	 * @since   4.2.0
	 *
	 * @param   string $text   Text to draw. May contain newline characters.
	 */
	public function draw( $text ) {

		// Bail if a font face wasn't defined.
		if ( ! isset( $this->font_face ) ) {
			return new WP_Error( 'wp_to_social_pro_gd_text_draw_missing_font_face', __( 'You must specify a font file.', 'wp-to-social-pro' ) );
		}

		// Define lines of text based on the text wrapping setting.
		$lines = ( $this->text_wrapping_overflow ? $this->wrap_text_with_overflow( $text ) : array( $text ) );

		// Calculate line height, in pixels.
		$line_height_pixels = $this->line_height * $this->text_size;

		// Calculate text height.
		$text_height = count( $lines ) * $line_height_pixels;

		// Determine text vertical alignment.
		switch ( $this->text_align['y'] ) {

			case 'center':
				$text_align_y = ( $this->box['height'] / 2 ) - ( $text_height / 2 );
				break;

			case 'bottom':
				$text_align_y = $this->box['height'] - $text_height;
				break;

			case 'top':
			default:
				$text_align_y = 0;
				break;

		}

		// Iterate through each line of text, adding it to the image.
		foreach ( $lines as $current_line => $line ) {

			// Get text bounding box.
			$box       = $this->calculate_box( $line );
			$box_width = $box[2] - $box[0];

			switch ( $this->text_align['x'] ) {

				case 'center':
					$text_align_x = ( $this->box['width'] - $box_width ) / 2;
					break;
				case 'right':
					$text_align_x = ( $this->box['width'] - $box_width );
					break;
				case 'left':
				default:
					$text_align_x = 0;
					break;

			}

			// Define the current text line's X and Y position.
			$current_line_x_pos = $this->box['x'] + $text_align_x;
			$current_line_y_pos = $this->box['y'] + $text_align_y + ( $line_height_pixels * ( 1 - $this->baseline ) ) + ( $current_line * $line_height_pixels );

			// Apply Text Background Color.
			if ( $this->text_background_color ) {
				$this->draw_text_background(
					$current_line_x_pos,
					$this->box['y'] + $text_align_y + ( $current_line * $line_height_pixels ) + ( $line_height_pixels - $this->text_size ) + ( 1 - $this->line_height ) * 13 * ( 1 / 50 * $this->text_size ),
					$box_width,
					$this->text_size,
					$this->text_background_color
				);
			}

			// Draw Text Shadow.
			if ( $this->text_shadow ) {
				$this->draw_text_shadow( $current_line_x_pos, $current_line_y_pos, $line );
			}

			// Draw Text Stroke.
			$this->draw_text_stroke( $current_line_x_pos, $current_line_y_pos, $line );

			// Draw Text.
			$this->draw_text( $current_line_x_pos, $current_line_y_pos, $line );

		}

	}

	/**
	 * Converts the given hex color to RGB
	 *
	 * @since   4.2.0
	 *
	 * @param   string $hex    Hex color (e.g. #000000).
	 * @return  mixed           array | WP_Error
	 */
	private function hex_to_rgba( $hex ) {

		$hex = str_replace( '#', '', $hex );

		if ( strlen( $hex ) === 6 ) {
			return array(
				'r' => hexdec( substr( $hex, 0, 2 ) ),
				'g' => hexdec( substr( $hex, 2, 2 ) ),
				'b' => hexdec( substr( $hex, 4, 2 ) ),
			);
		}

		if ( strlen( $hex ) === 3 ) {
			return array(
				'r' => hexdec( str_repeat( substr( $hex, 0, 1 ), 2 ) ),
				'g' => hexdec( str_repeat( substr( $hex, 1, 1 ), 2 ) ),
				'b' => hexdec( str_repeat( substr( $hex, 2, 1 ), 2 ) ),
			);
		}

		// If here, we couldn't convert the hex to RGBA.
		return new WP_Error(
			'wp_to_social_pro_gd_text_hex_to_rgba_error',
			sprintf(
				/* translators: HEX Color */
				__( 'Could not convert hex color %s to RGBA', 'wp-to-social-pro' ),
				$hex
			)
		);

	}

	/**
	 * Splits overflowing text into array of strings that won't overflow the text box.
	 *
	 * @since   4.2.0
	 *
	 * @param   string $text Text.
	 * @return  array
	 */
	protected function wrap_text_with_overflow( $text ) {

		$lines = array();

		// Split text explicitly into lines by \n, \r\n and \r.
		$explicit_lines = preg_split( '/\n|\r\n?/', $text );

		// Iterate through each line, checking if it needs to be wrapped.
		foreach ( $explicit_lines as $line ) {

			$words = explode( ' ', $line );
			$line  = $words[0];
			$count = count( $words );

			for ( $i = 1; $i < $count; $i++ ) {
				$box = $this->calculate_box( $line . ' ' . $words[ $i ] );

				if ( ( $box[4] - $box[6] ) >= $this->box['width'] ) {
					$lines[] = $line;
					$line    = $words[ $i ];
				} else {
					$line .= ' ' . $words[ $i ];
				}
			}

			$lines[] = $line;

		}

		return $lines;

	}

	/**
	 * Gets the given RBGA color index from the image, if it already exists.
	 * If it doesn't exist it adds the color, returning the index.
	 *
	 * @since   4.2.0
	 *
	 * @param   array $color  RBG(A) Color.
	 * @return  int             Index
	 */
	protected function get_color_index( $color ) {

		// Check if the given RBGa combination is already a palette in the image.
		if ( isset( $color['a'] ) ) {
			$index = imagecolorexactalpha( $this->im, $color['r'], $color['g'], $color['b'], $color['a'] );
		} else {
			$index = imagecolorexact( $this->im, $color['r'], $color['g'], $color['b'] );
		}

		// If a palette was founding matching the RBGA values, return its index.
		if ( $index !== -1 ) {
			return $index;
		}

		if ( isset( $color['a'] ) ) {
			return imagecolorallocatealpha( $this->im, $color['r'], $color['g'], $color['b'], $color['a'] );
		}

		return imagecolorallocate( $this->im, $color['r'], $color['g'], $color['b'] );

	}

	/**
	 * Returns the font size in points
	 *
	 * @since   4.2.0
	 *
	 * @return  float
	 */
	protected function get_text_size_in_points() {

		return 0.75 * $this->text_size;

	}

	/**
	 * Draws a filled rectangle
	 *
	 * @since   4.2.0
	 *
	 * @param   int   $x      X Position.
	 * @param   int   $y      Y Position.
	 * @param   int   $width  Width.
	 * @param   int   $height Height.
	 * @param   mixed $color  rgba array or hex string.
	 */
	protected function draw_text_background( $x, $y, $width, $height, $color ) {

		imagefilledrectangle( $this->im, $x, $y, $x + $width, $y + $height, $this->get_color_index( $color ) );

	}

	/**
	 * Returns the bounding box of the given text, font size and font being used
	 *
	 * @since   4.2.0
	 *
	 * @param   string $text   Text.
	 * @return  array
	 */
	protected function calculate_box( $text ) {

		return imageftbbox( $this->get_text_size_in_points(), 0, $this->font_face, $text );

	}

	/**
	 * Draw the text's shadow, if specified
	 *
	 * @since   4.2.0
	 *
	 * @param   int    $x      Horizontal Starting Point.
	 * @param   int    $y      Vertical Starting Point.
	 * @param   string $text   Text to Draw.
	 */
	protected function draw_text_shadow( $x, $y, $text ) {

		$this->draw_text_on_image(
			$x + $this->text_shadow['x'],
			$y + $this->text_shadow['y'],
			$this->text_shadow['color'],
			$text
		);

	}

	/**
	 * Draw the text's stroke, if specified
	 *
	 * @since   4.2.0
	 *
	 * @param   int    $x      Horizontal Starting Point.
	 * @param   int    $y      Vertical Starting Point.
	 * @param   string $text   Text to Draw.
	 */
	protected function draw_text_stroke( $x, $y, $text ) {

		// Bail if no text stroke specified.
		if ( $this->stroke_size <= 0 ) {
			return;
		}

		$size = $this->stroke_size;

		for ( $c1 = $x - $size; $c1 <= $x + $size; $c1++ ) {
			for ( $c2 = $y - $size; $c2 <= $y + $size; $c2++ ) {
				$this->draw_text_on_image( $c1, $c2, $this->stroke_color, $text );
			}
		}

	}

	/**
	 * Draw the text
	 *
	 * @since   4.2.0
	 *
	 * @param   int    $x      Horizontal Starting Point.
	 * @param   int    $y      Vertical Starting Point.
	 * @param   string $text   Text to Draw.
	 */
	protected function draw_text( $x, $y, $text ) {

		$this->draw_text_on_image(
			$x,
			$y,
			$this->text_color,
			$text
		);

	}

	/**
	 * Draw the text on the image
	 *
	 * @since   4.2.0
	 *
	 * @param   int    $x      Horizontal Starting Point.
	 * @param   int    $y      Vertical Starting Point.
	 * @param   array  $color  RGBA Color.
	 * @param   string $text   Text to Draw.
	 */
	protected function draw_text_on_image( $x, $y, $color, $text ) {

		imagefttext(
			$this->im,
			$this->get_text_size_in_points(),
			0, // no rotation.
			(int) $x,
			(int) $y,
			$this->get_color_index( $color ),
			$this->font_face,
			$text
		);

	}
}
