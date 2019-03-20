<?php
/**
 * LearnPress BuddyPress Functions
 *
 * Define common functions for both front-end and back-end
 *
 * @author   ThimPress
 * @package  LearnPress/BuddyPress/Functions
 * @version  3.0.0
 */

// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'learn_press_buddypress_paging_nav' ) ) :
	/**
	 * Display navigation to next/previous set of posts when applicable.
	 *
	 * @param array
	 */
	function learn_press_buddypress_paging_nav( $args = array() ) {

		$args = wp_parse_args(
			$args,
			array(
				'num_pages'     => 0,
				'paged'         => get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1,
				'wrapper_class' => 'learn-press-pagination',
				'base'          => false
			)
		);
		if ( $args['num_pages'] < 2 ) {
			return;
		}
		$paged        = $args['paged'];
		$pagenum_link = html_entity_decode( $args['base'] === false ? get_pagenum_link() : $args['base'] );

		$query_args = array();
		$url_parts  = explode( '?', $pagenum_link );

		if ( isset( $url_parts[1] ) ) {
			wp_parse_str( $url_parts[1], $query_args );
		}

		$pagenum_link = remove_query_arg( array_keys( $query_args ), $pagenum_link );
		$pagenum_link = trailingslashit( $pagenum_link ) . '%_%';

		$format = $GLOBALS['wp_rewrite']->using_index_permalinks() && ! strpos( $pagenum_link, 'index.php' ) ? 'index.php/' : '';
		$format .= '?paged=%#%';

		// Set up paginated links.
		$links = paginate_links( array(
			'base'      => $pagenum_link,
			'format'    => $format,
			'total'     => $args['num_pages'],
			'current'   => max( 1, $paged ),
			'mid_size'  => 1,
			'add_args'  => array_map( 'urlencode', $query_args ),
			'prev_text' => __( '<', 'learnpress-buddypress' ),
			'next_text' => __( '>', 'learnpress-buddypress' ),
			'type'      => 'list'
		) );

		if ( $links ) { ?>
            <div class="<?php echo $args['wrapper_class']; ?>"><?php echo $links; ?></div>
            <!-- .pagination -->
			<?php
		}
	}

endif;

if( ! function_exists( 'learn_press_buddypress_user_can_view' ) ) :

	function learn_press_buddypress_user_can_view() {

		if( ! is_user_logged_in() ) {
			return false;
		}

		if( current_user_can( 'administrator' ) ) {
			return true;
		}

		// Get the current logged in user id.
		$current_user_id = get_current_user_id();

		// Check if BuddyPress is installed and activated.
		if( function_exists( 'bp_is_active' ) ) {

			$viewed_user_id = 0;

			//If the current page being viewed is a user profile.
			if( bp_is_user() ) {

				// Get the user id of the current profile that is being viewed.
				$viewed_user_id = bp_displayed_user_id();

			}

			// If it's the current user's own profile, then of course they can view the tab.
			if( bp_is_my_profile() ) {
				return true;
			}

			//Now we know that the current user is viewing the profile of another user. So we check if the groups component is active first
			if( bp_is_active( 'groups' ) ) {

				//Check if the viewed user is a member of any groups.
				$viewed_user_group_ids = groups_get_user_groups( $viewed_user_id );
						
				//If the viewed user has groups, we loop through all of them
				foreach( $viewed_user_group_ids['groups'] as $group_id ) { 

					//If the current user is a mod or admin of any of those groups, then yes, they can view the tab.
					if( groups_is_user_mod( $current_user_id, $group_id ) || groups_is_user_admin( $current_user_id, $group_id ) ){

						return true;

					}

				}

			}

		}

		return false;

	}

endif;

if( ! function_exists('etbi_get_credit_points_activity_action') ) {

	function etbi_get_credit_points_activity_action( $user_id, $request, $mycred = null ) {

		$reference = $request['ref'];
		$amount = absint( $request['amount'] );
		$user_id = absint( $request['user_id'] );
		$profile_link = bp_core_get_userlink( $user_id );
		$entry = $mycred->template_tags_general( $request['entry'] );

		$activity_action = sprintf( '%1$s gained %2$s %3$s',
		 
						$profile_link,
						$amount,
						$entry );

		return apply_filters( 'etbi_' . $reference . '_activity_action', $activity_action, $request );

	}

}

if( ! function_exists('etbi_time_interval') ) {

	function etbi_time_interval( $time ) {

	    $dtF = new \DateTime('@0');
	    $dtT = new \DateTime("@$time");
	    return $dtF->diff($dtT)->format('%a days, %h hours, %i minutes');

	}

}

