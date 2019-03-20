<?php
/**
 * Template for displaying user's certificates in profile page.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/addons/certificates/list-certificates.php.
 *
 * @author  ThimPress
 * @package LearnPress/Certificates
 * @version 3.0.0
 */

defined( 'ABSPATH' ) or exit;

if ( $certificates ) {
	?>
    <h3 class="profile-heading"><?php esc_html_e( 'Certificates', 'learnpress-certificates' ); ?></h3>
    <ul class="learn-press-courses profile-certificates">
		<?php foreach ( $certificates as $course_id => $data ) { ?>
            <li class="course">
				<?php
				$certificate = new LP_User_Certificate( $data );
				$template_id = uniqid( $certificate->get_uni_id() );
				$course      = learn_press_get_course( $course_id );
				?>
                <a href="<?php echo $certificate->get_sharable_permalink(); ?>" class="course-permalink">
                    <div class="course-thumbnail">
                        <div id="<?php echo $template_id; ?>" class="certificate-preview">
                            <div class="certificate-preview-inner">
                                <img class="cert-template" src="<?php echo $certificate->get_template(); ?>">
                                <canvas></canvas>
                            </div>
                        </div>
                    </div>
                </a>
                <h4 class="course-title">
                    <a href="<?php echo $course->get_permalink();?>"><?php echo $course->get_title(); ?></a>
                </h4>
            </li>
            <script type="text/javascript">

                jQuery(function ($) {
                    

                    var certificates = ( $.isArray( window.certificates ) && window.certificates.count > 0 ) ? window.certificates : [];

                    console.log( certificates );

                   
                    certificates.push(new LP_Certificate('#<?php echo $template_id;?>', <?php echo $certificate;?>));

                    window.certificates = certificates;

                });

            </script>
		<?php } ?>
    </ul>
<?php } else {
	learn_press_display_message( __( 'No certificates!', 'learnpress-certificates' ) );
} ?>
