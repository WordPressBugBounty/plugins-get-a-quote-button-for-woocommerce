<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * CF7 custom tags for WooCommerce product data
 *
 * @since 3.0
 */
class WPB_GQB_CF7_Product_Data_Tags {

	public $separator = '';

	/**
	 * Classs constructor.
	 */
	public function __construct() {
		$this->separator = apply_filters( 'wpb_gqb_data_tag_separator', '&#10;' );
		add_action( 'wpcf7_init', array( $this, 'wpcf7_add_form_tag_gqb_product_data' ) );
		add_action( 'wpcf7_form_class_attr', array( $this, 'is_show_the_product_data_fields' ) );

		// Fully automatic quote data delivery (no shortcode/tag required in the form).
		add_filter( 'wpcf7_form_hidden_fields', array( $this, 'add_auto_product_data_hidden_field' ) );
		add_filter( 'wpcf7_mail_components', array( $this, 'append_auto_product_data_to_mail' ) );
	}

	// phpcs:disable WordPress.Security.NonceVerification.Missing -- these methods only read the popup's own hidden fields (product id, qty, variations, etc.) to render/append quote data; CF7 handles the actual submission's own validation, there is no separate state-changing action here.

	/**
	 * Adding the custom CF7 form tag
	 *
	 * @return void
	 * @since 3.0
	 */
	public function wpcf7_add_form_tag_gqb_product_data() {

		wpcf7_add_form_tag(
			array( 'gqb_product_data', 'gqb_product_data*' ),
			array( $this, 'wpcf7_gqb_product_data_form_tag_handler' ),
			array( 'name-attr' => true )
		);
	}

	/**
	 * WooCommerce product data form tag handler
	 *
	 * @param object $tag The form tag.
	 * @return mixed
	 */
	public function wpcf7_gqb_product_data_form_tag_handler( $tag ) {
		// wpb_quote_list is the one marker our JS always sends for every flow
		// (regular button, cart page, and quote list) - unlike wpb_post_id,
		// which is absent for the quote-list "Send Quote" button since there's
		// no single product ID in that context.
		if ( empty( $tag->name ) || ! isset( $_POST['wpb_quote_list'] ) ) {
			wpb_gqb_quote_debug_log(
				'10_tag_handler:aborted_by_guard',
				array(
					'tag_name'             => isset( $tag->name ) ? $tag->name : '(empty)',
					'wpb_quote_list_isset' => isset( $_POST['wpb_quote_list'] ),
				)
			);

			return '';
		}

		$show_product_data = wpb_gqb_get_option( 'wpb_gqb_form_product_info', 'woo_settings', 'hide' );
		$editable          = wpb_gqb_get_option( 'wpb_gqb_form_product_info_editable', 'woo_settings', 'yes' );
		$product_id        = isset( $_POST['wpb_post_id'] ) ? absint( wp_unslash( $_POST['wpb_post_id'] ) ) : 0;
		$wpb_is_cart_page  = isset( $_POST['wpb_cart_page'] ) ? wc_string_to_bool( wp_unslash( $_POST['wpb_cart_page'] ) ) : false;
		$wpb_is_quote_list = wc_string_to_bool( wp_unslash( $_POST['wpb_quote_list'] ) );
		$validation_error  = wpcf7_get_validation_error( $tag->name );
		$class             = wpcf7_form_controls_class( $tag->type, 'wpcf7-text' );

		if ( $validation_error ) {
			$class .= ' wpcf7-not-valid';
		}

		if ( 'hide' === $show_product_data ) {
			$class .= ' gqb_hidden_field';
		}

		if ( 'no' === $editable ) {
			$class .= ' gqb_not_editable_field';
		}

		$class .= ' gqb-' . $tag->name;

		$atts = array();

		if ( 'product-data-content' === $tag->name ) {
			// Context-aware "all-in-one" tag: works for the single-product,
			// cart-page, and quote-list flows alike, unlike the tag names
			// below which are hard-routed to whichever flow is active.
			$value = $this->get_auto_quote_data_block();
		} elseif ( $wpb_is_quote_list ) {
			$value = $this->get_quote_list_data( $tag );
		} elseif ( $wpb_is_cart_page ) {
			$value = $this->get_cart_page_data( $tag );
		} else {
			$value = $this->get_product_data( $tag, $product_id );
		}

		wpb_gqb_quote_debug_log(
			'11_tag_handler:resolved',
			array(
				'tag_name'      => $tag->name,
				'is_quote_list' => $wpb_is_quote_list,
				'is_cart_page'  => $wpb_is_cart_page,
				'product_id'    => $product_id,
				'value_length'  => strlen( (string) $value ),
				'value_preview' => mb_substr( (string) $value, 0, 120 ),
			)
		);

		$atts['value'] = $value;
		$atts['class'] = $tag->get_class_option( $class );
		$atts['id']    = $tag->get_id_option();
		$atts['name']  = $tag->name;
		$atts['type']  = 'text';

		if ( $tag->is_required() ) {
			$atts['type'] = 'hidden';
		}

		if ( apply_filters( 'wpb_gqb_data_tag_textarea', true ) && in_array( $tag->name, array( 'product-variations', 'product-description', 'product-short-description', 'product-fields', 'wcpa-product-fields', 'cart-content', 'quote-list-content', 'product-data-content' ) ) ) {
			$atts['type']  = '';
			$atts['value'] = '';
			$atts['rows']  = $tag->get_rows_option( '3' );

			$html = sprintf(
				'<span class="wpcf7-form-control-wrap" data-name="%1$s"><textarea %2$s %5$s>%3$s</textarea>%4$s</span>',
				esc_attr( $tag->name ),
				wpcf7_format_atts( $atts ),
				wp_kses_post( $value ),
				$validation_error,
				( 'no' === $editable ? 'readonly' : '' )
			);
		} else {
			$html = sprintf(
				'<span class="wpcf7-form-control-wrap" data-name="%1$s"><input %2$s %4$s/>%3$s</span>',
				esc_attr( $tag->name ),
				wpcf7_format_atts( $atts ),
				$validation_error,
				( 'no' === $editable ? 'readonly' : '' )
			);
		}

		wpb_gqb_quote_debug_log(
			'12_tag_handler:html_output',
			array(
				'tag_name'    => $tag->name,
				'html_length' => strlen( $html ),
			)
		);

		return apply_filters( 'wpb_gqb_product_data_form_tag_handler', $html, $tag, $product_id );
	}

