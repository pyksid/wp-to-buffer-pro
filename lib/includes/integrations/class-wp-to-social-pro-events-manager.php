<?php
/**
 * Events Manager Plugin Class.
 *
 * @package WP_To_Social_Pro
 * @author  WP Zinc
 */

/**
 * Provides compatibility with Events Manager
 *
 * @package WP_To_Social_Pro
 * @author  WP Zinc
 * @version 4.3.8
 */
class WP_To_Social_Pro_Events_Manager {

	/**
	 * Holds the base object.
	 *
	 * @since   4.3.8
	 *
	 * @var     object
	 */
	public $base;

	/**
	 * Constructor
	 *
	 * @since   4.3.8
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
		add_filter( $this->base->plugin->filter_name . '_publish_builds_args_schedule__event_start_date', array( $this, 'schedule_status_event_start_date' ), 10, 3 );
		add_filter( $this->base->plugin->filter_name . '_publish_builds_args_schedule__event_end_date', array( $this, 'schedule_status_event_end_date' ), 10, 3 );

		// Google Business Profile: Register Start and End Date options.
		add_filter( $this->base->plugin->filter_name . '_get_google_business_start_date_options', array( $this, 'register_google_business_start_date_options' ), 10, 2 );
		add_filter( $this->base->plugin->filter_name . '_get_google_business_end_date_options', array( $this, 'register_google_business_end_date_options' ), 10, 2 );

		// Google Business Profile: Define Start and End Date based on Event Date.
		add_filter( $this->base->plugin->filter_name . '_publish_parse_google_business_start_date__event_start_local', array( $this, 'schedule_google_business_start_date' ), 10, 4 );
		add_filter( $this->base->plugin->filter_name . '_publish_parse_google_business_end_date__event_end_local', array( $this, 'schedule_google_business_end_date' ), 10, 4 );

	}

	/**
	 * Defines the available schedule options for statuses
	 *
	 * @since   4.4.0
	 *
	 * @param   array  $schedule           Schedule Options.
	 * @param   string $post_type          Post Type.
	 * @return  array                      Schedule Options
	 */
	public function register_schedule_options( $schedule, $post_type ) {

		// Bail if Events Manager isn't active.
		if ( ! $this->is_active() ) {
			return $schedule;
		}

		// Bail if this isn't an Event Post Type.
		if ( $post_type !== 'event' ) {
			return $schedule;
		}

		// Add schedule options and return.
		return array_merge(
			$schedule,
			array(
				'_event_start_date' => __( 'Events Manager: Relative to Event Start Date', 'wp-to-social-pro' ),
				'_event_end_date'   => __( 'Events Manager: Relative to Event End Date', 'wp-to-social-pro' ),
			)
		);

	}

	/**
	 * Outputs schedule option settings when a schedule option belonging to Events Manager
	 * has been selected
	 *
	 * @since   4.4.0
	 *
	 * @param   string $post_type  Post Type.
	 */
	public function output_schedule_options_form_fields( $post_type ) {

		// Bail if Events Manager isn't active.
		if ( ! $this->is_active() ) {
			return;
		}

		// Bail if this isn't an Event Post Type.
		if ( $post_type !== 'event' ) {
			return;
		}

		// Output Events Manager specific settings.
		?>
		<span class="events_manager">
			<select name="<?php echo esc_attr( $this->base->plugin->name ); ?>_schedule_em_relation" size="1">
				<option value="before"><?php esc_attr_e( 'Before Event Date', 'wp-to-social-pro' ); ?></option>
				<option value="after"><?php esc_attr_e( 'After Event Date', 'wp-to-social-pro' ); ?></option>
			</select> 
		</span>
		<?php

	}

