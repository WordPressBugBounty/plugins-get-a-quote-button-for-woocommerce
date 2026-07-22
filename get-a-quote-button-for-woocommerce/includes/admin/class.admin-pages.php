<?php
if (! defined('ABSPATH')) {
	exit;
}

/**
 * Plugin admin pages class
 */
class WPB_GQB_Admin_Pages
{

	/**
	 * Class constructor
	 */
	public function __construct()
	{
		add_action('admin_menu', array($this, 'admin_pages_menu'));
		add_action('admin_enqueue_scripts', array($this, 'admin_pages_scripts'), 999);
		add_filter('upload_mimes', array($this, 'allow_svg_upload_mime'));
		add_filter('wp_check_filetype_and_ext', array($this, 'fix_svg_filetype_check'), 10, 4);
	}

	/**
	 * Allow SVG uploads (for the "Quote Widget Icon" media picker) for
	 * admins only. WordPress blocks SVG uploads by default for security
	 * reasons; only manage_options users can reach this media picker anyway.
	 *
	 * @param array $mimes
	 * @return array
	 */
	public function allow_svg_upload_mime($mimes)
	{
		if (! current_user_can('manage_options')) {
			return $mimes;
		}

		$mimes['svg'] = 'image/svg+xml';

		return $mimes;
	}

	/**
	 * WordPress' file type sniffing (finfo) doesn't reliably recognize SVG
	 * as image/svg+xml on every server, which causes the upload to be
	 * rejected even after allow_svg_upload_mime() permits the extension.
	 * Trust the file extension for .svg specifically.
	 *
	 * @param array  $data
	 * @param string $file
	 * @param string $filename
	 * @param array  $mimes
	 * @return array
	 */
	public function fix_svg_filetype_check($data, $file, $filename, $mimes)
	{
		if (! empty($data['ext']) && ! empty($data['type'])) {
			return $data;
		}

		$filetype = wp_check_filetype($filename, $mimes);

		if ('svg' === $filetype['ext']) {
			$data['ext']  = 'svg';
			$data['type'] = 'image/svg+xml';
		}

		return $data;
	}

	/**
	 * Admin Menus
	 */
	public function admin_pages_menu()
	{
		add_menu_page(
			esc_html__('Get a Quote Settings', 'get-a-quote-button'),
			esc_html__('Get a Quote', 'get-a-quote-button'),
			'manage_options',
			'get-a-quote-button-settings',
			function () {
				echo '<div class="wrap wpb-get-quote-admin-app" id="wpb-gqb-settings-app"></div>';
			},
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- encodes the menu icon SVG as a data: URI, not code obfuscation.
			'data:image/svg+xml;base64,' . base64_encode('<svg fill="#f3f1f1" viewBox="0 0 16 16" id="request-sent-16px" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path id="Path_50" data-name="Path 50" d="M-11.5,0h-11A2.5,2.5,0,0,0-25,2.5v8A2.5,2.5,0,0,0-22.5,13h.5v2.5a.5.5,0,0,0,.309.462A.489.489,0,0,0-21.5,16a.5.5,0,0,0,.354-.146L-18.293,13H-11.5A2.5,2.5,0,0,0-9,10.5v-8A2.5,2.5,0,0,0-11.5,0ZM-10,10.5A1.5,1.5,0,0,1-11.5,12h-7a.5.5,0,0,0-.354.146L-21,14.293V12.5a.5.5,0,0,0-.5-.5h-1A1.5,1.5,0,0,1-24,10.5v-8A1.5,1.5,0,0,1-22.5,1h11A1.5,1.5,0,0,1-10,2.5Zm-2.038-3.809a.518.518,0,0,1-.109.163l-2,2A.5.5,0,0,1-14.5,9a.5.5,0,0,1-.354-.146.5.5,0,0,1,0-.708L-13.707,7H-18.5A1.5,1.5,0,0,0-20,8.5a.5.5,0,0,1-.5.5.5.5,0,0,1-.5-.5A2.5,2.5,0,0,1-18.5,6h4.793l-1.147-1.146a.5.5,0,0,1,0-.708.5.5,0,0,1,.708,0l2,2a.518.518,0,0,1,.109.163A.505.505,0,0,1-12.038,6.691Z" transform="translate(25)"></path> </g></svg>'),
			57
		);

		// Rename the first submenu by adding a submenu with the same slug
		add_submenu_page(
			'get-a-quote-button-settings',
			esc_html__('Get a Quote Settings', 'get-a-quote-button'),
			esc_html__('Settings', 'get-a-quote-button'),
			'manage_options',
			'get-a-quote-button-settings',
		);

		add_submenu_page(
			'get-a-quote-button-settings',
			esc_html__('Get a Quote Buttons', 'get-a-quote-button'),
			esc_html__('Quote Buttons', 'get-a-quote-button'),
			'manage_options',
			'wpb-get-a-quote-buttons',
			function () {
				echo '<div class="wrap wpb-get-quote-admin-app" id="wpb-gqb-shortcodes-app"></div>';
			}
		);

		add_submenu_page(
			'get-a-quote-button-settings',
			esc_html__('Documentation', 'get-a-quote-button'),
			esc_html__('Docs', 'get-a-quote-button'),
			'delete_posts',
			'wpb-get-a-quote-button-docs',
			function () {
				// phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- fixed, hardcoded external docs URL, not user input; wp_safe_redirect() would block it as an off-site host.
				wp_redirect(esc_url('https://docs.wpbean.com/docs/get-a-quote-button-for-woocommerce/'));
				exit;
			}
		);
	}

