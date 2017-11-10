<?php

function shortcode_testimonial_short ( $atts ) {

	if( !isset( $atts['id'] ) )
		return;

	$args = array(

		'post_type' => 'testimonial',
		'posts_per_page' => 1

	);

	if( isset( $atts['category'] ) ) {

		$category = $atts['category'];

		if( is_numeric( $category ) )
		$category = get_term_by( 'id', $category, 'testimonial_category' )->slug;

		$args['testimonial_category'] = $category;

	}

	if( is_numeric( $atts['id'] ) )
		$args['include'] = $atts['id'];
	elseif( $atts['id'] == 'random' )
		$args['orderby'] = 'rand';

	if( $testimonials = get_posts( $args ) ) {

		ob_start();

		$testimonial = new WP_Testimonial( array_pop( $testimonials )->ID );
		$testimonial->word_limit = ( isset( $atts['word_limit'] ) && is_numeric( $atts['word_limit'] ) ? $atts['word_limit'] : -1 );
		$testimonial->render();

		$output = ob_get_contents();
		ob_end_clean();

		if( isset( $atts['cycle'] ) && $atts['cycle'] ) {

			$output = '<script type="text/javascript" src="' . plugins_url( 'assets/js/ajax.js', dirname( __FILE__ ) ) . '"></script>' .
								sprintf( '<script type="text/javascript">jQuery(document).ready( function() { cycleTestimonial(%s, "%s"); });</script>', $testimonial->ID, admin_url( 'admin-ajax.php' ) ) .
								$output;

		}

		return $output;

	}

}
add_shortcode( 'testimonial_short', 'shortcode_testimonial_short' );

function shortcode_random_testimonials ( $atts ) {
	$args = array(
		'order' => 'ASC',
		'orderby' => 'rand',
		'post_type' => 'testimonial',
        'posts_per_page' => 3
	);

	$output = '';

	if (query_posts($args)) {
        ob_start();

        $count = 1;
        while(have_posts()) {
            the_post();

            $testimonial = new WP_Testimonial( get_the_ID() );
            $testimonial->word_limit = 20;
            $testimonial->render_short('shortcode', ($count == 3));
            $count++;
        }

        $output = ob_get_contents();
        ob_end_clean();

		wp_reset_query();
	}

    return $output;
}
add_shortcode( 'random_testimonials', 'shortcode_random_testimonials' );

function shortcode_testimonials ( $atts ) {
    $args = array(
        'order' => 'DESC',
        'orderby' => 'date',
        'post_type' => 'testimonial',
        'nopaging' => true
    );

    $output = '';

    if(query_posts($args)) {

        if(have_posts()) {
            ob_start();

            $index = 1;
            while(have_posts()) {
                the_post();

                $testimonial = new WP_Testimonial( get_the_ID() );
                $testimonial->word_limit = 20;
                $testimonial->render('shortcode', ($index == 1), ($index == 3));

                if ($index == 3) {
                    $index = 1;
                } else {
                    $index++;
                }
            }

            if ($index > 1) {
                echo '</div>';
            }

            $output = ob_get_contents();
            ob_end_clean();
        }

        wp_reset_query();
        return $output;
    }

}
add_shortcode( 'testimonials', 'shortcode_testimonials' );

