<?php
/*
Plugin Name: LearnPress - BuddyPress Integration
Plugin URI: http://thimpress.com/learnpress
Description: Using the profile system provided by BuddyPress.
Author: ThimPress
Version: 3.0.2
Author URI: http://thimpress.com
Tags: learnpress, lms, add-on, buddypress
Text Domain: learnpress-buddypress
Domain Path: /languages/
*/

// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

define( 'LP_ADDON_BUDDYPRESS_FILE', __FILE__ );
define( 'LP_ADDON_BUDDYPRESS_VER', '3.0.2' );
define( 'LP_ADDON_BUDDYPRESS_REQUIRE_VER', '3.0.0' );
define( 'LP_ADDON_BP_VER', '3.0.2' );

/**
 * Class LP_Addon_BuddyPress_Preload
 */
class LP_Addon_BuddyPress_Preload {

	/**
	 * LP_Addon_BuddyPress_Preload constructor.
	 */
	public function __construct() {
		add_action( 'learn-press/ready', array( $this, 'load' ) );
		add_action( 'bp_register_activity_actions', array( $this, 'register_actions' ), 10 );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		add_action( 'init', array( $this, 'mycred_remote_register_action' ), 1 );
		add_action( 'bp_setup_globals', array( $this, 'pipeline_bp_notifications' ) );
		add_filter( 'bp_notifications_get_registered_components',array( $this, 'pipeline_notifications_get_registered_components' ), 10, 1 );
		add_filter( 'bp_notifications_get_notifications_for_user', array( $this,  'pipeline_format_buddypress_notifications' ), 10, 5 );
	}

	/**
	 * Load addon
	 */
	public function load() {
		LP_Addon::load( 'LP_Addon_BuddyPress', 'inc/load.php', __FILE__ );
		remove_action( 'admin_notices', array( $this, 'admin_notices' ) );
	}

	public function register_actions() {
	    $bp = buddypress();

	    /**************************************************************************
	       for the purpose of this tutorial we arbitrary set the $bp->component->id
	       !important you have to use the BP_Component class to do so
	       See : https://codex.buddypress.org/bp_component/
	    ***************************************************************************/
	    $bp->bp_plugin = new stdClass();
	    $bp->bp_plugin->id = 'lp-buddypress';
	 
	    bp_activity_set_action(	
	        $bp->bp_plugin->id,                        // The unique string ID of the component the activity action is attached to
	        'start_quiz',                        // the action type
	        __( 'Started Quiz', 'plugin-domain' ), // the action description used in Activity Administration screens dropdown filters
	        false,       // A callable function for formatting the action string
	        __( 'Started Quiz', 'plugin-domain' ), // the action label of the activity front-end dropdown filters
	        array( 'activity', 'member' )              // Activity stream contexts where the filter should appear
	    );

	    bp_activity_set_action(	
	        $bp->bp_plugin->id,                        // The unique string ID of the component the activity action is attached to
	        'finish_quiz',                        // the action type
	        __( 'Finished Quiz', 'plugin-domain' ), // the action description used in Activity Administration screens dropdown filters
	        false,       // A callable function for formatting the action string
	        __( 'Finished Quiz', 'plugin-domain' ), // the action label of the activity front-end dropdown filters
	        array( 'activity', 'member' )              // Activity stream contexts where the filter should appear
	    );

	    bp_activity_set_action(	
	        $bp->bp_plugin->id,                        // The unique string ID of the component the activity action is attached to
	        'enroll_course',                        // the action type
	        __( 'Started Course', 'plugin-domain' ), // the action description used in Activity Administration screens dropdown filters
	        false,       // A callable function for formatting the action string
	        __( 'Started Course', 'plugin-domain' ), // the action label of the activity front-end dropdown filters
	        array( 'activity', 'member' )              // Activity stream contexts where the filter should appear
	    );

	    bp_activity_set_action(	
	        $bp->bp_plugin->id,                        // The unique string ID of the component the activity action is attached to
	        'passed_course',                        // the action type
	        __( 'Passed Course', 'plugin-domain' ), // the action description used in Activity Administration screens dropdown filters
	        false,       // A callable function for formatting the action string
	        __( 'Passed Course', 'plugin-domain' ), // the action label of the activity front-end dropdown filters
	        array( 'activity', 'member' )              // Activity stream contexts where the filter should appear
	    );

	    bp_activity_set_action(	
	        $bp->bp_plugin->id,                        // The unique string ID of the component the activity action is attached to
	        'credit_points',                        // the action type
	        __( 'Points', 'plugin-domain' ), // the action description used in Activity Administration screens dropdown filters
	        false,       // A callable function for formatting the action string
	        __( 'Points', 'plugin-domain' ), // the action label of the activity front-end dropdown filters
	        array( 'activity', 'member' )              // Activity stream contexts where the filter should appear
	    );

	}

