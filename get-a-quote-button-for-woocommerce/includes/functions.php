<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 *  Get settings option
 */
if ( ! function_exists( 'wpb_gqb_get_option' ) ) {

	function wpb_gqb_get_option( $option, $section, $default = '' ) {
		$options = get_option( $section );

		if ( isset( $options[ $option ] ) ) {
			return $options[ $option ];
		}

		return $default;
	}
}

/**
 * Resolve the "Quote List" page ID for the multi-product quote system.
 *
 * Prefers the page explicitly picked in Settings > Multi-Product Quote
 * (stored inside the `quote_settings` option array), falling back to the
 * page auto-created by wc_create_page() on install (stored as a flat,
 * top-level `wpb_gqb_quote_list_page_id` option) — these are two separate
 * storage locations, not wc_get_page_id()'s `woocommerce_{page}_page_id`
 * convention, so wc_get_page_id() can't be used here.
 */
if ( ! function_exists( 'wpb_gqb_get_quote_list_page_id' ) ) {

	function wpb_gqb_get_quote_list_page_id() {
		$page_id = absint( wpb_gqb_get_option( 'wpb_gqb_quote_list_page_id', 'quote_settings', 0 ) );

		if ( ! $page_id ) {
			$page_id = absint( get_option( 'wpb_gqb_quote_list_page_id', 0 ) );
		}

		return $page_id;
	}
}

/**
 * Trace a step of the quote-data pipeline (button click -> popup render ->
 * mail send) to wp-content/wpb-gqb-quote-debug.log. No-op unless the
 * WPB_GQB_QUOTE_DEBUG constant is defined truthy in main.php/wp-config.php,
 * so it's safe to leave these calls in place permanently.
 *
 * @param string $step  Short label identifying where in the pipeline this is.
 * @param array  $data  Extra context to log alongside the step.
 * @return void
 */
if ( ! function_exists( 'wpb_gqb_quote_debug_log' ) ) {

	function wpb_gqb_quote_debug_log( $step, $data = array() ) {
		if ( ! defined( 'WPB_GQB_QUOTE_DEBUG' ) || ! WPB_GQB_QUOTE_DEBUG ) {
			return;
		}

		$line = '[' . gmdate( 'Y-m-d H:i:s' ) . '] [pid:' . getmypid() . '] ' . $step;

		if ( ! empty( $data ) ) {
			$line .= ' -> ' . wp_json_encode( $data );
		}

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- opt-in debug logging gated by the WPB_GQB_QUOTE_DEBUG constant, not left-over debug code.
		error_log( $line . "\n", 3, trailingslashit( WP_CONTENT_DIR ) . 'wpb-gqb-quote-debug.log' );
	}
}


/**
 * IF Show cart button
 */
if ( ! function_exists( 'is_wpb_gqb_show_cart_btn' ) ) {

	function is_wpb_gqb_show_cart_btn() {
		$product = wc_get_product( get_the_ID() );

		if ( isset( $product ) && ! empty( $product ) ) {
			return apply_filters( 'wpb_gqb_show_cart_btn', true, $product );
		}
	}
}

/**
 * IF Show price
 */
if ( ! function_exists( 'is_wpb_gqb_show_price' ) ) {

	function is_wpb_gqb_show_price() {
		$product = wc_get_product( get_the_ID() );

		if ( isset( $product ) && ! empty( $product ) ) {
			$price = $product->get_price();
			return apply_filters( 'wpb_gqb_show_price', $price, $product );
		}
	}
}

/**
 * IF Show price on the multi-product Quote List page.
 *
 * Defaults to following the existing Hide Price logic (the `wpb_gqb_show_price`
 * filter chain already populated by WPB_GQB_WC_Remove_Cart_Price), independently
 * overridable via the "Show Prices on Quote List" setting.
 */
if ( ! function_exists( 'is_wpb_gqb_show_price_on_quote_list' ) ) {

	function is_wpb_gqb_show_price_on_quote_list( $product ) {
		$mode = wpb_gqb_get_option( 'wpb_gqb_show_prices_on_quote_list', 'quote_settings', 'auto' );

		if ( 'always' === $mode ) {
			return true;
		}

		if ( 'never' === $mode ) {
			return false;
		}

		if ( ! is_object( $product ) || empty( $product ) ) {
			return true;
		}

		return apply_filters( 'wpb_gqb_show_price', $product->get_price(), $product ) !== false;
	}
}

