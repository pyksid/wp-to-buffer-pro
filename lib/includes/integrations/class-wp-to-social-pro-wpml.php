<?php
/**
 * WPML Plugin Class.
 *
 * @package WP_To_Social_Pro
 * @author  WP Zinc
 */

/**
 * Adds WPML as a status condition, allowing statuses to be configured
 * to post / not post based on the Post's language.
 *
 * @package WP_To_Social_Pro
 * @author  WP Zinc
 * @version 5.1.2
 */
class WP_To_Social_Pro_WPML {

	/**
	 * Holds the base object.
	 *
	 * @since   5.1.2
	 *
	 * @var     object
	 */
	public $base;

	/**
	 * Constructor
	 *
	 * @since   5.1.2
	 *
	 * @param   object $base    Base Plugin Class.
	 */
	public function __construct( $base ) {

		// Store base class.
		$this->base = $base;

		// Add WPML as a status condition.
		add_filter( $this->base->plugin->filter_name . '_settings_get_default_status', array( $this, 'get_default_status' ) );

		// Output conditional field setting on statuses.
		add_action( $this->base->plugin->filter_name . '_output_condition_form_fields', array( $this, 'output_status_conditional_fields' ) );

		// Check condition is met when posting a status.
		add_action( $this->base->plugin->filter_name . '_publish_status_conditions_met', array( $this, 'check_conditions' ), 10, 3 );

	}

	/**
	 * Adds the WPML array key to the status array, if WPML is active.
	 *
	 * @since   5.1.2
	 *
	 * @param   array $status     Default Status Settings.
	 * @return  array
	 */
	public function get_default_status( $status ) {

		// Bail if WPML not active.
		if ( ! function_exists( 'wpml_get_language_information' ) ) {
			return $status;
		}

		// Add WPML defualt setting to all statuses.
		$status['wpml'] = array(
			'compare' => 0,
			'value'   => '',
		);

		return $status;

	}

	/**
	 * Outputs the WPML conditional field on statuses, if WPML is active.
	 *
	 * @since   5.1.2
	 *
	 * @param   string $post_type  Post Type.
	 */
	public function output_status_conditional_fields( $post_type ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter

		// Bail if WPML not active.
		if ( ! function_exists( 'wpml_get_language_information' ) ) {
			return;
		}

		// Get list of languages enabled in WPML.
		$languages = wpml_get_active_languages_filter( null );

		// Output condition field.
		?>
		<tr>
			<td>
				<label for="wpml_compare" data-for="wpml_compare_index">
					<?php esc_html_e( 'WPML', 'wp-to-social-pro' ); ?>
				</label>
			</td>
			<td>
				<select name="<?php echo esc_attr( $this->base->plugin->name ); ?>_wpml[compare]" id="wpml_compare" data-id="wpml_compare_index" size="1" class="widefat">
					<option value="0"><?php esc_attr_e( 'No Conditions', 'wp-to-social-pro' ); ?></option>
					<option value="="><?php esc_attr_e( 'Equals', 'wp-to-social-pro' ); ?></option>
					<option value="!="><?php esc_attr_e( 'Does not Equal', 'wp-to-social-pro' ); ?></option>
				</select>
			</td>
			<td>
				<select name="<?php echo esc_attr( $this->base->plugin->name ); ?>_wpml[value]" id="wpml_value" size="1" class="widefat">
					<option value=""><?php esc_attr_e( '(Any Language)', 'wp-to-social-pro' ); ?></option>
					<?php
					foreach ( $languages as $language_code => $language ) {
						?>
						<option value="<?php echo esc_attr( $language_code ); ?>"><?php echo esc_html( $language['native_name'] . ' (' . $language['translated_name'] . ')' ); ?></option>
						<?php
					}
					?>
				</select>
			</td>
			<td class="actions">&nbsp;</td>
		</tr>
		<?php

	}

	/**
	 * Determine the language of the Post in WPML, to decide whether to send a status.
	 *
	 * @since   5.1.2
	 *
	 * @param   bool    $conditions_met             Conditions met.
	 * @param   array   $status                     Parsed Status Message Settings.
	 * @param   WP_Post $post                       WordPress Post.
	 */
	public function check_conditions( $conditions_met, $status, $post ) {

		global $sitepress;

		// Bail if WPML isn't active.
		if ( ! function_exists( 'wpml_get_language_information' ) ) {
			return $conditions_met;
		}
		if ( is_null( $sitepress ) ) {
			return $conditions_met;
		}

		// Bail if no WPML condition exists for this status.
		if ( ! array_key_exists( 'wpml', $status ) ) {
			return $conditions_met;
		}
		if ( ! $status['wpml']['compare'] ) {
			return $conditions_met;
		}

		// Get the Post's Language.
		$post_language = wpml_get_language_information( null, $post->ID );

		// Bail if we couldn't fetch language information.
		if ( is_wp_error( $post_language ) ) {
			return $conditions_met;
		}

		// Check condition.
		switch ( $status['wpml']['compare'] ) {
			case '=':
				return ( $status['wpml']['value'] === $post_language['language_code'] );

			case '!=':
				return ( $status['wpml']['value'] !== $post_language['language_code'] );

			default:
				// Unsupported comparison method.
				return $conditions_met;
		}

	}

}
