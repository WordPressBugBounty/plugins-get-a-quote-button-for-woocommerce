<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sending the product meta with the mail
 * Product title, SKU, Price
 */
function wpb_gqb_cf7_add_form_tags() {

	$name_attr = ( wpb_gqb_get_option( 'wpb_gqb_cf7_form_name_attr', 'form_settings' ) === 'on' ? true : false );

	wpcf7_add_form_tag( 'gqb_product_id', 'wpb_gqb_cf7_send_product_id_tag_handler', array( 'name-attr' => $name_attr ) );
	wpcf7_add_form_tag( 'gqb_product_url', 'wpb_gqb_cf7_send_product_url_tag_handler', array( 'name-attr' => $name_attr ) );
	wpcf7_add_form_tag( 'gqb_product_title', 'wpb_gqb_cf7_send_post_title_tag_handler', array( 'name-attr' => $name_attr ) );
	wpcf7_add_form_tag( 'gqb_product_description', 'wpb_gqb_cf7_send_description_tag_handler', array( 'name-attr' => $name_attr ) );
	wpcf7_add_form_tag( 'gqb_product_short_description', 'wpb_gqb_cf7_send_short_description_tag_handler', array( 'name-attr' => $name_attr ) );
	wpcf7_add_form_tag( 'gqb_product_sku', 'wpb_gqb_cf7_send_product_sku_tag_handler', array( 'name-attr' => $name_attr ) );
	wpcf7_add_form_tag( 'gqb_product_price', 'wpb_gqb_cf7_send_product_price_tag_handler', array( 'name-attr' => $name_attr ) );
	wpcf7_add_form_tag( 'gqb_product_status', 'wpb_gqb_cf7_send_product_status_tag_handler', array( 'name-attr' => $name_attr ) );
	wpcf7_add_form_tag( 'gqb_product_type', 'wpb_gqb_cf7_send_product_type_tag_handler', array( 'name-attr' => $name_attr ) );
	wpcf7_add_form_tag( 'gqb_product_stock_status', 'wpb_gqb_cf7_send_product_stock_status_tag_handler', array( 'name-attr' => $name_attr ) );
	wpcf7_add_form_tag( 'gqb_product_stock_quantity', 'wpb_gqb_cf7_send_product_stock_quantity_tag_handler', array( 'name-attr' => $name_attr ) );
	wpcf7_add_form_tag( 'gqb_product_variations', 'wpb_gqb_cf7_send_product_variation_tag_handler', array( 'name-attr' => $name_attr ) );
	wpcf7_add_form_tag( 'gqb_product_variations_value_only', 'wpb_gqb_cf7_send_product_variation_value_only_tag_handler', array( 'name-attr' => $name_attr ) );
	wpcf7_add_form_tag( 'gqb_product_variation_sku', 'wpb_gqb_cf7_send_product_variation_sku_tag_handler', array( 'name-attr' => $name_attr ) );
	wpcf7_add_form_tag( 'gqb_product_qty', 'wpb_gqb_cf7_send_product_qty_tag_handler', array( 'name-attr' => $name_attr ) );
}

add_action( 'wpcf7_init', 'wpb_gqb_cf7_add_form_tags' );

// phpcs:disable WordPress.Security.NonceVerification.Missing -- these legacy tag handlers only read the popup's own hidden fields to render form-tag output; CF7 handles the actual submission's own validation.

/**
 * Send the product ID
 *
 * @return string
 */
function wpb_gqb_cf7_send_product_id_tag_handler() {

	if ( isset( $_POST['wpb_post_id'] ) ) {
		$id = absint( wp_unslash( $_POST['wpb_post_id'] ) );
		return '<input class="gqb_hidden_field gqb_product_id" type="text" name="gqb_product_id" value="' . esc_attr( $id ) . '">';
	}
}

/**
 * Send the product URL
 *
 * @return string
 */
function wpb_gqb_cf7_send_product_url_tag_handler() {
	if ( isset( $_POST['wpb_post_id'] ) ) {

		$id      = absint( wp_unslash( $_POST['wpb_post_id'] ) );
		$product = wc_get_product( $id );

		if ( $product ) {
			$permalink = get_permalink( $product->get_id() );

			return '<input class="gqb_hidden_field gqb_product_url" type="text" name="gqb_product_url" value="' . esc_attr( $permalink ) . '">';
		}
	}
}

/**
 * Send the product title
 *
 * @return string
 */
function wpb_gqb_cf7_send_post_title_tag_handler() {
	if ( isset( $_POST['wpb_post_id'] ) ) {
		$id = absint( wp_unslash( $_POST['wpb_post_id'] ) );
		return '<input class="gqb_hidden_field gqb_product_title" type="text" name="gqb_product_title" value="' . esc_attr( get_the_title( $id ) ) . '">';
	}
}