	/**
	 * Prepare the cart data for the form tag
	 *
	 * @param object $tag The form tag.
	 * @return void
	 */
	public function get_cart_page_data( $tag ) {
		if ( empty( $tag->name ) ) {
			return '';
		}

		if ( WC()->cart->is_empty() ) {
			return '';
		}

		$output = '';

		switch ( $tag->name ) {
			case 'cart-content':
				$output = wpb_gqb_build_cart_content();
				break;

			default:
				break;
		}

		return apply_filters( 'wpb_gqb_get_cart_page_data', $output, $tag->name, WC()->cart );
	}

	/**
	 * Prepare the multi-product quote list data for the form tag
	 *
	 * @param object $tag The form tag.
	 * @return string
	 */
	public function get_quote_list_data( $tag ) {
		if ( empty( $tag->name ) || ! class_exists( 'WPB_GQB_Quote_List' ) ) {
			wpb_gqb_quote_debug_log(
				'20_get_quote_list_data:aborted',
				array(
					'tag_name'                => isset( $tag->name ) ? $tag->name : '(empty)',
					'quote_list_class_exists' => class_exists( 'WPB_GQB_Quote_List' ),
				)
			);

			return '';
		}

		$items = WPB_GQB_Quote_List::get_items();

		wpb_gqb_quote_debug_log(
			'21_get_quote_list_data:items',
			array(
				'tag_name'   => $tag->name,
				'item_count' => is_array( $items ) ? count( $items ) : '(not array)',
			)
		);

		if ( empty( $items ) ) {
			return '';
		}

		$output = '';

		switch ( $tag->name ) {
			case 'quote-list-content':
				$output = wpb_gqb_build_quote_list_content();
				break;

			default:
				break;
		}

		return apply_filters( 'wpb_gqb_get_quote_list_data', $output, $tag->name );
	}