	/**
	 * Admin Pages Scripts
	 */
	public function admin_pages_scripts($admin_page)
	{
		$base_url = plugin_dir_path(__FILE__) . 'assets/build/';

		$apps = array(
			array(
				'app'      => 'shortcodesPage',
				'page_url' => 'get-a-quote_page_wpb-get-a-quote-buttons',
			),
			array(
				'app'      => 'settingsPage',
				'page_url' => 'toplevel_page_get-a-quote-button-settings',
			),
		);

		foreach ($apps as $app) {
			if ($app['page_url'] !== $admin_page) {
				continue;
			}

			if ('settingsPage' === $app['app']) {
				// Powers the "Quote Widget Icon" media picker on the
				// Multi-Product Quote System settings tab.
				wp_enqueue_media();
			}

			$asset_file = $base_url . $app['app'] . '.asset.php';

			if (! file_exists($asset_file)) {
				continue;
			}

			$asset = include $asset_file;

			wp_enqueue_script(
				$app['page_url'] . '-script',
				plugins_url('assets/build/' . $app['app'] . '.js', __FILE__),
				$asset['dependencies'],
				$asset['version'],
				array(
					'in_footer' => true,
				)
			);

			wp_localize_script(
				$app['page_url'] . '-script',
				'WPB_Quote_API',
				array(
					'rest_root'  => esc_url_raw(rest_url()),
					'nonce'      => wp_create_nonce('wp_rest'),
					'adminUrl'   => admin_url(),
					'isPremium'  => wpb_gqb_fs()->can_use_premium_code__premium_only(),
					'upgradeUrl' => esc_url_raw(
						add_query_arg(
							array(
								'utm_content'  => 'Get+A+Quote+Pro',
								'utm_campaign' => 'adminapp',
								'utm_medium'   => 'admin-app',
								'utm_source'   => 'FreeVersion',
							),
							WPB_GQB_PREMIUM_URL
						)
					),
				)
			);

			wp_enqueue_style(
				$app['page_url'] . '-style',
				plugins_url('assets/build/style-' . $app['app'] . '.css', __FILE__),
				array('common', 'wp-components'), // depend on WordPress admin common styles
				$asset['version'],
			);
		}
	}
}
new WPB_GQB_Admin_Pages();
