<?php

/**
 * Plugin Name:       Get a Quote Button for WooCommerce
 * Plugin URI:        https://wpbean.com/downloads/get-a-quote-button-pro-for-woocommerce-and-elementor/
 * Description:       Get a Quote Button for WooCommerce using Contact Form 7. It can be used for requesting a quote, pre-sale questions or query.
 * Requires at least: 6.6
 * Requires PHP:      7.4
 * Version:           1.7
 * Author:            WPBean
 * Author URI:        https://wpbean.com/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       get-a-quote-button-for-woocommerce
 * Domain Path:       /languages
 *
 * @package Get a Quote Button for WooCommerce
 */

if (! defined('ABSPATH')) exit; // Exit if accessed directly 

/**
 * Define constants
 */

if (! defined('WPB_GQB_FREE_INIT')) {
	define('WPB_GQB_FREE_INIT', plugin_basename(__FILE__));
}


/**
 * This version can't be activate if premium version is active
 */

if (defined('WPB_GQB_PREMIUM')) {
	function wpb_gqb_install_free_admin_notice()
	{
?>
		<div class="error">
			<p><?php esc_html_e('You can\'t activate the free version of Get a Quote Button while you are using the premium one.', 'get-a-quote-button-for-woocommerce'); ?></p>
		</div>
	<?php
	}

	add_action('admin_notices', 'wpb_gqb_install_free_admin_notice');
	deactivate_plugins(plugin_basename(__FILE__));
	return;
}


/* -------------------------------------------------------------------------- */
/*                                Plugin Class                                */
/* -------------------------------------------------------------------------- */

class WPB_Get_Quote_Button
{

	//  Plugin version
	public $version = '1.7';

	// The plugin url
	public $plugin_url;

	// The plugin path
	public $plugin_path;

	// The theme directory path
	public $theme_dir_path;

	// Initializes the WPB_Get_Quote_Button() class
	public static function init()
	{
		static $instance = false;

		if (!$instance) {
			$instance = new WPB_Get_Quote_Button();

			add_action('after_setup_theme', array($instance, 'plugin_init'));
			add_action('activated_plugin', array($instance, 'activation_redirect'));
			add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($instance, 'plugin_action_links'));
			register_activation_hook(__FILE__, array($instance, 'activate'));
			register_deactivation_hook(plugin_basename(__FILE__), array($instance, 'wpb_gqb_lite_plugin_deactivation'));
		}

