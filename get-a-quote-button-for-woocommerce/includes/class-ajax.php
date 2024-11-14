<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly 

/**
 * Ajax Class
 */
class WPB_GQB_Ajax {

    /**
     * Bind actions
     */
    function __construct() {

        add_action( 'wp_ajax_fire_contact_form', array( $this, 'fire_contact_form' ) );
        add_action( 'wp_ajax_nopriv_fire_contact_form', array( $this, 'fire_contact_form' ) );
    }

    /**
     * Form Content
     */

    public function fire_contact_form() {
        check_ajax_referer( 'wpb-get-a-quote-button-ajax', '_wpnonce' );

        $contact_form_id = isset( $_POST['contact_form_id'] ) ? intval( $_POST['contact_form_id'] ) : 0;

        if ( $contact_form_id > 0 && get_post_type( $contact_form_id ) === 'wpcf7_contact_form' ) {

            $response = do_shortcode( '[contact-form-7 id="'.esc_attr($contact_form_id).'"]' );
        
            wp_send_json_success($response);
        }else{
            wp_send_json_error( esc_html__( 'Invalid CF7 Form ID', 'wpb-get-a-quote-button' ) );
        }
    }
}
