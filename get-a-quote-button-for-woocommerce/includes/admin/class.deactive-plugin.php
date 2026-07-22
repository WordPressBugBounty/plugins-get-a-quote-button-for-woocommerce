<?php

defined( 'ABSPATH' ) || exit;

/**
 * Deactivate the free version class
 */
class WPB_GQB_Deactivate_plugin {

	/**
	 * Class constructor
	 */
	public function __construct() {
		$this->deactivate_plugin( 'WPB_GQB_FREE_INIT', WPB_GQB_PREMIUM );
	}

	/**
	 * Deactivate plugin
	 *
	 * @param string $to_deactive
	 * @param string $to_active
	 * @return void
	 */
	function deactivate_plugin( $to_deactive, $to_active ) {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( defined( $to_deactive ) && is_plugin_active( constant( $to_deactive ) ) ) {
			deactivate_plugins( constant( $to_deactive ) );

			if ( ! function_exists( 'wp_create_nonce' ) ) {
				header( 'Location: plugins.php' );
				exit();
			}

			global $status, $page, $s;
			$redirect = 'plugins.php?action=activate&plugin=' . $to_active . '&plugin_status=' . $status . '&paged=' . $page . '&s=' . $s;
			$redirect = esc_url_raw( add_query_arg( '_wpnonce', wp_create_nonce( 'activate-plugin_' . $to_active ), $redirect ) );

			header( 'Location: ' . $redirect );
			exit();
		}
	}
}
new WPB_GQB_Deactivate_plugin();