/**
 * Build the "Quote List Items:" text block used by both the CF7
 * {quote-list-content} form-tag and the WPForms {gqb_quote_list_content}
 * smart tag. The intro text, per-item line format, and trailing total line
 * are all driven by the "Quote Data Template" fields in Settings >
 * Multi-Product Quote (`quote_settings` option), so site owners can
 * customize the wording/order using dynamic tags without touching code.
 *
 * Available tags for the item template: {product_name}, {sku}, {price},
 * {quantity}, {subtotal}, {product_id}, {product_url}.
 * Available tags for the total template: {total}, {subtotal}, {items_count}.
 *
 * @return string Empty string when the quote list is empty/unavailable.
 */
if ( ! function_exists( 'wpb_gqb_build_quote_list_content' ) ) {

	function wpb_gqb_build_quote_list_content() {
		if ( ! class_exists( 'WPB_GQB_Quote_List' ) ) {
			return '';
		}

		$items = WPB_GQB_Quote_List::get_items();

		if ( empty( $items ) ) {
			return '';
		}

		$currency_symbol   = get_woocommerce_currency_symbol();
		$show_totals_block = wpb_gqb_get_option( 'wpb_gqb_show_prices_on_quote_list', 'quote_settings', 'auto' ) !== 'never';

		$intro_text     = wpb_gqb_get_option( 'wpb_gqb_quote_list_intro_text', 'quote_settings', __( 'Quote List Items:', 'get-a-quote-button' ) );
		$item_template  = wpb_gqb_get_option( 'wpb_gqb_quote_item_template', 'quote_settings', "- {product_name}\n  SKU: {sku}\n  Price: {price}\n  Quantity: {quantity}\n  Subtotal: {subtotal}" );
		$total_template = wpb_gqb_get_option( 'wpb_gqb_quote_total_template', 'quote_settings', __( 'Total', 'get-a-quote-button' ) . ': {total}' );

		$output = $intro_text . "\n";

		foreach ( $items as $item ) {
			$product = WPB_GQB_Quote_List::get_item_product( $item );

			if ( ! $product ) {
				continue;
			}

			$link_product    = $product->is_type( 'variation' ) ? wc_get_product( $product->get_parent_id() ) : $product;
			$sku             = $product->get_sku() ? $product->get_sku() : __( 'N/A', 'get-a-quote-button' );
			$qty             = isset( $item['quantity'] ) ? absint( $item['quantity'] ) : 0;
			$unit            = WPB_GQB_Quote_List::get_item_price( $item );
			$subtotal        = $unit * $qty;
			$show_item_price = function_exists( 'is_wpb_gqb_show_price_on_quote_list' ) ? is_wpb_gqb_show_price_on_quote_list( $product ) : true;

			$item_output = strtr(
				$item_template,
				array(
					'{product_name}' => $product->get_name(),
					'{sku}'          => $sku,
					'{product_id}'   => $product->get_id(),
					'{product_url}'  => $link_product ? get_permalink( $link_product->get_id() ) : '',
					'{quantity}'     => $qty,
					'{price}'        => $show_item_price ? $currency_symbol . wc_format_decimal( $unit, 2 ) : __( 'Price on request', 'get-a-quote-button' ),
					'{subtotal}'     => $show_item_price ? $currency_symbol . wc_format_decimal( $subtotal, 2 ) : __( 'On request', 'get-a-quote-button' ),
				)
			);

			if ( ! empty( $item['variation'] ) && is_array( $item['variation'] ) ) {
				foreach ( $item['variation'] as $attribute => $value ) {
					$item_output .= "\n  " . ucfirst( str_replace( array( 'attribute_pa_', 'attribute_', 'pa_' ), '', $attribute ) ) . ": {$value}";
				}
			}

			$custom_fields = array_merge(
				is_array( $item['wapf_fields'] ?? null ) ? $item['wapf_fields'] : array(),
				is_array( $item['wcpa_fields'] ?? null ) ? $item['wcpa_fields'] : array()
			);

			foreach ( $custom_fields as $label => $value ) {
				$item_output .= "\n  {$label}: {$value}";
			}

			$output .= $item_output . "\n\n";
		}

		if ( $show_totals_block ) {
			$totals  = WPB_GQB_Quote_List::get_totals();
			$output .= strtr(
				$total_template,
				array(
					'{total}'       => $currency_symbol . wc_format_decimal( $totals['total'], 2 ),
					'{subtotal}'    => $currency_symbol . wc_format_decimal( $totals['subtotal'], 2 ),
					'{items_count}' => WPB_GQB_Quote_List::get_items_count(),
				)
			) . "\n";
		}

		return apply_filters( 'wpb_gqb_quote_list_content', $output, $items );
	}
}

