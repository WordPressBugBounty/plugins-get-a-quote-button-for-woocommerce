<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Ajax Class
 */
class WPB_GQB_Ajax {


	private $form_plugin;

	/**
	 * Constructor
	 */
	function __construct() {
		add_action( 'wp_ajax_fire_contact_form', array( $this, 'fire_contact_form' ) );
		add_action( 'wp_ajax_nopriv_fire_contact_form', array( $this, 'fire_contact_form' ) );

		add_action( 'wp_ajax_wpb_gqb_add_to_quote', array( $this, 'add_to_quote' ) );
		add_action( 'wp_ajax_nopriv_wpb_gqb_add_to_quote', array( $this, 'add_to_quote' ) );

		add_action( 'wp_ajax_wpb_gqb_update_quote_item', array( $this, 'update_quote_item' ) );
		add_action( 'wp_ajax_nopriv_wpb_gqb_update_quote_item', array( $this, 'update_quote_item' ) );

		add_action( 'wp_ajax_wpb_gqb_clear_quote', array( $this, 'clear_quote' ) );
		add_action( 'wp_ajax_nopriv_wpb_gqb_clear_quote', array( $this, 'clear_quote' ) );

		add_action( 'wp_ajax_wpb_gqb_refresh_quote_data_field', array( $this, 'refresh_quote_data_field' ) );
		add_action( 'wp_ajax_nopriv_wpb_gqb_refresh_quote_data_field', array( $this, 'refresh_quote_data_field' ) );

		$this->form_plugin = wpb_gqb_get_option( 'wpb_gqb_form_plugin', 'form_settings', 'wpcf7' );

		if ( 'wpcf7' === $this->form_plugin ) {
			$this->form_plugin = 'wpcf7_contact_form';
		}
	}

