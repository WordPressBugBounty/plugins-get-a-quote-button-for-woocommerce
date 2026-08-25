<?php

/**
 * Plugin Name:       Get a Quote Button for WooCommerce 
 * Plugin URI:        https://wpbean.com/plugins/
 * Description:       Get a Quote Button for WooCommerce using Contact Form 7 or WPForms. It can be used for requesting a quote, pre-sale questions or query.
 * Version:           6.6.9
 * Author:            WPBean
 * Author URI:        https://wpbean.com
 * Text Domain:       get-a-quote-button
 * Domain Path:       /languages
 */
if ( !defined( 'ABSPATH' ) ) {
    exit;
    // Exit if accessed directly
}
/**
 * Freemius SDK
 */
require_once __DIR__ . '/includes/class-freemius.php';
/**
 * Plugin Main Class
 */
class WPB_Get_Quote_Button {
    /**
     * The plugin url
     *
     * @var string
     */
    public $plugin_url;

    /**
     * The plugin path
     *
     * @var string
     */
    public $plugin_path;

    /**
     * The theme directory path
     *
     * @var string
     */
    public $theme_dir_path;

    /**
     * Define Constants
     */
    public function define_constants() {
        define( 'WPB_GQB_VERSION', '6.6.9' );
        define( 'WPB_GQB_PLUGIN_FILE', __FILE__ );
        define( 'WPB_GQB_URI', plugin_dir_path( __FILE__ ) );
        // Single source of truth for the "Upgrade to Pro" landing page — update
        // here to change it everywhere (plugin action links and both React
        // admin apps, which receive it via wp_localize_script as upgradeUrl).
        define( 'WPB_GQB_PREMIUM_URL', 'https://wpbean.com/downloads/get-a-quote-button-pro-for-woocommerce-and-elementor/' );
        define( 'WPB_GQB_PLUGIN_PATH', trailingslashit( plugin_dir_path( __FILE__ ) ) );
        define( 'WPB_GQB_PLUGIN_WOO_TEMPLATE_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/woo-templates/' );
        // Set to true (or define it earlier in wp-config.php) to trace the
        // quote-data pipeline (button click -> popup render -> mail send)
        // to wp-content/wpb-gqb-quote-debug.log.
        if ( !defined( 'WPB_GQB_QUOTE_DEBUG' ) ) {
            define( 'WPB_GQB_QUOTE_DEBUG', false );
        }
    }

