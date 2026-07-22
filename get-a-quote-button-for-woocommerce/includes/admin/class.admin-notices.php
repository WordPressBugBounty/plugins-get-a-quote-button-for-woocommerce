<?php

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

/**
 * Admin Notices Class
 */
class WPB_GQB_Admin_Notices
{

	/**
	 * Class Constructor
	 */
	public function __construct()
	{
		add_action('admin_notices', array($this, 'add_admin_notices'));
		add_action('admin_init', array($this, 'dismiss_admin_notice'));
		add_action('admin_enqueue_scripts', array($this, 'enqueue_notice_scripts'));

		// Include necessary files for plugin functions
		if (! function_exists('get_plugins')) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
	}

	/**
	 * Enqueue scripts for dismissible notices
	 */
	public function enqueue_notice_scripts()
	{
		wp_enqueue_script('jquery');

		// Add custom styles for notices
		add_action('admin_head', array($this, 'add_notice_styles'));
	}

	/**
	 * Add custom styles for admin notices
	 */
	public function add_notice_styles()
	{
?>
		<style type="text/css">
			.wpb-gqb-admin-notice {
				position: relative;
				border-left-width: 4px;
				border-left-style: solid;
				padding: 20px 24px !important;
				box-shadow: 0 1px 1px 0 rgba(0, 0, 0, .1);
				background: #fff;
			}

			.wpb-gqb-admin-notice.notice-error {
				border-left-color: #d63638;
			}

			.wpb-gqb-admin-notice.notice-warning {
				border-left-color: #dba617;
			}

			.wpb-gqb-admin-notice.notice-info {
				border-left-color: #2271b1;
			}

			.wpb-gqb-admin-notice.notice-success {
				border-left-color: #00a32a;
			}

			.wpb-gqb-admin-notice h3 {
				margin: 0 !important;
				font-size: .9rem;
				font-weight: 600;
				line-height: 1.4;
				color: #1d2327;
			}

			.wpb-gqb-admin-notice p {
				margin: 0;
				padding: 0;
				font-size: 13px;
				line-height: 1.6;
				color: #50575e;
			}

			.wpb-gqb-admin-notice .wpb-notice-actions {
				margin-top: 16px;
				display: flex;
				align-items: center;
				gap: 12px;
				flex-wrap: wrap;
			}

			.wpb-gqb-admin-notice .button {
				height: auto;
				padding: 6px 16px;
				font-size: 13px;
				line-height: 1.6;
				text-decoration: none;
				white-space: nowrap;
				margin: 0;
				display: flex;
				gap: 5px;
				align-items: center;
			}

			.wpb-gqb-admin-notice .button-primary {
				background: #2271b1;
				border-color: #2271b1;
				color: #fff;
				font-weight: 500;
			}

			.wpb-gqb-admin-notice .button-primary:hover,
			.wpb-gqb-admin-notice .button-primary:focus {
				background: #135e96;
				border-color: #135e96;
				color: #fff;
			}

			.wpb-gqb-admin-notice .button-primary .dashicons {
				color: #fff !important;
			}

			.wpb-gqb-admin-notice .button-secondary {
				background: #f6f7f7;
				border-color: #c3c4c7;
				color: #2c3338;
			}

			.wpb-gqb-admin-notice .button-secondary:hover,
			.wpb-gqb-admin-notice .button-secondary:focus {
				background: #f0f0f1;
				border-color: #8c8f94;
				color: #2c3338;
			}

			.wpb-gqb-admin-notice .button-secondary .dashicons {
				color: #2c3338;
			}

			.wpb-gqb-admin-notice .wpb-notice-link {
				color: #2271b1;
				text-decoration: none;
				font-weight: 500;
				display: inline-flex;
				align-items: center;
				gap: 4px;
			}

			.wpb-gqb-admin-notice .wpb-notice-link:hover {
				color: #135e96;
			}

			.wpb-gqb-admin-notice .wpb-notice-link .dashicons {
				font-size: 16px;
				width: 16px;
				height: 16px;
			}

			.wpb-gqb-admin-notice .notice-dismiss {
				top: 20px;
				right: 20px;
			}

			.wpb-gqb-admin-notice .notice-dismiss:before {
				color: #787c82;
				width: 20px;
				height: 20px;
				font-size: 13px;
			}

			.wpb-gqb-admin-notice .notice-dismiss:hover:before {
				color: #d63638;
			}

			/* Icon styling for notice types */
			.wpb-gqb-admin-notice-icon {
				display: inline-flex;
				align-items: center;
				gap: 8px;
				margin-bottom: 12px;
			}

			.wpb-gqb-admin-notice-icon .dashicons {
				width: 20px;
				height: 20px;
				font-size: 20px;
			}

			.wpb-gqb-admin-notice.notice-error .wpb-gqb-admin-notice-icon .dashicons {
				color: #d63638;
			}

			.wpb-gqb-admin-notice.notice-warning .wpb-gqb-admin-notice-icon .dashicons {
				color: #dba617;
			}

			.wpb-gqb-admin-notice.notice-info .wpb-gqb-admin-notice-icon .dashicons {
				color: #2271b1;
			}

			.wpb-gqb-admin-notice.notice-success .wpb-gqb-admin-notice-icon .dashicons {
				color: #00a32a;
			}
		</style>
	<?php
	}

