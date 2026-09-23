<?php
/**
 * Common class.
 *
 * @package WPZinc\Social\Pro
 * @author WP Zinc
 */

namespace WPZinc\Social\Pro;

/**
 * Common functions that don't fit into other classes.
 *
 * @package WPZinc\Social\Pro
 * @author  WP Zinc
 * @version 3.0.0
 */
class Common {

	/**
	 * Holds the base class object.
	 *
	 * @since   3.4.7
	 *
	 * @var     object
	 */
	public $base;

	/**
	 * Constructor
	 *
	 * @since   3.4.7
	 *
	 * @param   object $base    Base Plugin Class.
	 */
	public function __construct( $base ) {

		// Store base class.
		$this->base = $base;

	}

	/**
	 * Helper method to retrieve three character day names and their full names
	 *
	 * @since   4.1.1
	 *
	 * @return  array   Days
	 */
	public function get_days() {

		// Define days.
		$days = array(
			'mon' => __( 'Monday', 'wpzinc-social' ),
			'tue' => __( 'Tuesday', 'wpzinc-social' ),
			'wed' => __( 'Wednesday', 'wpzinc-social' ),
			'thu' => __( 'Thursday', 'wpzinc-social' ),
			'fri' => __( 'Friday', 'wpzinc-social' ),
			'sat' => __( 'Saturday', 'wpzinc-social' ),
			'sun' => __( 'Sunday', 'wpzinc-social' ),
		);

		/**
		 * Defines the available days.
		 *
		 * @since   4.1.1
		 *
		 * @param   array   $days   Days.
		 */
		$days = apply_filters( $this->base->plugin->filter_name . '_get_days', $days );

		// Return filtered results.
		return $days;

	}

	/**
	 * Helper method to retrieve status post type options
	 *
	 * @since   6.0.0
	 *
	 * @return  array   Status Post Type Options
	 */
	public function get_status_post_type_options() {

		// Build status post type options.
		$status_post_type_options = array(
			'text'           => __( 'Text', 'wpzinc-social' ),
			'link'           => __( 'Link', 'wpzinc-social' ),
			'image'          => __( 'Image', 'wpzinc-social' ),
			'story'          => __( 'Story', 'wpzinc-social' ),
			'pin'            => __( 'Pin', 'wpzinc-social' ),
			'googlebusiness' => __( 'Google Business', 'wpzinc-social' ),
		);

		/**
		 * Defines the available status post type options.
		 *
		 * @since   6.0.0
		 *
		 * @param   array   $status_post_type_options   Status Post Type Options.
		 */
		$status_post_type_options = apply_filters( $this->base->plugin->filter_name . '_get_status_post_type_options', $status_post_type_options );

		// Return filtered results.
		return $status_post_type_options;

	}

	/**
	 * Helper method to retrieve schedule options
	 *
	 * @since   3.0.0
	 *
	 * @param   mixed $post_type          Post Type (false | string).
	 * @param   bool  $is_post_screen     Displaying the Post Screen.
	 * @return  array                       Schedule Options
	 */
	public function get_schedule_options( $post_type = false, $is_post_screen = false ) {

		// The immediate option is always available, regardless of Plugin.
		// Each Plugin registers its own additional schedule options via the filter below.
		$schedule = array(
			'immediate' => __( 'Post Immediately', 'wpzinc-social' ),
		);

		/**
		 * Defines the available schedule options for each individual status.
		 *
		 * @since   3.0.0
		 *
		 * @param   array   $schedule           Schedule Options.
		 * @param   string  $post_type          Post Type.
		 * @param   bool    $is_post_screen     On Post Edit Screen.
		 */
		$schedule = apply_filters( $this->base->plugin->filter_name . '_get_schedule_options', $schedule, $post_type, $is_post_screen );

		// Return filtered results.
		return $schedule;

	}

	/**
	 * Helper method to retrieve days used for the schedule relative days option
	 *
	 * @since   3.9.8
	 *
	 * @return  array   Days
	 */
	public function get_schedule_relative_days() {

		// Build days.
		$days = array(
			'today'     => __( 'Today', 'wpzinc-social' ),
			'tomorrow'  => __( 'Tomorrow', 'wpzinc-social' ),
			'monday'    => __( 'Next Monday', 'wpzinc-social' ),
			'tuesday'   => __( 'Next Tuesday', 'wpzinc-social' ),
			'wednesday' => __( 'Next Wednesday', 'wpzinc-social' ),
			'thursday'  => __( 'Next Thursday', 'wpzinc-social' ),
			'friday'    => __( 'Next Friday', 'wpzinc-social' ),
			'saturday'  => __( 'Next Saturday', 'wpzinc-social' ),
			'sunday'    => __( 'Next Sunday', 'wpzinc-social' ),
		);

		/**
		 * Defines the available days for a status' Custom Time (Relative Format) option.
		 *
		 * @since   3.9.8
		 *
		 * @param   array   $days   Days.
		 */
		$days = apply_filters( $this->base->plugin->filter_name . '_get_schedule_relative_days', $days );

		// Return filtered results.
		return $days;

	}

