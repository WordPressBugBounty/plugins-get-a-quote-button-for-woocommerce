<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly 

/**
 * Ajax Class
 */
class WPB_GQB_Ajax {

    /**
     * The form plugin being used
     *
     * @var string
     */
    private $form_plugin;

    /**
     * Bind actions
     */
    function __construct() {
        add_action( 'wp_ajax_fire_contact_form', array( $this, 'fire_contact_form' ) );
        add_action( 'wp_ajax_nopriv_fire_contact_form', array( $this, 'fire_contact_form' ) );

        $this->form_plugin = wpb_gqb_get_option( 'wpb_gqb_form_plugin', 'form_settings', 'wpcf7' );

		if('wpcf7' === $this->form_plugin){
			$this->form_plugin = 'wpcf7_contact_form';	
		}
    }

    /**
     * Form Content
     */
    public function fire_contact_form() {
        if ( ! wp_doing_ajax() ) {
            wp_send_json_error( esc_html__( 'Invalid request', 'get-a-quote-button-for-woocommerce' ) );
        }

        // phpcs:disable WordPress.Security.NonceVerification.Missing
		if ( ! isset( $_POST['contact_form_id'] ) ) {
			return;
		}

        // For WPForms Pro, not displaying for non login users.
        add_filter('wpforms_current_user_can', '__return_true');

        $contact_form_id = isset( $_POST['contact_form_id'] ) ? sanitize_key( $_POST['contact_form_id'] ) : 0;
        $shortcode_tag   = 'wpcf7_contact_form' === $this->form_plugin? 'contact-form-7' : 'wpforms';

        if( $this->form_plugin === 'wpcf7_contact_form' && get_post_type( $contact_form_id ) !== 'wpcf7_contact_form' ) {
            $contact_form_id = wpb_gqb_wpcf7_get_contact_form_id_by_hash( $contact_form_id );
        } else {
            $contact_form_id = intval( $contact_form_id );
        }

        if ( $contact_form_id > 0 && get_post_type( $contact_form_id ) === $this->form_plugin ) {
            $response = do_shortcode( '['.esc_attr( $shortcode_tag ).' id="'.esc_attr($contact_form_id).'"]' );
            wp_send_json_success($response);
        } else {
            wp_send_json_error( esc_html__( 'Invalid Form ID', 'get-a-quote-button-for-woocommerce' ) );
        }
    }

}