	public function mycred_remote_register_action() {

		add_action( 'mycred_remote_action_CREDIT', array( $this, 'bp_pipeline_activity_and_notification' ), 10, 1 );

	}

	public function pipeline_bp_notifications() {

		buddypress()->pipeline_notifications = new stdClass;
		buddypress()->pipeline_notifications->notification_callback = array( $this, 'pipeline_format_buddypress_notifications' );

		buddypress()->active_components['pipeline_notifications'] = 1;

	}

	public function bp_pipeline_activity_and_notification( $mycred_remote ) {

		if( function_exists('bp_is_active') ) {

			$ref = $mycred_remote->request['ref'];
			$user_id = $mycred_remote->user->ID;

			error_log( 'MyCRED REMOTE API IS BEING RUN' );

			error_log( 'mycred_remote_action_' . $ref );

			error_log( "User id is : {$user_id}" );

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
				default :
					break;
			}

		}

	}

	public function get_pipeline_name( $pipeline_id, $user_log = array() ) {

		if( is_array( $user_log ) && empty( $user_log ) ) {

			error_log( 'No user log' );

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

		$server_url = trailingslashit( 'https://server.t-bio.info/' );
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

			$pipeline_link = '<a href="'.esc_url( $pipeline_url ).'">'. esc_html( $pipeline_name ) .'</a>';
			$server_link = '<a href="https://server.t-bio.info" target="_blank">server.t-bio.info</a>';

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

			$pipeline_link = '<a href="'.esc_url( $pipeline_url ).'" target="_blank">'. esc_html( $pipeline_name ) .'</a>';
			$server_link = '<a href="https://server.t-bio.info" target="_blank">server.t-bio.info</a>';

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

			$pipeline_link = '<a href="'.esc_url( $pipeline_url ).'" target="_blank">'. esc_html( $pipeline_name ) .'</a>';
			$server_link = '<a href="https://server.t-bio.info" target="_blank">server.t-bio.info</a>';

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
		            'secondary_item_id' => $user_id,
		            'component_name'    => 'pipeline_notifications',
		            'component_action'  => $type,
		            'date_notified'     => bp_core_current_time(),
		            'is_new'            => 1,
		        ) );
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

		error_log( 'Notification action: ' . $action );

		// New custom notifications
		if ( 'pipeline_done' === $action ) {
		
			$pipeline_id = $item_id;
			$user_id = $secondary_item_id;
			$user_log = $this->etbi_get_user_server_log( $user_id );
			$pipeline = $user_log['Pipelines'][ $pipeline_id ];
			$pipeline_name = $this->get_pipeline_name( $pipeline_id, $user_log );
			$pipeline_url = $this->get_pipeline_url( $pipeline_id, $user_log );	
		
			$notification_title = 'Your pipeline: ' . $pipeline_name . ' has finished processing.';
			$notification_text = 'Your pipeline: ' . $pipeline_name . ' has finished processing.';

			error_log( 'Format: ' . $format );
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

			//error_log( "{$return}" );
			
			return $return;
			
		}			

	}

	/**
	 * Admin notice
	 */
	public function admin_notices() {
		?>
        <div class="error">
            <p><?php echo wp_kses(
					sprintf(
						__( '<strong>%s</strong> addon version %s requires %s version %s or higher is <strong>installed</strong> and <strong>activated</strong>.', 'learnpress-buddypress' ),
						__( 'LearnPress BuddyPress', 'learnpress-buddypress' ),
						LP_ADDON_BUDDYPRESS_VER,
						sprintf( '<a href="%s" target="_blank"><strong>%s</strong></a>', admin_url( 'plugin-install.php?tab=search&type=term&s=learnpress' ), __( 'LearnPress', 'learnpress-buddypress' ) ),
						LP_ADDON_BUDDYPRESS_REQUIRE_VER
					),
					array(
						'a'      => array(
							'href'  => array(),
							'blank' => array()
						),
						'strong' => array()
					)
				); ?>
            </p>
        </div>
		<?php
	}
}

new LP_Addon_BuddyPress_Preload();