		return $instance;
	}

	//Initialize the plugin
	function plugin_init()
	{
		$this->file_includes();
		$this->init_classes();

		// Localize our plugin
		add_action('init', array($this, 'localization_setup'));

		// Loads frontend scripts and styles
		add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'), 999);

		add_action('admin_notices', array($this, 'admin_notices'));

		add_action('admin_notices', array($this, 'wpb_gqb_pro_discount_admin_notice'));

		add_action('admin_init', array($this, 'wpb_gqb_pro_discount_admin_notice_dismissed'));

		// In case any theme disable the CF7 scripts
		add_filter('wpcf7_load_js', '__return_true', 30);
		add_filter('wpcf7_load_css', '__return_true', 30);

		add_action('wp_footer', array($this, 'force_cf7_script_loading'));
	}

	/**
	 * Force CF7 Script Loading
	 */
	public function force_cf7_script_loading()
	{
		$force_cf7_scripts 	= wpb_gqb_get_option('wpb_gqb_force_cf7_scripts', 'form_settings');
		$form_id 			= wpb_gqb_get_option('wpb_gqb_cf7_form_id', 'form_settings');

		if ('on' === $force_cf7_scripts && isset($form_id)) {
			echo '<div class="wpb_gqb_hidden_cf7" style="display:none">';
			echo do_shortcode('[contact-form-7 id="' . esc_attr($form_id) . '"]');
			echo '</div>';
		}
	}

	/**
	 * Pro version discount
	 */
	function wpb_gqb_pro_discount_admin_notice()
	{
		$user_id = get_current_user_id();
		if (!get_user_meta($user_id, 'wpb_gqb_pro_discount_dismissed')) {
			printf('<div class="wpb-gqb-discount-notice updated" style="padding: 30px 20px;border-left-color: #27ae60;border-left-width: 5px;margin-top: 20px;"><p style="font-size: 18px;line-height: 32px">%s <a target="_blank" href="%s">%s</a>! %s <b>%s</b></p><a href="%s">%s</a></div>', esc_html__('Get a 10% exclusive discount on the premium version of the', 'get-a-quote-button-for-woocommerce'), 'https://wpbean.com/downloads/get-a-quote-button-pro-for-woocommerce-and-elementor/', esc_html__('Get a Quote Button for WooCommerce', 'get-a-quote-button-for-woocommerce'), esc_html__('Use discount code - ', 'get-a-quote-button-for-woocommerce'), 'NewCustomer', esc_url(add_query_arg('wpb-gqb-pro-discount-admin-notice-dismissed', 'true')), esc_html__('Dismiss', 'get-a-quote-button-for-woocommerce'));
		}
	}


	function wpb_gqb_pro_discount_admin_notice_dismissed()
	{
		$user_id = get_current_user_id();
		if (isset($_GET['wpb-gqb-pro-discount-admin-notice-dismissed'])) {
			add_user_meta($user_id, 'wpb_gqb_pro_discount_dismissed', 'true', true);
		}
	}

	/**
	 * Plugin Deactivation
	 */

	function wpb_gqb_lite_plugin_deactivation()
	{
		$user_id = get_current_user_id();
		if (get_user_meta($user_id, 'wpb_gqb_pro_discount_dismissed')) {
			delete_user_meta($user_id, 'wpb_gqb_pro_discount_dismissed');
		}

		flush_rewrite_rules();
	}





	// The plugin activation function
	public function activate()
	{
		update_option('wpb_gqb_installed', time());
		update_option('wpb_gqb_version', $this->version);
	}

	// The plugin activation redirect
	function activation_redirect($plugin)
	{
		if ($plugin === plugin_basename(__FILE__)) {
			wp_safe_redirect(admin_url('admin.php?page=get-a-quote-button'));
			exit; // Ensure script execution stops after redirection
		}
	}

	function plugin_action_links($links)
	{


		$custom['wpb-gqb-pro'] = sprintf(
			'<a href="%1$s" aria-label="%2$s" target="_blank" rel="noopener noreferrer"
				style="color: #00a32a; font-weight: 700;"
				onmouseover="this.style.color=\'#008a20\';"
				onmouseout="this.style.color=\'#00a32a\';"
				>%3$s</a>',
			esc_url(
				add_query_arg(
					[
						'utm_content'  => 'Get+A+Quote+Pro',
						'utm_campaign' => 'adminlink',
						'utm_medium'   => 'plugin-actionlink',
						'utm_source'   => 'FreeVersion',
					],
					'https://wpbean.com/downloads/get-a-quote-button-pro-for-woocommerce-and-elementor/'
				)
			),
			esc_attr__('Upgrade to Get a Quote Pro', 'get-a-quote-button-for-woocommerce'),
			esc_html__('Get the Pro', 'get-a-quote-button-for-woocommerce')
		);

		$custom['wpb-gqb-settings'] = sprintf(
			'<a href="%s" aria-label="%s">%s</a>',
			esc_url(
				add_query_arg(
					['page' => 'get-a-quote-button'],
					admin_url('admin.php')
				)
			),
			esc_attr__('Go to Get a Quote Settings page', 'get-a-quote-button-for-woocommerce'),
			esc_html__('Settings', 'get-a-quote-button-for-woocommerce')
		);

		$custom['wpb-gqb-docs'] = sprintf(
			'<a href="%1$s" aria-label="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a>',
			esc_url('https://docs.wpbean.com/docs/get-a-quote-button-for-woocommerce/'),
			esc_attr__('Read the documentation', 'get-a-quote-button-for-woocommerce'),
			esc_html__('Docs', 'get-a-quote-button-for-woocommerce')
		);

		return array_merge($custom, (array) $links);


		// $links[] = '<a href="'. esc_url( 'https://wpbean.com/downloads/get-a-quote-button-pro-for-woocommerce-and-elementor/?utm_content=Get+A+Quote+Pro&utm_campaign=adminlink&utm_medium=plugin-actionlink&utm_source=FreeVersion' ) .'">'. esc_html__('Get the Pro', 'get-a-quote-button-for-woocommerce') .'</a>';
		// $links[] = '<a href="'. esc_url( admin_url( 'admin.php?page=get-a-quote-button' ) ) .'">'. esc_html__('Settings', 'get-a-quote-button-for-woocommerce') .'</a>';
		// return $links;
	}

	// Load the required files
	function file_includes()
	{
		include_once dirname(__FILE__) . '/includes/functions.php';
		include_once dirname(__FILE__) . '/includes/class-shortcode.php';

		if (is_admin()) {
			include_once dirname(__FILE__) . '/includes/admin/class.settings-api.php';
			include_once dirname(__FILE__) . '/includes/admin/class.settings-config.php';

			if (!class_exists('WpBean_AccordionMenu_AvailableHire')) {
				include_once __DIR__ . '/includes/AvailableHire/AvailableHire.php';
			}
		}

		if (class_exists('woocommerce')) {
			include_once dirname(__FILE__) . '/includes/class-woocommerce.php';
		}

		if (defined('DOING_AJAX') && DOING_AJAX) {
			include_once dirname(__FILE__) . '/includes/class-ajax.php';
		}
	}

	// Initialize the classes
	public function init_classes()
	{

		new WPB_GQB_Shortcode_Handler();

		if (is_admin()) {
			new WPB_GQB_Plugin_Settings();

			new WpBean_AccordionMenu_AvailableHire();
		}

		if (class_exists('woocommerce')) {
			new WPB_GQB_WooCommerce_Handler();
		}

		if (defined('DOING_AJAX') && DOING_AJAX) {
			new WPB_GQB_Ajax();
		}
	}

	// Initialize plugin for localization
	public function localization_setup()
	{
		load_plugin_textdomain('get-a-quote-button-for-woocommerce', false, dirname(plugin_basename(__FILE__)) . '/languages/');
	}

	// Loads frontend scripts and styles
	public function enqueue_scripts()
	{

		do_action('cfturnstile_enqueue_scripts');

		wp_enqueue_script('google-recaptcha'); // Enqueue Google reCAPTCHA script if enabled

		if (function_exists('wpcf7_script_is')) {
			if (!wpcf7_script_is()) {
				wpcf7_enqueue_scripts();
				wpcf7_enqueue_styles();
			}
		}

		// All styles goes here
		wp_enqueue_style('wpb-get-a-quote-button-sweetalert2', plugins_url('assets/css/sweetalert2.min.css', __FILE__), array(), $this->version);
		wp_enqueue_style('wpb-get-a-quote-button-styles', plugins_url('assets/css/frontend.css', __FILE__), array(), $this->version);

		// All scripts goes here
		wp_enqueue_script('wpb-get-a-quote-button-sweetalert2', plugins_url('assets/js/sweetalert2.all.min.js', __FILE__), array('jquery'), $this->version, true);
		wp_enqueue_script('wpb-get-a-quote-button-scripts', plugins_url('assets/js/frontend.js', __FILE__), array('jquery', 'wp-util'), $this->version, true);


		$btn_color       		= wpb_gqb_get_option('wpb_gqb_btn_color', 'btn_settings', '#ffffff');
		$bg_color       		= wpb_gqb_get_option('wpb_gqb_btn_bg_color', 'btn_settings', '#17a2b8');
		$btn_hover_color       	= wpb_gqb_get_option('wpb_gqb_btn_hover_color', 'btn_settings', '#ffffff');
		$btn_bg_hover_color     = wpb_gqb_get_option('wpb_gqb_btn_bg_hover_color', 'btn_settings', '#138496');
		$custom_css = "
		.wpb-get-a-quote-button-btn-default,
		.wpb-gqf-form-style-true input[type=submit],
		.wpb-gqf-form-style-true input[type=button],
		.wpb-gqf-form-style-true input[type=submit],
		.wpb-gqf-form-style-true input[type=button]{
			color: {$btn_color};
			background: {$bg_color};
		}
		.wpb-get-a-quote-button-btn-default:hover, .wpb-get-a-quote-button-btn-default:focus,
		.wpb-gqf-form-style-true input[type=submit]:hover, .wpb-gqf-form-style-true input[type=submit]:focus,
		.wpb-gqf-form-style-true input[type=button]:hover, .wpb-gqf-form-style-true input[type=button]:focus,
		.wpb-gqf-form-style-true input[type=submit]:hover,
		.wpb-gqf-form-style-true input[type=button]:hover,
		.wpb-gqf-form-style-true input[type=submit]:focus,
		.wpb-gqf-form-style-true input[type=button]:focus {
			color: {$btn_hover_color};
			background: {$btn_bg_hover_color};
		}";

		wp_add_inline_style('wpb-get-a-quote-button-styles', $custom_css);
	}

	// plugin admin notices
	public function admin_notices()
	{

		$form_plugin = wpb_gqb_get_option('wpb_gqb_form_plugin', 'form_settings', 'wpcf7');

		if (! defined('WPCF7_PLUGIN') || ! defined('WPFORMS_VERSION')) {
			// If Contact Form 7 is not installed or activated
			if ($form_plugin == 'wpcf7' && ! defined('WPCF7_PLUGIN')) {
				$this->show_cf7_notice();
			}

			// If WPForms is not installed or activated
			if ($form_plugin == 'wpforms' && ! defined('WPFORMS_VERSION')) {
				$this->show_wpforms_notice();
			}
		}
	}

	/**
	 * Show notice if Contact Form 7 is not installed or activated
	 */
	public function show_cf7_notice()
	{
	?>
		<div class="notice notice-error is-dismissible">
			<p>
				<b><?php esc_html_e('Get a Quote Button', 'get-a-quote-button-for-woocommerce'); ?></b> <?php esc_html_e('requires', 'get-a-quote-button-for-woocommerce'); ?> <b><?php esc_html_e('Contact Form 7', 'get-a-quote-button-for-woocommerce'); ?></b> <?php esc_html_e('to work with.', 'get-a-quote-button-for-woocommerce'); ?>
				<a href="https://wordpress.org/plugins/contact-form-7" target="_blank"><?php esc_html_e('Install Contact Form 7', 'get-a-quote-button-for-woocommerce'); ?></a>
			</p>
		</div>
	<?php
	}

	/**
	 * Show notice if WPForms is not installed or activated
	 */
	public function show_wpforms_notice()
	{
	?>
		<div class="notice notice-error is-dismissible">
			<p>
				<b><?php esc_html_e('Get a Quote Button', 'get-a-quote-button-for-woocommerce'); ?></b> <?php esc_html_e('requires', 'get-a-quote-button-for-woocommerce'); ?> <b><?php esc_html_e('WPForms', 'get-a-quote-button-for-woocommerce'); ?></b> <?php esc_html_e('to work with.', 'get-a-quote-button-for-woocommerce'); ?>
				<a href="https://wordpress.org/plugins/wpforms-lite" target="_blank"><?php esc_html_e('Install WPForms', 'get-a-quote-button-for-woocommerce'); ?></a>
			</p>
		</div>
<?php
	}
}


/* -------------------------------------------------------------------------- */
/*                            Initialize the plugin                           */
/* -------------------------------------------------------------------------- */

function wpb_get_quote_button()
{
	return WPB_Get_Quote_Button::init();
}

// kick it off
wpb_get_quote_button();