if( ! function_exists('learn_press_get_user_server_log') ) {

	function learn_press_get_user_server_log( $user, $args = array() ) {

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

}

if( ! function_exists('learn_press_validate_date') ) {

	function learn_press_validate_date( $date, $format = 'Y-m-d' ) {

	    $d = DateTime::createFromFormat($format, $date);
	    // The Y ( 4 digits year ) returns TRUE for any integer with any number of digits so changing the comparison from == to === fixes the issue.
	    return $d && $d->format($format) === $date;

	}

}

if( ! function_exists('learn_press_get_user_time_range') ) {

	function learn_press_get_user_time_range( $args = array() ) {

		$day = date('w');	

		$defaults = apply_filters( 'etbi_get_default_time_range', array(

			'format'		=> 'Y-m-d',
			'day'			=> date('w'),
			'from'			=> date('Y-m-d', strtotime('-'.($day).' days')),
			'until'			=> date('Y-m-d', strtotime('+'.(6-$day).' days'))

		) );

		$args = wp_parse_args( $args, $defaults );

		if ( ( DateTime::createFromFormat($args['format'], $args['from']) === FALSE ) && ( DateTime::createFromFormat($args['format'], $args['until']) === FALSE ) ) {
	  		
			return false;
	  		
		}

		$from = $args['from'];
		$until = $args['until'];
		$format = $args['format'];

		$time_range = array( 'from' => $from, 'until' => $until, 'format' => $format );

		return apply_filters( 'etbi_get_time_range', $time_range, $args );

	}	

}


if( ! function_exists('learn_press_pick_user_date_range') ) {

	function learn_press_pick_user_date_range() {

		if( learn_press_buddypress_user_can_view() ) {

			$from 	= $_POST['from'];
			$until 	= $_POST['until'];
			$formatted_from = date( 'Y-m-d', strtotime( $from ) );
			$formatted_until = date( 'Y-m-d', strtotime( $until ) );
			$user_id = ( wp_doing_ajax() && ( isset( $_POST['user_id'] ) && ! empty( $_POST['user_id'] ) ) ) ? (int) $_POST['user_id'] : bp_displayed_user_id();
			$user_name = bp_core_get_userlink( $user_id, true );

			$args = array( 'user_id' => $user_id, 'mycred_active' => true  );
	 
			if( ( isset( $from ) && $from ) && ( isset( $until ) && $until ) ) {

				if( learn_press_validate_date( $from ) && learn_press_validate_date( $until ) ) {

					$args['time'] = array( 'dates' => array( $from . " 00:00:01", $until . " 23:59:59" ), 'compare' => 'BETWEEN' );

					if( wp_doing_ajax() ) {

						wp_send_json( apply_filters( 'etbi_progress_chart_l18n', array(

								'edu_points'		=> learn_press_get_user_education_points( $user_id, array( 'from' => $from, 'until' => $until ) ),
								'server_points'		=> learn_press_get_user_server_points( $user_id, array( 'from' => $from, 'until' => $until ) ),
								'user_name'			=> $user_name,
								'from'				=> $from,
								'until'				=> $until,
								'html'				=> learn_press_get_template_content( 'profile/user-progress-page.php', $args, learn_press_template_path() . '/addons/buddypress/', LP_ADDON_BUDDYPRESS_PATH . '/templates' )

						) ) );

						wp_die();

					}

				}

			}

		}

	}

}

if( ! function_exists('learn_press_get_user_education_points') ) {

	function learn_press_get_user_education_points( $user_id, $args = array() ) {

		$learner_statuses = array( 'learnpress_learner_take_free_course', 'learnpress_learner_pass_course', 'learnpress_learner_take_paid_course' );
		$time_range = learn_press_get_user_time_range();

		$defaults = apply_filters( 'etbi_default_edu_points_args', array(

			'from'		=> $time_range['from'],
			'until'		=> $time_range['until'],
			'format'	=> $time_range['format']

		) );

		$args= wp_parse_args( $args, $defaults );

		$mycred_args = array(

			'user_id'	=> $user_id,
			'ref'		=> array( 'ids' => $learner_statuses, 'compare' => 'IN' ),
			'time' 		=> array( 'dates' => array( $args['from'] . " 00:00:01", $args['until'] . " 23:59:59" ), 'compare' 	=> 'BETWEEN' ),
			'fields'	=> array( 'creds', 'time' )	

		);

		$log = new myCRED_Query_Log( $mycred_args );
		$creds = array();

		if( $log->have_entries() ) {

			foreach ( $log->results as $cred ) {

				$date_time = date_i18n( $args['format'], $cred->time );

				if( array_key_exists( $date_time, $creds ) ) {

					$creds[ $date_time ] += (int) $cred->creds;

					continue;

				}

				$creds[ $date_time ] = (int) $cred->creds;

			}

		}

		return apply_filters( 'learn_press_user_education_points', $creds, $log, $args );

	}


}

if( ! function_exists('learn_press_get_user_server_points') ) {

	function learn_press_get_user_server_points( $user_id, $args = array() ) {

		$pipeline_statuses = array( 'pipeline_processing', 'pipeline_done' );
		$time_range = learn_press_get_user_time_range();

		$defaults = apply_filters( 'etbi_default_server_points_args', array(

			'from'		=> $time_range['from'],
			'until'		=> $time_range['until'],
			'format'	=> $time_range['format'],
			'ref'		=> array( 'ids' => $pipeline_statuses, 'compare' => 'IN' )

		) );

		$args = wp_parse_args( $args, $defaults );

		$mycred_args = array(

			'user_id'	=> $user_id,
			'ref'		=> array( 'ids' => $pipeline_statuses, 'compare' => 'IN' ),
			'time' 		=> array( 'dates' => array( $args['from'] . " 00:00:01", $args['until'] . " 23:59:59" ), 'compare' 	=> 'BETWEEN' ),
			'fields'	=> array( 'creds', 'time' )	

		);

		$log = new myCRED_Query_Log( $mycred_args );
		$creds = array();

		if( $log->have_entries() ) {

			foreach ( $log->results as $cred ) {
				
				$date_time = date_i18n( $args['format'], $cred->time );

				if( array_key_exists( $date_time, $creds ) ) {

					$creds[ $date_time ] += (int) $cred->creds;

					continue;

				}

				$creds[ $date_time ] = (int) $cred->creds;

			}

		}

		return apply_filters( 'learn_press_user_server_points', $creds, $log, $args );

	}

}

