<?php
/**
 * Cron class.
 *
 * @package WP_To_Social_Pro
 * @author WP Zinc
 */

/**
 * Functions to schedule, reschedule and unschedule events with WordPress' Cron for
 * - Log Cleanup (old log entries that are no longer needed)
 * - Media Cleanup (images auto generated by the Plugin that are no longer needed)
 * - Repost Post(s)
 *
 * @package  WP_To_Social_Pro
 * @author   WP Zinc
 * @version  3.7.2
 */
class WP_To_Social_Pro_Cron {

	/**
	 * Holds the base class object.
	 *
	 * @since   3.7.2
	 *
	 * @var     object
	 */
	public $base;

	/**
	 * Constructor
	 *
	 * @since   3.7.2
	 *
	 * @param   object $base    Base Plugin Class.
	 */
	public function __construct( $base ) {

		// Store base class.
		$this->base = $base;

	}

	/**
	 * Schedules the log cleanup event in the WordPress CRON on a daily basis
	 *
	 * @since   3.9.8
	 */
	public function schedule_log_cleanup_event() {

		// Bail if the preserve logs settings is indefinite.
		if ( ! $this->base->get_class( 'settings' )->get_setting( 'log', '[preserve_days]' ) ) {
			return;
		}

		// Bail if the scheduled event already exists.
		$scheduled_event = $this->get_log_cleanup_event();
		if ( $scheduled_event !== false ) {
			return;
		}

		// Schedule event.
		$scheduled_date_time = gmdate( 'Y-m-d', strtotime( '+1 day' ) ) . ' 00:00:00';
		wp_schedule_event( strtotime( $scheduled_date_time ), 'daily', $this->base->plugin->filter_name . '_log_cleanup_cron' );

	}

	/**
	 * Unschedules the log cleanup event in the WordPress CRON.
	 *
	 * @since   3.9.8
	 */
	public function unschedule_log_cleanup_event() {

		wp_clear_scheduled_hook( $this->base->plugin->filter_name . '_log_cleanup_cron' );

	}

	/**
	 * Reschedules the log cleanup event in the WordPress CRON, by unscheduling
	 * and scheduling it.
	 *
	 * @since   3.9.8
	 */
	public function reschedule_log_cleanup_event() {

		$this->unschedule_log_cleanup_event();
		$this->schedule_log_cleanup_event();

	}

	/**
	 * Returns the scheduled log cleanup event, if it exists
	 *
	 * @since   3.9.8
	 */
	public function get_log_cleanup_event() {

		return wp_get_schedule( $this->base->plugin->filter_name . '_log_cleanup_cron' );

	}

