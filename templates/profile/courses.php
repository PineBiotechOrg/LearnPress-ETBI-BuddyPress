<?php
/**
 * Template for displaying BuddyPress profile courses page.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/addons/buddypress/profile/courses.php.
 *
 * @author   ThimPress
 * @package  LearnPress/BuddyPress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();
?>

<?php
$limit     = apply_filters( 'learn_press_profile_tab_courses_all_limit', LP()->settings->get( 'profile_courses_limit', 10 ) );
$num_pages = learn_press_get_num_pages( $user->_get_found_rows(), $limit );
$user_id = bp_displayed_user_id();
$profile = learn_press_get_profile( $user_id );

$query   = 'purchased';

$tax_query = array( 

                array(

                    'taxonomy'  => 'course_category',
                    'field'     => 'slug',
                    'terms'     => array('project'),
                    'operator'  =>'NOT IN'

) );
 ?>

<?php $courses = $profile->query_courses( $query, array( 'status' => '' ) ); ?>

<?php if ( $courses['items'] ) { ?>

        <div id="lp-project-archive" class="thim-course-grid" data-cookie="grid-layout">

            <?php
                global $post;

                foreach ( $courses['items'] as $item ) {

                    $course = learn_press_get_course( $item->get_id() );
                    $post   = get_post( $item->get_id() );
                    setup_postdata( $post );
                    learn_press_get_template( 'content-course.php', array( 'user_course' => $item, 'user' => $user ) );

                }
                wp_reset_postdata();
            ?>

        </div>

<?php
    } else {
        learn_press_display_message( __( 'No courses!', 'learnpress-buddypress' ) );
    }
?>