	/**
	 * Form Content
	 */
	public function fire_contact_form() {

		if ( ! wp_doing_ajax() ) {
			wp_send_json_error( esc_html__( 'Invalid request', 'get-a-quote-button' ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- renders the requested CF7/WPForms form via do_shortcode(); read-only, and CF7/WPForms validate the form's own submission independently.
		if ( ! isset( $_POST['contact_form_id'] ) ) {
			return;
		}

		// For WPForms Pro, not displaying for non login users.
		add_filter( 'wpforms_current_user_can', '__return_true' );

		$response        = '';
		$contact_form_id = isset( $_POST['contact_form_id'] ) ? intval( wp_unslash( $_POST['contact_form_id'] ) ) : 0;
		$wpb_post_id     = isset( $_POST['wpb_post_id'] ) ? intval( wp_unslash( $_POST['wpb_post_id'] ) ) : 0;
		$wpb_quote_list  = isset( $_POST['wpb_quote_list'] ) && 'true' === sanitize_text_field( wp_unslash( $_POST['wpb_quote_list'] ) );
		$show_title      = wpb_gqb_get_option( 'wpb_gqb_show_title', 'popup_settings', 'on' );
		$shortcode_tag   = 'wpcf7_contact_form' === $this->form_plugin ? 'contact-form-7' : 'wpforms';

		wpb_gqb_quote_debug_log(
			'01_fire_contact_form:request',
			array(
				'contact_form_id' => $contact_form_id,
				'wpb_post_id_raw' => isset( $_POST['wpb_post_id'] ) ? wp_unslash( $_POST['wpb_post_id'] ) : '(not set)',
				'wpb_quote_list'  => $wpb_quote_list,
				'wpb_cart_page'   => isset( $_POST['wpb_cart_page'] ) ? wp_unslash( $_POST['wpb_cart_page'] ) : '(not set)',
				'form_plugin'     => $this->form_plugin,
			)
		);

		if ( $contact_form_id > 0 && get_post_type( $contact_form_id ) === $this->form_plugin ) {
			if ( $wpb_quote_list && 'on' === $show_title ) {
				$quote_list_title = wpb_gqb_get_option( 'wpb_gqb_quote_list_popup_title', 'quote_settings', __( 'Your Quote List', 'get-a-quote-button' ) );
				$response        .= '<h3 class="wpb-gqb-product-title">' . esc_html( $quote_list_title ) . '</h3>';
			} elseif ( $wpb_post_id && 'on' === $show_title ) {
				$response .= '<h3 class="wpb-gqb-product-title">' . esc_html( get_the_title( $wpb_post_id ) ) . '</h3>';
			}

			ob_start();

			echo do_shortcode( '[' . esc_attr( $shortcode_tag ) . ' id="' . esc_attr( $contact_form_id ) . '"]' );

			$response .= ob_get_clean();

			wpb_gqb_quote_debug_log(
				'02_fire_contact_form:rendered',
				array(
					'response_length'          => strlen( $response ),
					'has_auto_hidden_field'    => false !== strpos( $response, '_wpb_gqb_auto_product_data' ),
					'has_product_data_content' => false !== strpos( $response, 'gqb-product-data-content' ),
				)
			);

			wp_send_json_success( $response );
		} else {
			wpb_gqb_quote_debug_log( '02_fire_contact_form:invalid_form_id' );
			wp_send_json_error( esc_html__( 'Invalid Form ID', 'get-a-quote-button' ) );
		}
		// phpcs:enable
	}

	/**
	 * Add a product to the multi-product quote list (session-backed).
	 */
	public function add_to_quote() {
		if ( ! wp_doing_ajax() ) {
			wp_send_json_error( esc_html__( 'Invalid request', 'get-a-quote-button' ) );
		}

		if ( ! class_exists( 'WPB_GQB_Quote_List' ) ) {
			wp_send_json_error( esc_html__( 'The quote list is unavailable.', 'get-a-quote-button' ) );
		}

		if ( ! check_ajax_referer( 'wpb_gqb_quote_actions', 'nonce', false ) ) {
			wp_send_json_error( esc_html__( 'Invalid request', 'get-a-quote-button' ) );
		}

		$product_id   = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0;
		$variation_id = isset( $_POST['variation_id'] ) ? absint( wp_unslash( $_POST['variation_id'] ) ) : 0;
		$quantity     = isset( $_POST['qty'] ) ? absint( wp_unslash( $_POST['qty'] ) ) : 1;
		$price        = isset( $_POST['price'] ) ? sanitize_text_field( wp_unslash( $_POST['price'] ) ) : '';

		$variation = array();
		if ( isset( $_POST['variation'] ) && $_POST['variation'] !== '' ) {
			$decoded   = json_decode( sanitize_text_field( wp_unslash( $_POST['variation'] ) ), true );
			$variation = is_array( $decoded ) ? $decoded : array();
		}

		$wapf_fields = array();
		if ( isset( $_POST['wapf'] ) && $_POST['wapf'] !== '' ) {
			$decoded     = json_decode( wp_unslash( $_POST['wapf'] ), true );
			$wapf_fields = is_array( $decoded ) ? $decoded : array();
		}

		$wcpa_fields = array();
		if ( isset( $_POST['wcpa'] ) && $_POST['wcpa'] !== '' ) {
			$decoded     = json_decode( wp_unslash( $_POST['wcpa'] ), true );
			$wcpa_fields = is_array( $decoded ) ? $decoded : array();
		}

		$item = WPB_GQB_Quote_List::add_item(
			array(
				'product_id'   => $product_id,
				'variation_id' => $variation_id,
				'variation'    => $variation,
				'quantity'     => $quantity ? $quantity : 1,
				'wapf_fields'  => $wapf_fields,
				'wcpa_fields'  => $wcpa_fields,
				'price'        => is_numeric( $price ) ? floatval( $price ) : null,
			)
		);

		if ( is_wp_error( $item ) ) {
			wp_send_json_error( $item->get_error_message() );
		}

		$quote_page_id = wpb_gqb_get_quote_list_page_id();

		wp_send_json_success(
			array(
				'message'     => esc_html( wpb_gqb_get_option( 'wpb_gqb_quote_added_message_text', 'quote_settings', esc_html__( 'Added to your quote list.', 'get-a-quote-button' ) ) ),
				'count'       => WPB_GQB_Quote_List::get_items_count(),
				'quote_url'   => $quote_page_id > 0 ? get_permalink( $quote_page_id ) : '',
				'widget_html' => class_exists( 'WPB_GQB_Quote_Widget_Shortcode' ) ? do_shortcode( '[wpb-quote-widget]' ) : '',
			)
		);
	}

	/**
	 * Update the quantity of a quote item, or remove it entirely.
	 */
	public function update_quote_item() {
		if ( ! wp_doing_ajax() ) {
			wp_send_json_error( esc_html__( 'Invalid request', 'get-a-quote-button' ) );
		}

		if ( ! class_exists( 'WPB_GQB_Quote_List' ) ) {
			wp_send_json_error( esc_html__( 'The quote list is unavailable.', 'get-a-quote-button' ) );
		}

		if ( ! check_ajax_referer( 'wpb_gqb_quote_actions', 'nonce', false ) ) {
			wp_send_json_error( esc_html__( 'Invalid request', 'get-a-quote-button' ) );
		}

		if ( ! isset( $_POST['key'] ) ) {
			wp_send_json_error( esc_html__( 'Invalid quote item.', 'get-a-quote-button' ) );
		}

		$key          = sanitize_text_field( wp_unslash( $_POST['key'] ) );
		$quote_action = isset( $_POST['quote_action'] ) ? sanitize_key( wp_unslash( $_POST['quote_action'] ) ) : 'update';
		$qty          = isset( $_POST['qty'] ) ? absint( wp_unslash( $_POST['qty'] ) ) : 0;

		if ( 'remove' === $quote_action ) {
			WPB_GQB_Quote_List::remove_item( $key );
		} else {
			WPB_GQB_Quote_List::update_item_qty( $key, $qty );
		}

		$totals        = WPB_GQB_Quote_List::get_totals();
		$item          = WPB_GQB_Quote_List::get_item( $key );
		$item_subtotal = '';

		if ( $item ) {
			$unit_price    = WPB_GQB_Quote_List::get_item_price( $item );
			$item_subtotal = function_exists( 'wc_price' ) ? wc_price( $unit_price * absint( $item['quantity'] ) ) : '';
		}

		wp_send_json_success(
			array(
				'count'         => WPB_GQB_Quote_List::get_items_count(),
				'subtotal'      => function_exists( 'wc_price' ) ? wc_price( $totals['subtotal'] ) : $totals['subtotal'],
				'total'         => function_exists( 'wc_price' ) ? wc_price( $totals['total'] ) : $totals['total'],
				'item_subtotal' => $item_subtotal,
				'is_empty'      => empty( WPB_GQB_Quote_List::get_items() ),
				'widget_html'   => class_exists( 'WPB_GQB_Quote_Widget_Shortcode' ) ? do_shortcode( '[wpb-quote-widget]' ) : '',
			)
		);
	}

	/**
	 * Empty the quote list, called client-side after a successful quote-list
	 * form submission when the "Clear quote after submit" setting is on.
	 */
	public function clear_quote() {
		if ( ! wp_doing_ajax() ) {
			wp_send_json_error( esc_html__( 'Invalid request', 'get-a-quote-button' ) );
		}

		if ( ! class_exists( 'WPB_GQB_Quote_List' ) ) {
			wp_send_json_error( esc_html__( 'The quote list is unavailable.', 'get-a-quote-button' ) );
		}

		if ( ! check_ajax_referer( 'wpb_gqb_quote_actions', 'nonce', false ) ) {
			wp_send_json_error( esc_html__( 'Invalid request', 'get-a-quote-button' ) );
		}

		WPB_GQB_Quote_List::clear();

		wp_send_json_success(
			array(
				'count'       => 0,
				'html'        => do_shortcode( '[wpb-quote-list]' ),
				'widget_html' => class_exists( 'WPB_GQB_Quote_Widget_Shortcode' ) ? do_shortcode( '[wpb-quote-widget]' ) : '',
			)
		);
	}

	/**
	 * Refresh the value of the "automatically send quote data" hidden field
	 * (`_wpb_gqb_auto_product_data`, already baked into the CF7/WPForms form
	 * by WPB_GQB_CF7_Data_Tags/WPB_GQB_WPForms_Custom_Smart_Tags when the
	 * form was first rendered) after the quote list changes.
	 *
	 * The inline "Quote Form Display" mode keeps a single, already-rendered
	 * form on the page for as long as the user is on the Quote List page, so
	 * a qty/remove change must not re-render the whole form (that would
	 * regenerate a brand new WPForms nonce/anti-spam timestamps and reinit
	 * the form's JS on top of a live instance) - only this one field's value
	 * needs to stay in sync with the list.
	 */
	public function refresh_quote_data_field() {
		if ( ! wp_doing_ajax() ) {
			wp_send_json_error( esc_html__( 'Invalid request', 'get-a-quote-button' ) );
		}

		if ( ! function_exists( 'wpb_gqb_build_quote_list_content' ) ) {
			wp_send_json_error( esc_html__( 'The quote list is unavailable.', 'get-a-quote-button' ) );
		}

		$block = wpb_gqb_build_quote_list_content();

		wp_send_json_success(
			array(
				// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- transports the plain-text quote-list summary through JSON, not code obfuscation.
				'value' => $block ? base64_encode( $block ) : '',
			)
		);
	}
}
if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
	new WPB_GQB_Ajax();
}
