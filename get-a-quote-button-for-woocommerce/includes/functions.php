<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly 

/* -------------------------------------------------------------------------- */
/* Get settings option 
/* -------------------------------------------------------------------------- */

if( !function_exists('wpb_gqb_get_option') ){
    function wpb_gqb_get_option( $option, $section, $default = '' ) {
 
        $options = get_option( $section );
     
        if ( isset( $options[$option] ) ) {
            return $options[$option];
        }
     
        return $default;
    }
}

/**
 * Searches for a contact form ID by a hash string.
 *
 * @param string $hash Part of a hash string.
 * @return Contact form ID.
 */
if ( ! function_exists( 'wpb_gqb_wpcf7_get_contact_form_id_by_hash' ) ) {
    function wpb_gqb_wpcf7_get_contact_form_id_by_hash( $hash ) {
        global $wpdb;

        $hash = trim( $hash );

        if ( strlen( $hash ) < 7 ) {
            return null;
        }

        $like = $wpdb->esc_like( $hash ) . '%';

        // Properly prepared SQL query, ignoring PHPCS false positive
        return $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_hash' AND meta_value LIKE %s",
                $like
            )
        );
    }
}


/**
 * Show or hide the product info in the form
 */

add_action( 'init', function(){
    add_filter('wpcf7_form_class_attr', function( $class ){
        $product_info       = wpb_gqb_get_option( 'wpb_gqb_form_product_info', 'woo_settings', 'hide' );

        if( $product_info ){
            $class .= ' wpb_gqb_form_product_info_' . esc_attr($product_info);
        }

        return $class;
    });
});

/**
 * CF7 Post Shortcode
 */

add_action( 'wpcf7_init', 'wpb_gqb_cf7_add_form_tag_for_post_title' );
 
function wpb_gqb_cf7_add_form_tag_for_post_title() {
    wpcf7_add_form_tag( 'post_title', 'wpb_gqb_cf7_post_title_tag_handler' );
    wpcf7_add_form_tag( 'gqb_product_title', 'wpb_gqb_cf7_send_post_title_tag_handler' );
}
 
function wpb_gqb_cf7_post_title_tag_handler( $tag ) {
    if(isset($_POST['wpb_post_id'])){
        $id = intval( wp_unslash( $_POST['wpb_post_id'] ) );
        return '<input type="hidden" name="post-title" value="'. esc_attr( get_the_title($id) ).'">';
    }
}

/**
 * Send the product title
 */

function wpb_gqb_cf7_send_post_title_tag_handler( $tag ) {
    if(isset($_POST['wpb_post_id'])){
        $id = intval( wp_unslash( $_POST['wpb_post_id'] ) );
        return '<input class="gqb_hidden_field gqb_product_title" type="text" name="gqb_product_title" value="'. esc_attr( get_the_title($id) ) .'">';
    }
}


/**
 * Premium Links
 */

add_action( 'wpb_gqb_after_settings_page', function(){
    ?>
    <div class="wpb_gqb_pro_features wrap">
        <h3>Premium Version Features:</h3>
        <ul>
            <li>Get user selected product informations like <strong>title</strong>, <strong>price</strong>, <strong>quantity</strong>, and <strong>variations</strong> etc.</li>
            <li>Contact Form 7 and WPForms custom mail/smart tags for <strong>title</strong>, <strong>price</strong>, <strong>quantity</strong>, and <strong>variations</strong> etc.</li>
            <li>Hide the price and cart button if has a quote button for <strong>selected products</strong>.</li>
            <li>Advanced custom shortcode builder for multiple quote buttons.</li>
            <li>Different quote buttons for different products.</li>
            <li>Different contact forms for different quote buttons.</li>
            <li>Selected products, categories, tags, types, stock status, User status, role, etc filter can be added to apply the quote buttons.</li>
            <li>Button and popup spacing and typography customization.</li>
            <li>Elementor support, adding custom quote button directly from the Elementor editor. </li>
        </ul>
        <div class="wpb-submit-button">
            <a class="button button-primary button-pro" href="https://wpbean.com/downloads/get-a-quote-button-pro-for-woocommerce-and-elementor/" target="_blank">Get the Pro</a>
        </div>
    </div>
    <?php
} );