    // Initializes the WPB_Get_Quote_Button() class
    public static function init() {
        static $instance = false;
        if ( !$instance ) {
            $instance = new WPB_Get_Quote_Button();
            $instance->define_constants();
            // Include the database migration file early to ensure activation hook works
            include_once __DIR__ . '/includes/admin/class.db-migrate.php';
            register_activation_hook( __FILE__, array($instance, 'activate') );
            add_filter( 'wpcf7_use_recaptcha_net', '__return_true' );
            add_action( 'init', array($instance, 'init_functions') );
            add_action( 'plugins_loaded', array($instance, 'plugin_init') );
            add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array($instance, 'plugin_action_links') );
        }
        return $instance;
    }

    /**
     * Initialize selected functions using init hook
     *
     * @return void
     */
    public function init_functions() {
        // Disable loop_add_to_cart
        add_filter(
            'woocommerce_loop_add_to_cart_link',
            function ( $button, $product, $args = array() ) {
                if ( function_exists( 'is_wpb_gqb_show_cart_btn' ) && !is_wpb_gqb_show_cart_btn() && !$product->is_in_stock() ) {
                    return false;
                } else {
                    return $button;
                }
            },
            20,
            3
        );
    }

    /**
     * Initialize the plugin
     *
     * @return void
     */
    public function plugin_init() {
        $this->file_includes();
        $this->localization_setup();
        // Force loding the CF7 scripts
        add_filter( 'wpcf7_load_js', '__return_true', 30 );
        add_filter( 'wpcf7_load_css', '__return_true', 30 );
    }

    /**
     * Plugin activation function
     *
     * @return void
     */
    public function activate() {
        update_option( 'wpb_gqb_installed', time() );
        update_option( 'wpb_gqb_version', WPB_GQB_VERSION );
    }

    /**
     * Plugin action links
     *
     * @param array $links
     * @return void
     */
    function plugin_action_links( $links ) {
        $custom = array();
        $custom['wpb-gqb-pro'] = sprintf(
            '<a href="%1$s" aria-label="%2$s" target="_blank" rel="noopener noreferrer"
                    style="color: #00a32a; font-weight: 700;"
                    onmouseover="this.style.color=\'#008a20\';"
                    onmouseout="this.style.color=\'#00a32a\';"
                    >%3$s</a>',
            esc_url( add_query_arg( [
                'utm_content'  => 'Get+A+Quote+Pro',
                'utm_campaign' => 'adminlink',
                'utm_medium'   => 'plugin-actionlink',
                'utm_source'   => 'FreeVersion',
            ], WPB_GQB_PREMIUM_URL ) ),
            esc_attr__( 'Upgrade to Get a Quote Pro', 'get-a-quote-button' ),
            esc_html__( 'Get the Pro', 'get-a-quote-button' )
        );
        $custom['wpb-gqb-settings'] = sprintf(
            '<a href="%s" aria-label="%s">%s</a>',
            esc_url( add_query_arg( [
                'page' => 'get-a-quote-button-settings',
            ], admin_url( 'admin.php' ) ) ),
            esc_attr__( 'Go to Get a Quote Settings page', 'get-a-quote-button' ),
            esc_html__( 'Settings', 'get-a-quote-button' )
        );
        $custom['wpb-gqb-docs'] = sprintf(
            '<a href="%1$s" aria-label="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a>',
            esc_url( 'https://docs.wpbean.com/docs/get-a-quote-button-for-woocommerce/' ),
            esc_attr__( 'Read the documentation', 'get-a-quote-button' ),
            esc_html__( 'Docs', 'get-a-quote-button' )
        );
        return array_merge( $custom, $links );
    }

    /**
     * Load the required files
     *
     * @return void
     */
    function file_includes() {
        include_once __DIR__ . '/includes/functions.php';
        if ( is_admin() ) {
            include_once __DIR__ . '/includes/admin/class.admin-pages.php';
            include_once __DIR__ . '/includes/admin/class.review-notice.php';
        }
        // Baseline features available on every license tier (free + premium):
        // the core quote button, its WooCommerce/CF7/WPForms integrations, and
        // the Multi-Product Quote System used with its default settings.
        include_once __DIR__ . '/includes/admin/rest-api/Class.Settings-Rest-API.php';
        include_once __DIR__ . '/includes/frontend/class.quote-buttons.php';
        include_once __DIR__ . '/includes/frontend/class.enqueue-scripts.php';
        include_once __DIR__ . '/includes/class-shortcode.php';
        if ( class_exists( 'woocommerce' ) ) {
            include_once __DIR__ . '/includes/woocommerce/class.WooCommerce.php';
            // Multi-Product Quote System (session-backed, requires WooCommerce).
            include_once __DIR__ . '/includes/frontend/class.quote-list.php';
            include_once __DIR__ . '/includes/frontend/class.quote-list-shortcode.php';
            include_once __DIR__ . '/includes/frontend/class.quote-widget-shortcode.php';
            include_once __DIR__ . '/includes/frontend/class.quote-widget-classic-widget.php';
            include_once __DIR__ . '/includes/frontend/class.quote-widget-block.php';
        }
        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            include_once __DIR__ . '/includes/class-ajax.php';
        }
        // CF7 custom data tags.
        if ( defined( 'WPCF7_PLUGIN' ) ) {
            include_once __DIR__ . '/includes/cf7/cf7-product-data-tag-deprecated.php';
            // Planning to deprecated to the next update.
            include_once __DIR__ . '/includes/cf7/class.cf7-data-tags.php';
            // Adding new product data shortcodes and cf7 tag generator.
            include_once __DIR__ . '/includes/cf7/class.cf7-data-tag-generator.php';
        }
        // WPForms custom smart tags.
        if ( defined( 'WPFORMS_VERSION' ) ) {
            include_once __DIR__ . '/includes/wpforms/class.smart-tags.php';
        }
    }

    /**
     * Localization Setup
     *
     * @return void
     */
    public function localization_setup() {
        load_plugin_textdomain( 'get-a-quote-button', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }

}

/**
 *  Initialize the plugin
 *
 * @return void
 */
function wpb_get_quote_button() {
    return WPB_Get_Quote_Button::init();
}

wpb_get_quote_button();