/**
 * Send the product description
 *
 * @return string
 */
function wpb_gqb_cf7_send_description_tag_handler() {
	if ( isset( $_POST['wpb_post_id'] ) ) {

		$id      = absint( wp_unslash( $_POST['wpb_post_id'] ) );
		$product = wc_get_product( $id );

		if ( $product ) {
			$html = sprintf( '<textarea class="gqb_hidden_field gqb_product_description wpcf7-form-control wpcf7-textarea" style="display:none;" id="gqb_product_description" name="gqb_product_description">%s</textarea>', wp_kses_post( $product->get_description() ) );

			return $html;
		}
	}
}

/**
 * Send the product short description
 *
 * @return string
 */
function wpb_gqb_cf7_send_short_description_tag_handler() {
	if ( isset( $_POST['wpb_post_id'] ) ) {

		$id      = absint( wp_unslash( $_POST['wpb_post_id'] ) );
		$product = wc_get_product( $id );

		if ( $product ) {
			$html = sprintf( '<textarea class="gqb_hidden_field gqb_product_short_description wpcf7-form-control wpcf7-textarea" style="display:none;" id="gqb_product_short_description" name="gqb_product_short_description">%s</textarea>', wp_kses_post( $product->get_short_description() ) );

			return $html;
		}
	}
}

/**
 * Send the product variation
 *
 * @return string
 */
function wpb_gqb_cf7_send_product_variation_tag_handler() {
	if ( isset( $_POST['wpb_gqb_variations'] ) ) {
		$variation_list = '';
		$variations     = stripslashes( sanitize_text_field( $_POST['wpb_gqb_variations'] ) );
		$variations     = json_decode( $variations );

		if ( isset( $variations ) && ! empty( $variations ) ) {
			foreach ( $variations as $key => $variation ) {
				$variation_list .= $key . ' : ' . $variation . '&#10;';
			}
		}

		$html = sprintf( '<textarea class="gqb_hidden_field gqb_product_variations wpcf7-form-control wpcf7-textarea" style="display:none;" id="gqb_product_variations" name="gqb_product_variations">%s</textarea>', esc_html( $variation_list ) );

		return $html;
	}
}

/**
 * Send the product variation [value only]
 *
 * @return string
 */
function wpb_gqb_cf7_send_product_variation_value_only_tag_handler() {
	if ( isset( $_POST['wpb_gqb_variations'] ) ) {
		$variation_list = '';
		$variations     = stripslashes( sanitize_text_field( $_POST['wpb_gqb_variations'] ) );
		$variations     = json_decode( $variations );

		if ( isset( $variations ) && ! empty( $variations ) ) {
			foreach ( $variations as $key => $variation ) {
				$variation_list .= $variation . '&#32;';
			}
		}

		$html = sprintf( '<textarea class="gqb_hidden_field gqb_product_variations_value_only wpcf7-form-control wpcf7-textarea" style="display:none;" id="gqb_product_variations_value_only" name="gqb_product_variations_value_only">%s</textarea>', wp_kses_post( $variation_list ) );

		return $html;
	}
}

/**
 * Send the product variation SKU
 *
 * @return string
 */
function wpb_gqb_cf7_send_product_variation_sku_tag_handler() {

	$variation_sku = '';

	if ( isset( $_POST['wpb_gqb_variation_sku'] ) ) {

		$variation_sku = stripslashes( sanitize_text_field( $_POST['wpb_gqb_variation_sku'] ) );
		$html          = sprintf( '<textarea class="gqb_hidden_field gqb_product_variation_sku wpcf7-form-control wpcf7-textarea" style="display:none;" id="gqb_product_variation_sku" name="gqb_product_variation_sku">%s</textarea>', wp_kses_post( $variation_sku ) );

		return $html;
	}
}

/**
 * Send the product qty
 *
 * @return string
 */
function wpb_gqb_cf7_send_product_qty_tag_handler() {

	if ( isset( $_POST['wpb_gqb_qty'] ) ) {
		$qty = absint( wp_unslash( $_POST['wpb_gqb_qty'] ) );

		return '<input class="gqb_hidden_field gqb_product_qty" type="number" name="gqb_product_qty" value="' . esc_attr( $qty ) . '">';
	}
}

/**
 * Send the product SKU
 *
 * @return string
 */