	/**
	 * Prepare the product data for the form tag
	 *
	 * @param objecy $tag The form tag.
	 * @param objecy $product_id The currently quote requested product ID.
	 * @return void
	 */
	public function get_product_data( $tag, $product_id ) {
		if ( empty( $tag->name ) || ! isset( $_POST['wpb_post_id'] ) ) {
			return '';
		}

		if ( empty( $product_id ) ) {
			return '';
		}

		$product = wc_get_product( $product_id );

		if ( ! is_object( $product ) || ! $product->exists() ) {
			return '';
		}

		$output = '';
		$data   = $tag->name;
		$id     = $product->get_id();

		switch ( $data ) {
			case 'product-id':
				$output = absint( $id );
				break;

			case 'product-url':
				$output = esc_url( get_permalink( $id ) );
				break;

			case 'product-title':
				$output = esc_html( get_the_title( $id ) );
				break;

			case 'product-description':
				$output = wp_kses_post( $product->get_description() );
				break;

			case 'product-short-description':
				$output = wp_kses_post( $product->get_short_description() );
				break;

			case 'product-sku':
				$output = esc_html( $product->get_sku() );
				break;

			case 'product-price':
				if ( $product->is_type( 'variable' ) ) {
					if ( isset( $_POST['wpb_gqb_variation_price'] ) ) {
						$output = json_decode( sanitize_text_field( wp_unslash( $_POST['wpb_gqb_variation_price'] ) ), true );
					}
				} else {
					$output = esc_html( $product->get_price() );
				}
				break;

			case 'total-price':
				$qty = $price = 0;

				if ( isset( $_POST['wpb_gqb_qty'] ) ) {
					$qty = absint( wp_unslash( $_POST['wpb_gqb_qty'] ) );
				}

				if ( $product->is_type( 'variable' ) ) {
					if ( isset( $_POST['wpb_gqb_variation_price'] ) ) {
						$decoded_price = json_decode( wp_unslash( $_POST['wpb_gqb_variation_price'] ), true );
						if ( is_numeric( $decoded_price ) ) {
							$price = floatval( $decoded_price );
						}
					}
				} else {
					$price = floatval( $product->get_price() );
				}

				$output = $qty * $price;
				break;

			case 'product-status':
				$output = esc_html( $product->get_status() );
				break;

			case 'product-type':
				$output = esc_html( $product->get_type() );
				break;

			case 'product-stock-status':
				$output = esc_html( $product->get_stock_status() );
				break;

			case 'product-stock-quantity':
				$output = esc_html( $product->get_stock_quantity() );
				break;

			case 'product-variations':
				$variation_list = '';
				$variations     = json_decode( sanitize_text_field( wp_unslash( $_POST['wpb_gqb_variations'] ) ), true );

				if ( isset( $variations ) && ! empty( $variations ) ) {
					foreach ( $variations as $key => $variation ) {
						$variation_list .= ucfirst( $key ) . ' : ' . $variation . $this->separator;
					}
				}

				$output = esc_html( $variation_list );
				break;

			case 'product-variations-value-only':
				$variation_list = '';
				$variations     = json_decode( sanitize_text_field( wp_unslash( $_POST['wpb_gqb_variations'] ) ), true );

				if ( isset( $variations ) && ! empty( $variations ) ) {
					foreach ( $variations as $key => $variation ) {
						$variation_list .= $variation . '&#32;';
					}
				}

				$output = esc_html( $variation_list );
				break;

			case 'product-variation-sku':
				$variation_sku = '';

				if ( isset( $_POST['wpb_gqb_variation_sku'] ) ) {
					$variation_sku = json_decode( sanitize_text_field( wp_unslash( $_POST['wpb_gqb_variation_sku'] ) ), true );
				}

				$output = esc_html( $variation_sku );
				break;

			case 'product-fields':
				$wapf   = '';
				$fields = json_decode( sanitize_text_field( wp_unslash( $_POST['wpb_gqb_wapf'] ) ), true );

				if ( ! empty( $fields ) ) {
					foreach ( $fields as $key => $field ) {
						if ( isset( $tag->raw_values ) && is_array( $tag->raw_values ) ) {
							if ( in_array( $key, $tag->raw_values, true ) ) {
								$wapf .= ucfirst( sanitize_text_field( $key ) ) . ' : ' . esc_html( $field ) . $this->separator;
							}
						} else {
							$wapf .= ucfirst( sanitize_text_field( $key ) ) . ' : ' . esc_html( $field ) . $this->separator;
						}
					}
				}

				$output = esc_html( $wapf );

				break;

			case 'wcpa-product-fields':
				$output = '';

				if ( empty( $_POST['wpb_gqb_wcpa'] ) ) {
					break;
				}

				$fields = json_decode( wp_unslash( $_POST['wpb_gqb_wcpa'] ), true );

				if ( ! is_array( $fields ) || empty( $fields ) ) {
					break;
				}

				$formatted = array();

				foreach ( $fields as $key => $field ) {

					// Ensure both key and value are strings
					if ( ! is_string( $key ) || ( ! is_string( $field ) && ! is_numeric( $field ) ) ) {
						continue;
					}

					$clean_key   = sanitize_text_field( $key );
					$clean_value = sanitize_text_field( (string) $field );

					if ( $clean_key === '' || $clean_value === '' ) {
						continue;
					}

					$formatted[] = esc_html( $clean_key ) . ' : ' . esc_html( $clean_value );
				}

				if ( ! empty( $formatted ) ) {
					$output = implode( "\n", $formatted );
				}

				break;

			case 'product-qty':
				$qty = '';

				if ( isset( $_POST['wpb_gqb_qty'] ) ) {
					$qty = absint( wp_unslash( $_POST['wpb_gqb_qty'] ) );
				}

				$output = esc_html( $qty );
				break;

			case 'product-image-url':
				$image_url = '';

				if ( get_the_post_thumbnail_url( $id ) ) {
					$image_url = get_the_post_thumbnail_url( $id );
				}

				$output = esc_url( $image_url );
				break;

			default:
				$output = '';
				break;
		}

		return apply_filters( 'wpb_gqb_get_product_data', $output, $data, $id );
	}

