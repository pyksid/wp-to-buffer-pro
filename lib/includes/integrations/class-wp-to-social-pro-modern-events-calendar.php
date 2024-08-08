<?php
/**
 * Modern Events Calendar Plugin Class.
 *
 * @package WP_To_Social_Pro
 * @author  WP Zinc
 */

/**
 * Provides compatibility with Modern Events Calendar
 *
 * @package WP_To_Social_Pro
 * @author  WP Zinc
 * @version 4.9.5
 */
class WP_To_Social_Pro_Modern_Events_Calendar {

	/**
	 * Holds the base object.
	 *
	 * @since   4.9.5
	 *
	 * @var     object
	 */
	public $base;

	/**
	 * Holds the Post Type for Events.
	 *
	 * @since   4.9.5
	 *
	 * @var     string
	 */
	public $post_type = 'mec-events';

	/**
	 * Constructor
	 *
	 * @since   4.9.5
	 *
	 * @param   object $base    Base Plugin Class.
	 */
	public function __construct( $base ) {

		// Store base class.
		$this->base = $base;

		// Register Schedule Options.
		add_filter( $this->base->plugin->filter_name . '_get_schedule_options', array( $this, 'register_schedule_options' ), 10, 2 );

		// Output Schedule Options Form Fields.
		add_action( $this->base->plugin->filter_name . '_output_schedule_options_form_fields', array( $this, 'output_schedule_options_form_fields' ) );

		// Output Status Row Schedule.
		add_filter( $this->base->plugin->filter_name . '_settings_get_status_row_schedule', array( $this, 'get_status_row_schedule' ), 10, 5 );

		// Register Status Tags.
		add_filter( $this->base->plugin->filter_name . '_get_tags', array( $this, 'register_status_tags' ), 10, 2 );

		// Replace Tags with Values.
		add_filter( $this->base->plugin->filter_name . '_publish_get_all_possible_searches_replacements', array( $this, 'register_searches_replacements' ), 10, 3 );

		// Schedule Status based on Event Date.
		add_filter( $this->base->plugin->filter_name . '_publish_builds_args_schedule_mec_start_datetime', array( $this, 'schedule_status_event_start_date' ), 10, 3 );
		add_filter( $this->base->plugin->filter_name . '_publish_builds_args_schedule_mec_end_datetime', array( $this, 'schedule_status_event_end_date' ), 10, 3 );

		// Google Business Profile: Register Start and End Date options.
		add_filter( $this->base->plugin->filter_name . '_get_google_business_start_date_options', array( $this, 'register_google_business_start_date_options' ), 10, 2 );
		add_filter( $this->base->plugin->filter_name . '_get_google_business_end_date_options', array( $this, 'register_google_business_end_date_options' ), 10, 2 );

		// Google Business Profile: Define Start and End Date based on Event Date.
		add_filter( $this->base->plugin->filter_name . '_publish_parse_google_business_start_date_mec_start_datetime', array( $this, 'schedule_google_business_start_date' ), 10, 4 );
		add_filter( $this->base->plugin->filter_name . '_publish_parse_google_business_end_date_mec_end_datetime', array( $this, 'schedule_google_business_end_date' ), 10, 4 );

	}

	/**
	 * Defines the available schedule options for statuses
	 *
	 * @since   4.9.5
	 *
	 * @param   array  $schedule           Schedule Options.
	 * @param   string $post_type          Post Type.
	 */
	public function register_schedule_options( $schedule, $post_type ) {

		// Bail if Modern Events Calendar isn't active.
		if ( ! $this->is_active() ) {
			return $schedule;
		}

		// Bail if this isn't an Event Post Type.
		if ( $post_type !== $this->post_type ) {
			return $schedule;
		}

		// Add schedule options and return.
		return array_merge(
			$schedule,
			array(
				'mec_start_datetime' => __( 'Modern Events Calendar: Relative to Event Start Date', 'wp-to-social-pro' ),
				'mec_end_datetime'   => __( 'Modern Events Calendar: Relative to Event End Date', 'wp-to-social-pro' ),
			)
		);

	}

	/**
	 * Outputs schedule option settings when a schedule option belonging to Modern Events Calendar
	 * has been selected
	 *
	 * @since   4.9.5
	 *
	 * @param   string $post_type  Post Type.
	 */
	public function output_schedule_options_form_fields( $post_type ) {

		// Bail if Modern Events Calendar isn't active.
		if ( ! $this->is_active() ) {
			return;
		}

		// Bail if this isn't an Event Post Type.
		if ( $post_type !== $this->post_type ) {
			return;
		}

		// Output Modern Events Calendar specific settings.
		?>
		<span class="modern_events_calendar">
			<select name="<?php echo esc_attr( $this->base->plugin->name ); ?>_schedule_mec_relation" size="1">
				<option value="before"><?php esc_attr_e( 'Before Event Date', 'wp-to-social-pro' ); ?></option>
				<option value="after"><?php esc_attr_e( 'After Event Date', 'wp-to-social-pro' ); ?></option>
			</select> 
		</span>
		<?php

	}