	/**
	 *  Get settings option
	 */
	function wpb_gqb_get_option($option, $section, $default = '')
	{

		$options = get_option($section);

		if (isset($options[$option])) {
			return $options[$option];
		}

		return $default;
	}

	/**
	 * Add admin notices
	 */
	public function add_admin_notices()
	{
		$form_plugin = wpb_gqb_get_option('wpb_gqb_form_plugin', 'form_settings', 'wpcf7');

		$notices = array(
			'divi'        => array(
				'title'      => esc_html__('Is Divi used to create your product page?', 'get-a-quote-button'),
				'message'    => esc_html__('To learn how to add the Get a Quote button to Divi, please refer to the following documentation.', 'get-a-quote-button'),
				'link'       => 'https://docs.wpbean.com/docs/get-a-quote-button-for-woocommerce/show-quote-button-in-divi-elementor-pro/',
				'type'       => 'info',
				'conditions' => array(defined('ET_BUILDER_VERSION')), // Check if Divi is active
			),
			'elementor'   => array(
				'title'      => esc_html__('Is Elementor used to create your product page?', 'get-a-quote-button'),
				'message'    => esc_html__('To learn how to add the Get a Quote button to Elementor, please refer to the following documentation.', 'get-a-quote-button'),
				'link'       => 'https://docs.wpbean.com/docs/get-a-quote-button-for-woocommerce/show-quote-button-in-divi-elementor-pro/',
				'type'       => 'info',
				'conditions' => array(defined('ELEMENTOR_VERSION')), // Check if Elementor is active
			),
			'woocommerce' => array(
				'title'       => esc_html__('The Get a Quote Button plugin requires WooCommerce.', 'get-a-quote-button'),
				'message'     => esc_html__('For the Get a Quote Plugin to work, please install and activate the WooCommerce plugin.', 'get-a-quote-button'),
				'type'        => 'error',
				'plugin_slug' => 'woocommerce',
				'plugin_file' => 'woocommerce/woocommerce.php',
				'install_url' => wp_nonce_url(
					self_admin_url('update.php?action=install-plugin&plugin=woocommerce'),
					'install-plugin_woocommerce'
				),
				'conditions'  => array(! class_exists('WooCommerce')), // Show if WooCommerce is NOT active
			),
			'cf7'         => array(
				'title'       => esc_html__('The Get a Quote Button plugin requires Contact Form 7.', 'get-a-quote-button'),
				'message'     => esc_html__('For the Get a Quote Plugin to work, please install and activate the Contact Form 7 plugin.', 'get-a-quote-button'),
				'type'        => 'error',
				'plugin_slug' => 'contact-form-7',
				'plugin_file' => 'contact-form-7/wp-contact-form-7.php',
				'install_url' => wp_nonce_url(
					self_admin_url('update.php?action=install-plugin&plugin=contact-form-7'),
					'install-plugin_contact-form-7'
				),
				'conditions'  => array(
					$form_plugin === 'wpcf7',
					! defined('WPCF7_PLUGIN'), // Show if CF7 is selected but NOT active
				),
			),
			'wpforms'     => array(
				'title'       => esc_html__('The Get a Quote Button plugin requires WPForms.', 'get-a-quote-button'),
				'message'     => esc_html__('For the Get a Quote Plugin to work, please install and activate the WPForms plugin.', 'get-a-quote-button'),
				'type'        => 'error',
				'plugin_slug' => 'wpforms-lite',
				'plugin_file' => 'wpforms-lite/wpforms.php',
				'install_url' => wp_nonce_url(
					self_admin_url('update.php?action=install-plugin&plugin=wpforms-lite'),
					'install-plugin_wpforms-lite'
				),
				'conditions'  => array(
					$form_plugin === 'wpforms',
					! defined('WPFORMS_VERSION'), // Show if WPForms is selected but NOT active
				),
			),
		);

		// Loop through notices and display them
		foreach ($notices as $notice_key => $notice) {
			$this->display_notice($notice_key, $notice);
		}
	}

