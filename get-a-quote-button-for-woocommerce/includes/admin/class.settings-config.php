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
        add_action( 'admin_menu', array( $this, 'admin_menu_doc_support' ), 90 );
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

    /**
     * Adding the settings page to the WP admin menu.
     *
     * @return void
     */
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

        add_submenu_page(
				$this->settings_name,
				esc_html__( 'Quote Button Settings', 'get-a-quote-button-for-woocommerce' ),
				esc_html__( 'Settings', 'get-a-quote-button-for-woocommerce' ),
				'delete_posts',
				$this->settings_name,
				array( $this, 'plugin_page' )
			);
    }

    /**
     * Adding the doc and support links to the quote admin menu.
     *
     * @return void
     */
    public function admin_menu_doc_support() {
        add_submenu_page(
            $this->settings_name,
            esc_html__( 'Documentation', 'get-a-quote-button-for-woocommerce' ),
            esc_html__( 'Docs', 'get-a-quote-button-for-woocommerce' ),
            'delete_posts',
            $this->settings_name . '-docs',
            array( $this, 'documentation_page_redirect' )
        );

        add_submenu_page(
            $this->settings_name,
            esc_html__( 'Support', 'get-a-quote-button-for-woocommerce' ),
            esc_html__( 'Support', 'get-a-quote-button-for-woocommerce' ),
            'delete_posts',
            $this->settings_name . '-support',
            array( $this, 'support_page_redirect' )
        );

        add_submenu_page(
            $this->settings_name,
            esc_html__( 'Get the Pro', 'get-a-quote-button-for-woocommerce' ),
            esc_html__( 'Get the Pro', 'get-a-quote-button-for-woocommerce' ),
            'delete_posts',
            $this->settings_name . '-pro',
            array( $this, 'pro_page_redirect' )
        );
    }

    /**
     * Redirect to the documentation page.
     *
     * @return void
     */
    public function documentation_page_redirect() {
        wp_redirect( esc_url( 'https://docs.wpbean.com/docs/get-a-quote-button-for-woocommerce/' ) );
        exit;
    }

    /**
     * Redirect to the support page.
     *
     * @return void
     */
    public function support_page_redirect() {
        wp_redirect( esc_url( 'https://wpbean.com/support/' ) );
        exit;
    }

    /**
     * Redirect to the pro page.
     *
     * @return void
     */
    public function pro_page_redirect() {
        wp_redirect( esc_url( 'https://wpbean.com/downloads/get-a-quote-button-pro-for-woocommerce-and-elementor/?utm_content=Get+A+Quote+Pro&utm_campaign=adminlink&utm_medium=admin-submenu&utm_source=FreeVersion' ) );
        exit;
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
            ),
            array(
                'id'    => 'hide_cart_settings',
                'title' => esc_html__( 'Hide Cart Button', 'get-a-quote-button-for-woocommerce' ),
                'pro'   => true,
            ),
            array(
                'id'    => 'hide_price_settings',
                'title' => esc_html__( 'Hide Price', 'get-a-quote-button-for-woocommerce' ),
                'pro'   => true,
            ),
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
                    'name'    => 'wpb_gqb_form_plugin',
                    'label'   => esc_html__( 'Form Plugin for the Quote', 'get-a-quote-button-for-woocommerce' ),
                    'desc'    => esc_html__( 'Select a form plugin that you like to use for the quote popup. Please install the selected plugin.', 'get-a-quote-button-for-woocommerce' ),
                    'type'    => 'select',
                    'size'    => 'wpb-select-buttons',
                    'default' => 'wpcf7',
                    'options' => array(
                        'wpcf7'   => esc_html__( 'Contact Form 7', 'get-a-quote-button-for-woocommerce' ),
                        'wpforms' => esc_html__( 'WPForms', 'get-a-quote-button-for-woocommerce' ),
                    ),
                ),
                array(
                    'name'    => 'wpb_gqb_cf7_form_id',
                    'label'   => esc_html__( 'Select a CF7 Form', 'get-a-quote-button-for-woocommerce' ),
                    'desc'    => esc_html__( 'Select a Contact Form 7 form for Quote Button. Add [post_title] shortcode to CF7 form body and [post-title] to the CF7 mail body to show the product or any post title in mail. Example : <label>[post_title]</label>, Subject: [post-title]', 'get-a-quote-button-for-woocommerce' ),
                    'type'    => 'select',
                    'options' => wp_list_pluck(get_posts(array( 'post_type' => 'wpcf7_contact_form', 'numberposts' => -1 )), 'post_title', 'ID'),
                ),
                array(
                    'name'    => 'wpb_gqb_wpforms_form_id',
                    'label'   => esc_html__( 'Select a WPForms Form', 'get-a-quote-button-for-woocommerce' ),
                    'desc'    => esc_html__( 'If you are using the Quote button shortcode builder please ignore this.', 'get-a-quote-button-for-woocommerce' ),
                    'type'    => 'select',
                    'options' => wp_list_pluck(
                        get_posts(
                            array(
                                'post_type'   => 'wpforms',
                                'numberposts' => -1,
                            )
                        ),
                        'post_title',
                        'ID'
                    ),
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
                array(
                    'name'              => 'wpb_gqb_btn_border_radius',
                    'label'             => esc_html__( 'Button Border Radius', 'get-a-quote-button-for-woocommerce' ),
                    'desc'              => esc_html__( 'Button border radius. Can be in any CSS unit (px, em/rem, %). Default: 3px', 'get-a-quote-button-for-woocommerce' ),
                    'type'              => 'numberunit',
                    'default'           => 3,
                    'default_unit'      => 'px',
                    'sanitize_callback' => 'floatval',
                    'pro'               => true,
                    'options'           => array(
                        'px'  => esc_html__( 'PX', 'get-a-quote-button-for-woocommerce' ),
                        '%'   => esc_html__( '%', 'get-a-quote-button-for-woocommerce' ),
                        'em'  => esc_html__( 'em', 'get-a-quote-button-for-woocommerce' ),
                        'rem' => esc_html__( 'rem', 'get-a-quote-button-for-woocommerce' ),
                    ),
                ),
                array(
                    'name'              => 'wpb_gqb_btn_font_size',
                    'label'             => esc_html__( 'Button Font Size', 'get-a-quote-button-for-woocommerce' ),
                    'desc'              => esc_html__( 'Button font size. Can be in any CSS unit (px, em, rem). Default: 15px', 'get-a-quote-button-for-woocommerce' ),
                    'type'              => 'numberunit',
                    'default'           => 15,
                    'default_unit'      => 'px',
                    'sanitize_callback' => 'floatval',
                    'pro'               => true,
                    'options'           => array(
                        'px'  => esc_html__( 'PX', 'get-a-quote-button-for-woocommerce' ),
                        'em'  => esc_html__( 'em', 'get-a-quote-button-for-woocommerce' ),
                        'rem' => esc_html__( 'rem', 'get-a-quote-button-for-woocommerce' ),
                    ),
                ),
                array(
                    'name'              => 'wpb_gqb_btn_font_weight',
                    'label'             => esc_html__( 'Button Font Weight', 'get-a-quote-button-for-woocommerce' ),
                    'desc'              => esc_html__( 'Default: 600', 'get-a-quote-button-for-woocommerce' ),
                    'type'              => 'select',
                    'size'              => 'wpb-select-buttons',
                    'default'           => 600,
                    'sanitize_callback' => 'floatval',
                    'pro'               => true,
                    'options'           => array(
                        '100' => esc_html__( '100', 'get-a-quote-button-for-woocommerce' ),
                        '200' => esc_html__( '200', 'get-a-quote-button-for-woocommerce' ),
                        '300' => esc_html__( '300', 'get-a-quote-button-for-woocommerce' ),
                        '400' => esc_html__( '400', 'get-a-quote-button-for-woocommerce' ),
                        '500' => esc_html__( '500', 'get-a-quote-button-for-woocommerce' ),
                        '600' => esc_html__( '600', 'get-a-quote-button-for-woocommerce' ),
                        '700' => esc_html__( '700', 'get-a-quote-button-for-woocommerce' ),
                        '800' => esc_html__( '800', 'get-a-quote-button-for-woocommerce' ),
                        '900' => esc_html__( '900', 'get-a-quote-button-for-woocommerce' ),
                    ),
                ),
                array(
                    'name'              => 'wpb_gqb_btn_padding',
                    'label'             => esc_html__( 'Button Padding', 'get-a-quote-button-for-woocommerce' ),
                    'desc'              => esc_html__( 'Button padding. Can be in any CSS unit (px, em, rem).', 'get-a-quote-button-for-woocommerce' ),
                    'type'              => 'spacing',
                    'sanitize_callback' => 'floatval',
                    'pro'               => true,
                    'options'           => array(
                        'px'  => esc_html__( 'PX', 'get-a-quote-button-for-woocommerce' ),
                        'em'  => esc_html__( 'em', 'get-a-quote-button-for-woocommerce' ),
                        'rem' => esc_html__( 'rem', 'get-a-quote-button-for-woocommerce' ),
                    ),
                ),
                array(
                    'name'              => 'wpb_gqb_btn_margin',
                    'label'             => esc_html__( 'Button Margin', 'get-a-quote-button-for-woocommerce' ),
                    'desc'              => esc_html__( 'Button margin. Can be in any CSS unit (px, em, rem).', 'get-a-quote-button-for-woocommerce' ),
                    'type'              => 'spacing',
                    'sanitize_callback' => 'floatval',
                    'pro'               => true,
                    'options'           => array(
                        'px'  => esc_html__( 'PX', 'get-a-quote-button-for-woocommerce' ),
                        'em'  => esc_html__( 'em', 'get-a-quote-button-for-woocommerce' ),
                        'rem' => esc_html__( 'rem', 'get-a-quote-button-for-woocommerce' ),
                    ),
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
                array(
                    'name'              => 'wpb_gqb_popup_padding',
                    'label'             => esc_html__( 'Popup Padding', 'get-a-quote-button-for-woocommerce' ),
                    'desc'              => esc_html__( 'Popup window padding. Can be in any CSS unit (px, em/rem, %). Default: 30px', 'get-a-quote-button-for-woocommerce' ),
                    'type'              => 'numberunit',
                    'default'           => 30,
                    'default_unit'      => 'px',
                    'sanitize_callback' => 'floatval',
                    'pro'               => true,
                    'options'           => array(
                        'px'  => esc_html__( 'PX', 'get-a-quote-button-for-woocommerce' ),
                        '%'   => esc_html__( '%', 'get-a-quote-button-for-woocommerce' ),
                        'em'  => esc_html__( 'em', 'get-a-quote-button-for-woocommerce' ),
                        'rem' => esc_html__( 'rem', 'get-a-quote-button-for-woocommerce' ),
                    ),
                ),
                array(
                    'name'    => 'wpb_gqb_popup_bg_color',
                    'label'   => esc_html__( 'Popup Background', 'get-a-quote-button-for-woocommerce' ),
                    'desc'    => esc_html__( 'Choose popup background color.', 'get-a-quote-button-for-woocommerce' ),
                    'type'    => 'color',
                    'default' => '#ffffff',
                    'pro'     => true,
                ),
                array(
                    'name'              => 'wpb_gqb_popup_border_radius',
                    'label'             => esc_html__( 'Popup Border Radius', 'get-a-quote-button-for-woocommerce' ),
                    'desc'              => esc_html__( 'Can be in any CSS unit (px, em/rem, %). Default: 5px', 'get-a-quote-button-for-woocommerce' ),
                    'type'              => 'numberunit',
                    'default'           => 5,
                    'default_unit'      => 'px',
                    'sanitize_callback' => 'floatval',
                    'pro'               => true,
                    'options'           => array(
                        'px'  => esc_html__( 'PX', 'get-a-quote-button-for-woocommerce' ),
                        '%'   => esc_html__( '%', 'get-a-quote-button-for-woocommerce' ),
                        'em'  => esc_html__( 'em', 'get-a-quote-button-for-woocommerce' ),
                        'rem' => esc_html__( 'rem', 'get-a-quote-button-for-woocommerce' ),
                    ),
                ),
            ),
            'hide_cart_settings'  => array(
                array(
                    'name'    => 'wpb_gqb_hide_cart_type',
                    'label'   => esc_html__( 'How would you like the cart to be hidden?', 'get-a-quote-button-for-woocommerce' ),
                    'desc'    => esc_html__( 'For some themes, the CSS might need to be changed. If this is the case, please contact.', 'get-a-quote-button-for-woocommerce' ),
                    'type'    => 'select',
                    'size'    => 'wpb-select-buttons',
                    'default' => 'after_cart',
                    'pro'     => true,
                    'options' => array(
                        'programmatically' => esc_html__( 'Programmatically', 'get-a-quote-button-for-woocommerce' ),
                        'using_css'        => esc_html__( 'Using CSS', 'get-a-quote-button-for-woocommerce' ),
                    ),
                ),
                array(
                    'name'  => 'wpb_gqb_hide_cart_button_for_all',
                    'label' => esc_html__( 'Hide for all the products', 'get-a-quote-button-for-woocommerce' ),
                    'desc'  => esc_html__( 'Hide the cart button for all the products.', 'get-a-quote-button-for-woocommerce' ),
                    'type'  => 'checkbox',
                    'pro'   => true,
                ),
                array(
                    'name'  => 'wpb_gqb_hide_cart_button_for_selected',
                    'label' => esc_html__( 'Hide for selected products', 'get-a-quote-button-for-woocommerce' ),
                    'desc'  => esc_html__( 'Hide the cart button if the quote button is enabled for a product (based on the selected products, categories and tags on shortcode generator).', 'get-a-quote-button-for-woocommerce' ),
                    'type'  => 'checkbox',
                    'pro'   => true,
                ),
                array(
                    'name'  => 'wpb_gqb_hide_cart_button_for_featured',
                    'label' => esc_html__( 'Hide for featured products', 'get-a-quote-button-for-woocommerce' ),
                    'desc'  => esc_html__( 'Hide the cart button only for the featured products.', 'get-a-quote-button-for-woocommerce' ),
                    'type'  => 'checkbox',
                    'pro'   => true,
                ),
            ),
            'hide_price_settings' => array(
                array(
                    'name'    => 'wpb_gqb_hide_price_type',
                    'label'   => esc_html__( 'How would you like the price to be hidden?', 'get-a-quote-button-for-woocommerce' ),
                    'desc'    => esc_html__( 'For some themes, the CSS might need to be changed. If this is the case, please contact.', 'get-a-quote-button-for-woocommerce' ),
                    'type'    => 'select',
                    'size'    => 'wpb-select-buttons',
                    'default' => 'after_cart',
                    'pro'     => true,
                    'options' => array(
                        'programmatically' => esc_html__( 'Programmatically', 'get-a-quote-button-for-woocommerce' ),
                        'using_css'        => esc_html__( 'Using CSS', 'get-a-quote-button-for-woocommerce' ),
                    ),
                ),
                array(
                    'name'  => 'wpb_gqb_hide_price_for_all',
                    'label' => esc_html__( 'Hide for all the products', 'get-a-quote-button-for-woocommerce' ),
                    'desc'  => esc_html__( 'Hide the price for all the products.', 'get-a-quote-button-for-woocommerce' ),
                    'type'  => 'checkbox',
                    'pro'   => true,
                ),
                array(
                    'name'  => 'wpb_gqb_hide_price_for_selected',
                    'label' => esc_html__( 'Hide for selected products', 'get-a-quote-button-for-woocommerce' ),
                    'desc'  => esc_html__( 'Hide the price if the quote button is enabled for a product (based on the selected products, categories and tags on shortcode generator).', 'get-a-quote-button-for-woocommerce' ),
                    'type'  => 'checkbox',
                    'pro'   => true,
                ),
                array(
                    'name'  => 'wpb_gqb_hide_price_for_featured',
                    'label' => esc_html__( 'Hide for featured products', 'get-a-quote-button-for-woocommerce' ),
                    'desc'  => esc_html__( 'Hide the price only for the featured products.', 'get-a-quote-button-for-woocommerce' ),
                    'type'  => 'checkbox',
                    'pro'   => true,
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