	/**
	 * Build a full, human-readable summary of the current single-product quote
	 * request (title, SKU, variations, custom fields, price, qty, total).
	 * Reads the same $_POST fields the button sent when the popup was opened.
	 *
	 * @param object $product The WooCommerce product.
	 * @return string
	 */
	public function get_full_product_data_summary( $product ) {
		return wpb_gqb_build_single_product_summary( $product );
	}

	/**
	 * Build the appropriate full quote-data block (single product, cart page,
	 * or multi-product quote list) for the request that opened the popup.
	 *
	 * @return string
	 */
	public function get_auto_quote_data_block() {
		if ( ! isset( $_POST['wpb_quote_list'] ) ) {
			wpb_gqb_quote_debug_log( '30_auto_block:no_wpb_quote_list_in_post' );

			return '';
		}

		$wpb_is_quote_list = wc_string_to_bool( wp_unslash( $_POST['wpb_quote_list'] ) );
		$wpb_is_cart_page  = isset( $_POST['wpb_cart_page'] ) && wc_string_to_bool( wp_unslash( $_POST['wpb_cart_page'] ) );

		if ( $wpb_is_quote_list ) {
			$block = $this->get_quote_list_data( (object) array( 'name' => 'quote-list-content' ) );
			wpb_gqb_quote_debug_log( '31_auto_block:quote_list_branch', array( 'block_length' => strlen( $block ) ) );
			return $block;
		}

		if ( $wpb_is_cart_page ) {
			$block = $this->get_cart_page_data( (object) array( 'name' => 'cart-content' ) );
			wpb_gqb_quote_debug_log( '31_auto_block:cart_page_branch', array( 'block_length' => strlen( $block ) ) );
			return $block;
		}

		$product_id = isset( $_POST['wpb_post_id'] ) ? absint( wp_unslash( $_POST['wpb_post_id'] ) ) : 0;

		if ( empty( $product_id ) ) {
			wpb_gqb_quote_debug_log( '31_auto_block:no_product_id' );

			return '';
		}

		$block = $this->get_full_product_data_summary( wc_get_product( $product_id ) );
		wpb_gqb_quote_debug_log(
			'31_auto_block:single_product_branch',
			array(
				'product_id'   => $product_id,
				'block_length' => strlen( $block ),
			)
		);

		return $block;
	}