	/**
	 * Returns the text to display for a status' schedule setting in the table row.
	 *
	 * @since   4.9.5
	 *
	 * @param   string $output     Output.
	 * @param   array  $status     Status.
	 * @param   string $action     Action.
	 * @param   string $post_type  Post Type.
	 * @param   array  $schedule   Schedule Options.
	 * @return  string
	 */
	public function get_status_row_schedule( $output, $status, $action, $post_type, $schedule ) {  // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter

		// Bail if Modern Events Calendar isn't active.
		if ( ! $this->is_active() ) {
			return $output;
		}

		// Bail if this isn't an Event Post Type.
		if ( $post_type !== $this->post_type ) {
			return $output;
		}

		// Define labels.
		switch ( $status['schedule_mec_relation'] ) {
			case 'before':
				$relation = __( 'before', 'wp-to-social-pro' );
				break;

			case 'after':
				$relation = __( 'after', 'wp-to-social-pro' );
				break;
		}
		switch ( $status['schedule'] ) {
			case 'mec_start_datetime':
				$label = __( 'Event Start Date', 'wp-to-social-pro' );
				break;

			case 'mec_end_datetime':
				$label = __( 'Event End Date', 'wp-to-social-pro' );
				break;
		}

		// Output.
		return sprintf(
			/* translators: %1$s: Number of Days, %2$s: Number of Hours, %3$s: Number of Minutes, %4$s: Translated 'before' or 'after' string, %5$s: Translated 'Event Start Date' or 'Event End Date' string */
			__( '%1$s days, %2$s hours, %3$s minutes %4$s %5$s', 'wp-to-social-pro' ),
			$status['days'],
			$status['hours'],
			$status['minutes'],
			$relation,
			$label
		);

	}

	/**
	 * Defines Dynamic Status Tags that can be inserted into status(es) for the given Post Type.
	 * These tags are also added to any 'Insert Tag' dropdowns.
	 *
	 * @since   4.9.5
	 *
	 * @param   array  $tags       Tags.
	 * @param   stirng $post_type  Post Type.
	 * @return  array               Tags
	 */
	public function register_status_tags( $tags, $post_type ) {

		// Bail if Modern Events Calendar isn't active.
		if ( ! $this->is_active() ) {
			return $tags;
		}

		// Bail if this isn't an Event Post Type.
		if ( $post_type !== $this->post_type ) {
			return $tags;
		}

		// Register Status Tags.
		return array_merge(
			$tags,
			array(
				'modern_events_calendar' => array(
					// Event.
					'{mec_event_start_date}'            => __( 'Event Start Date', 'wp-to-social-pro' ),
					'{mec_event_start_time}'            => __( 'Event Start Time', 'wp-to-social-pro' ),
					'{mec_event_end_date}'              => __( 'Event End Date', 'wp-to-social-pro' ),
					'{mec_event_end_time}'              => __( 'Event End Time', 'wp-to-social-pro' ),
					'{mec_event_cost}'                  => __( 'Event Cost', 'wp-to-social-pro' ),

					// Venue.
					'{mec_event_venue_name}'            => __( 'Event Venue Name', 'wp-to-social-pro' ),
					'{mec_event_venue_address}'         => __( 'Event Venue Address (Full)', 'wp-to-social-pro' ),
					'{mec_event_venue_website_url}'     => __( 'Event Venue Website URL', 'wp-to-social-pro' ),

					// Organizer.
					'{mec_event_organizer_name}'        => __( 'Event Organizer Name', 'wp-to-social-pro' ),
					'{mec_event_organizer_phone}'       => __( 'Event Organizer Phone', 'wp-to-social-pro' ),
					'{mec_event_organizer_email}'       => __( 'Event Organizer Email', 'wp-to-social-pro' ),
					'{mec_event_organizer_website_url}' => __( 'Event Organizer Website URL', 'wp-to-social-pro' ),
				),
			)
		);

	}

	/**
	 * Registers any additional status message tags, and their Post data replacements, that are supported.
	 *
	 * @since   4.9.5
	 *
	 * @param   array   $searches_replacements  Registered Supported Tags and their Replacements.
	 * @param   WP_Post $post                   WordPress Post.
	 * @param   WP_User $author                 WordPress User (Author of the Post).
	 * @return  array                               Registered Supported Tags and their Replacements
	 */
	public function register_searches_replacements( $searches_replacements, $post, $author ) {

		// Bail if Modern Events Calendar isn't active.
		if ( ! $this->is_active() ) {
			return $searches_replacements;
		}

		// Bail if this Post isn't an Event.
		if ( $post->post_type !== $this->post_type ) {
			return $searches_replacements;
		}

		// Register Tags and their replacement values.
		return array_merge( $searches_replacements, $this->get_searches_replacements( $post, $author ) );

	}