	/**
	 * Helper method to retrieve schedule custom relation options
	 *
	 * @since   3.3.3
	 *
	 * @return  array   Schedule Custom Relation Options
	 */
	public function get_schedule_custom_relation_options() {

		// Build schedule options.
		$schedule = array(
			'before' => __( 'Before Custom Field Value', 'wpzinc-social' ),
			'after'  => __( 'After Custom Field Value', 'wpzinc-social' ),
		);

		/**
		 * Defines the available schedule options, relative to a custom field, for each individual status.
		 *
		 * @since   3.3.3
		 *
		 * @param   array   $schedule   Schedule Options.
		 */
		$schedule = apply_filters( $this->base->plugin->filter_name . '_get_schedule_custom_relation_options', $schedule );

		// Return filtered results.
		return $schedule;

	}

	/**
	 * Helper method to retrieve Google Business Start Date options
	 *
	 * @since   4.9.0
	 *
	 * @param   mixed $post_type          Post Type (false | string).
	 * @return  array   Start Date Options
	 */
	public function get_google_business_start_date_options( $post_type = false ) {

		// Build schedule options.
		$schedule = array(
			'custom' => __( 'Custom Field / Post Meta Value', 'wpzinc-social' ),
		);

		/**
		 * Defines the available start date options for a Google Business Profile status.
		 *
		 * @since   4.9.0
		 *
		 * @param   array       $schedule   Schedule Options.
		 * @param   bool|string $post_type  Post Type (false | string).
		 */
		$schedule = apply_filters( $this->base->plugin->filter_name . '_get_google_business_start_date_options', $schedule, $post_type );

		// Return filtered results.
		return $schedule;

	}

	/**
	 * Helper method to retrieve Google Business Start Date options
	 *
	 * @since   4.9.0
	 *
	 * @param   mixed $post_type          Post Type (false | string).
	 * @return  array   End Date Options
	 */
	public function get_google_business_end_date_options( $post_type = false ) {

		// Build schedule options.
		$schedule = array(
			'custom' => __( 'Custom Field / Post Meta Value', 'wpzinc-social' ),
		);

		/**
		 * Defines the available start date options for a Google Business Profile status.
		 *
		 * @since   4.9.0
		 *
		 * @param   array       $schedule   Schedule Options.
		 * @param   bool|string $post_type  Post Type (false | string).
		 */
		$schedule = apply_filters( $this->base->plugin->filter_name . '_get_google_business_end_date_options', $schedule, $post_type );

		// Return filtered results.
		return $schedule;

	}

	/**
	 * Helper method to retrieve public Post Types
	 *
	 * @since   3.0.0
	 *
	 * @return  array   Public Post Types
	 */
	public function get_post_types() {

		// Get public Post Types.
		$types = get_post_types(
			array(
				'public' => true,
			),
			'objects'
		);

		// Filter out excluded post types.
		$excluded_types = $this->get_excluded_post_types();
		foreach ( $excluded_types as $excluded_type ) {
			unset( $types[ $excluded_type ] );
		}

		/**
		 * Defines the available Post Type Objects that can have statues defined and be sent to social media.
		 *
		 * @since   3.0.0
		 *
		 * @param   array   $types  Post Types.
		 */
		$types = apply_filters( $this->base->plugin->filter_name . '_get_post_types', $types );

		// Return filtered results.
		return $types;

	}

	/**
	 * Helper method to retrieve excluded Post Types, which should not send
	 * statuses to the API
	 *
	 * @since   3.0.0
	 *
	 * @return  array   Excluded Post Types
	 */
	public function get_excluded_post_types() {

		// Get excluded Post Types.
		$types = array(
			'attachment',
			'revision',
			'elementor_library',
		);

		/**
		 * Defines the Post Type Objects that cannot have statues defined and not be sent to social media.
		 *
		 * @since   3.0.0
		 *
		 * @param   array   $types  Post Types.
		 */
		$types = apply_filters( $this->base->plugin->filter_name . '_get_excluded_post_types', $types );

		// Return filtered results.
		return $types;

	}

	/**
	 * Helper method to retrieve excluded Taxonomies
	 *
	 * @since   3.0.5
	 *
	 * @return  array   Excluded Post Types
	 */
	public function get_excluded_taxonomies() {

		// Get excluded Post Types.
		$taxonomies = array(
			'post_format',
			'nav_menu',
		);

		/**
		 * Defines taxonomies to exclude from the Conditions: Taxonomies dropdowns for each individual status.
		 *
		 * @since   3.0.5
		 *
		 * @param   array   $taxonomies     Excluded Taxonomies.
		 */
		$taxonomies = apply_filters( $this->base->plugin->filter_name . '_get_excluded_taxonomies', $taxonomies );

		// Return filtered results.
		return $taxonomies;

	}

