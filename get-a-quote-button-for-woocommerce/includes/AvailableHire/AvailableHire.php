<?php
if (! defined('ABSPATH')) {
	exit;
}

class WpBean_AccordionMenu_AvailableHire
{
	/**
	 * Holds the instance of the class.
	 *
	 * @var WpBean_AccordionMenu_AvailableHire
	 */
	private static $initialized = false;
	
	/**
	 * Class Constructor
	 */
	public function __construct()
	{
		if ( self::$initialized ) {
            return;
        }
        self::$initialized = true;

		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		add_action( 'admin_notices', array( $this, 'available_hire_admin_notice' ) );
		add_action( 'admin_init', array( $this, 'available_hire_admin_notice_dismissed' ) );
	}

	public function admin_scripts(){
		wp_enqueue_style( 'wpb_accordion_menu_available_hire_style', plugins_url( 'assets/css/available-hire.css', __FILE__ ), '', '1.0' );
	}

	/**
	 * Pro version available hire admin notice.
	 *
	 * @return void
	 */
	public function available_hire_admin_notice() {
		$user_id     = get_current_user_id();
		$screen      = get_current_screen();
		$dismiss_url = wp_nonce_url(
			add_query_arg( 'wpbean-accordion-menu-available-hire-admin-notice-dismissed', 'true' ),
			'wpbean_accordion_menu_available_hire_admin_notice_dismissed',
			'wpbean_accordion_menu_available_hire_admin_notice_dismissed_nonce'
		);
		
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! get_user_meta( $user_id, 'wpbean_accordion_menu_pro_available_hire_dismissed' ) && 'dashboard' === $screen->id ) {
			?>
			<div class="wpb-plugin-available-hire-header notice updated">
				<div>
					<div class="wpb-plugin-available-hire-content">
						<div class="wpb-plugin-available-hire-text">
							<h3>👋 Need expert WordPress & WooCommerce development help?</h3>
							<p>I’m currently available for hire — Custom development, bug fixing, optimization & more. <a href="<?php echo esc_url( $dismiss_url ); ?>" class="wpb-notice-dismiss">Dismiss</a></p>
						</div>
						<div class="wpb-plugin-available-hire-action">
							<?php
								
								$site_name  = get_bloginfo( 'name' );
								$site_url   = home_url();
								$utm_params = array(
									'utm_source'   => 'plugin-admin-notice',
									'utm_medium'   => 'wp-dashboard',
									'utm_campaign' => 'available-for-hire',
									'utm_content'  => rawurlencode( $site_name . ' - ' . $site_url ),
								);
								$hire_url = add_query_arg( $utm_params, 'https://wpbean.com/web-development-services/' );
							?>
							<a href="<?php echo esc_url( $hire_url ); ?>" class="button" target="_blank">Let’s Talk →</a>
						</div>
					</div>
				</div>
			</div>
			<?php
		}
	}

	/**
	 * Initialize the dismissed function
	 *
	 * @return void
	 */
	public function available_hire_admin_notice_dismissed() {
		$user_id = get_current_user_id();

		//delete_user_meta( $user_id, 'wpbean_accordion_menu_pro_available_hire_dismissed' );

		if ( ! empty( $_GET['wpbean-accordion-menu-available-hire-admin-notice-dismissed'] ) ) { // WPCS: input var ok.
			check_admin_referer( 'wpbean_accordion_menu_available_hire_admin_notice_dismissed', 'wpbean_accordion_menu_available_hire_admin_notice_dismissed_nonce' );
			add_user_meta( $user_id, 'wpbean_accordion_menu_pro_available_hire_dismissed', 'true', true );
		}
	}
}
