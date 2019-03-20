<?php
/**
 * Template for displaying BuddyPress profile points page.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/addons/buddypress/profile/points.php.
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

if( $mycred_active ) {

	$pagination = 25;

	$args = array( 'number' => $pagination, 'user_id' => $user_id );

	$log = new myCRED_Query_Log( $args );

	if( $log->have_entries() ) {

		// $log->front_navigation( 'top', $pagination );

		$log->display();

		$log->front_navigation( 'bottom', $pagination );

	} else {

		echo '<span class="alert alert-primary">'.__( 'No points yet', 'etbi' ).'</span>';

	}

}