	/**
	 * Helper method to retrieve a Post Type's taxonomies
	 *
	 * @since   3.0.0
	 *
	 * @param   string $post_type  Post Type.
	 * @return  array               Taxonomies
	 */
	public function get_taxonomies( $post_type ) {

		// Get Post Type Taxonomies.
		$taxonomies = get_object_taxonomies( $post_type, 'objects' );

		// Get excluded Taxonomies.
		$excluded_taxonomies = $this->get_excluded_taxonomies();

		// If excluded taxonomies exist, remove them from the taxonomies array now.
		if ( count( $excluded_taxonomies ) > 0 ) {
			foreach ( $excluded_taxonomies as $excluded_taxonomy ) {
				unset( $taxonomies[ $excluded_taxonomy ] );
			}
		}

		/**
		 * Defines available taxonomies for the given Post Type, which are used in the Conditions: Taxonomies dropdowns
		 * for each individual status.
		 *
		 * @since   3.0.0
		 *
		 * @param   array   $taxonomies             Taxonomies.
		 * @param   string  $post_type              Post Type.
		 */
		$taxonomies = apply_filters( $this->base->plugin->filter_name . '_get_taxonomies', $taxonomies, $post_type );

		// Return filtered results.
		return $taxonomies;

	}

	/**
	 * Helper method to retrieve all taxonomies
	 *
	 * @since   3.6.7
	 *
	 * @return  array               Taxonomies
	 */
	public function get_all_taxonomies() {

		// Get Post Type Taxonomies.
		$taxonomies = get_taxonomies( array(), 'objects' );

		// Get excluded Taxonomies.
		$excluded_taxonomies = $this->get_excluded_taxonomies();

		// If excluded taxonomies exist, remove them from the taxonomies array now.
		if ( count( $excluded_taxonomies ) > 0 ) {
			foreach ( $excluded_taxonomies as $excluded_taxonomy ) {
				unset( $taxonomies[ $excluded_taxonomy ] );
			}
		}

		/**
		 * Defines available taxonomies, regardless of Post Type, which are used in the Conditions: Taxonomies dropdowns
		 * for each individual status.
		 *
		 * @since   3.6.7
		 *
		 * @param   array   $taxonomies             Taxonomies.
		 */
		$taxonomies = apply_filters( $this->base->plugin->filter_name . '_get_all_taxonomies', $taxonomies );

		// Return filtered results.
		return $taxonomies;

	}

	/**
	 * Helper method to retrieve all WordPress Roles
	 *
	 * @since   3.0.6
	 *
	 * @return  array   Roles
	 */
	public function get_user_roles() {

		// Define roles.
		$roles = get_editable_roles();

		// Remove excluded roles.
		$excluded_roles = $this->get_excluded_user_roles();
		foreach ( $roles as $role_name => $role ) {
			if ( in_array( $role_name, $excluded_roles, true ) ) {
				unset( $roles[ $role_name ] );
			}
		}

		/**
		 * Defines WordPress User Roles.
		 *
		 * @since   3.0.6
		 *
		 * @param   array   $roles  WordPress User Roles.
		 */
		$roles = apply_filters( $this->base->plugin->filter_name . '_get_user_roles', $roles );

		// Return filtered results.
		return $roles;

	}

	/**
	 * Helper method to retrieve all excluded WordPress Roles
	 *
	 * These roles are implied to have full access
	 *
	 * @since   3.0.6
	 *
	 * @return  array   Excluded Roles
	 */
	public function get_excluded_user_roles() {

		// Define excluded roles.
		$excluded_roles = array();

		/**
		 * Defines WordPress User Roles to exclude from Settings screens.
		 *
		 * @since   3.0.6
		 *
		 * @param   array   $excluded_roles     Excluded WordPress User Roles
		 */
		$excluded_roles = apply_filters( $this->base->plugin->filter_name . '_get_excluded_user_roles', $excluded_roles );

		// Return filtered results.
		return $excluded_roles;

	}

	/**
	 * Helper method to retrieve all repost frequency units (days, weeks etc)
	 *
	 * @since   3.7.2
	 *
	 * @return  array   Repost Frequency Units
	 */
	public function get_repost_frequency_units() {

		// Define units.
		$units = array(
			'days'   => __( 'Days', 'wpzinc-social' ),
			'months' => __( 'Months', 'wpzinc-social' ),
			'years'  => __( 'Years', 'wpzinc-social' ),
		);

		/**
		 * Defines available Reposting frequency units when defining Repost status(es).
		 *
		 * @since   3.7.2
		 *
		 * @param   array   $units  Repost Frequency Units.
		 */
		$units = apply_filters( $this->base->plugin->filter_name . '_get_repost_frequency_units', $units );

		// Return filtered results.
		return $units;

	}