function shortcode_testimonial_submission ( $atts ) {

	ob_start();

	if( isset( $_POST['testimonial-postback'] ) && wp_verify_nonce( $_POST['testimonial_nonce'], 'add-testimonial' ) ):

		// Require WordPress core functions we require for file upload
		if( !function_exists( 'media_handle_upload' ) ) {

			require_once( ABSPATH . 'wp-admin/includes/image.php' );
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
			require_once( ABSPATH . 'wp-admin/includes/media.php' );

		}

		// Build post array object
		$testimonial = array(

			'ID' => NULL,
			'post_content' => apply_filters( 'the_content', esc_textarea( $_POST['testimonial_description'] ) ),
			'post_name' => '',
			'post_type' => 'testimonial',
			'post_status' => 'draft',
			'post_title' => $_POST['testimonial_title']

		);

		// Only process captcha if it is not disabled by a filter
		$captcha = true;

		if( !apply_filters( 'ct_disable_captcha', false ) ) {

			// Ensure CAPTCHA passed
			require_once( trailingslashit( dirname( __FILE__ ) ) . 'recaptchalib.php' );

			$captcha = recaptcha_check_answer(

				'6Letc-kSAAAAANmcKKUmmybcly0ma7LXXc5Llcmm',
				$_SERVER['REMOTE_ADDR'],
				isset( $_POST['recaptcha_challenge_field'] ) ? $_POST['recaptcha_challenge_field'] : '',
				isset( $_POST['recaptcha_response_field'] ) ? $_POST['recaptcha_response_field'] : ''

			)->is_valid;

		}

		// Insert new testimonial, if successful, update meta data
		if( ( $post_id = wp_insert_post( $testimonial, false ) ) && $captcha ) {

			// Cache testimonial post we just inserted
			$testimonial = get_post( $post_id );

			update_post_meta( $post_id, 'testimonial_client_name', sanitize_text_field( $_POST['testimonial_client_name'] ) );
			update_post_meta( $post_id, 'testimonial_client_company_name', sanitize_text_field( $_POST['testimonial_client_company_name'] ) );
			update_post_meta( $post_id, 'testimonial_client_email', sanitize_email( $_POST['testimonial_client_email'] ) );
			update_post_meta( $post_id, 'testimonial_client_company_website', esc_url( $_POST['testimonial_client_company_website'] ) );
			update_post_meta( $post_id, 'testimonial_client_permission', $_POST['permission'] == '' ? 'no' : sanitize_text_field( $_POST['permission'] ) );

			// If thumbnail has been uploaded, assign as thumbnail
			if( !empty( $_FILES['thumbnail']['tmp_name'] ) )
				if( $attachment_id = media_handle_upload( 'thumbnail', $post_id ) )
					update_post_meta( $post_id, '_thumbnail_id', $attachment_id );

			// If a category has been selected, update the object term
			if( '' != $_POST['testimonial_category_group'] )
				wp_set_object_terms( $post_id, $_POST['testimonial_category_group'], 'testimonial_category' );

			// Send email notification to admin
			if( apply_filters( 'ct_send_new_testimonial_notification', true ) ) {

				$email = apply_filters( 'new_testimonial_notification_email', get_option( 'admin_email' ) );

				// Start output buffering and grab contents of email
				ob_start();

				include( trailingslashit( dirname( __FILE__) ) . 'templates/email-new-testimonial.php' );

				$html = ob_get_contents();
				ob_end_clean();

				// Prepare headers and send email

				$headers = array(
					'From: ' . sprintf( '%s <%s>', get_option( 'blogname' ), $email ),
					'Content-type: text/html',
					'Reply-to: ' . $email
				);

				if( apply_filters( 'new_testimonial_notification', true ) )
				wp_mail( $email, 'New Testimonial | ' . get_option( 'blogname' ), $html, $headers );

			}

			echo sprintf( '<p>%s</p>', apply_filters( 'new_testimonial_confirmation_message', __( 'We successfully received your testimonial. If approved, it will appear on our website. Thank you!', 'clean-testimonials' ) ) );

		}
		else {
			echo sprintf( '<p class="error">%s</p>', apply_filters( 'new_testimonial_failure_message', __( 'Sorry, but there was a problem with submitting your testimonial. Please ensure all required fields have been supplied and that you entered the CAPTCHA code correctly.', 'clean-testimonials' ) ) );
		}

	else:
	?>

	<script src="<?php echo plugins_url( 'assets/js/validation.js', dirname( __FILE__ ) ); ?>"></script>
	<script type="text/javascript">
		var RecaptchaOptions = {
			theme: '<?php echo apply_filters( 'testimonial_submission_captcha_theme', 'clean' ); ?>'
		}
	</script>

	<form id="add-testimonial" enctype="multipart/form-data" name="add-testimonial" method="POST" action="<?php the_permalink(); ?>">

		<label for="testimonial_title"><?php _e( 'Testimonial Title' ,'clean-testimonials' ); ?> (eg, &quot;<?php _e( "I'm so super happy", 'clean-testimonials' ); ?>!&quot;)</label><br />
		<input type="text" name="testimonial_title" required="required"/><br />

		<label for="testimonial_description"><?php _e( 'Your Testimonial (be as descriptive as you like here!)', 'clean-testimonials' ); ?></label><br />
		<textarea name="testimonial_description" rows="10" cols="20" required="required"></textarea><br />

		<label for="testimonial_category_group"><?php _e( 'Category (optional)', 'clean-testimonials' ); ?></label><br />
		<select name="testimonial_category_group">

			<option value=""><?php _e( 'None', 'clean-testimonials' ); ?></option>

			<?php if( $terms = get_terms( 'testimonial_category', array( 'hide_empty' => false ) ) ): ?>

				<?php foreach( $terms as $term ): ?>
				<option value="<?php echo $term->slug; ?>"><?php echo $term->name; ?></option>
				<?php endforeach; ?>

			<?php endif; ?>

		</select><br />

		<label for="testimonial_client_name"><?php _e( 'Your Name', 'clean-testimonials' ); ?></label><br />
		<input type="text" name="testimonial_client_name" id="testimonial_client_name" required="required"/><br />

		<label for="testimonial_client_company_name"><?php _e( 'Company Name', 'clean-testimonials' ); ?> <em><?php _e( '(optional)', 'clean-testimonials' ); ?></em></label><br />
		<input type="text" name="testimonial_client_company_name" id="testimonial_client_company_name" /><br />

		<label for="testimonial_client_email"><?php _e( 'Your Email' ,'clean-testimonials' ); ?> <em><?php _e( '(optional)', 'clean-testimonials' ); ?></em></label><br />
		<input type="text" name="testimonial_client_email" id="testimonial_client_email" /><br />

		<label for="testimonial_client_company_website"><?php _e( 'Your Website', 'clean-testimonials' ); ?> <em><?php _e( '(optional)', 'clean-testimonials' ); ?></em></label><br />
		<input type="text" name="testimonial_client_company_website" id="testimonial_client_company_website" /><br />

		<label for="thumbnail"><?php _e( 'Thumbnail', 'clean-testimonials' ); ?> <em><?php _e( '(optional)', 'clean-testimonials' ); ?></em></label><br />
		<input type="file" name="thumbnail" id="thumbnail" /><br />

		<label for="permission"><?php _e( 'Can we display your contact details? (EG, email and website)', 'clean-testimonials' ); ?>?</label><br />
		<input type="radio" name="permission" value="no" required="required" />&nbsp;<?php _e( 'No', 'clean-testimonials' ); ?><br />
		<input type="radio" name="permission" value="yes" required="required" />&nbsp;<?php _e( 'Yes', 'clean-testimonials' ); ?><br />

		<!-- hidden postback test field and nonce -->
		<input type="hidden" name="testimonial-postback" value="true" />
		<input type="hidden" name="testimonial_nonce" value="<?php echo wp_create_nonce( 'add-testimonial' ); ?>" />

		<?php

		if( !apply_filters( 'ct_disable_captcha', false ) ) {

			// Output captcha field if it is not disabled
			require_once( trailingslashit( dirname( __FILE__ ) ) . 'recaptchalib.php' );
			echo recaptcha_get_html('6Letc-kSAAAAAHOFnLaXa5lGfXLS9NN0InD0LsJP');

		}

		?>

		<input type="submit" id="submit-testimonial" value="Submit Testimonial" />

	</form>

	<?php

	endif;

	$content = ob_get_contents();
	ob_end_clean();

	return $content;

}
add_shortcode( 'testimonial-submission-form', 'shortcode_testimonial_submission' );

?>