/**
 * Build the "Cart Items" / "Cart Totals" plain-text block shared by the CF7
 * {gqb_product_data} "cart-content" tag and the WPForms {gqb_cart_content}
 * smart tag. Returns '' when the cart has no items.
 *
 * @param bool $decode_currency_entities Whether to html_entity_decode() the
 *                                       currency symbol (WPForms notification
 *                                       emails render as plain text, so HTML
 *                                       entities like &pound; must be decoded
 *                                       first; CF7's textarea output is HTML,
 *                                       so it doesn't need this).
 * @return string
 */
if ( ! function_exists( 'wpb_gqb_build_cart_content' ) ) {

	function wpb_gqb_build_cart_content( $decode_currency_entities = false ) {
		if ( ! function_exists( 'WC' ) || ! WC()->cart || WC()->cart->is_empty() ) {
			return '';
		}

		$currency_symbol = get_woocommerce_currency_symbol();

		if ( $decode_currency_entities ) {
			$currency_symbol = html_entity_decode( $currency_symbol, ENT_QUOTES, 'UTF-8' );
		}

		$output = esc_html__( 'Cart Items:', 'get-a-quote-button' ) . "\n";

		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$product  = $cart_item['data'];
			$name     = $product->get_name();
			$sku      = $product->get_sku() ? $product->get_sku() : esc_html__( 'N/A', 'get-a-quote-button' );
			$price    = wc_format_decimal( $product->get_price(), 2 );
			$qty      = $cart_item['quantity'];
			$subtotal = wc_format_decimal( $cart_item['line_subtotal'], 2 );

			$output .= "- {$name}\n";
			$output .= '  ' . esc_html__( 'SKU', 'get-a-quote-button' ) . ": {$sku}\n";
			$output .= '  ' . esc_html__( 'Price', 'get-a-quote-button' ) . ": {$currency_symbol}{$price}\n";
			$output .= '  ' . esc_html__( 'Quantity', 'get-a-quote-button' ) . ": {$qty}\n";
			$output .= '  ' . esc_html__( 'Subtotal', 'get-a-quote-button' ) . ": {$currency_symbol}{$subtotal}\n\n";
		}

		$subtotal_raw = WC()->cart->get_subtotal();
		$discount_raw = WC()->cart->get_discount_total();
		$total_raw    = WC()->cart->get_total( 'edit' );

		$output .= esc_html__( 'Cart Totals:', 'get-a-quote-button' ) . "\n";
		$output .= '  ' . esc_html__( 'Subtotal', 'get-a-quote-button' ) . ": {$currency_symbol}" . wc_format_decimal( $subtotal_raw, 2 ) . "\n";

		if ( $discount_raw > 0 ) {
			foreach ( WC()->cart->get_coupons() as $coupon ) {
				$output .= '  ' . sprintf(
					/* translators: %s: Coupon code. */
					esc_html__( 'Coupon (%s)', 'get-a-quote-button' ),
					$coupon->get_code()
				) . ": -{$currency_symbol}" . wc_format_decimal( $discount_raw, 2 ) . "\n";
			}
		}

		$output .= '  ' . esc_html__( 'Total', 'get-a-quote-button' ) . ": {$currency_symbol}" . wc_format_decimal( $total_raw, 2 ) . "\n";

		return apply_filters( 'wpb_gqb_build_cart_content', $output );
	}
}