	/**
	 * Helper method to retrieve available tags for status updates
	 *
	 * @since   3.0.0
	 *
	 * @param   string $post_type  Post Type.
	 * @return  array               Tags
	 */
	public function get_tags( $post_type ) {

		// Get post type.
		$post_types = $this->get_post_types();

		// Build tags array.
		$tags = array(
			'post'   => array(
				'{sitename}'              => __( 'Site Name', 'wpzinc-social' ),
				'{title}'                 => __( 'Post Title', 'wpzinc-social' ),
				'{excerpt}'               => __( 'Post Excerpt (Full)', 'wpzinc-social' ),
				'{excerpt:characters(?)}' => array(
					'question'      => __( 'Enter the maximum number of characters the Post Excerpt should display.', 'wpzinc-social' ),
					'default_value' => '150',
					'replace'       => '?',
					'label'         => __( 'Post Excerpt (Character Limited)', 'wpzinc-social' ),
				),
				'{excerpt:words(?)}'      => array(
					'question'      => __( 'Enter the maximum number of words the Post Excerpt should display.', 'wpzinc-social' ),
					'default_value' => '55',
					'replace'       => '?',
					'label'         => __( 'Post Excerpt (Word Limited)', 'wpzinc-social' ),
				),
				'{excerpt:sentences(?)}'  => array(
					'question'      => __( 'Enter the maximum number of sentences the Post Excerpt should display.', 'wpzinc-social' ),
					'default_value' => '1',
					'replace'       => '?',
					'label'         => __( 'Post Excerpt (Sentence Limited)', 'wpzinc-social' ),
				),
				'{content}'               => __( 'Post Content (Full)', 'wpzinc-social' ),
				'{content_more_tag}'      => __( 'Post Content (Up to More Tag)', 'wpzinc-social' ),
				'{content:characters(?)}' => array(
					'question'      => __( 'Enter the maximum number of characters the Post Content should display.', 'wpzinc-social' ),
					'default_value' => '150',
					'replace'       => '?',
					'label'         => __( 'Post Content (Character Limited)', 'wpzinc-social' ),
				),
				'{content:words(?)}'      => array(
					'question'      => __( 'Enter the maximum number of words the Post Content should display.', 'wpzinc-social' ),
					'default_value' => '55',
					'replace'       => '?',
					'label'         => __( 'Post Content (Word Limited)', 'wpzinc-social' ),
				),
				'{content:sentences(?)}'  => array(
					'question'      => __( 'Enter the maximum number of sentences the Post Content should display.', 'wpzinc-social' ),
					'default_value' => '1',
					'replace'       => '?',
					'label'         => __( 'Post Content (Sentence Limited)', 'wpzinc-social' ),
				),
				'{date}'                  => __( 'Post Date', 'wpzinc-social' ),
				'{url}'                   => __( 'Post URL', 'wpzinc-social' ),
				'{url_short}'             => __( 'Post URL, Shortened', 'wpzinc-social' ),
				'{id}'                    => __( 'Post ID', 'wpzinc-social' ),
			),

			'author' => array(
				'{author_user_login}'    => __( 'Author Login', 'wpzinc-social' ),
				'{author_user_nicename}' => __( 'Author Nice Name', 'wpzinc-social' ),
				'{author_user_email}'    => __( 'Author Email', 'wpzinc-social' ),
				'{author_user_url}'      => __( 'Author URL', 'wpzinc-social' ),
				'{author_display_name}'  => __( 'Author Display Name', 'wpzinc-social' ),
				'{author_field_NAME}'    => __( 'Author Meta Field', 'wpzinc-social' ),
			),
		);

		// Add any taxonomies for the given Post Type, if the Post Type exists.
		$taxonomies = array();
		if ( isset( $post_types[ $post_type ] ) ) {
			// Get taxonomies specific to the Post Type.
			$taxonomies = $this->get_taxonomies( $post_type );
		} else {
			// We're on the Bulk Publishing Settings, so return all Taxonomies.
			$taxonomies = $this->get_all_taxonomies();
		}

		if ( count( $taxonomies ) > 0 ) {
			$tags['taxonomy'] = array();

			foreach ( $taxonomies as $tax => $details ) {
				$tags['taxonomy'][ '{taxonomy_' . $tax . '}' ] = sprintf(
					/* translators: Taxonomy Name, Singular */
					__( 'Taxonomy: %s: Hashtag Format', 'wpzinc-social' ),
					$details->labels->singular_name
				);
				$tags['taxonomy'][ '{taxonomy_' . $tax . '_hashtag_retain_case}' ] = sprintf(
					/* translators: Taxonomy Name, Singular */
					__( 'Taxonomy: %s: Hashtag Format, Retaining Case', 'wpzinc-social' ),
					$details->labels->singular_name
				);
				$tags['taxonomy'][ '{taxonomy_' . $tax . '_hashtag_underscore}' ] = sprintf(
					/* translators: Taxonomy Name, Singular */
					__( 'Taxonomy: %s: Hashtag Format, Underscores', 'wpzinc-social' ),
					$details->labels->singular_name
				);
				$tags['taxonomy'][ '{taxonomy_' . $tax . '_name}' ] = sprintf(
					/* translators: Taxonomy Name, Singular */
					__( 'Taxonomy: %s: Name Format', 'wpzinc-social' ),
					$details->labels->singular_name
				);
			}
		}

		/**
		 * Defines Dynamic Status Tags that can be inserted into status(es) for the given Post Type.
		 * These tags are also added to any 'Insert Tag' dropdowns.
		 *
		 * @since   3.0.0
		 *
		 * @param   array   $tags       Dynamic Status Tags.
		 * @param   string  $post_type  Post Type.
		 */
		$tags = apply_filters( $this->base->plugin->filter_name . '_get_tags', $tags, $post_type );

		// If there are any Custom Tags defined in the Plugin Settings for this Post Type,
		// add them now.
		$existing_custom_tags = $this->base->get_class( 'settings' )->get_setting( 'custom_tags', $post_type, '' );
		if ( ! empty( $existing_custom_tags ) && is_array( $existing_custom_tags ) && isset( $existing_custom_tags['key'] ) ) {
			foreach ( $existing_custom_tags['key'] as $index => $existing_custom_tag ) {
				// Skip empty keys.
				if ( empty( $existing_custom_tag ) ) {
					continue;
				}

				// Add custom tag to array.
				$tags['post'][ '{custom_field_' . $existing_custom_tags['key'][ $index ] . '}' ] = $existing_custom_tags['label'][ $index ];
			}
		}

		// Finally, append the generic Post Custom Field tag.
		$tags['post']['{custom_field_NAME}'] = __( 'Post Meta Field', 'wpzinc-social' );

		// Return filtered results.
		return $tags;

	}

