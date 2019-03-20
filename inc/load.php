<?php
/**
 * Plugin load class.
 *
 * @author   ThimPress
 * @package  LearnPress/BuddyPress/Classes
 * @version  3.0.0
 */

// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LP_Addon_BuddyPress' ) ) {
	/**
	 * Class LP_Addon_BuddyPress.
	 */
	class LP_Addon_BuddyPress extends LP_Addon {

		/**
		 * LP_Addon_BuddyPress constructor.
		 */
		public function __construct() {
			if ( ! $this->buddypress_is_active() ) {
				add_action( 'admin_notices', array( $this, 'admin_notices' ) );
			} else {
				parent::__construct();
				add_action( 'wp_enqueue_scripts', array( $this, 'wp_assets' ) );
			}
		}

		/**
		 * Define constants.
		 */
		protected function _define_constants() {
			define( 'LP_ADDON_BUDDYPRESS_PATH', dirname( LP_ADDON_BUDDYPRESS_FILE ) );
			define( 'LP_ADDON_BUDDYPRESS_TEMPLATE', LP_ADDON_BUDDYPRESS_PATH . '/templates/' );
		}

		/**
		 * Includes.
		 */
		protected function _includes() {
			include_once LP_ADDON_BUDDYPRESS_PATH . '/inc/functions.php';
		}

		/**
		 * Init hooks.
		 */
		protected function _init_hooks() {
			add_action( 'wp_loaded', array( $this, 'bp_add_new_item' ) );
			add_action( 'bp_setup_admin_bar', array( $this, 'bp_setup_courses_bar' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ), 0 );
			add_action( 'learn_press_update_order_status', array( $this, 'enroll_course_activity_update' ), 10, 2 );
			add_action( 'learn-press/user-course-finished', array( $this, 'pass_course_activity_update' ), 10, 3 );
			add_action( 'learn-press/user/quiz-started', array( $this, 'start_quiz_activity_update' ), 10, 3 );
			add_action( 'learn-press/user/quiz-finished', array( $this, 'finish_quiz_activity_update' ), 10, 3 );


			add_filter( 'mycred_add_finished', array( $this, 'credit_points_activity_update' ), 10, 3 );
			add_filter( 'learnpress_get_user_badge', array( $this, 'make_mycred_badge_a_link' ), 10, 4 );
			add_filter( 'learn-press/profile/quizzes-filters', array( $this, 'change_quizzes_filters' ) );
			add_filter( 'etbi_user_links', array( $this, 'etbi_add_profile_links' ), 10, 1 );
			// add_filter( 'bp_notifications_get_registered_components',array( $this, 'pipeline_notifications_get_registered_components' ), 10, 1 );
			// add_filter( 'bp_notifications_get_notifications_for_user', array( $this,  'pipeline_format_buddypress_notifications' ), 10, 5 );

			add_action( 'wp_ajax_get_user_progress', 'learn_press_pick_user_date_range' );
		}

		/**
		 * Add new item.
		 */
		public function bp_add_new_item() {
			global $bp;

			$user_id = bp_displayed_user_id();
			$url = bp_core_get_user_domain( $user_id );

			// array(
			// 			'name'                    => __( 'Orders', 'learnpress-buddypress' ),
			// 			'slug'                    => $this->get_tab_orders_slug(),
			// 			'show_for_displayed_user' => false,
			// 			'screen_function'         => array( $this, 'bp_tab_content' ),
			// 			'default_subnav_slug'     => 'all',
			// 			'position'                => 100
			// 	),
			// array(
			// 	'name'                    	=> __( 'Points', 'learnpress-buddypress' ),
			// 	'slug'                    	=> 'points',
			// 	'show_for_displayed_user' 	=> false, //( $this->mycred_is_active() && learn_press_buddypress_user_can_view() ),
			// 	'screen_function'         	=> array( $this, 'bp_tab_content' ),
			// 	'default_subnav_slug'     	=> 'all',
			// 	'position'                	=> 70
			// )
			$tabs = apply_filters( 'learn-press/buddypress/profile-tabs', array(
					array(
						'name'                    	=> __( 'Courses', 'learnpress-buddypress' ),
						'slug'                    	=> $this->get_tab_courses_slug(),
						'show_for_displayed_user' 	=> learn_press_buddypress_user_can_view(),
						'screen_function'         	=> array( $this, 'bp_tab_content' ),
						'default_subnav_slug'     	=> 'courses',
						'position'                	=> 20
					),
					array(
						'name'                    	=> __( 'Progress', 'learnpress-buddypress' ),
						'slug'                    	=> 'progress',
						'show_for_displayed_user' 	=> ( $this->mycred_is_active() && learn_press_buddypress_user_can_view() ), //learn_press_buddypress_user_can_view() ),
						'screen_function'         	=> array( $this, 'bp_tab_content' ),
						'default_subnav_slug'     	=> 'all',
						'position'                	=> 70
					)
				)
			);
			$subtabs = array(
				array(
					'name'                    		=> __( 'All', 'learnpress-buddypress' ),
					'slug'                    		=> $this->get_tab_courses_slug(),
					'parent_slug'					=> $this->get_tab_courses_slug(),
					'parent_url'      				=> $url . $this->get_tab_courses_slug() .'/', 
					'user_has_access' 				=> learn_press_buddypress_user_can_view(),
					'screen_function'         		=> array( $this, 'bp_subtab_content' ),
					'position'                		=> 15
				),
				array(
					'name'                    		=> __( 'Quizzes', 'learnpress-buddypress' ),
					'slug'                    		=> $this->get_tab_quizzes_slug(),
					'parent_slug'					=> $this->get_tab_courses_slug(),
					'parent_url'      				=> $url . $this->get_tab_courses_slug() .'/', 
					'user_has_access' 				=> learn_press_buddypress_user_can_view(),
					'screen_function'         		=> array( $this, 'bp_subtab_content' ),
					'position'                		=> 25
				),
				array(
					'name'                    		=> __( 'Certificates', 'learnpress-buddypress' ),
					'slug'                    		=> $this->get_tab_certificates_slug(),
					'parent_slug'					=> $this->get_tab_courses_slug(),
					'parent_url'      				=> $url . $this->get_tab_courses_slug() .'/', 
					'user_has_access' 				=> learn_press_buddypress_user_can_view(),
					'screen_function'         		=> array( $this, 'bp_subtab_content' ),
					'position'                		=> 35
				),

			);
			// create new nav item
			foreach ( $tabs as $tab ) {
				bp_core_new_nav_item( $tab );
				// if( $type == 'nav' ) {
				// 	bp_core_new_nav_item( $tab );
				// } else if( $type == 'subnav' ) {
				// 	bp_core_new_subnav_item( $tab );					
				// }
	
			}

			foreach ($subtabs as $subtab ) {
				bp_core_new_subnav_item( $subtab );
			}
		}

		/**
		 * Setup courses bar.
		 */
		public function bp_setup_courses_bar() {

			if ( ! get_current_user_id() ) {
				return;
			}
			// Define the WordPress global
			global $wp_admin_bar;

			global $bp;
			$courses_slug = $this->get_tab_courses_slug();
			$courses_name = __( 'Courses', 'learnpress-buddypress' );
			$courses_link = $this->bp_get_current_link( 'courses' );
			$items        = array(
				array(
					'parent' => $bp->my_account_menu_id,
					'id'     => 'my-account-' . $courses_slug,
					'title'  => $courses_name,
					'href'   => trailingslashit( $courses_link )
				),
				array(
					'parent' => 'my-account-' . $courses_slug,
					'id'     => 'my-account-' . $courses_slug . '-all',
					'title'  => __( 'All courses', 'learnpress-buddypress' ),
					'href'   => trailingslashit( $courses_link . 'all' )
				)
			);
			// Add each admin menu
			foreach ( $items as $item ) {
				$wp_admin_bar->add_menu( $item );
			}
		}

		/**
		 * Get current link.
		 *
		 * @param string $tab
		 *
		 * @return bool|string
		 */
		public function bp_get_current_link( $tab = 'courses' ) {
			// Determine user to use
			if ( bp_displayed_user_domain() ) {
				$user_domain = bp_displayed_user_domain();
			} elseif ( bp_loggedin_user_domain() ) {
				$user_domain = bp_loggedin_user_domain();
			} else {
				return false;
			}

			$func = "get_tab_{$tab}_slug";
			if ( is_callable( array( $this, $func ) ) ) {
				$slug = call_user_func( array( $this, $func ) );
			} else {
				$slug = '';
			}

			// Link to user courses
			return trailingslashit( $user_domain . $slug );
		}

		public function etbi_add_profile_links( $links ) {

			$user_id = get_current_user_id();
			$url = bp_core_get_user_domain( $user_id );

			$links[ $this->get_tab_courses_slug() ] = array(

				'icon'		=> array( 'name' => 'graduation-cap', 'size' => 'sm' ),
				'text'		=> __('Courses', 'etbi'),
				'link'		=> trailingslashit( $url . $this->get_tab_courses_slug() ),
				'enabled'	=> is_user_logged_in(),
				'position'	=> 15

			);

			return $links;

		}

		/**
		 * Get link.
		 *
		 * @param $link
		 * @param $user_id
		 * @param $course_id
		 *
		 * @return string
		 */
		public function bp_get_link( $link, $user_id, $course_id ) {
			// Determine user to use
			if ( is_null( $user_id ) ) {
				$course  = get_post( $course_id );
				$user_id = $course->post_author;
			}
			$link = bp_core_get_user_domain( $user_id );

			return trailingslashit( $link . 'courses' );
		}

		/**
		 * Get profile tab courses slug.
		 *
		 * @return mixed
		 */
		public function get_tab_courses_slug() {
			$slugs = LP()->settings->get( 'profile_endpoints' );
			$slug  = '';
			if ( isset( $slugs['profile-courses'] ) ) {
				$slug = $slugs['profile-courses'];
			}
			if ( ! $slug ) {
				$slug = 'courses';
			}

			return apply_filters( 'learn_press_bp_tab_courses_slug', $slug );
		}

				/**
		 * Get profile tab courses slug.
		 *
		 * @return mixed
		 */
		public function get_tab_certificates_slug() {
			$slugs = LP()->settings->get( 'profile_endpoints' );
			$slug  = '';
			if ( isset( $slugs['certificates'] ) ) {
				$slug = $slugs['certificates'];
			}
			if ( ! $slug ) {
				$slug = 'certificates';
			}

			return apply_filters( 'learn_press_bp_tab_certificates_slug', $slug );
		}

		/**
		 * Get profile tab quizzes slug.
		 *
		 * @return mixed
		 */
		public function get_tab_quizzes_slug() {
			$slugs = LP()->settings->get( 'profile_endpoints' );
			$slug  = '';
			if ( isset( $slugs['profile-quizzes'] ) ) {
				$slug = $slugs['profile-quizzes'];
			}
			if ( ! $slug ) {
				$slug = 'quizzes';
			}

			return apply_filters( 'learn_press_bp_tab_quizzes_slug', $slug );
		}

		/**
		 * Get profile tab quizzes slug.
		 *
		 * @return mixed
		 */
		public function get_tab_orders_slug() {
			$slugs = LP()->settings->get( 'profile_endpoints' );
			$slug  = '';
			if ( isset( $slugs['profile-orders'] ) ) {
				$slug = $slugs['profile-orders'];
			}
			if ( ! $slug ) {
				$slug = 'orders';
			}

			return apply_filters( 'learn_press_bp_tab_orders_slug', $slug );
		}

		/**
		 * Get tab content.
		 */
		public function bp_tab_content() {
			global $bp;
			$current_component = $bp->current_component;
			$slugs = LP()->settings->get( 'profile_endpoints' );
			$tab_slugs = array_keys( $slugs, $current_component );
			$tab_slug = array_shift( $tab_slugs );
			
			switch ( $current_component ) {
				case  $this->get_tab_courses_slug():
					$type = 'courses';
					break;
				case  $this->get_tab_orders_slug():
					$type = 'orders';
					break;
				case  'projects':
					$type = 'projects';
					break;
				case 'points':
					$type = 'points';
					break;
				case 'progress':
					$type = 'progress';
					break;
			}

			if ( $type ) {
				add_action( 'bp_template_content', array( $this, "bp_tab_{$type}_content" ) );
				bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
			}

			do_action( 'learn-press/buddypress/bp-tab-content', $current_component );
		}


		/**
		 * Get tab content.
		 */
		public function bp_subtab_content() {
			global $bp;
			$current_action = $bp->current_action;
			$slugs = LP()->settings->get( 'profile_endpoints' );
			$tab_slugs = array_keys( $slugs, $current_action );
			$tab_slug = array_shift( $tab_slugs );
			$type = '';
			
			switch ( $current_action ) {
				case  $this->get_tab_quizzes_slug():
					$type = 'quizzes';
					break;
				case $this->get_tab_certificates_slug():
					$type = 'certificates';
					break;
			}

			if ( $type ) {

				add_action( 'bp_template_content', array( $this, "bp_subtab_{$type}_content" ) );
				bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
			}

			do_action( 'learn-press/buddypress/bp-subtab-content', $current_action );
		}

		/**
		 * Tab courses content.
		 */
		public function bp_tab_courses_content() {
			$user_id = bp_displayed_user_id();
			$user = learn_press_get_user( $user_id );
			$args = array( 'user' => $user );
			learn_press_get_template( 'profile/courses.php', $args, learn_press_template_path() . '/addons/buddypress/', LP_ADDON_BUDDYPRESS_PATH . '/templates' );
		}

		/**
		 * Tab courses content.
		 */
		public function bp_tab_projects_content() {
			$user_id = bp_displayed_user_id();
			$user = learn_press_get_user( $user_id );
			$args = array( 'user' => $user );
			learn_press_get_template( 'profile/courses.php', $args, learn_press_template_path() . '/addons/buddypress/', LP_ADDON_BUDDYPRESS_PATH . '/templates' );
		}

		/**
		 * Tab quizzes content.
		 */
		public function bp_subtab_quizzes_content() {
			$user_id = bp_displayed_user_id();
			$user = learn_press_get_user( $user_id );
			$args = array( 'user' => $user, 'user_id' => $user_id );

			learn_press_get_template( 'profile/quizzes.php', $args, learn_press_template_path() . '/addons/buddypress/', LP_ADDON_BUDDYPRESS_PATH . '/templates' );
		}

				/**
		 * Tab quizzes content.
		 */
		public function bp_subtab_certificates_content() {
			global $wp;

			$user_id = bp_displayed_user_id();
			$user = learn_press_get_user( $user_id );
			$profile = learn_press_get_profile( $user_id );

			if ( ! empty( $wp->query_vars['act'] ) && ! empty( $wp->query_vars['cert-id'] ) ) {
				$key = $wp->query_vars['cert-id'];
				if ( $certificate = LP_Certificate::get_cert_by_key( $key ) ) {
					if ( $certificate->get_id() ) {
						learn_press_certificate_get_template( 'details.php', array( 'certificate' => $certificate ) );
					}
				}
			} else {
				$certificates = LP_Certificate::get_user_certificates( $profile->get_user()->get_id() );
				$args = array( 'certificates' => $certificates );
				//learn_press_certificate_get_template( 'list-certificates.php', array( 'certificates' => $certificates ) );
				learn_press_get_template( 'profile/list-certificates.php', $args, learn_press_template_path() . '/addons/certificates/', LP_ADDON_BUDDYPRESS_PATH . '/templates' );
			}

		}

		/**
		 * Tab courses content.
		 */
		public function bp_tab_points_content() {
			$user_id = bp_displayed_user_id();
			$user = learn_press_get_user( $user_id );
			$args = array( 'user_id' => $user_id, 'user' => $user, 'mycred_active' => $this->mycred_is_active() );
			learn_press_get_template( 'profile/points.php', $args, learn_press_template_path() . '/addons/buddypress/', LP_ADDON_BUDDYPRESS_PATH . '/templates' );
		}

		/**
		 * Tab courses content.
		 */
		public function bp_tab_progress_content() {
			$user_id = bp_displayed_user_id();
			$user = learn_press_get_user( $user_id );
			$args = array( 'user_id' => $user_id, 'user' => $user, 'mycred_active' => $this->mycred_is_active() );
			learn_press_get_template( 'profile/user-progress-page.php', $args, learn_press_template_path() . '/addons/buddypress/', LP_ADDON_BUDDYPRESS_PATH . '/templates' );
		}

		public function make_mycred_badge_a_link( $badge, $user_id, $svg, $points ) {

			if( $this->mycred_is_active() && learn_press_buddypress_user_can_view() ) {

				$points_url = trailingslashit( bp_core_get_userlink( $user_id, false, true ) . 'progress' );
				$badge = '<a href="'.esc_url( $points_url ).'" class="points-badge-link">'. $badge .'</a>';

			}

			return $badge;

		}

		public function change_quizzes_filters( $defaults ) {

			$user_id = bp_displayed_user_id();
			$url = bp_core_get_user_domain( $user_id );
			$current_filter = LP_Request::get_string( 'filter-status' );

			if( bp_is_current_component( $this->get_tab_courses_slug() ) && bp_is_current_action( $this->get_tab_quizzes_slug() ) ) {

				$url = trailingslashit( bp_core_get_user_domain( $user_id ) . '/' . $this->get_tab_courses_slug() . '/' . $this->get_tab_quizzes_slug() );

			}
			

			$defaults = array(
				'all'       => sprintf( '<a href="%s">%s</a>', esc_url( $url ), __( 'All', 'learnpress' ) ),
				'completed' => sprintf( '<a href="%s">%s</a>', esc_url( add_query_arg( 'filter-status', 'completed', $url ) ), __( 'Finished', 'learnpress' ) ),
				'passed'    => sprintf( '<a href="%s">%s</a>', esc_url( add_query_arg( 'filter-status', 'passed', $url ) ), __( 'Passed', 'learnpress' ) ),
				'failed'    => sprintf( '<a href="%s">%s</a>', esc_url( add_query_arg( 'filter-status', 'failed', $url ) ), __( 'Failed', 'learnpress' ) )
			);

			if ( ! $current_filter ) {
				$keys           = array_keys( $defaults );
				$current_filter = reset( $keys );
			}

			foreach ( $defaults as $k => $v ) {
				if ( $k === $current_filter ) {
					$defaults[ $k ] = sprintf( '<span>%s</span>', strip_tags( $v ) );
				}
			}

			return $defaults;

		}

		/**
		 * Tab orders content.
		 */
		public function bp_tab_orders_content() {
			learn_press_get_template( 'profile/tabs/orders.php', array( 'user' => learn_press_get_current_user() ) );
		}

		/**
		 * Admin scripts.
		 *
		 * @param $hook
		 */
		public function admin_scripts( $hook ) {
			global $post;
			if ( $post && in_array( $post->post_type, array(
					LP_COURSE_CPT,
					LP_LESSON_CPT,
					LP_QUESTION_CPT,
					LP_QUIZ_CPT,
					LP_ORDER_CPT
				) )
			) {
				add_filter( 'bp_activity_maybe_load_mentions_scripts', array( $this, 'dequeue_script' ), - 99 );
			}
		}

		/**
		 * Dequeue script.
		 *
		 * @param $load_mentions
		 *
		 * @return bool
		 */
		public function dequeue_script( $load_mentions ) {
			return false;
		}

		/**
		 * Frontend assets.
		 */
		public function wp_assets() {
			wp_enqueue_style( 'learn-press-buddypress', plugins_url( '/assets/css/site.css', LP_ADDON_BUDDYPRESS_FILE ) );


		    if( $this->buddypress_is_active() ) {

		        if( is_buddypress() ) {

		            if( bp_is_user() && bp_is_current_component('progress') ) {

		            	$user_id = bp_displayed_user_id();
		            	$time_range = learn_press_get_user_time_range();
		            	$from = $time_range['from'];
		            	$until = $time_range['until'];

		                wp_enqueue_style( 'user-progress-css', plugins_url( '/assets/css/user-progress.css', LP_ADDON_BUDDYPRESS_FILE ) );

		                wp_enqueue_script( 'bp-charts-script', 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.2/Chart.bundle.min.js', null, LP_ADDON_BP_VER, true );
                    	wp_enqueue_script( 'date-range-picker', 'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js', array( 'jquery', 'bp-charts-script' ), LP_ADDON_BP_VER, true );
		                wp_enqueue_script( 'user-progress-js', plugins_url( '/assets/js/user-progress.js', LP_ADDON_BUDDYPRESS_FILE ), array( 'jquery', 'bp-charts-script', 'date-range-picker' ), LP_ADDON_BP_VER, true );

	                    wp_localize_script( 'user-progress-js', 'user_progress', apply_filters( 'user_progress_chart_l18n', array(

							'edu_points'		=> learn_press_get_user_education_points( $user_id, array( 'from' => $from, 'until' => $until ) ),
							'server_points'		=> learn_press_get_user_server_points( $user_id, array( 'from' => $from, 'until' => $until ) ),
	                        'from'              => $time_range[ 'from' ],
	                        'until'             => $time_range[ 'until' ],
	                        'user_id'			=> $user_id

	                    ) ) );

		            }

		        }

		    }

		}

		/**
		 * Check BuddyPress active.
		 *
		 * @return bool
		 */
		public function buddypress_is_active() {
			if ( ! function_exists( 'is_plugin_active' ) ) {
				include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			}

			return class_exists( 'BuddyPress' ) && is_plugin_active( 'buddypress/bp-loader.php' );
		}

		/**
		 * Check myCRED active.
		 *
		 * @return bool
		 */
		public function mycred_is_active() {
			return class_exists( 'myCRED_Core' );
		}

		public function pipeline_notifications_get_registered_components( $components = array() ) {

			// Force $component_names to be an array
			if ( ! is_array( $components ) ) {
				$components = array();
			}
			// Add 'custom' component to registered components array
			array_push( $components, 'lp-buddypress' );
			// Return component's with 'custom' appended
			return $components;

		}

		public function pipeline_format_buddypress_notifications( $action, $item_id, $secondary_item_id, $total_items, $format = 'string' ) {

			// New custom notifications
			if ( 'pipeline_done' === $action ) {
			
				$pipeline_id = $item_id;
				$user_log = $this->etbi_get_user_server_log( $user_id );
				$pipeline = $user_log['Pipelines'][ $pipeline_id ];
				$pipeline_name = $this->get_pipeline_name( $pipeline_id, $user_log );
				$pipeline_url = $this->get_pipeline_url( $pipeline_id, $user_log );	
			
				$notification_title = 'Your pipeline: ' . $pipeline_name . ' has finished processing.';
				$notification_text = 'Your pipeline: ' . $pipeline_name . ' has finished processing.';
				// WordPress Toolbar
				if ( 'string' === $format ) {
					$return = apply_filters( 'pipeline_done_notification', '<a href="' . esc_url( $pipeline_url ) . '" title="' . esc_attr( $notification_title ) . '">' . esc_html( $notification_text ) . '</a>', $notification_text, $pipeline_url );
				// Deprecated BuddyBar
				} else {
					$return = apply_filters( 'pipeline_done_notification', array(
						'text' => $notification_text,
						'link' => $pipeline_url
					), $pipeline_url, (int) $total_items, $notification_text, $notification_title );
				}
				
				return $return;
				
			}			

		}


		public function bp_pipeline_activity_and_notification( $mycred_remote ) {

			if( $this->buddypress_is_active() ) {

				$ref = $mycred_remote->request['ref'];
				$user_id = $mycred_remote->user->ID;

				switch ( $ref ) {
					case 'pipeline_fresh':
						$this->pipeline_fresh_activity_update( $user_id, $mycred_remote );
						break;

					case 'pipeline_processing':
						$this->pipeline_processing_activity_update( $user_id, $mycred_remote );
						break;

					case 'pipeline_done':
						$this->pipeline_done_activity_update( $user_id, $mycred_remote );
						break;
				}

			}

		}

		public function get_pipeline_name( $pipeline_id, $user_log = array() ) {

			if( is_array( $user_log ) && empty( $user_log ) ) {

				return false;

			}

			$pipeline = $user_log['Pipelines'][$pipeline_id];

			if( empty( $pipeline['pipealtername'] ) ) {

				return $pipeline['pipename'];

			}

			return $pipeline['pipealtername'];

		}

		public function get_pipeline_url( $pipeline_id, $user_log = array() ) {

			if( is_array( $user_log ) && empty( $user_log ) ) {

				return false;

			}

			$server_url = trailingslashit( 'server.t-bio.info' );
			$pipeline = $user_log['Pipelines'][$pipeline_id];
			$pipeline_graphname = $pipeline['pipelinegraphname'];

			if( empty( $pipeline_graphname ) ) {

				return false;

			}

			$pipeline_url = trailingslashit( $server_url . $pipeline['pipelinegraphname'] . '/' . $pipeline_id );

			return apply_filters( 'etbi_pipeline_url', $pipeline_url, $pipeline_id, $user_log );

		}

		public function pipeline_fresh_activity_update( $user_id, $mycred_remote ) {

				$ref = $mycred_remote->request['ref'];
				$pipeline_id = $mycred_remote->request['data'];
				$user_id = $mycred_remote->user->ID;
				$profile_link = bp_core_get_userlink( $user_id );
				$user_log = $this->etbi_get_user_server_log( $user_id );
				$pipeline_name = $this->get_pipeline_name( $pipeline_id, $user_log );
				$pipeline_url = $this->get_pipeline_url( $pipeline_id, $user_log );	

				$pipeline_link = '<a href="'.esc_url( ).'">'. esc_html( $pipeline_name ) .'</a>';
				$server_link = '<a href="server.t-bio.info">server.t-bio.info</a>';

				$action = sprintf( '%1$s prepared the pipeline %2$s on %3$s', 
					$profile_link,
					$pipeline_link,
					$server_link );

				$component = 'lp-buddypress';
				$type = 'pipeline_fresh';

				$primary_link = $pipeline_link;

				$args = apply_filters( 'learn_press_pipeline_fresh_activity_args', array(

					'action'			=> $action,
					'component'			=> $component,
					'type'				=> $type,
					'primary_link'		=> $primary_link,
					'user_id'			=> $user_id
				) );	

				bp_activity_add( $args );	

		}

		public function pipeline_processing_activity_update( $user_id, $mycred_remote ) {

				$ref = $mycred_remote->request['ref'];
				$pipeline_id = $mycred_remote->request['data'];
				$user_id = $mycred_remote->user->ID;
				$profile_link = bp_core_get_userlink( $user_id );
				$user_log = $this->etbi_get_user_server_log( $user_id );
				$pipeline_name = $this->get_pipeline_name( $pipeline_id, $user_log );
				$pipeline_url = $this->get_pipeline_url( $pipeline_id, $user_log );	

				$pipeline_link = '<a href="'.esc_url( ).'">'. esc_html( $pipeline_name ) .'</a>';
				$server_link = '<a href="server.t-bio.info">server.t-bio.info</a>';

				$action = sprintf( '%1$s started the pipeline %2$s on %3$s', 
					$profile_link,
					$pipeline_link,
					$server_link );

				$component = 'lp-buddypress';
				$type = 'pipeline_processing';

				$primary_link = $pipeline_link;

				$args = apply_filters( 'learn_press_pipeline_processing_activity_args', array(

					'action'			=> $action,
					'component'			=> $component,
					'type'				=> $type,
					'primary_link'		=> $primary_link,
					'user_id'			=> $user_id
				) );	

				bp_activity_add( $args );	

		}

		public function pipeline_done_activity_update( $user_id, $mycred_remote ) {

				$ref = $mycred_remote->request['ref'];
				$pipeline_id = $mycred_remote->request['data'];
				$user_id = $mycred_remote->user->ID;
				$profile_link = bp_core_get_userlink( $user_id );
				$user_log = $this->etbi_get_user_server_log( $user_id );
				$pipeline_name = $this->get_pipeline_name( $pipeline_id, $user_log );
				$pipeline_url = $this->get_pipeline_url( $pipeline_id, $user_log );	

				$pipeline_link = '<a href="'.esc_url( ).'">'. esc_html( $pipeline_name ) .'</a>';
				$server_link = '<a href="server.t-bio.info">server.t-bio.info</a>';

				$action = sprintf( '%1$s finished the pipeline %2$s on %3$s', 
					$profile_link,
					$pipeline_link,
					$server_link );

				$component = 'lp-buddypress';
				$type = 'pipeline_done';

				$primary_link = $pipeline_link;

				$args = apply_filters( 'learn_press_pipeline_done_activity_args', array(

					'action'			=> $action,
					'component'			=> $component,
					'type'				=> $type,
					'primary_link'		=> $primary_link,
					'user_id'			=> $user_id
				) );	

				bp_activity_add( $args );

			    if ( bp_is_active( 'notifications' ) ) {
			        bp_notifications_add_notification( array(
			            'user_id'           => $user_id,
			            'item_id'           => $pipeline_id,
			            'component_name'    => $component,
			            'component_action'  => $type,
			            'date_notified'     => bp_core_current_time(),
			            'is_new'            => 1,
			        ) );
			    }	


		}


		public function start_quiz_activity_update( $quiz_id, $course_id, $user_id ) {

			if( $this->buddypress_is_active() ) {

				if( bp_is_active( 'activity' ) ) {

					$user = get_userdata( $user_id );
					$profile_link = bp_core_get_userlink( $user_id );
					$quiz_link = '<a href="'.esc_url( get_permalink( $quiz_id ) ).'">'. esc_html( get_the_title( $quiz_id ) ) .'</a>';
					$course_link = '<a href="'.esc_url( get_permalink( $course_id ) ).'">'. esc_html( get_the_title( $course_id ) ) .'</a>';

					$action = sprintf( '%1$s started the quiz %2$s in the course %3$s', 

						$profile_link,
						$quiz_link,
						$course_link );
					$component = 'lp-buddypress';
					$type = 'start_quiz';
					$primary_link = get_permalink( $course_id );

					$args = apply_filters( 'learn_press_start_quiz_activity_args', array(

						'action'			=> $action,
						'component'			=> $component,
						'type'				=> $type,
						'primary_link'		=> $primary_link,
						'user_id'			=> $user_id,
						'item_id'			=> $course_id,
						'secondary_item_id'	=> $quiz_id
					) );	

					bp_activity_add( $args );		

				}

			}

		}

		public function finish_quiz_activity_update( $quiz_id, $course_id, $user_id ) {

			if( $this->buddypress_is_active() ) {

				if( bp_is_active( 'activity' ) ) {

					$user = get_userdata( $user_id );
					$profile_link = bp_core_get_userlink( $user_id );
					$quiz_link = '<a href="'.esc_url( get_permalink( $quiz_id ) ).'">'. esc_html( get_the_title( $quiz_id ) ) .'</a>';
					$course_link = '<a href="'.esc_url( get_permalink( $course_id ) ).'">'. esc_html( get_the_title( $course_id ) ) .'</a>';

					$action = sprintf( '%1$s finished the quiz %2$s in the course %3$s', 

						$profile_link,
						$quiz_link,
						$course_link );
					$component = 'lp-buddypress';
					$type = 'finish_quiz';
					$primary_link = get_permalink( $course_id );

					$args = apply_filters( 'learn_press_start_quiz_activity_args', array(

						'action'			=> $action,
						'component'			=> $component,
						'type'				=> $type,
						'primary_link'		=> $link,
						'user_id'			=> $user_id,
						'item_id'			=> $course_id,
						'secondary_item_id'	=> $quiz_id
					) );	

					bp_activity_add( $args );		

				}

			}

		}

		public function enroll_course_activity_update( $status, $order_id ) {

			// Check if order is invalid
			if ( ! $order_id || $status != 'completed' ) {
				return;
			}

			$order = new LP_Order( $order_id );

			if ( ! $order ) {
				return;
			}

			$user_id = $order->get_user( 'ID' ); // get_post_meta( $order_id, '_learn_press_customer_id', true ) ? get_post_meta( $order_id, '_learn_press_customer_id', true ) : 0;
			// Check if user is invalid
			if ( ! $user_id ) {
				return;
			}

			if ( $items = $order->get_items() ) {
				foreach ( $items as $item ) {
					//$take_course = get_post_meta( $order_id, '_learn_press_transaction_method', true ) == 'free' ? 'take_free_course' : 'take_paid_course';

					$course_id = ! empty( $item['course_id'] ) ? absint( $item['course_id'] ) : 0;
					if ( ! $course_id ) {
						continue;
					}
					$course = learn_press_get_course( $course_id );
					if ( ! $course ) {
						continue;
					}
					$take_course = $course->is_free() ? 'take_free_course' : 'take_paid_course';

					// Execute
					if( $this->buddypress_is_active() ) {

						if( bp_is_active( 'activity' ) ) {

							$user = get_userdata( $user_id );
							$profile_link = bp_core_get_userlink( $user_id );
							$course_link = '<a href="'.esc_url( get_permalink( $course_id ) ).'">'. esc_html( get_the_title( $course_id ) ) .'</a>';

							$action = sprintf( '%1$s enrolled in the course %2$s', 

								$profile_link,
								$course_link );

							$component = 'lp-buddypress';
							$type = 'enroll_course';
							$primary_link = get_permalink( $course_id );

							$args = apply_filters( 'learn_press_enroll_course_activity_args', array(

								'action'			=> $action,
								'component'			=> $component,
								'type'				=> $type,
								'primary_link'		=> $link,
								'user_id'			=> $user_id,
								'item_id'			=> $course_id
							) );	

							bp_activity_add( $args );	

						}

					}
				}
			}

		}

		public function pass_course_activity_update( $course_id, $user_id, $result ) {


			// Check if course or user is invalid
			if ( ! $course_id || ! $user_id ) {
				return;
			}

			$user = learn_press_get_user( $user_id );
			// Check if user has not passed the course
			if ( ! $user->has_passed_course( $course_id ) ) {
				return;
			}

			// Execute
			if( $this->buddypress_is_active() ) {

				if( bp_is_active( 'activity' ) ) {

					$user = get_userdata( $user_id );
					$profile_link = bp_core_get_userlink( $user_id );
					$course_link = '<a href="'.esc_url( get_permalink( $course_id ) ).'">'. esc_html( get_the_title( $course_id ) ) .'</a>';

					$action = sprintf( '%1$s passed the course %2$s', 

						$profile_link,
						$course_link );

					$component = 'lp-buddypress';
					$type = 'passed_course';
					$primary_link = get_permalink( $course_id );

					$args = apply_filters( 'learn_press_pass_course_activity_args', array(

						'action'			=> $action,
						'component'			=> $component,
						'type'				=> $type,
						'primary_link'		=> $link,
						'user_id'			=> $user_id,
						'item_id'			=> $course_id
					) );	

					bp_activity_add( $args );	

				}

			}

		}

		public function credit_points_activity_update( $result, $request, $mycred ) {

			$reference = $request['ref'];
			$amount = absint( $request['amount'] );
			$user_id = absint( $request['user_id'] );

			if( bp_is_active( 'activity' ) ) {

				$user = get_userdata( $user_id );
				$profile_link = bp_core_get_userlink( $user_id );

				$action = etbi_get_credit_points_activity_action( $user_id, $request, $mycred );

				$component = 'lp-buddypress';
				$type = 'credit_points';
				$primary_link = null;

				$args = apply_filters( 'learn_press_credit_points', array( 

					'action'		=> $action,
					'component'		=> $component,
					'type'			=> $type,
					'primary_link'	=> $link,
					'user_id'		=> $user_id,
					'hide_sitewide'	=> true
					

				 ) );

				if( $result ) {

					bp_activity_add( $args );

				}

				

			}

		}

		public function etbi_get_user_server_log( $user, $args = array() ) {

			$user_email = '';

			if( is_email( $user ) ) {

				$user_email = $user;

			} else if( is_numeric( $user ) ) {

				$user = get_userdata( $user );
				$user_email = $user->user_email;

			} else if( $user = get_user_by( 'slug', $user ) ) {

				$user_email = $user->user_email;

			} else {

				return false;

			}

			$defaults = apply_filters( 'etbi_user_server_log_default_args', array(

				'pipe_id' 		=> null,
				'field'			=> null,
				'assoc'			=> true, // Whether to return an associative array. See: http://php.net/json_decode
				'api_client'	=> 'pinesystem@pine.com',
				'api_password'	=> 'fdsf45fgdf33fFFd',
				'url'			=> 'http://server.t-bio.info:3000/userlog/' . $user_email,
				'cookie'		=> tempnam( sys_get_temp_dir(), "CURLCOOKIE" )

			) );

			$args = wp_parse_args( $args, $defaults );

			$api_client = $args['api_client'];
			$api_password = $args['api_password'];
			$url = $args['url'];
			$cookie = $args['cookie'];

			$postdata = "user[email]=" . $api_client . "&user[password]=" . $api_password;
		    $ch = curl_init();
		    curl_setopt($ch, CURLOPT_URL, $url);
		    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6");
		    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
		    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
		    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
		    curl_setopt($ch, CURLOPT_REFERER, $url);
		    curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
		    curl_setopt($ch, CURLOPT_POST, 1);
		    $result = curl_exec($ch);
		    curl_close($ch);

			$user_log = json_decode( $result, $args['assoc'] );

			return $user_log;

		}

		/**
		 * Show admin notice when inactive BuddyPress.
		 */
		public function admin_notices() {
			?>
            <div class="notice notice-error">
                <p>
					<?php echo wp_kses(
						sprintf(
							__( '<strong>BuddyPress</strong> addon for <strong>LearnPress</strong> requires %s plugin is <strong>installed</strong> and <strong>activated</strong>.', 'learnpress-buddypress' ),
							sprintf( '<a href="%s" target="_blank">BuddyPress</a>', admin_url( 'plugin-install.php?tab=search&type=term&s=buddypress' ) )
						), array(
							'a'      => array(
								'href'   => array(),
								'target' => array(),
							),
							'strong' => array()
						)
					); ?>
                </p>
            </div>
		<?php }
	}
}

add_action( 'plugins_loaded', array( 'LP_Addon_BuddyPress', 'instance' ) );