	/**
	 * Returns tags and their Post data replacements, that are supported for Modern Events Calendar.
	 *
	 * @since   4.9.5
	 *
	 * @param   WP_Post $post                   WordPress Post.
	 * @param   WP_User $author                 WordPress User (Author of the Post).
	 * @return  array                               Registered Supported Tags and their Replacements
	 */
	private function get_searches_replacements( $post, $author ) {

		$searches_replacements = array(
			// Event.
			'mec_event_start_date'            => date_i18n( get_option( 'date_format' ), strtotime( get_post_meta( $post->ID, 'mec_start_date', true ) ) ),
			'mec_event_start_time'            => get_post_meta( $post->ID, 'mec_start_time_hour', true ) . ':' . str_pad( get_post_meta( $post->ID, 'mec_start_time_minutes', true ), 2, '0', STR_PAD_LEFT ) . get_post_meta( $post->ID, 'mec_start_time_ampm', true ),
			'mec_event_end_date'              => date_i18n( get_option( 'date_format' ), strtotime( get_post_meta( $post->ID, 'mec_end_date', true ) ) ),
			'mec_event_end_time'              => get_post_meta( $post->ID, 'mec_end_time_hour', true ) . ':' . str_pad( get_post_meta( $post->ID, 'mec_end_time_minutes', true ), 2, '0', STR_PAD_LEFT ) . get_post_meta( $post->ID, 'mec_end_time_ampm', true ),
			'mec_event_cost'                  => get_post_meta( $post->ID, 'mec_cost', true ),

			// Venue.
			'mec_event_venue_name'            => '',
			'mec_event_venue_address'         => '',
			'mec_event_venue_website_url'     => '',

			// Organizer.
			'mec_event_organizer_name'        => '',
			'mec_event_organizer_phone'       => '',
			'mec_event_organizer_email'       => '',
			'mec_event_organizer_website_url' => '',
		);

		// Get Venue.
		$event_venue_id = get_post_meta( $post->ID, 'mec_location_id', true );
		if ( $event_venue_id ) {
			$venue = get_term( $event_venue_id, 'mec_location', 'ARRAY_A' );
			if ( is_array( $venue ) ) {
				$searches_replacements = array_merge(
					$searches_replacements,
					array(
						'mec_event_venue_name'        => $venue['name'],
						'mec_event_venue_address'     => get_term_meta( $event_venue_id, 'address', true ),
						'mec_event_venue_website_url' => get_term_meta( $event_venue_id, 'url', true ),
					)
				);
			}
		}

		// Get Organizer.
		$event_organizer_id = get_post_meta( $post->ID, 'mec_organizer_id', true );
		if ( $event_organizer_id ) {
			$organizer = get_term( $event_organizer_id, 'mec_organizer', 'ARRAY_A' );
			if ( is_array( $organizer ) ) {
				$searches_replacements = array_merge(
					$searches_replacements,
					array(
						'mec_event_organizer_name'        => $organizer['name'],
						'mec_event_organizer_phone'       => get_term_meta( $event_organizer_id, 'tel', true ),
						'mec_event_organizer_email'       => get_term_meta( $event_organizer_id, 'email', true ),
						'mec_event_organizer_website_url' => get_term_meta( $event_organizer_id, 'url', true ),
					)
				);
			}
		}

		/**
		 * Registers any additional status message tags, and their Post data replacements, that are supported
		 * for Modern Events Calendar
		 *
		 * @since   4.1.2
		 *
		 * @param   array       $searches_replacements  Registered Supported Tags and their Replacements.
		 * @param   WP_Post     $post                   WordPress Post.
		 * @param   WP_User     $author                 WordPress User (Author of the Post).
		 */
		$searches_replacements = apply_filters( $this->base->plugin->filter_name . '_publish_register_modern_events_calendar_searches_replacements', $searches_replacements, $post, $author );

		return $searches_replacements;

	}