	/**
	 * Helper method to retrieve available tags for status updates, in a flattened
	 * key/value array
	 *
	 * @since   4.5.7
	 *
	 * @param   string $post_type  Post Type.
	 * @return  array               Tags
	 */
	public function get_tags_flat( $post_type ) {

		$tags_flat = array();
		foreach ( $this->get_tags( $post_type ) as $tag_group => $tag_group_tags ) {
			foreach ( $tag_group_tags as $tag => $tag_attributes ) {
				$tags_flat[] = array(
					'key'   => $tag,
					'value' => $tag,
				);
			}
		}

		return $tags_flat;

	}

	/**
	 * Helper method to retrieve Post actions
	 *
	 * @since   3.0.0
	 *
	 * @return  array           Post Actions
	 */
	public function get_post_actions() {

		// Build post actions.
		$actions = array(
			'publish'      => __( 'Publish', 'wpzinc-social' ),
			'update'       => __( 'Update', 'wpzinc-social' ),
			'repost'       => __( 'Repost', 'wpzinc-social' ),
			'bulk_publish' => __( 'Bulk Publish', 'wpzinc-social' ),
		);

		/**
		 * Defines the Post actions which trigger status(es) to be sent to social media.
		 *
		 * @since   3.0.0
		 *
		 * @param   array   $actions    Post Actions.
		 */
		$actions = apply_filters( $this->base->plugin->filter_name . '_get_post_actions', $actions );

		// Return filtered results.
		return $actions;

	}

	/**
	 * Helper method to retrieve Post actions, with labels in the past tense.
	 *
	 * @since   3.7.2
	 *
	 * @return  array           Post Actions
	 */
	public function get_post_actions_past_tense() {

		// Build post actions.
		$actions = array(
			'publish'      => __( 'Published', 'wpzinc-social' ),
			'update'       => __( 'Updated', 'wpzinc-social' ),
			'repost'       => __( 'automatically reposted by this Plugin', 'wpzinc-social' ),
			'bulk_publish' => __( 'manually bulk published using this Plugin\'s Bulk Publish functionality', 'wpzinc-social' ),
		);

		/**
		 * Defines the Post actions which trigger status(es) to be sent to social media,
		 * with labels set to the past tense.
		 *
		 * @since   3.0.0
		 *
		 * @param   array   $actions    Post Actions.
		 */
		$actions = apply_filters( $this->base->plugin->filter_name . '_get_post_actions_past_tense', $actions );

		// Return filtered results.
		return $actions;

	}

	/**
	 * Helper method to retrieve Conditional Options
	 *
	 * @since   3.2.0
	 *
	 * @return  array           Condition Options
	 */
	public function get_condition_options() {

		// Build condition options.
		$options = array(
			''            => __( 'No Conditions', 'wpzinc-social' ),
			'include_any' => __( 'Post(s) must include ANY Terms', 'wpzinc-social' ),
			'include_all' => __( 'Post(s) must include ALL Terms', 'wpzinc-social' ),
			'exclude_any' => __( 'Post(s) must exclude ANY Terms', 'wpzinc-social' ),
		);

		/**
		 * Defines the available Options for Taxonomy Terms Conditionals.
		 *
		 * @since   3.2.0
		 *
		 * @param   array   $options    Condition Options.
		 */
		$options = apply_filters( $this->base->plugin->filter_name . '_get_condition_options', $options );

		// Return filtered results.
		return $options;

	}

