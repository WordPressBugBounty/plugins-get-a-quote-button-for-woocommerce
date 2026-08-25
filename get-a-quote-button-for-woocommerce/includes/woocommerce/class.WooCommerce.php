<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WooCommerce Configuration Class
 */
class WPB_GQB_WooCommerce_Handler {

	/**
	 * Constructor
	 */
	public function __construct() {
		$single_show     = wpb_gqb_get_option( 'woo_single_show_quote_form', 'woo_settings' );
		$loop_show       = wpb_gqb_get_option( 'woo_loop_show_quote_form', 'woo_settings' );
		$btn_position    = wpb_gqb_get_option( 'wpb_gqb_btn_position', 'woo_settings', 'after_cart' );
		$single_position = ( $btn_position === 'after_cart' ? 30 : 20 );
		$loop_position   = ( $btn_position === 'after_cart' ? 10 : 6 );

		// Add global quote button to the single product.
		if ( $single_show === 'on' ) {
			add_action(
				apply_filters( 'wpb_gqb_woo_single_position', 'woocommerce_single_product_summary' ),
				array( $this, 'woo_add_contact_form_button' ),
				apply_filters( 'wpb_gqb_woo_single_priority', $single_position )
			);
		}

		// Add global quote button to the product loop.
		if ( $loop_show === 'on' ) {
			add_action(
				apply_filters( 'wpb_gqb_woo_loop_position', 'woocommerce_after_shop_loop_item' ),
				array( $this, 'woo_add_contact_form_button' ),
				apply_filters( 'wpb_gqb_woo_loop_priority', $loop_position )
			);
		}

		add_action( 'wpb_gqb_quote_button_hook', array($this, 'woo_add_contact_form_button') );

		$this->file_includes();
	}

	/**
	 * Load the required files
	 *
	 * @return void
	 */
	function file_includes() {
		include_once __DIR__ . '/class.product-meta.php';
		include_once __DIR__ . '/class.remove-cart-price.php';
	}

	/**
	 * Quote Button ShortCode
	 *
	 * @param int    $form_id The contact form ID
	 * @param string $classes Additional button css classes
	 * @return void
	 */
	public function get_quote_shortcode( $form_id, $classes = '' ) {
		return do_shortcode( '[wpb-quote-button id="' . esc_attr( $form_id ) . '" class="' . esc_attr( $classes ) . '"]' );
	}

	/**
	 * Adding Global Quote Button to Product (using the settings)
	 */
	public function woo_add_contact_form_button() {
		global $product;
		$form_plugin = wpb_gqb_get_option( 'wpb_gqb_form_plugin', 'form_settings', 'wpcf7' );

		if ( 'wpcf7' === $form_plugin ) {
			$form_plugin = 'wpcf7_contact_form';
		}

		$cf7_form_id     = wpb_gqb_get_option( 'wpb_gqb_cf7_form_id', 'form_settings' );
		$wpforms_form_id = wpb_gqb_get_option( 'wpb_gqb_wpforms_form_id', 'form_settings' );
		$form_id         = apply_filters( 'wpb_gqb_woo_product_contact_form_id', 'wpcf7_contact_form' === $form_plugin ? $cf7_form_id : $wpforms_form_id );
		$woo_show_only   = wpb_gqb_get_option( 'wpb_gqb_woo_show_only_for', 'woo_settings', 'all_products' );
		$woo_btn_guest   = wpb_gqb_get_option( 'wpb_gqb_woo_btn_guest', 'woo_settings', 'on' );
		$wpb_gqb_disable = get_post_meta( get_the_id(), '_wpb_gqb_disable', true );

		if ( $woo_btn_guest !== 'on' && ! is_user_logged_in() ) {
			return false;
		}

		if ( $wpb_gqb_disable === 'yes' ) {
			return false;
		}

		if ( $woo_show_only === 'out_of_stock' ) {
			$stock_status = get_post_meta( get_the_ID(), '_stock_status', true );

			if ( $product->get_type() === 'variable' ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_quote_shortcode() returns do_shortcode() output, already-escaped HTML from our own shortcode handler.
				echo $this->get_quote_shortcode( $form_id, 'wpb-gqb-variable-show-only-for-outofstock' );
			} elseif ( $stock_status === 'outofstock' ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_quote_shortcode() returns do_shortcode() output, already-escaped HTML from our own shortcode handler.
				echo $this->get_quote_shortcode( $form_id );
			}
		} elseif ( $woo_show_only === 'featured' ) {
			if ( $product->is_featured() ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_quote_shortcode() returns do_shortcode() output, already-escaped HTML from our own shortcode handler.
				echo $this->get_quote_shortcode( $form_id );
			}
		} else {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_quote_shortcode() returns do_shortcode() output, already-escaped HTML from our own shortcode handler.
			echo $this->get_quote_shortcode( $form_id );
		}
	}
}
new WPB_GQB_WooCommerce_Handler();