function wpb_gqb_cf7_send_product_sku_tag_handler() {

	if ( isset( $_POST['wpb_post_id'] ) ) {

		$id      = absint( wp_unslash( $_POST['wpb_post_id'] ) );
		$product = wc_get_product( $id );

		if ( $product ) {
			return '<input class="gqb_hidden_field gqb_product_sku" type="text" name="gqb_product_sku" value="' . esc_attr( $product->get_sku() ) . '">';
		}
	}
}

/**
 * Send the product Price
 *
 * @return string
 */
function wpb_gqb_cf7_send_product_price_tag_handler() {

	if ( isset( $_POST['wpb_post_id'] ) ) {

		$id      = absint( wp_unslash( $_POST['wpb_post_id'] ) );
		$product = wc_get_product( $id );

		if ( $product ) {
			return '<input class="gqb_hidden_field gqb_product_price" type="text" name="gqb_product_price" value="' . esc_attr( $product->get_price() ) . '">';
		}
	}
}

/**
 * Send the product status
 *
 * @return string
 */
function wpb_gqb_cf7_send_product_status_tag_handler() {

	if ( isset( $_POST['wpb_post_id'] ) ) {

		$id      = absint( wp_unslash( $_POST['wpb_post_id'] ) );
		$product = wc_get_product( $id );

		if ( $product ) {
			return '<input class="gqb_hidden_field gqb_product_status" type="text" name="gqb_product_status" value="' . esc_attr( $product->get_status() ) . '">';
		}
	}
}

/**
 * Send the product type
 *
 * @return string
 */
function wpb_gqb_cf7_send_product_type_tag_handler() {

	if ( isset( $_POST['wpb_post_id'] ) ) {

		$id      = absint( wp_unslash( $_POST['wpb_post_id'] ) );
		$product = wc_get_product( $id );

		if ( $product ) {
			return '<input class="gqb_hidden_field gqb_product_type" type="text" name="gqb_product_type" value="' . esc_attr( $product->get_type() ) . '">';
		}
	}
}

/**
 * Send the product stock status
 *
 * @return string
 */
function wpb_gqb_cf7_send_product_stock_status_tag_handler() {

	if ( isset( $_POST['wpb_post_id'] ) ) {

		$id      = absint( wp_unslash( $_POST['wpb_post_id'] ) );
		$product = wc_get_product( $id );

		if ( $product ) {
			return '<input class="gqb_hidden_field gqb_product_stock_status" type="text" name="gqb_product_stock_status" value="' . esc_attr( $product->get_stock_status() ) . '">';
		}
	}
}

/**
 * Send the product stock quantity
 *
 * @return string
 */
function wpb_gqb_cf7_send_product_stock_quantity_tag_handler() {

	if ( isset( $_POST['wpb_post_id'] ) ) {

		$id      = absint( wp_unslash( $_POST['wpb_post_id'] ) );
		$product = wc_get_product( $id );

		if ( $product ) {
			return '<input class="gqb_hidden_field gqb_product_stock_quantity" type="text" name="gqb_product_stock_quantity" value="' . esc_attr( $product->get_stock_quantity() ) . '">';
		}
	}
}

/**
 * CF7 Post Shortcode [Old]
 */
function wpb_gqb_cf7_add_form_tag_for_post_title() {
	wpcf7_add_form_tag( 'post_title', 'wpb_gqb_cf7_post_title_tag_handler' );
}

add_action( 'wpcf7_init', 'wpb_gqb_cf7_add_form_tag_for_post_title' );

/**
 * Send the product title
 *
 * @return string
 */
function wpb_gqb_cf7_post_title_tag_handler() {
	if ( isset( $_POST['wpb_post_id'] ) ) {
		$id = absint( wp_unslash( $_POST['wpb_post_id'] ) );
		return '<input class="gqb_hidden_field gqb_product_post-title" type="text" name="post-title" value="' . esc_html( get_the_title( $id ) ) . '">';
	}
}

/**
 * Show the product title in Popup (CF7) [ Deprecated ]
 * Now has option in the Popup settings for showing the post title
 */
function wpb_gqb_cf7_add_form_tag_get_the_title() {
	wpcf7_add_form_tag( 'gqp_get_the_title', 'wpb_gqb_cf7_get_the_title_tag_handler' );
}

add_action( 'wpcf7_init', 'wpb_gqb_cf7_add_form_tag_get_the_title' );

/**
 * Send the product title
 *
 * @return string
 */
function wpb_gqb_cf7_get_the_title_tag_handler() {
	if ( isset( $_POST['wpb_post_id'] ) ) {
		$id = absint( wp_unslash( $_POST['wpb_post_id'] ) );
		return esc_html( get_the_title( $id ) );
	}
}

// phpcs:enable WordPress.Security.NonceVerification.Missing
