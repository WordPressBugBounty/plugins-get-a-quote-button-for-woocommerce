<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Multi-Product Quote List Shortcode
 *
 * Renders the [wpb-quote-list] page: the customer-facing review/edit screen
 * for the multi-product quote system, backed by WPB_GQB_Quote_List.
 */
class WPB_GQB_Quote_List_Shortcode {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_shortcode( 'wpb-quote-list', array( $this, 'render' ) );

		$form_plugin = wpb_gqb_get_option( 'wpb_gqb_form_plugin', 'form_settings', 'wpcf7' );

		if ( 'wpcf7' !== $form_plugin ) {
			// Must run before `wp_enqueue_scripts` - see the matching comment in
			// WPB_GQB_Shortcode_Handler::__construct(). The [wpforms] shortcode
			// here is rendered via wc_get_template(), not stored post content,
			// so WPForms' own content scan never detects it either.
			add_filter( 'wpforms_global_assets', '__return_true' );
		}
	}

	/**
	 * Shortcode handler.
	 *
	 * @return string
	 */
	public function render() {
		if ( ! class_exists( 'WPB_GQB_Quote_List' ) || ! function_exists( 'WC' ) ) {
			return '';
		}

		// Every visitor's item list is unique and session-bound, so this page must
		// never be served from a full-page cache (WP Rocket, LiteSpeed, etc.) - a
		// cached copy would freeze whatever items happened to be in the session of
		// whoever first triggered the cache, and show that to every later visitor.
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
		nocache_headers();

		if ( function_exists( 'wpcf7_enqueue_scripts' ) ) {
			wpcf7_enqueue_scripts();
		}

		if ( function_exists( 'wpcf7_enqueue_styles' ) ) {
			wpcf7_enqueue_styles();
		}

		$form_plugin = wpb_gqb_get_option( 'wpb_gqb_form_plugin', 'form_settings', 'wpcf7' );
		$form_id     = 'wpcf7' === $form_plugin
			? wpb_gqb_get_option( 'wpb_gqb_cf7_form_id', 'form_settings' )
			: wpb_gqb_get_option( 'wpb_gqb_wpforms_form_id', 'form_settings' );

		$args = array(
			'items'       => WPB_GQB_Quote_List::get_items(),
			'totals'      => WPB_GQB_Quote_List::get_totals(),
			'form_id'     => $form_id,
			'form_plugin' => 'wpcf7' === $form_plugin ? 'wpcf7_contact_form' : 'wpforms',
		);

		ob_start();

		wc_get_template( 'quote-list/quote-list.php', $args, 'get-a-quote-button/', WPB_GQB_PLUGIN_WOO_TEMPLATE_PATH );

		return ob_get_clean();
	}
}
new WPB_GQB_Quote_List_Shortcode();
