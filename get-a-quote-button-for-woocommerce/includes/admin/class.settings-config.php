<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly 

if ( !class_exists('WPB_GQB_Plugin_Settings' ) ):
class WPB_GQB_Plugin_Settings {

    private $settings_api;
    private $settings_name = 'get-a-quote-button';

    function __construct() {
        $this->settings_api = new WPB_GQB_WeDevs_Settings_API;

        add_action( 'admin_init', array($this, 'admin_init') );
        add_action( 'admin_menu', array($this, 'admin_menu') );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
    }

    function admin_init() {

        //set the settings
        $this->settings_api->set_sections( $this->get_settings_sections() );
        $this->settings_api->set_fields( $this->get_settings_fields() );

        //initialize settings
        $this->settings_api->admin_init();
    }

    function admin_enqueue_scripts() {
        $screen = get_current_screen();

        if( $screen->id == 'toplevel_page_' . $this->settings_name ){
            $this->settings_api->admin_enqueue_scripts();
        }
    }

    function admin_menu() {
        add_menu_page(
            esc_html__( 'Get a Quote Button Settings', 'get-a-quote-button-for-woocommerce' ),
            esc_html__( 'Quote Button', 'get-a-quote-button-for-woocommerce' ),
            'delete_posts',
            $this->settings_name,
            array( $this, 'plugin_page' ),
            'dashicons-money-alt',
            50
        );
    }

    function get_settings_sections() {
        $sections = array(
            array(
                'id'    => 'form_settings',
                'title' => esc_html__( 'Form Settings', 'get-a-quote-button-for-woocommerce' )
            ),
            array(
                'id'    => 'woo_settings',
                'title' => esc_html__( 'WooCommerce Settings', 'get-a-quote-button-for-woocommerce' )
            ),
            array(
                'id'    => 'btn_settings',
                'title' => esc_html__( 'Button Settings', 'get-a-quote-button-for-woocommerce' )
            ),
            array(
                'id'    => 'popup_settings',
                'title' => esc_html__( 'Popup Settings', 'get-a-quote-button-for-woocommerce' )
            )
        );
        return $sections;
    }