	/**
	 * Helper method to retrieve Post Override Options
	 *
	 * @since   3.2.5
	 *
	 * @return  array       Post Override Options
	 */
	public function get_override_options() {

		// Build condition options.
		$options = array(
			'-1' => __( 'Do NOT Post', 'wpzinc-social' ),
			'0'  => __( 'Use Plugin Settings', 'wpzinc-social' ),
			'1'  => __( 'Post using Manual Settings', 'wpzinc-social' ),
		);

		/**
		 * Defines the available override options to display in the meta box for individual Posts.
		 *
		 * @since   3.2.5
		 *
		 * @param   array   $options    Condition Options.
		 */
		$options = apply_filters( $this->base->plugin->filter_name . '_get_override_options', $options );

		// Return filtered results.
		return $options;

	}

	/**
	 * Helper method to retrieve Authors
	 *
	 * @since   3.3.8
	 *
	 * @return  array   Authors
	 */
	public function get_authors() {

		// Filter arguments to get Authors.
		$args = apply_filters(
			$this->base->plugin->filter_name . '_get_authors_args',
			array(
				'role__not_in' => 'subscriber',
			)
		);

		// Run query.
		$user_query = new \WP_User_Query( $args );

		// Get Authors.
		$authors = $user_query->get_results();

		/**
		 * Defines the available override options to display in the meta box for individual Posts.
		 *
		 * @since   3.3.8
		 *
		 * @param   array   $authors    WordPress Users.
		 */
		$authors = apply_filters( $this->base->plugin->filter_name . '_get_authors', $authors );

		// Return filtered results.
		return $authors;

	}

	/**
	 * Helper method to retrieve Post comparison operators, used for Conditional Options on status(es).
	 *
	 * @since   3.3.8
	 *
	 * @return  array   Meta Compare options
	 */
	public function get_comparison_operators() {

		// Define meta compare options.
		$comparison_operators = array(
			'='         => __( 'Equals', 'wpzinc-social' ),
			'!='        => __( 'Does not Equal', 'wpzinc-social' ),
			'>'         => __( 'Greater Than', 'wpzinc-social' ),
			'>='        => __( 'Greater Than or Equal To', 'wpzinc-social' ),
			'<'         => __( 'Less Than', 'wpzinc-social' ),
			'<='        => __( 'Less Than or Equal To', 'wpzinc-social' ),
			'IN'        => __( 'In (Comma Separated Values)', 'wpzinc-social' ),
			'NOT IN'    => __( 'Not In (Comma Separated Values)', 'wpzinc-social' ),
			'LIKE'      => __( 'Like', 'wpzinc-social' ),
			'NOT LIKE'  => __( 'Not Like', 'wpzinc-social' ),
			'EMPTY'     => __( 'Empty (Value Ignored)', 'wpzinc-social' ),
			'NOT EMPTY' => __( 'Not Empty (Value Ignored)', 'wpzinc-social' ),
		);

		/**
		 * Backward compatible filter; defines the available Post comparison operators, used for Conditional Options on status(es).
		 *
		 * @since   3.3.8
		 *
		 * @param   array   $comparison_operators    Comparison Operators.
		 */
		$comparison_operators = apply_filters( $this->base->plugin->filter_name . '_get_meta_compare', $comparison_operators );

		/**
		 * Defines the available Post comparison operators, used for Conditional Options on status(es).
		 *
		 * @since   4.0.7
		 *
		 * @param   array   $comparison_operators    Comparison Operators.
		 */
		$comparison_operators = apply_filters( $this->base->plugin->filter_name . '_get_comparison_operators', $comparison_operators );

		// Return filtered results.
		return $comparison_operators;

	}

	/**
	 * Helper method to retrieve Custom Field comparison operators, used for Conditional Options on status(es).
	 *
	 * @since   3.3.8
	 *
	 * @return  array   Meta Compare options
	 */
	public function get_custom_field_comparison_operators() {

		// Define comparison operators.
		$comparison_operators = array(
			'='          => __( 'Equals', 'wpzinc-social' ),
			'!='         => __( 'Does not Equal', 'wpzinc-social' ),
			'>'          => __( 'Greater Than', 'wpzinc-social' ),
			'>='         => __( 'Greater Than or Equal To', 'wpzinc-social' ),
			'<'          => __( 'Less Than', 'wpzinc-social' ),
			'<='         => __( 'Less Than or Equal To', 'wpzinc-social' ),
			'IN'         => __( 'In (Comma Separated Values)', 'wpzinc-social' ),
			'NOT IN'     => __( 'Not In (Comma Separated Values)', 'wpzinc-social' ),
			'LIKE'       => __( 'Like', 'wpzinc-social' ),
			'NOT LIKE'   => __( 'Not Like', 'wpzinc-social' ),
			'EMPTY'      => __( 'Empty (Value Ignored)', 'wpzinc-social' ),
			'NOT EMPTY'  => __( 'Not Empty (Value Ignored)', 'wpzinc-social' ),
			'NOT EXISTS' => __( 'Not Exists (Value Ignored)', 'wpzinc-social' ),
		);

		/**
		 * Defines the available Custom Field comparison operators, used for Conditional Options on status(es).
		 *
		 * @since   4.0.8
		 *
		 * @param   array   $comparison_operators   Comparison Operators.
		 */
		$comparison_operators = apply_filters( $this->base->plugin->filter_name . '_get_custom_field_comparison_operators', $comparison_operators );

		// Return filtered results.
		return $comparison_operators;

	}