	/**
	 * Check if plugin is installed
	 *
	 * @param string $plugin_file Plugin file path
	 * @return bool
	 */
	private function is_plugin_installed($plugin_file)
	{
		$installed_plugins = get_plugins();
		return isset($installed_plugins[$plugin_file]);
	}

	/**
	 * Display individual notice
	 *
	 * @param string $notice_key Unique key for the notice
	 * @param array  $notice Notice data
	 */
	private function display_notice($notice_key, $notice)
	{
		// Check if notice is already dismissed
		if (get_user_meta(get_current_user_id(), 'wpb_gqb_dismissed_notice_' . $notice_key, true)) {
			return;
		}

		// Check conditions if they exist
		if (isset($notice['conditions']) && is_array($notice['conditions'])) {
			// All conditions must be true to show the notice
			foreach ($notice['conditions'] as $condition) {
				if (! $condition) {
					return; // Don't show notice if any condition is false
				}
			}
		}

		// Set notice type (default to 'info' if not specified)
		$notice_type = isset($notice['type']) ? $notice['type'] : 'info';

		// Set icon based on notice type
		$icon_map = array(
			'error'   => 'warning',
			'warning' => 'warning',
			'info'    => 'info',
			'success' => 'yes-alt',
		);
		$icon     = isset($icon_map[$notice_type]) ? $icon_map[$notice_type] : 'info';

	?>
		<div class="notice notice-<?php echo esc_attr($notice_type); ?> is-dismissible wpb-gqb-admin-notice" data-notice-key="<?php echo esc_attr($notice_key); ?>">
			<div class="wpb-gqb-admin-notice-icon">
				<span class="dashicons dashicons-<?php echo esc_attr($icon); ?>"></span>
				<h3><?php echo esc_html($notice['title']); ?></h3>
			</div>
			<p><?php echo esc_html($notice['message']); ?></p>

			<div class="wpb-notice-actions">
				<?php if (isset($notice['plugin_file'])) : ?>
					<?php if ($this->is_plugin_installed($notice['plugin_file'])) : ?>
						<!-- Plugin is installed but not active, show activate button -->
						<a href="<?php echo esc_url(wp_nonce_url(self_admin_url('plugins.php?action=activate&plugin=' . rawurlencode($notice['plugin_file'])), 'activate-plugin_' . $notice['plugin_file'])); ?>" class="button button-primary">
							<span class="dashicons dashicons-admin-plugins"></span>
							<?php esc_html_e('Activate Plugin', 'get-a-quote-button'); ?>
						</a>
					<?php elseif (isset($notice['install_url'])) : ?>
						<!-- Plugin is not installed, show install button -->
						<a href="<?php echo esc_url($notice['install_url']); ?>" class="button button-primary">
							<span class="dashicons dashicons-download"></span>
							<?php esc_html_e('Install Plugin', 'get-a-quote-button'); ?>
						</a>
					<?php endif; ?>
				<?php endif; ?>

				<?php if (isset($notice['link'])) : ?>
					<a href="<?php echo esc_url($notice['link']); ?>" class="wpb-notice-link" target="_blank">
						<?php esc_html_e('View Documentation', 'get-a-quote-button'); ?>
						<span class="dashicons dashicons-external"></span>
					</a>
				<?php endif; ?>
			</div>
		</div>

		<script type="text/javascript">
			jQuery(document).ready(function($) {
				$(document).on('click', '.wpb-gqb-admin-notice .notice-dismiss', function() {
					var noticeKey = $(this).closest('.wpb-gqb-admin-notice').data('notice-key');

					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'wpb_gqb_dismiss_notice',
							notice_key: noticeKey,
							nonce: '<?php echo esc_js(wp_create_nonce('wpb_gqb_dismiss_notice')); ?>'
						}
					});
				});
			});
		</script>
<?php
	}

	/**
	 * Handle notice dismissal
	 */
	public function dismiss_admin_notice()
	{
		if (isset($_POST['action']) && $_POST['action'] === 'wpb_gqb_dismiss_notice') {
			// Verify nonce
			if (! isset($_POST['nonce']) || ! wp_verify_nonce($_POST['nonce'], 'wpb_gqb_dismiss_notice')) {
				wp_die('Security check failed');
			}

			$notice_key = isset($_POST['notice_key']) ? sanitize_text_field($_POST['notice_key']) : '';

			if ($notice_key) {
				update_user_meta(get_current_user_id(), 'wpb_gqb_dismissed_notice_' . $notice_key, true);
			}

			wp_die(); // Required for AJAX
		}
	}
}
new WPB_GQB_Admin_Notices();