    /**
     * Returns all the settings fields
     *
     * @return array settings fields
     */
    function get_settings_fields() {
        $settings_fields = array(
            'form_settings' => array(
                array(
                    'name'    => 'wpb_gqb_cf7_form_id',
                    'label'   => esc_html__( 'Select a CF7 Form', 'get-a-quote-button-for-woocommerce' ),
                    'desc'    => esc_html__( 'Select a Contact Form 7 form for Quote Button. Add [post_title] shortcode to CF7 form body and [post-title] to the CF7 mail body to show the product or any post title in mail. Example : <label>[post_title]</label>, Subject: [post-title]', 'get-a-quote-button-for-woocommerce' ),
                    'type'    => 'select',
                    'options' => wp_list_pluck(get_posts(array( 'post_type' => 'wpcf7_contact_form', 'numberposts' => -1 )), 'post_title', 'ID'),
                ),
                array(
                    'name'      => 'wpb_gqb_force_cf7_scripts',
                    'label'     => esc_html__( 'Force loading CF7\'s Scripts', 'get-a-quote-button-for-woocommerce' ),
                    'desc'      => esc_html__( 'If you\'re experiencing trouble submitting the popup form, check this.', 'get-a-quote-button-for-woocommerce' ),
                    'type'      => 'checkbox',
                ),
            ),
            'woo_settings' => array(
                array(
                    'name'      => 'woo_single_show_quote_form',
                    'label'     => esc_html__( 'Single Product', 'get-a-quote-button-for-woocommerce' ),
                    'desc'      => esc_html__( 'Show quote button on single product page.', 'get-a-quote-button-for-woocommerce' ),
                    'type'      => 'checkbox',
                    'default'   => 'on',
                ),
                array(
                    'name'      => 'woo_loop_show_quote_form',
                    'label'     => esc_html__( 'Products Loop', 'get-a-quote-button-for-woocommerce' ),
                    'desc'      => esc_html__( 'Show quote button on products loop.', 'get-a-quote-button-for-woocommerce' ),
                    'type'      => 'checkbox',
                ),
                array(
                    'name'    => 'wpb_gqb_btn_position',
                    'label'   => esc_html__( 'Button Position', 'get-a-quote-button-for-woocommerce' ),
                    'desc'    => esc_html__( 'Select button position. Default: After Cart Button.', 'get-a-quote-button-for-woocommerce' ),
                    'type'    => 'select',
                    'size'    => 'wpb-select-buttons',
                    'default' => 'after_cart',
                    'options' => array(
                        'before_cart'    => esc_html__( 'Before Cart', 'get-a-quote-button-for-woocommerce' ),
                        'after_cart'     => esc_html__( 'After Cart', 'get-a-quote-button-for-woocommerce' ),
                    )
                ),
                array(
                    'name'    => 'wpb_gqb_woo_show_only_for',
                    'label'   => esc_html__( 'Show the Button for', 'get-a-quote-button-for-woocommerce' ),
                    'desc'    => esc_html__( 'You can show the button for all the products or any specific products type. Default: All Products', 'get-a-quote-button-for-woocommerce' ),
                    'type'    => 'select',
                    'size'    => 'wpb-select-buttons',
                    'default' => 'all_products',
                    'options' => array(
                        'all_products'  => esc_html__( 'All Products', 'get-a-quote-button-for-woocommerce' ),
                        'out_of_stock'  => esc_html__( 'Only for Out to Stock Products', 'get-a-quote-button-for-woocommerce' ),
                        'featured'      => esc_html__( 'Only for Featured Products', 'get-a-quote-button-for-woocommerce' ),
                    )
                ),
                array(
                    'name'      => 'wpb_gqb_woo_btn_guest',
                    'label'     => esc_html__( 'Show Quote Button for Guest', 'get-a-quote-button-for-woocommerce' ),
                    'desc'      => esc_html__( 'Show WooCommerce quote button for guest users.', 'get-a-quote-button-for-woocommerce' ),
                    'type'      => 'checkbox',
                    'default'   => 'on',
                ),
                array(
                    'name'    => 'wpb_gqb_form_product_info',
                    'label'   => esc_html__( 'Product information in the form', 'get-a-quote-button-for-woocommerce' ),
                    'desc'    => esc_html__( 'The product informations can be shown or hide.', 'get-a-quote-button-for-woocommerce' ),
                    'type'    => 'select',
                    'size'    => 'wpb-select-buttons',
                    'default' => 'hide',
                    'options' => array(
                        'hide'    => esc_html__( 'Hide', 'get-a-quote-button-for-woocommerce' ),
                        'show'    => esc_html__( 'Show', 'get-a-quote-button-for-woocommerce' ),
                    )
                ),
            ),
            'btn_settings' => array(
                array(
                    'name'              => 'wpb_gqb_btn_text',
                    'label'             => esc_html__( 'Quote Button Text', 'get-a-quote-button-for-woocommerce' ),
                    'desc'              => esc_html__( 'You can add your own text for the quote button.', 'get-a-quote-button-for-woocommerce' ),
                    'placeholder'       => esc_html__( 'Get a Quote', 'get-a-quote-button-for-woocommerce' ),
                    'type'              => 'text',
                    'default'           => esc_html__( 'Get a Quote', 'get-a-quote-button-for-woocommerce' ),
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                array(
                    'name'    => 'wpb_gqb_btn_size',
                    'label'   => esc_html__( 'Button Size', 'get-a-quote-button-for-woocommerce' ),
                    'desc'    => esc_html__( 'Select button size. Default: Medium.', 'get-a-quote-button-for-woocommerce' ),
                    'type'    => 'select',
                    'size'    => 'wpb-select-buttons',
                    'default' => 'large',
                    'options' => array(
                        'small'     => esc_html__( 'Small', 'get-a-quote-button-for-woocommerce' ),
                        'medium'    => esc_html__( 'Medium', 'get-a-quote-button-for-woocommerce' ),
                        'large'     => esc_html__( 'Large', 'get-a-quote-button-for-woocommerce' ),
                    )
                ),
                array(
                    'name'    => 'wpb_gqb_btn_color',
                    'label'   => esc_html__( 'Button Color', 'get-a-quote-button-for-woocommerce' ),
                    'desc'    => esc_html__( 'Choose button color.', 'get-a-quote-button-for-woocommerce' ),
                    'type'    => 'color',
                    'default' => '#ffffff'
                ),
                array(
                    'name'    => 'wpb_gqb_btn_bg_color',
                    'label'   => esc_html__( 'Button Background', 'get-a-quote-button-for-woocommerce' ),
                    'desc'    => esc_html__( 'Choose button background color.', 'get-a-quote-button-for-woocommerce' ),
                    'type'    => 'color',
                    'default' => '#17a2b8'
                ),
                array(
                    'name'    => 'wpb_gqb_btn_hover_color',
                    'label'   => esc_html__( 'Button Hover Color', 'get-a-quote-button-for-woocommerce' ),
                    'desc'    => esc_html__( 'Choose button hover color.', 'get-a-quote-button-for-woocommerce' ),
                    'type'    => 'color',
                    'default' => '#ffffff'
                ),
                array(
                    'name'    => 'wpb_gqb_btn_bg_hover_color',
                    'label'   => esc_html__( 'Button Hover Background', 'get-a-quote-button-for-woocommerce' ),
                    'desc'    => esc_html__( 'Choose button hover background color.', 'get-a-quote-button-for-woocommerce' ),
                    'type'    => 'color',
                    'default' => '#138496'
                ),
            ),
            'popup_settings' => array(
                array(
                    'name'      => 'wpb_gqb_form_style',
                    'label'     => esc_html__( 'Enable Form Style', 'get-a-quote-button-for-woocommerce' ),
                    'desc'      => esc_html__( 'Check this to enable the form style.', 'get-a-quote-button-for-woocommerce' ),
                    'type'      => 'checkbox',
                    'default'   => 'on',
                ),
                array(
                    'name'      => 'wpb_gqb_allow_outside_click',
                    'label'     => esc_html__( 'Close Popup on Outside Click', 'get-a-quote-button-for-woocommerce' ),
                    'desc'      => esc_html__( 'If checked, the user can dismiss the popup by clicking outside it.', 'get-a-quote-button-for-woocommerce' ),
                    'type'      => 'checkbox',
                ),
                array(
                    'name'              => 'wpb_gqb_popup_width',
                    'label'             => esc_html__( 'Popup Width', 'get-a-quote-button-for-woocommerce' ),
                    'desc'              => esc_html__( 'Popup window width, Can be in px or %. The default width is 500px.', 'get-a-quote-button-for-woocommerce' ),
                    'type'              => 'numberunit',
                    'default'           => 500,
                    'default_unit'      => 'px',
                    'sanitize_callback' => 'floatval',
                    'options' => array(
                        'px'            => esc_html__( 'Px', 'get-a-quote-button-for-woocommerce' ),
                        '%'    => esc_html__( '%', 'get-a-quote-button-for-woocommerce' ),
                    )
                ),
            ),
        );

        return $settings_fields;
    }

    function plugin_page() {
        echo '<div id="wpb-gqb-settings" class="wpb-plugin-settings-wrap wrap">';

        settings_errors();
        
        $this->settings_api->show_navigation();
        $this->settings_api->show_forms();

        echo '</div>';

        do_action( 'wpb_gqb_after_settings_page' );
    }

    /**
     * Get all the pages
     *
     * @return array page names with key value pairs
     */
    function get_pages() {
        $pages = get_pages();
        $pages_options = array();
        if ( $pages ) {
            foreach ($pages as $page) {
                $pages_options[$page->ID] = $page->post_title;
            }
        }

        return $pages_options;
    }

}
endif;