	/**
	 * Returns the scheduled log cleanup event's next date and time to run, if it exists
	 *
	 * @since   3.9.8
	 *
	 * @param   mixed $format     Format Timestamp (false | php date() compat. string).
	 */
	public function get_log_cleanup_event_next_scheduled( $format = false ) {

		// Get timestamp for when the event will next run.
		$scheduled = wp_next_scheduled( $this->base->plugin->filter_name . '_log_cleanup_cron' );

		// If no timestamp or we're not formatting the result, return it now.
		if ( ! $scheduled || ! $format ) {
			return $scheduled;
		}

		// Return formatted date/time.
		return date( $format, $scheduled ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
	}

	/**
	 * Schedules the media cleanup event in the WordPress CRON on a daily basis
	 *
	 * @since   4.2.0
	 */
	public function schedule_media_cleanup_event() {

		// Bail if the scheduled event already exists.
		$scheduled_event = $this->get_media_cleanup_event();
		if ( $scheduled_event !== false ) {
			return;
		}

		// Schedule event.
		$scheduled_date_time = gmdate( 'Y-m-d', strtotime( '+1 day' ) ) . ' 01:00:00';
		wp_schedule_event( strtotime( $scheduled_date_time ), 'daily', $this->base->plugin->filter_name . '_media_cleanup_cron' );

	}

	/**
	 * Unschedules the media cleanup event in the WordPress CRON.
	 *
	 * @since   4.2.0
	 */
	public function unschedule_media_cleanup_event() {

		wp_clear_scheduled_hook( $this->base->plugin->filter_name . '_media_cleanup_cron' );

	}

	/**
	 * Reschedules the media cleanup event in the WordPress CRON, by unscheduling
	 * and scheduling it.
	 *
	 * @since   4.2.0
	 */
	public function reschedule_media_cleanup_event() {

		$this->unschedule_media_cleanup_event();
		$this->schedule_media_cleanup_event();

	}

	/**
	 * Returns the scheduled media cleanup event, if it exists
	 *
	 * @since   4.2.0
	 */
	public function get_media_cleanup_event() {

		return wp_get_schedule( $this->base->plugin->filter_name . '_media_cleanup_cron' );

	}

	/**
	 * Returns the scheduled media cleanup event's next date and time to run, if it exists
	 *
	 * @since   4.2.0
	 *
	 * @param   mixed $format     Format Timestamp (false | php date() compat. string).
	 */
	public function get_media_cleanup_event_next_scheduled( $format = false ) {

		// Get timestamp for when the event will next run.
		$scheduled = wp_next_scheduled( $this->base->plugin->filter_name . '_media_cleanup_cron' );

		// If no timestamp or we're not formatting the result, return it now.
		if ( ! $scheduled || ! $format ) {
			return $scheduled;
		}

		// Return formatted date/time.
		return date( $format, $scheduled ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
	}

	/**
	 * Schedules the repost event in the WordPress CRON on an hourly basis, based on the Plugin
	 * Setting's scheduled time.
	 *
	 * @since   3.7.2
	 */
	public function schedule_repost_event() {

		// Bail if the repost cron is disabled.
		if ( $this->base->get_class( 'settings' )->get_option( 'repost_disable_cron', 0 ) ) {
			return;
		}

		// Bail if the scheduled event already exists.
		$scheduled_event = $this->get_repost_event();
		if ( $scheduled_event !== false ) {
			return;
		}

		// Schedule event.
		$scheduled_date_time = gmdate( 'Y-m-d H', strtotime( '+1 hour' ) ) . ':00:00';
		wp_schedule_event( strtotime( $scheduled_date_time ), 'hourly', $this->base->plugin->filter_name . '_repost_cron' );

	}

	/**
	 * Unschedules the repost event in the WordPress CRON.
	 *
	 * @since   3.7.2
	 */
	public function unschedule_repost_event() {

		wp_clear_scheduled_hook( $this->base->plugin->filter_name . '_repost_cron' );

	}

	/**
	 * Reschedules the repost event in the WordPress CRON, by unscheduling
	 * and scheduling it.
	 *
	 * @since   3.7.2
	 */
	public function reschedule_repost_event() {

		$this->unschedule_repost_event();
		$this->schedule_repost_event();

	}

	/**
	 * Returns the scheduled repost event, if it exists
	 *
	 * @since   3.7.2
	 */
	public function get_repost_event() {

		return wp_get_schedule( $this->base->plugin->filter_name . '_repost_cron' );

	}

	/**
	 * Returns the scheduled repost event's next date and time to run, if it exists
	 *
	 * @since   3.7.2
	 *
	 * @param   mixed $format     Format Timestamp (false | php date() compat. string).
	 */
	public function get_repost_event_next_scheduled( $format = false ) {

		// Get timestamp for when the event will next run.
		$scheduled = wp_next_scheduled( $this->base->plugin->filter_name . '_repost_cron' );

		// If no timestamp or we're not formatting the result, return it now.
		if ( ! $scheduled || ! $format ) {
			return $scheduled;
		}

		// Return formatted date/time.
		return date( $format, $scheduled ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
	}

	/**
	 * Runs the publish CRON event for the given Post ID.
	 *
	 * Calls WP_To_Social_Pro_Publish::publish() to compile statuses and send them.
	 *
	 * @since   3.7.2
	 *
	 * @param   int    $post_id    Post ID.
	 * @param   string $action     Action.
	 */
	public function publish( $post_id, $action ) {

		// Get Test Mode Flag.
		$test_mode = $this->base->get_class( 'settings' )->get_option( 'test_mode', false );

		// Send request to the API.
		$this->base->get_class( 'publish' )->publish( $post_id, $action, $test_mode );

		// Clear the log of any 'Pending' statuses (i.e. log entries telling the user that the status
		// was added to the WordPress Cron).
		$this->base->get_class( 'log' )->delete_pending_by_post_id( $post_id );

	}

	/**
	 * Runs the repost CRON event
	 *
	 * @since   3.7.2
	 *
	 * @param   bool $test_mode      Test Mode (don't send to API).
	 */
	public function repost( $test_mode = false ) {

		$this->base->get_class( 'log' )->add_to_debug_log( $this->base->plugin->displayName . ': repost(): Started' );
		$this->base->get_class( 'log' )->add_to_debug_log( $this->base->plugin->displayName . ': repost(): Test Mode: ' . $test_mode );

		// Bail if repost cron is disabled.
		// We shouldn't ever call this function if this is the case, but it's a useful sanity check.
		if ( $this->base->get_class( 'settings' )->get_setting( 'repost_disable_cron', 0 ) ) {
			$this->base->get_class( 'log' )->add_to_debug_log( $this->base->plugin->displayName . ': repost(): Stopped, as Settings > Repost Settings > Disable Repost Cron is selected' );
			return;
		}

		// Bail if no Repost Schedule exists.
		$repost_schedule = $this->base->get_class( 'settings' )->get_option( 'repost_time', false );
		if ( ! $repost_schedule ) {
			$this->base->get_class( 'log' )->add_to_debug_log( $this->base->plugin->displayName . ': repost(): Stopped, as Settings > Repost Settings > Repost Times are empty' );
			return;
		}

		// Get current day and hour based on the WordPress timezone.
		$current_timestamp = current_time( 'timestamp' ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp

		// mon,tue etc.
		$current_day = strtolower( date( 'D', $current_timestamp ) ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date

		// 00,01 etc.
		$current_hour = date( 'H', $current_timestamp ) . ':00'; // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date

		// Bail if no Repost Schedule exists for today.
		if ( ! isset( $repost_schedule[ $current_day ] ) ) {
			$this->base->get_class( 'log' )->add_to_debug_log( $this->base->plugin->displayName . ': repost(): Stopped, as Settings > Repost Settings has no schedule for Day ' . $current_day );
			return;
		}
		if ( ! $repost_schedule[ $current_day ] ) {
			$this->base->get_class( 'log' )->add_to_debug_log( $this->base->plugin->displayName . ': repost(): Stopped, as Settings > Repost Settings has no schedule for Day ' . $current_day );
			return;
		}

		// Bail if this hour isn't in the Repost Schedule.
		if ( ! in_array( $current_hour, $repost_schedule[ $current_day ], true ) ) {
			$this->base->get_class( 'log' )->add_to_debug_log( $this->base->plugin->displayName . ': repost(): Stopped, as Settings > Repost Settings is not set to run on Day ' . $current_day . ', Hour ' . $current_hour );
			return;
		}

		// If here, run the Repost action.
		$this->base->get_class( 'repost' )->run( false, $test_mode );

		$this->base->get_class( 'log' )->add_to_debug_log( $this->base->plugin->displayName . ': repost(): Finished' );

	}

	/**
	 * Runs the log cleanup CRON event
	 *
	 * @since   3.9.8
	 */
	public function log_cleanup() {

		// Bail if the preserve logs settings is indefinite.
		// We shouldn't ever call this function if this is the case, but it's a useful sanity check.
		$preserve_days = $this->base->get_class( 'settings' )->get_setting( 'log', '[preserve_days]' );
		if ( ! $preserve_days ) {
			return;
		}

		// Define the date cutoff.
		$date_time = date( 'Y-m-d H:i:s', strtotime( '-' . $preserve_days . ' days' ) ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
		// Delete log entries older than the date.
		$this->base->get_class( 'log' )->delete_by_request_sent_cutoff( $date_time );

	}

}