	/**
	 * Helper method to retrieve order by options
	 *
	 * @since   1.0.0
	 *
	 * @return  array   Order By
	 */
	public function get_order_by() {

		// Define order by.
		$order_by = array(
			'date'          => __( 'Published Date', 'wpzinc-social' ),
			'ID'            => __( 'Post ID', 'wpzinc-social' ),
			'author'        => __( 'Post Author', 'wpzinc-social' ),
			'title'         => __( 'Title', 'wpzinc-social' ),
			'name'          => __( 'Post Name', 'wpzinc-social' ),
			'modified'      => __( 'Modified Date', 'wpzinc-social' ),
			'rand'          => __( 'Random', 'wpzinc-social' ),
			'comment_count' => __( 'Number of Comments', 'wpzinc-social' ),
		);

		/**
		 * Defines the available WP_Query compatible order by options.
		 *
		 * @since   1.0.0
		 *
		 * @param   array   $order_by   Order By options.
		 */
		$order_by = apply_filters( $this->base->plugin->filter_name . '_get_order_by', $order_by );

		// Return filtered results.
		return $order_by;

	}

	/**
	 * Helper method to retrieve order options
	 *
	 * @since   1.0.0
	 *
	 * @return  array   Order
	 */
	public function get_order() {

		// Define order.
		$order = array(
			'DESC' => __( 'Descending (Z-A / Newest to Oldest)', 'wpzinc-social' ),
			'ASC'  => __( 'Ascending (A-Z / Oldest to Newest)', 'wpzinc-social' ),
		);

		/**
		 * Defines the available WP_Query compatible order options.
		 *
		 * @since   1.0.0
		 *
		 * @param   array   $order   Order options.
		 */
		$order = apply_filters( $this->base->plugin->filter_name . '_get_order', $order );

		// Return filtered results.
		return $order;

	}

	/**
	 * Helper method to return template tags that cannot have a character limit applied to them.
	 *
	 * @since   3.7.8
	 *
	 * @return  array   Tags.
	 */
	public function get_tags_excluded_from_character_limit() {

		$tags = array(
			'date',
			'url',
			'id',
			'author_user_email',
			'author_user_url',
		);

		/**
		 * Defines the tags that cannot have a character limit applied to them, as doing so would
		 * wrongly concatenate data (e.g. a URL would become malformed).
		 *
		 * @since   3.7.8
		 *
		 * @param   array   $tags   Tags.
		 */
		$tags = apply_filters( $this->base->plugin->filter_name . '_get_tags_excluded_from_character_limit', $tags );

		// Return filtered results.
		return $tags;

	}

	/**
	 * Helper method to retrieve available TTF fonts for use with Text to Image
	 *
	 * @since   4.2.0
	 *
	 * @return  array   Fonts
	 */
	public function get_fonts() {

		$fonts = array(
			'Lato-Regular'         => __( 'Lato (Regular)', 'wpzinc-social' ),
			'Merriweather-Regular' => __( 'Merriweather (Regular)', 'wpzinc-social' ),
			'Montserrat-Regular'   => __( 'Montserrat (Regular)', 'wpzinc-social' ),
			'NotoSans-Regular'     => __( 'Noto Sans (Regular)', 'wpzinc-social' ),
			'OpenSans-Regular'     => sprintf(
				'%s%s',
				__( 'Open Sans (Regular', 'wpzinc-social' ),
				extension_loaded( 'imagick' ) ? __( ', with Emoji Support)', 'wpzinc-social' ) : ')'
			),
			'Oswald-Regular'       => __( 'Oswald (Regular)', 'wpzinc-social' ),
			'Raleway-Regular'      => __( 'Raleway (Regular)', 'wpzinc-social' ),
		);

		/**
		 * Defines the available TTF fonts for use with Text to Image
		 *
		 * @since   4.2.0
		 *
		 * @param   array   $fonts  Fonts.
		 */
		$fonts = apply_filters( $this->base->plugin->filter_name . '_get_fonts', $fonts );

		// Return filtered results.
		return $fonts;

	}

	/**
	 * Helper method to retrieve transient expiration time
	 *
	 * @since   3.0.0
	 *
	 * @return  int     Expiration Time (seconds)
	 */
	public function get_transient_expiration_time() {

		// Set expiration time for all transients = 1 week.
		$expiration_time = ( 7 * DAY_IN_SECONDS );

		/**
		 * Defines the number of seconds before expiring transients.
		 *
		 * @since   3.0.0
		 *
		 * @param   int     $expiration_time    Transient Expiration Time, in seconds.
		 */
		$expiration_time = apply_filters( $this->base->plugin->filter_name . '_get_transient_expiration_time', $expiration_time );

		// Return filtered results.
		return $expiration_time;

	}