	/**
	 * Bake the full quote-data block into a hidden field at popup-render time,
	 * so it rides along with the form's own fields on actual submission -
	 * without the site owner needing to add any tag to the form template.
	 *
	 * @param array $hidden_fields
	 * @return array
	 */
	public function add_auto_product_data_hidden_field( $hidden_fields ) {
		$setting = wpb_gqb_get_option( 'wpb_gqb_auto_send_product_data', 'woo_settings', 'off' );

		if ( 'on' !== $setting ) {
			wpb_gqb_quote_debug_log( '40_hidden_field:setting_off', array( 'setting_value' => $setting ) );

			return $hidden_fields;
		}

		// Only true while rendering the form inside our own "fire_contact_form" AJAX request.
		if ( ! isset( $_POST['wpb_quote_list'] ) ) {
			wpb_gqb_quote_debug_log( '40_hidden_field:not_in_quote_request' );

			return $hidden_fields;
		}

		$block = $this->get_auto_quote_data_block();

		wpb_gqb_quote_debug_log( '41_hidden_field:block_built', array( 'block_length' => strlen( $block ) ) );

		if ( $block ) {
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- transports the plain-text quote summary through a hidden form field, not code obfuscation.
			$hidden_fields['_wpb_gqb_auto_product_data'] = base64_encode( $block );
		}

		return $hidden_fields;
	}

	/**
	 * Append the baked quote-data block (submitted back via the hidden field
	 * above) to the outgoing mail body.
	 *
	 * @param array $components Mail components (subject, body, etc).
	 * @return array
	 */
	public function append_auto_product_data_to_mail( $components ) {
		wpb_gqb_quote_debug_log(
			'50_mail_append:entry',
			array(
				'hidden_field_present' => ! empty( $_POST['_wpb_gqb_auto_product_data'] ),
				'body_present'         => ! empty( $components['body'] ),
			)
		);

		if ( empty( $_POST['_wpb_gqb_auto_product_data'] ) || empty( $components['body'] ) ) {
			return $components;
		}

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- decodes the plain-text quote summary baked into the hidden field above, not code obfuscation.
		$block = base64_decode( wp_unslash( $_POST['_wpb_gqb_auto_product_data'] ), true );

		wpb_gqb_quote_debug_log( '51_mail_append:decoded', array( 'block_length' => strlen( (string) $block ) ) );

		if ( ! $block ) {
			return $components;
		}

		$heading = wpb_gqb_get_option( 'wpb_gqb_quote_data_heading', 'quote_settings', esc_html__( 'Quote Details', 'get-a-quote-button' ) );
		$is_html = false !== stripos( $components['body'], '<html' );

		if ( $is_html ) {
			$addition = '<p><strong>' . $heading . '</strong><br>' . nl2br( esc_html( trim( $block ) ) ) . '</p>' . "\n";

			if ( false !== stripos( $components['body'], '</body>' ) ) {
				$components['body'] = preg_replace( '/<\/body>/i', $addition . '</body>', $components['body'], 1 );
			} else {
				$components['body'] .= $addition;
			}
		} else {
			$components['body'] = rtrim( $components['body'] ) . "\n\n" . $heading . ":\n" . trim( $block ) . "\n";
		}

		wpb_gqb_quote_debug_log(
			'52_mail_append:body_updated',
			array(
				'is_html'         => $is_html,
				'new_body_length' => strlen( $components['body'] ),
			)
		);

		return $components;
	}

	// phpcs:enable WordPress.Security.NonceVerification.Missing

	/**
	 * Show or hide the product data fields in the form.
	 *
	 * @param string $classes
	 * @return string
	 */
	public function is_show_the_product_data_fields( $classes ) {
		$product_info = wpb_gqb_get_option( 'wpb_gqb_form_product_info', 'woo_settings', 'hide' );

		if ( $product_info ) {
			$classes .= ' wpb_gqb_form_product_info_' . esc_attr( $product_info );
		}

		return $classes;
	}
}
new WPB_GQB_CF7_Product_Data_Tags();
