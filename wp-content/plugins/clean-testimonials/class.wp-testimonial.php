<?php

// WP_Testimonial class

final class WP_Testimonial {

	/* Members */
	public $client;
	public $company;
	public $email;
	public $website;

	// Constructor
	public function __construct ( $post_id = null ) {

		if( !is_null( $post_id ) ) {

			$testimonial = self::get_instance( $post_id );
			$meta = get_post_meta( $testimonial->ID, '' );

			// Copy WP_Post public members
			foreach( $testimonial as $key => $value )
				$this->$key = $value;

			// Assign WP_Testimonial specific members
			$this->client = $meta['testimonial_client_name'][0];
			$this->company = $meta['testimonial_client_company_name'][0];
			$this->email = $meta['testimonial_client_email'][0];
			$this->website = $meta['testimonial_client_company_website'][0];

		}

	}

	/**
	 * Render a testimonial.
	 *
	 * @param string $context
	 * @return string
	 */
	public function render_short( $context = 'shortcode', $last = false ) {
		do_action( 'ct_before_render_testimonial', $this, $context );

		$pre_render = apply_filters( 'ct_pre_render_testimonial', '', $this, $context );

		if ( strlen( $pre_render ) >= 1 ) {
			echo $pre_render;
		}
		else {
			ob_start();
			?>
			<div class="col span_4 <?php if ($last) echo 'col_last'; ?>">
                <a class="testimonial" href="<?= get_permalink($this->ID) ?>">
                    <span class="text">
                        <?php
                            if( isset($this->word_limit) && $this->word_limit > 0 ) {
                                $words = explode(' ', $this->post_content);
                                echo implode(' ', (count($words) <= $this->word_limit ? $words : array_slice($words, 0, $this->word_limit)));

                                if (count($words) > $this->word_limit) echo ' ...';
                            }
                            else echo $this->post_content;
                        ?>
                    </span>

                    <span class="team-member">
                        <?php $image = wp_get_attachment_image_src( get_post_thumbnail_id( $this->ID ), array( 200, 200 ) ); ?>
                        <img src="<?php echo $image[0]; ?>" alt="<?= $this->client ?>" title="<?= $this->client ?>"/>

                        <span class="light"><?= $this->client ?></span>

                        <span class="position"><?= $this->company ?></span>
                    </span>
                </a>
			</div>

			<?php
			echo apply_filters( 'ct_render_testimonial', ob_get_clean(), $this, $context );
		}

		do_action( 'ct_after_render_testimonial', $this, $context );
	}

    public function render($context = 'shortcode', $first = false, $last = false) {
        do_action( 'ct_before_render_testimonial', $this, $context );

        $pre_render = apply_filters( 'ct_pre_render_testimonial', '', $this, $context );

        if ( strlen( $pre_render ) >= 1 ) {
            echo $pre_render;
        }
        else {
            ob_start();

            if ($first) echo '<div class="col span_12 testimonial-row">';
            ?>

            <div class="col span_4 <?php if ($last) echo 'col_last'; ?>">
                <a class="testimonial" href="<?= get_permalink($this->ID) ?>">
                    <span class="text">
                        <?php
                            if( isset($this->word_limit) && $this->word_limit > 0 ) {
                                $words = explode(' ', $this->post_content);
                                echo implode(' ', (count($words) <= $this->word_limit ? $words : array_slice($words, 0, $this->word_limit)));

                                if (count($words) > $this->word_limit) echo ' ...';
                            }
                            else echo $this->post_content;
                        ?>
                    </span>

                    <span class="team-member">
                        <?php $image = wp_get_attachment_image_src( get_post_thumbnail_id( $this->ID ), array( 200, 200 ) ); ?>
                        <img src="<?php echo $image[0]; ?>" alt="<?= $this->client ?>" title="<?= $this->client ?>"/>

                        <span class="light"><?= $this->client ?></span>

                        <div class="position"><?= $this->company ?></div>
                    </span>
                </a>
            </div>

            <?php
            if ($last) echo '</div>';

            echo apply_filters( 'ct_render_testimonial', ob_get_clean(), $this, $context );
        }

        do_action( 'ct_after_render_testimonial', $this, $context );
    }

	public static function get_instance ( $post_id ) {

		return WP_Post::get_instance( $post_id );

	}

}

?>