	/**
	 * Returns the text to display for a status' schedule setting in the table row.
	 *
	 * @since   4.4.0
	 *
	 * @param   string $output     Output.
	 * @param   array  $status     Status.
	 * @param   string $action     Action.
	 * @param   string $post_type  Post Type.
	 * @param   array  $schedule   Schedule Options.
	 * @return  string
	 */
	public function get_status_row_schedule( $output, $status, $action, $post_type, $schedule ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter

		// Bail if Events Manager isn't active.
		if ( ! $this->is_active() ) {
			return $output;
		}

		// Bail if this isn't an Event Post Type.
		if ( $post_type !== 'event' ) {
			return $output;
		}

		// Define labels.
		switch ( $status['schedule_em_relation'] ) {
			case 'before':
				$relation = __( 'before', 'wp-to-social-pro' );
				break;

			case 'after':
				$relation = __( 'after', 'wp-to-social-pro' );
				break;
		}
		switch ( $status['schedule'] ) {
			case '_event_start_date':
				$label = __( 'Event Start Date', 'wp-to-social-pro' );
				break;

			case '_event_end_date':
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
	 * @since   4.3.8
	 *
	 * @param   array  $tags       Tags.
	 * @param   stirng $post_type  Post Type.
	 * @return  array               Tags
	 */
	public function register_status_tags( $tags, $post_type ) {

		// Bail if Events Manager isn't active.
		if ( ! $this->is_active() ) {
			return $tags;
		}

		// Depending on the Post Type, register Status Tags.
		switch ( $post_type ) {
			case 'event':
				return array_merge(
					$tags,
					array(
						'events_manager' => array(
							'{em_event_start_date}'      => __( 'Event Start Date', 'wp-to-social-pro' ),
							'{em_event_start_time}'      => __( 'Event Start Time', 'wp-to-social-pro' ),
							'{em_event_end_date}'        => __( 'Event End Date', 'wp-to-social-pro' ),
							'{em_event_end_time}'        => __( 'Event End Time', 'wp-to-social-pro' ),
							'{em_location}'              => __( 'Event Location (Full)', 'wp-to-social-pro' ),
							'{em_location_name}'         => __( 'Event Location Name', 'wp-to-social-pro' ),
							'{em_location_address}'      => __( 'Event Location Address (Full)', 'wp-to-social-pro' ),
							'{em_location_address_only}' => __( 'Event Location Address', 'wp-to-social-pro' ),
							'{em_location_town}'         => __( 'Event Location Town', 'wp-to-social-pro' ),
							'{em_location_state}'        => __( 'Event Location State', 'wp-to-social-pro' ),
							'{em_location_postcode}'     => __( 'Event Location Postcode', 'wp-to-social-pro' ),
							'{em_location_region}'       => __( 'Event Location Region', 'wp-to-social-pro' ),
							'{em_location_country}'      => __( 'Event Location Country', 'wp-to-social-pro' ),
							'{em_location_url}'          => __( 'Event Location URL', 'wp-to-social-pro' ),
						),
					)
				);

			case 'location':
				return array_merge(
					$tags,
					array(
						'events_manager' => array(
							'{em_location}'              => __( 'Event Location (Full)', 'wp-to-social-pro' ),
							'{em_location_name}'         => __( 'Event Location Name', 'wp-to-social-pro' ),
							'{em_location_address}'      => __( 'Event Location Address (Full)', 'wp-to-social-pro' ),
							'{em_location_address_only}' => __( 'Event Location Address', 'wp-to-social-pro' ),
							'{em_location_town}'         => __( 'Event Location Town', 'wp-to-social-pro' ),
							'{em_location_state}'        => __( 'Event Location State', 'wp-to-social-pro' ),
							'{em_location_postcode}'     => __( 'Event Location Postcode', 'wp-to-social-pro' ),
							'{em_location_region}'       => __( 'Event Location Region', 'wp-to-social-pro' ),
							'{em_location_country}'      => __( 'Event Location Country', 'wp-to-social-pro' ),
							'{em_location_url}'          => __( 'Event Location URL', 'wp-to-social-pro' ),
						),
					)
				);
		}

		return $tags;

	}

	/**
	 * Registers any additional status message tags, and their Post data replacements, that are supported.
	 *
	 * @since   4.3.8
	 *
	 * @param   array   $searches_replacements  Registered Supported Tags and their Replacements.
	 * @param   WP_Post $post                   WordPress Post.
	 * @param   WP_User $author                 WordPress User (Author of the Post).
	 * @return  array                               Registered Supported Tags and their Replacements
	 */
	public function register_searches_replacements( $searches_replacements, $post, $author ) {

		// Bail if Events Manager isn't active.
		if ( ! $this->is_active() ) {
			return $searches_replacements;
		}

		// Depending on the Post Type, register searches and replacements.
		switch ( $post->post_type ) {
			case 'event':
				return array_merge( $searches_replacements, $this->get_searches_replacements_event( $post, $author ) );

			case 'location':
				return array_merge( $searches_replacements, $this->get_searches_replacements_location( $post->ID ) );
		}

		return $searches_replacements;

	}

	/**
	 * Returns tags and their Post data replacements for Events.
	 *
	 * @since   4.3.8
	 *
	 * @param   WP_Post $post                   WordPress Post.
	 * @param   WP_User $author                 WordPress User (Author of the Post).
	 * @return  array                               Registered Supported Tags and their Replacements
	 */
	private function get_searches_replacements_event( $post, $author ) {

		// Register Event Search/Replacements.
		$event = em_get_event( $post->ID );

		// Get local date and time, as using $event->start() and $event->end() returns UTC
		// and seems to ignore the timezone specified on the Event.
		$start = date_create_from_format( 'Y-m-d H:i:s', get_post_meta( $post->ID, '_event_start_local', true ) );
		$end   = date_create_from_format( 'Y-m-d H:i:s', get_post_meta( $post->ID, '_event_end_local', true ) );

		// Start building searches and replacements.
		$searches_replacements = array(
			'em_event_start_date'      => $start->format( get_option( 'dbem_date_format' ) ),
			'em_event_start_time'      => $start->format( get_option( 'dbem_time_format' ) ),
			'em_event_end_date'        => $end->format( get_option( 'dbem_date_format' ) ),
			'em_event_end_time'        => $end->format( get_option( 'dbem_time_format' ) ),
			'em_location'              => '',
			'em_location_name'         => '',
			'em_location_address'      => '',
			'em_location_address_only' => '',
			'em_location_town'         => '',
			'em_location_state'        => '',
			'em_location_postcode'     => '',
			'em_location_region'       => '',
			'em_location_country'      => '',
			'em_location_url'          => '',
		);

		// Return if there's no location attached to the event.
		$location_id = ( ! $event->location_id ? get_post_meta( $post->ID, '_location_id', true ) : $event->location_id );
		if ( ! $location_id ) {
			return $searches_replacements;
		}

		// Get location.
		$location = em_get_location( $location_id );

		// Register location searches and replacements.
		$searches_replacements['em_location']              = $location->location_name . ', ' . $location->get_full_address();
		$searches_replacements['em_location_name']         = $location->location_name;
		$searches_replacements['em_location_address']      = $location->get_full_address();
		$searches_replacements['em_location_address_only'] = $location->location_address;
		$searches_replacements['em_location_town']         = $location->location_town;
		$searches_replacements['em_location_state']        = $location->location_state;
		$searches_replacements['em_location_postcode']     = $location->location_postcode;
		$searches_replacements['em_location_region']       = $location->location_region;
		$searches_replacements['em_location_country']      = $location->get_country();
		$searches_replacements['em_location_url']          = $location->get_permalink();

		/**
		 * Registers any additional status message tags, and their Post data replacements, that are supported
		 * for Events Manager
		 *
		 * @since   4.1.2
		 *
		 * @param   array       $searches_replacements  Registered Supported Tags and their Replacements.
		 * @param   WP_Post     $post                   WordPress Post.
		 * @param   WP_User     $author                 WordPress User (Author of the Post).
		 */
		$searches_replacements = apply_filters( $this->base->plugin->filter_name . '_publish_register_events_manager_searches_replacements', $searches_replacements, $post, $author );

		return $searches_replacements;

	}

	/**
	 * Returns tags and their Post data replacements for Locations.
	 *
	 * @since   4.8.0
	 *
	 * @param   int $location_id    WordPress Location Post ID.
	 * @return  array                   Registered Supported Tags and their Replacements
	 */
	private function get_searches_replacements_location( $location_id ) {

		// Define searches and replacements array.
		$searches_replacements = array(
			'em_location'              => '',
			'em_location_name'         => '',
			'em_location_address'      => '',
			'em_location_address_only' => '',
			'em_location_town'         => '',
			'em_location_state'        => '',
			'em_location_postcode'     => '',
			'em_location_region'       => '',
			'em_location_country'      => '',
			'em_location_url'          => '',
		);

		// Get location.
		$location = new EM_Location( $location_id, 'post_id' );

		// Add event location search/replacements.
		$searches_replacements['em_location']              = $location->location_name . ', ' . $location->get_full_address();
		$searches_replacements['em_location_name']         = $location->location_name;
		$searches_replacements['em_location_address']      = $location->get_full_address();
		$searches_replacements['em_location_address_only'] = $location->location_address;
		$searches_replacements['em_location_town']         = $location->location_town;
		$searches_replacements['em_location_state']        = $location->location_state;
		$searches_replacements['em_location_postcode']     = $location->location_postcode;
		$searches_replacements['em_location_region']       = $location->location_region;
		$searches_replacements['em_location_country']      = $location->get_country();
		$searches_replacements['em_location_url']          = $location->get_permalink();

		// Return.
		return $searches_replacements;

	}

	/**
	 * Define the date and time for the status to be published when the status' schedule is set to use the Event's Start Date
	 *
	 * @since   4.6.9
	 *
	 * @param   string  $scheduled_at   Schedule Status (yyyy-mm-dd hh:mm:ss format).
	 * @param   array   $status         Status.
	 * @param   WP_Post $post           WordPress Post.
	 * @return  string                  UTC Date and Time
	 */
	public function schedule_status_event_start_date( $scheduled_at, $status, $post ) {

		// Bail if Events Manager isn't active.
		if ( ! $this->is_active() ) {
			return $scheduled_at;
		}

		// Get adjusted date and time.
		$date_time = $this->base->get_class( 'date' )->adjust_date_time(
			get_post_meta( $post->ID, '_event_start_local', true ),
			$status['schedule_em_relation'],
			$status['days'],
			$status['hours'],
			$status['minutes']
		);

		// Return UTC date and time.
		return $this->base->get_class( 'date' )->get_utc_date_time( $date_time );

	}

	/**
	 * Define the date and time for the status to be published when the status' schedule is set to use the Event's End Date
	 *
	 * @since   4.6.9
	 *
	 * @param   string  $scheduled_at   Schedule Status (yyyy-mm-dd hh:mm:ss format).
	 * @param   array   $status         Status.
	 * @param   WP_Post $post           WordPress Post.
	 * @return  string                  UTC Date and Time
	 */
	public function schedule_status_event_end_date( $scheduled_at, $status, $post ) {

		// Bail if Events Manager isn't active.
		if ( ! $this->is_active() ) {
			return $scheduled_at;
		}

		// Get adjusted date and time.
		$date_time = $this->base->get_class( 'date' )->adjust_date_time(
			get_post_meta( $post->ID, '_event_end_local', true ),
			$status['schedule_em_relation'],
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
	 * @since   4.9.0
	 *
	 * @param   array  $schedule           Schedule Options.
	 * @param   string $post_type          Post Type.
	 */
	public function register_google_business_start_date_options( $schedule, $post_type ) {

		// Bail if Events Manager isn't active.
		if ( ! $this->is_active() ) {
			return $schedule;
		}

		// Bail if this isn't an Event Post Type.
		if ( $post_type !== 'event' ) {
			return $schedule;
		}

		// Add schedule options and return.
		return array_merge(
			$schedule,
			array(
				'_event_start_local' => __( 'Events Manager: Start Date', 'wp-to-social-pro' ),
			)
		);

	}

	/**
	 * Defines the available schedule options for statuses
	 *
	 * @since   4.9.0
	 *
	 * @param   array  $schedule           Schedule Options.
	 * @param   string $post_type          Post Type.
	 */
	public function register_google_business_end_date_options( $schedule, $post_type ) {

		// Bail if Events Manager isn't active.
		if ( ! $this->is_active() ) {
			return $schedule;
		}

		// Bail if this isn't an Event Post Type.
		if ( $post_type !== 'event' ) {
			return $schedule;
		}

		// Add schedule options and return.
		return array_merge(
			$schedule,
			array(
				'_event_end_local' => __( 'Event Manager: End Date', 'wp-to-social-pro' ),
			)
		);

	}

	/**
	 * Define a Google Business Profile status' start date to be the Event's start date.
	 *
	 * @since   4.9.0
	 *
	 * @param   bool|string $date                   Date (yyyy-mm-dd hh:mm:ss format).
	 * @param   array       $google_business_args   Google Business specific arguments for status.
	 * @param   array       $status                 Status.
	 * @param   WP_Post     $post                   WordPress Post.
	 * @return  string                              Date
	 */
	public function schedule_google_business_start_date( $date, $google_business_args, $status, $post ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter

		// Bail if Events Manager isn't active.
		if ( ! $this->is_active() ) {
			return $date;
		}

		return get_post_meta( $post->ID, '_event_start_local', true );

	}

	/**
	 * Define a Google Business Profile status' end date to be the Event's start date.
	 *
	 * @since   4.9.0
	 *
	 * @param   bool|string $date                   Date (yyyy-mm-dd hh:mm:ss format).
	 * @param   array       $google_business_args   Google Business specific arguments for status.
	 * @param   array       $status                 Status.
	 * @param   WP_Post     $post                   WordPress Post.
	 * @return  string                              Date
	 */
	public function schedule_google_business_end_date( $date, $google_business_args, $status, $post ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter

		// Bail if Events Manager isn't active.
		if ( ! $this->is_active() ) {
			return $date;
		}

		return get_post_meta( $post->ID, '_event_end_local', true );

	}

	/**
	 * Checks if the Events Manager Plugin is active
	 *
	 * @since   4.3.8
	 *
	 * @return  bool    Events Manager Plugin Active
	 */
	private function is_active() {

		return class_exists( 'EM_Event' );

	}

}