	/**
	 * Helper method to remove array keys that the given WordPress User Role doesn't have access to
	 *
	 * Checks if Restrict Roles is enabled
	 *
	 * The array can be either Post Type Settings or Post Settings, as the top level keys will always
	 * be profile_ids
	 *
	 * @since   3.0.6
	 *
	 * @param   array  $arr    Post Type or Post Settings.
	 * @param   string $role   Role.
	 * @return  array           Post Type or Post Settings
	 */
	public function maybe_remove_profiles_by_role( $arr, $role ) {

		// Check if restrict roles is enabled.
		$restrict_roles = (bool) $this->base->get_class( 'settings' )->get_option( 'restrict_roles', 0 );
		if ( ! $restrict_roles ) {
			return $arr;
		}

		// Iterate through profiles, checking if the role has access to the profile.
		foreach ( $arr as $profile_id => $data ) {
			// Always grant access to default.
			if ( $profile_id === 'default' ) {
				continue;
			}

			// Get access for this role and profile combination.
			$access = (bool) $this->base->get_class( 'settings' )->get_setting( 'roles', '[' . $role . '][' . $profile_id . ']', 0 );

			// If no access, remove profile from array.
			if ( ! $access ) {
				unset( $arr[ $profile_id ] );
			}
		}

		/**
		 * Defines the number of seconds before expiring transients.
		 *
		 * @since   3.0.6
		 *
		 * @param   array   $arr    Post Type or Post Settings.
		 * @param   string  $role   WordPress Role Name.
		 */
		$arr = apply_filters( $this->base->plugin->filter_name . '_maybe_remove_profiles_by_role', $arr, $role );

		// Return filtered results.
		return $arr;

	}

	/**
	 * Helper method to remove array keys that the given WordPress User Role doesn't have access to
	 *
	 * Checks if Restrict Post Types is enabled
	 *
	 * @since   3.7.2
	 *
	 * @param   array  $post_types  Post Types.
	 * @param   string $role        Role.
	 * @return  array               Post Types
	 */
	public function maybe_remove_post_types_by_role( $post_types, $role ) {

		// Check if restrict post types is enabled.
		$restrict_post_types = (bool) $this->base->get_class( 'settings' )->get_option( 'restrict_post_types', 0 );
		if ( ! $restrict_post_types ) {
			return $post_types;
		}

		// Iterate through profiles, checking if the role has access to the profile.
		foreach ( $post_types as $post_type => $post_type_object ) {
			// Get access for this role and profile combination.
			$access = (bool) $this->base->get_class( 'settings' )->get_setting( 'roles', '[' . $role . '][' . $post_type . ']', 0 );

			// If no access, remove profile from array.
			if ( ! $access ) {
				unset( $post_types[ $post_type ] );
			}
		}

		/**
		 * Defines the number of seconds before expiring transients.
		 *
		 * @since   3.7.2
		 *
		 * @param   array   $post_types     Post Types.
		 * @param   string  $role           WordPress Role Name.
		 */
		$post_types = apply_filters( $this->base->plugin->filter_name . '_maybe_remove_post_types_by_role', $post_types, $role );

		// Return filtered results.
		return $post_types;

	}

	/**
	 * Defines the registered filters that can be used on the Log WP_List_Table
	 *
	 * @since   3.9.8
	 *
	 * @return  array   Filters
	 */
	public function get_log_filters() {

		// Define filters.
		$filters = array(
			'action',
			'profile_id',
			'result',
			'request_sent_start_date',
			'request_sent_end_date',
			'orderby',
			'order',
		);

		/**
		 * Defines the registered filters that can be used on the Log WP_List_Tables.
		 *
		 * @since   3.9.8
		 *
		 * @param   array   $filters    Filters.
		 */
		$filters = apply_filters( $this->base->plugin->filter_name . '_get_log_filters', $filters );

		// Return filtered results.
		return $filters;

	}

	/**
	 * Helper method to check if the collation of the given table is correct
	 *
	 * @since   5.5.5
	 *
	 * @param   string $table_name            Table Name.
	 * @param   string $required_collation    Required Collation.
	 * @return  bool
	 */
	public function is_table_charset_and_collation_correct( $table_name = 'options', $required_collation = 'utf8mb4' ) {

		global $wpdb;

		$result = $wpdb->get_row(
			"SHOW CREATE TABLE `{$wpdb->{$table_name}}`",
			ARRAY_A
		);

		// If no result, return true, as we can't reliably tell if the collation is correct.
		if ( ! array_key_exists( 'Create Table', $result ) || empty( $result['Create Table'] ) ) {
			return true;
		}

		// Extract the default charset and collation from the create table SQL.
		preg_match( '/DEFAULT CHARSET=([a-zA-Z0-9_]+)/i', $result['Create Table'], $default_charset );
		preg_match( '/COLLATE=([a-zA-Z0-9_]+)/i', $result['Create Table'], $default_collation );

		if ( strpos( $default_charset[1], $required_collation ) === false ) {
			return false;
		}
		if ( strpos( $default_collation[1], $required_collation ) === false ) {
			return false;
		}

		return true;

	}

}