/**
 * Build the full, human-readable single-product quote-data summary (title,
 * SKU, variations, custom fields, price, qty, total, URL) shared by the CF7
 * and WPForms "automatically send quote data" feature. Driven by the
 * "Single Product Data Template" field in Settings > Multi-Product Quote
 * (`quote_settings` option) so the wording/order is customizable via
 * dynamic tags.
 *
 * Available tags: {product_name}, {product_id}, {sku}, {price}, {quantity},
 * {total}, {product_url}, {variations}, {custom_fields}.
 *
 * @param object $product The WooCommerce product.
 * @return string
 */
if ( ! function_exists( 'wpb_gqb_build_single_product_summary' ) ) {

	function wpb_gqb_build_single_product_summary( $product ) {
		if ( ! is_object( $product ) || ! $product->exists() ) {
			return '';
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- reads the popup's own hidden fields to render a mail summary; CF7/WPForms validate the form submission itself, there's no separate state-changing action here.
		$id              = $product->get_id();
		$currency_symbol = get_woocommerce_currency_symbol();
		$qty             = isset( $_POST['wpb_gqb_qty'] ) ? absint( wp_unslash( $_POST['wpb_gqb_qty'] ) ) : 1;

		if ( $product->is_type( 'variable' ) && isset( $_POST['wpb_gqb_variation_price'] ) ) {
			$price = json_decode( wp_unslash( $_POST['wpb_gqb_variation_price'] ), true );
			$price = is_numeric( $price ) ? floatval( $price ) : 0;
		} else {
			$price = floatval( $product->get_price() );
		}

		$variations_block = '';

		if ( isset( $_POST['wpb_gqb_variations'] ) ) {
			$variations = json_decode( sanitize_text_field( wp_unslash( $_POST['wpb_gqb_variations'] ) ), true );

			if ( ! empty( $variations ) && is_array( $variations ) ) {
				foreach ( $variations as $key => $value ) {
					$variations_block .= ucfirst( str_replace( array( 'attribute_pa_', 'attribute_', 'pa_' ), '', $key ) ) . ': ' . $value . "\n";
				}
			}
		}

		$wapf = isset( $_POST['wpb_gqb_wapf'] ) ? json_decode( wp_unslash( $_POST['wpb_gqb_wapf'] ), true ) : array();
		$wcpa = isset( $_POST['wpb_gqb_wcpa'] ) ? json_decode( wp_unslash( $_POST['wpb_gqb_wcpa'] ), true ) : array();

		$custom_fields       = array_merge( is_array( $wapf ) ? $wapf : array(), is_array( $wcpa ) ? $wcpa : array() );
		$custom_fields_block = '';

		foreach ( $custom_fields as $label => $value ) {
			if ( ! is_string( $label ) || ( ! is_string( $value ) && ! is_numeric( $value ) ) ) {
				continue;
			}

			$custom_fields_block .= sanitize_text_field( $label ) . ': ' . sanitize_text_field( (string) $value ) . "\n";
		}

		$template = wpb_gqb_get_option(
			'wpb_gqb_single_product_template',
			'quote_settings',
			"Product: {product_name}\nProduct ID: {product_id}\nSKU: {sku}\n{variations}{custom_fields}Price: {price}\nQuantity: {quantity}\nTotal: {total}\nProduct URL: {product_url}"
		);

		$output = strtr(
			$template,
			array(
				'{product_name}'  => get_the_title( $id ),
				'{product_id}'    => $id,
				'{sku}'           => $product->get_sku(),
				'{price}'         => $currency_symbol . wc_format_decimal( $price, 2 ),
				'{quantity}'      => $qty,
				'{total}'         => $currency_symbol . wc_format_decimal( $price * $qty, 2 ),
				'{product_url}'   => get_permalink( $id ),
				'{variations}'    => $variations_block,
				'{custom_fields}' => $custom_fields_block,
			)
		);

		// phpcs:enable WordPress.Security.NonceVerification.Missing

		return apply_filters( 'wpb_gqb_full_product_data_summary', $output, $id );
	}
}