	/**
	 * Define the UTC date and time for the status to be published when the status' schedule is set to use the Event's Start Date
	 *
	 * @since   4.9.5
	 *
	 * @param   string  $scheduled_at   Schedule Status (yyyy-mm-dd hh:mm:ss format).
	 * @param   array   $status         Status.
	 * @param   WP_Post $post           WordPress Post.
	 * @return  string                  UTC Date and Time
	 */
	public function schedule_status_event_start_date( $scheduled_at, $status, $post ) {

		// Bail if Modern Events Calendar isn't active.
		if ( ! $this->is_active() ) {
			return $scheduled_at;
		}

		// Get adjusted date and time.
		$date_time = $this->base->get_class( 'date' )->adjust_date_time(
			get_post_meta( $post->ID, 'mec_start_datetime', true ),
			$status['schedule_mec_relation'],
			$status['days'],
			$status['hours'],
			$status['minutes']
		);

		// Return UTC date and time.
		return $this->base->get_class( 'date' )->get_utc_date_time( $date_time );

	}

	/**
	 * Define the UTC date and time for the status to be published when the status' schedule is set to use the Event's End Date
	 *
	 * @since   4.9.5
	 *
	 * @param   string  $scheduled_at   Schedule Status (yyyy-mm-dd hh:mm:ss format).
	 * @param   array   $status         Status.
	 * @param   WP_Post $post           WordPress Post.
	 * @return  string                  UTC Date and Time
	 */
	public function schedule_status_event_end_date( $scheduled_at, $status, $post ) {

		// Bail if Modern Events Calendar isn't active.
		if ( ! $this->is_active() ) {
			return $scheduled_at;
		}

		$date_time = $this->base->get_class( 'date' )->adjust_date_time(
			get_post_meta( $post->ID, '_EventEndDate', true ),
			$status['schedule_mec_relation'],
			$status['days'],
			$status['hours'],
			$status['minutes']
		);

		// Return UTC date and time.
		return $this->base->get_class( 'date' )->get_utc_date_time( $date_time );

	}

	/**
	 * Defines the available schedule options for statuses
	 *
	 * @since   4.9.5
	 *
	 * @param   array  $schedule           Schedule Options.
	 * @param   string $post_type          Post Type.
	 */
	public function register_google_business_start_date_options( $schedule, $post_type ) {

		// Bail if Modern Events Calendar isn't active.
		if ( ! $this->is_active() ) {
			return $schedule;
		}

		// Bail if this isn't an Event Post Type.
		if ( $post_type !== $this->post_type ) {
			return $schedule;
		}

		// Add schedule options and return.
		return array_merge(
			$schedule,
			array(
				'mec_start_datetime' => __( 'Modern Events Calendar: Start Date', 'wp-to-social-pro' ),
			)
		);

	}

	/**
	 * Defines the available schedule options for statuses
	 *
	 * @since   4.9.5
	 *
	 * @param   array  $schedule           Schedule Options.
	 * @param   string $post_type          Post Type.
	 */
	public function register_google_business_end_date_options( $schedule, $post_type ) {

		// Bail if Modern Events Calendar isn't active.
		if ( ! $this->is_active() ) {
			return $schedule;
		}

		// Bail if this isn't an Event Post Type.
		if ( $post_type !== $this->post_type ) {
			return $schedule;
		}

		// Add schedule options and return.
		return array_merge(
			$schedule,
			array(
				'_EventEndDate' => __( 'Modern Events Calendar: End Date', 'wp-to-social-pro' ),
			)
		);

	}

	/**
	 * Define a Google Business Profile status' start date to be the Event's start date.
	 *
	 * @since   4.9.5
	 *
	 * @param   bool|string $date                   Date (yyyy-mm-dd hh:mm:ss format).
	 * @param   array       $google_business_args   Google Business specific arguments for status.
	 * @param   array       $status                 Status.
	 * @param   WP_Post     $post                   WordPress Post.
	 * @return  string                              Date
	 */
	public function schedule_google_business_start_date( $date, $google_business_args, $status, $post ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter

		// Bail if Modern Events Calendar isn't active.
		if ( ! $this->is_active() ) {
			return $date;
		}

		return get_post_meta( $post->ID, 'mec_start_datetime', true );

	}

	/**
	 * Define a Google Business Profile status' end date to be the Event's start date.
	 *
	 * @since   4.9.5
	 *
	 * @param   bool|string $date                   Date (yyyy-mm-dd hh:mm:ss format).
	 * @param   array       $google_business_args   Google Business specific arguments for status.
	 * @param   array       $status                 Status.
	 * @param   WP_Post     $post                   WordPress Post.
	 * @return  string                              Date
	 */
	public function schedule_google_business_end_date( $date, $google_business_args, $status, $post ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter

		// Bail if Modern Events Calendar isn't active.
		if ( ! $this->is_active() ) {
			return $date;
		}

		return get_post_meta( $post->ID, '_EventEndDate', true );

	}

	/**
	 * Checks if the Modern Events Calendar Plugin is active
	 *
	 * @since   4.9.5
	 *
	 * @return  bool    Modern Events Calendar Plugin Active
	 */
	private function is_active() {

		return class_exists( 'MEC' );

	}

}
