<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WPForms custom smart tags for WooCommerce product data.
 */
class WPB_GQB_WPForms_Custom_Smart_Tags {


	public $separator = '';

	/**
	 * Initialize the class.
	 */
	public function __construct() {
		$this->separator = apply_filters( 'wpb_gqb_data_tag_separator', '&#10;' );
		add_filter( 'wpforms_smart_tags', array( $this, 'custom_smart_tag' ) );
		add_filter( 'wpforms_smart_tag_process', array( $this, 'process_product_tags' ), 10, 6 );
		add_filter( 'wpforms_smart_tag_process', array( $this, 'process_wc_cart_tags' ), 10, 6 );
		add_filter( 'wpforms_smart_tag_process', array( $this, 'process_wcpa_product_fields' ), 10, 6 );
		add_filter( 'wpforms_smart_tag_process', array( $this, 'process_quote_list_tags' ), 10, 6 );

		// Fully automatic quote data delivery (no smart tag required in the form).
		add_action( 'wpforms_frontend_output', array( $this, 'add_auto_product_data_hidden_field' ) );
		add_filter( 'wpforms_emails_notifications_message', array( $this, 'append_auto_product_data_to_mail' ), 10, 3 );
	}

	// phpcs:disable WordPress.Security.NonceVerification.Missing -- these methods only read the popup's own hidden fields (product id, qty, variations, etc.) to render/append quote data; WPForms handles the actual submission's own validation, there is no separate state-changing action here.

	/**
	 * Create User Smart Tags from the WordPress profile.
	 *
	 * @link   https://wpforms.com/developers/how-to-create-more-user-smart-tags/
	 */
	public function custom_smart_tag( $tags ) {
		$tags['gqb_product_id']                    = esc_html__( 'WC Product ID', 'get-a-quote-button' );
		$tags['gqb_product_title']                 = esc_html__( 'WC Product Title', 'get-a-quote-button' );
		$tags['gqb_product_url']                   = esc_html__( 'WC Product URL', 'get-a-quote-button' );
		$tags['gqb_product_description']           = esc_html__( 'WC Product Description', 'get-a-quote-button' );
		$tags['gqb_product_short_description']     = esc_html__( 'WC Product Short Description', 'get-a-quote-button' );
		$tags['gqb_product_sku']                   = esc_html__( 'WC Product SKU', 'get-a-quote-button' );
		$tags['gqb_product_price']                 = esc_html__( 'WC Product Price', 'get-a-quote-button' );
		$tags['gqb_product_total_price']           = esc_html__( 'WC Product Total Price', 'get-a-quote-button' );
		$tags['gqb_product_status']                = esc_html__( 'WC Product Status', 'get-a-quote-button' );
		$tags['gqb_product_type']                  = esc_html__( 'WC Product Type', 'get-a-quote-button' );
		$tags['gqb_product_stock_status']          = esc_html__( 'WC Product Stock Status', 'get-a-quote-button' );
		$tags['gqb_product_stock_quantity']        = esc_html__( 'WC Product Stock Quantity', 'get-a-quote-button' );
		$tags['gqb_product_variations']            = esc_html__( 'WC Product Variations', 'get-a-quote-button' );
		$tags['gqb_product_variations_value_only'] = esc_html__( 'WC Product Variations Value Only', 'get-a-quote-button' );
		$tags['gqb_product_variation_sku']         = esc_html__( 'WC Product Variation SKU', 'get-a-quote-button' );
		$tags['gqb_product_qty']                   = esc_html__( 'WC Product Quantity', 'get-a-quote-button' );
		$tags['gqb_product_image_url']             = esc_html__( 'WC Product Image URL', 'get-a-quote-button' );
		$tags['gqb_cart_content']                  = esc_html__( 'WC Cart Content', 'get-a-quote-button' );
		$tags['gqb_quote_list_content']            = esc_html__( 'WC Quote List Content', 'get-a-quote-button' );

		if ( defined( 'WCPA_FILE' ) ) {
			$tags['gqb_wcpa_product_fields'] = esc_html__( 'WC Product Fields by Acowebs', 'get-a-quote-button' );
		}
		return $tags;
	}

	/**
	 * Process the WC Cart Smart Tags.
	 */
	public function process_wc_cart_tags( $content, $tag_name, $form_data, $fields, $entry_id, $smart_tag_object ) {
		$wpb_is_cart_page = wc_string_to_bool( wp_unslash( $_POST['wpb_cart_page'] ?? '' ) ) ? true : false;

		if ( ! $wpb_is_cart_page ) {
			return $content;
		}

		if ( 'gqb_cart_content' === $tag_name ) {
			$cart_content = WC()->cart->is_empty()
				? esc_html__( 'Cart is empty.', 'get-a-quote-button' )
				: $this->build_cart_content();

			$content = str_replace( '{gqb_cart_content}', $cart_content, $content );
		}

		return $content;
	}

	/**
	 * Build the Cart Content block. Shared by the {gqb_cart_content} smart
	 * tag and the automatic quote-data delivery feature.
	 *
	 * @return string
	 */
	private function build_cart_content() {
		// WPForms notification emails render as plain text, so the currency
		// symbol's HTML entities (e.g. &pound;) must be decoded first.
		return wpb_gqb_build_cart_content( true );
	}

	/**
	 * Process the multi-product Quote List smart tag.
	 */
	public function process_quote_list_tags( $content, $tag_name, $form_data, $fields, $entry_id, $smart_tag_object ) {
		if ( 'gqb_quote_list_content' !== $tag_name ) {
			return $content;
		}

		$wpb_is_quote_list = isset( $_POST['wpb_quote_list'] ) ? wc_string_to_bool( wp_unslash( $_POST['wpb_quote_list'] ) ) : false;

		if ( ! $wpb_is_quote_list || ! class_exists( 'WPB_GQB_Quote_List' ) ) {
			return str_replace( '{gqb_quote_list_content}', '', $content );
		}

		$quote_content = $this->build_quote_list_content();

		if ( '' === $quote_content ) {
			$quote_content = esc_html__( 'Quote list is empty.', 'get-a-quote-button' );
		}

		return str_replace( '{gqb_quote_list_content}', $quote_content, $content );
	}

	/**
	 * Build the multi-product Quote List content block. Shared by the
	 * {gqb_quote_list_content} smart tag and the automatic quote-data
	 * delivery feature. Returns an empty string when the quote list is
	 * empty or unavailable.
	 *
	 * @return string
	 */
	private function build_quote_list_content() {
		return wpb_gqb_build_quote_list_content();
	}

	public function process_wcpa_product_fields(
		$content,
		$tag_name,
		$form_data,
		$fields,
		$entry_id,
		$smart_tag_object
	) {

		if ( 'gqb_wcpa_product_fields' !== $tag_name ) {
			return $content;
		}

		$replacement = '';

		if ( ! empty( $_POST['wpb_gqb_wcpa'] ) ) {

			$fields = json_decode(
				wp_unslash( $_POST['wpb_gqb_wcpa'] ),
				true
			);

			if ( is_array( $fields ) && ! empty( $fields ) ) {

				$formatted = array();

				foreach ( $fields as $key => $value ) {

					// Validate key & value types
					if ( ! is_string( $key ) || ( ! is_string( $value ) && ! is_numeric( $value ) ) ) {
						continue;
					}

					$clean_key   = sanitize_text_field( $key );
					$clean_value = sanitize_text_field( (string) $value );

					if ( $clean_key === '' || $clean_value === '' ) {
						continue;
					}

					$formatted[] =
						esc_html( ucfirst( str_replace( '_', ' ', $clean_key ) ) ) .
						' : ' .
						esc_html( $clean_value );
				}

				if ( ! empty( $formatted ) ) {
					$replacement = implode( $this->separator, $formatted );
				}
			}
		}

		return str_replace(
			'{gqb_wcpa_product_fields}',
			$replacement,
			$content
		);
	}

	/**
	 * Process the WC Products Smart Tags.
	 */
	public function process_product_tags( $content, $tag_name, $form_data, $fields, $entry_id, $smart_tag_object ) {
		$product_id = isset( $_POST['wpb_post_id'] ) ? absint( wp_unslash( $_POST['wpb_post_id'] ) ) : '';
		$product    = wc_get_product( $product_id );

		if ( ! $product ) {
			return $content;
		}

		if ( 'gqb_product_id' === $tag_name ) {
			$content = str_replace( '{gqb_product_id}', $product_id, $content );
		}

		if ( 'gqb_product_title' === $tag_name ) {
			$content = str_replace( '{gqb_product_title}', esc_html( $product->get_name() ), $content );
		}

		if ( 'gqb_product_url' === $tag_name ) {
			$content = str_replace( '{gqb_product_url}', esc_url( $product->get_permalink() ), $content );
		}

		if ( 'gqb_product_description' === $tag_name ) {
			$content = str_replace( '{gqb_product_description}', wp_kses_post( $product->get_description() ), $content );
		}

		if ( 'gqb_product_short_description' === $tag_name ) {
			$content = str_replace( '{gqb_product_short_description}', wp_kses_post( $product->get_short_description() ), $content );
		}

		if ( 'gqb_product_sku' === $tag_name ) {
			$content = str_replace( '{gqb_product_sku}', esc_html( $product->get_sku() ), $content );
		}

		if ( 'gqb_product_price' === $tag_name ) {
			if ( $product->is_type( 'variable' ) ) {
				if ( isset( $_POST['wpb_gqb_variation_price'] ) ) {
					$variation_price = json_decode( sanitize_text_field( wp_unslash( $_POST['wpb_gqb_variation_price'] ) ), true );
					$content         = str_replace( '{gqb_product_price}', esc_html( $variation_price ), $content );
				}
			} else {
				$content = str_replace( '{gqb_product_price}', esc_html( $product->get_price() ), $content );
			}
		}

		if ( 'gqb_product_total_price' === $tag_name ) {
			$qty   = isset( $_POST['wpb_gqb_qty'] ) ? absint( wp_unslash( $_POST['wpb_gqb_qty'] ) ) : 0;
			$price = 0;

			if ( $product->is_type( 'variable' ) ) {
				if ( isset( $_POST['wpb_gqb_variation_price'] ) ) {
					$variation_price = json_decode( sanitize_text_field( wp_unslash( $_POST['wpb_gqb_variation_price'] ) ), true );
					$price           = $variation_price;
				}
			} else {
				$price = $product->get_price();
			}

			$total_price = $qty * $price;
			$content     = str_replace( '{gqb_product_total_price}', esc_html( $total_price ), $content );
		}

		if ( 'gqb_product_status' === $tag_name ) {
			$status  = $product->get_status();
			$content = str_replace( '{gqb_product_status}', esc_html( $status ), $content );
		}

		if ( 'gqb_product_type' === $tag_name ) {
			$type    = $product->get_type();
			$content = str_replace( '{gqb_product_type}', esc_html( $type ), $content );
		}

		if ( 'gqb_product_stock_status' === $tag_name ) {
			$stock_status = $product->get_stock_status();
			$content      = str_replace( '{gqb_product_stock_status}', esc_html( $stock_status ), $content );
		}

		if ( 'gqb_product_stock_quantity' === $tag_name ) {
			$stock_quantity = $product->get_stock_quantity();
			$content        = str_replace( '{gqb_product_stock_quantity}', esc_html( $stock_quantity ), $content );
		}

		if ( 'gqb_product_variations' === $tag_name ) {
			$variations     = json_decode( sanitize_text_field( wp_unslash( $_POST['wpb_gqb_variations'] ) ), true );
			$variation_list = '';

			if ( isset( $variations ) && ! empty( $variations ) ) {
				foreach ( $variations as $key => $variation ) {
					$variation_list .= ucfirst( $key ) . ' : ' . $variation . $this->separator;
				}
			}

			$content = str_replace( '{gqb_product_variations}', $variation_list, $content );
		}

		if ( 'gqb_product_variations_value_only' === $tag_name ) {
			$variations     = json_decode( sanitize_text_field( wp_unslash( $_POST['wpb_gqb_variations'] ) ), true );
			$variation_list = '';

			if ( isset( $variations ) && ! empty( $variations ) ) {
				foreach ( $variations as $key => $variation ) {
					$variation_list .= esc_html( $variation ) . '&#32;';
				}
			}

			$content = str_replace( '{gqb_product_variations_value_only}', $variation_list, $content );
		}

		if ( 'gqb_product_variation_sku' === $tag_name ) {
			$variation_sku = isset( $_POST['wpb_gqb_variation_sku'] ) ? json_decode( sanitize_text_field( wp_unslash( $_POST['wpb_gqb_variation_sku'] ) ), true ) : '';
			$content       = str_replace( '{gqb_product_variation_sku}', esc_html( $variation_sku ), $content );
		}

		if ( 'gqb_product_qty' === $tag_name ) {
			$qty     = isset( $_POST['wpb_gqb_qty'] ) ? absint( wp_unslash( $_POST['wpb_gqb_qty'] ) ) : 0;
			$content = str_replace( '{gqb_product_qty}', esc_html( $qty ), $content );
		}

		if ( 'gqb_product_image_url' === $tag_name ) {
			$image_url = wp_get_attachment_url( $product->get_image_id() );
			$content   = str_replace( '{gqb_product_image_url}', esc_url( $image_url ), $content );
		}

		return $content;
	}

	/**
	 * Build a full, human-readable summary of the current single-product quote
	 * request (title, SKU, variations, custom fields, price, qty, total).
	 * Reads the same $_POST fields process_product_tags() uses.
	 *
	 * @param WC_Product $product The WooCommerce product.
	 * @return string
	 */
	private function get_full_product_data_summary( $product ) {
		return wpb_gqb_build_single_product_summary( $product );
	}

	/**
	 * Build the appropriate full quote-data block (single product, cart page,
	 * or multi-product quote list) for the request that opened the popup.
	 *
	 * @return string
	 */
	private function get_auto_quote_data_block() {
		if ( ! isset( $_POST['wpb_quote_list'] ) ) {
			return '';
		}

		$wpb_is_quote_list = wc_string_to_bool( wp_unslash( $_POST['wpb_quote_list'] ) );
		$wpb_is_cart_page  = isset( $_POST['wpb_cart_page'] ) && wc_string_to_bool( wp_unslash( $_POST['wpb_cart_page'] ) );

		if ( $wpb_is_quote_list ) {
			return $this->build_quote_list_content();
		}

		if ( $wpb_is_cart_page ) {
			return WC()->cart->is_empty() ? '' : $this->build_cart_content();
		}

		$product_id = isset( $_POST['wpb_post_id'] ) ? absint( wp_unslash( $_POST['wpb_post_id'] ) ) : 0;

		if ( empty( $product_id ) ) {
			return '';
		}

		return $this->get_full_product_data_summary( wc_get_product( $product_id ) );
	}

	/**
	 * Bake the full quote-data block into a hidden field at popup-render time,
	 * so it rides along with the form's own fields on actual submission -
	 * without the site owner needing to add any smart tag to the notification
	 * template. Hooked to `wpforms_frontend_output`, which fires inside the
	 * form's own <form> tags.
	 *
	 * @return void
	 */
	public function add_auto_product_data_hidden_field() {
		$setting = wpb_gqb_get_option( 'wpb_gqb_auto_send_product_data', 'woo_settings', 'off' );

		if ( 'on' !== $setting ) {
			return;
		}

		// Only true while rendering the form inside our own "fire_contact_form" AJAX request.
		if ( ! isset( $_POST['wpb_quote_list'] ) ) {
			return;
		}

		$block = $this->get_auto_quote_data_block();

		if ( $block ) {
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- transports the plain-text quote summary through a hidden form field, not code obfuscation.
			echo '<input type="hidden" name="_wpb_gqb_auto_product_data" value="' . esc_attr( base64_encode( $block ) ) . '">';
		}
	}

	/**
	 * Append the baked quote-data block (submitted back via the hidden field
	 * above) to the outgoing notification email.
	 *
	 * @param string $message  The email message to be sent out.
	 * @param string $template The email template name.
	 * @param object $notifications The Notifications instance.
	 * @return string
	 */
	public function append_auto_product_data_to_mail( $message, $template = '', $notifications = null ) {
		if ( empty( $_POST['_wpb_gqb_auto_product_data'] ) || empty( $message ) ) {
			return $message;
		}

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- decodes the plain-text quote summary baked into the hidden field above, not code obfuscation.
		$block = base64_decode( wp_unslash( $_POST['_wpb_gqb_auto_product_data'] ), true );

		if ( ! $block ) {
			return $message;
		}

		$heading = wpb_gqb_get_option( 'wpb_gqb_quote_data_heading', 'quote_settings', esc_html__( 'Quote Details', 'get-a-quote-button' ) );
		$block   = str_replace( "\n", "\r\n", trim( $block ) );

		return rtrim( $message ) . "\r\n\r\n" . $heading . ":\r\n" . $block;
	}

	// phpcs:enable WordPress.Security.NonceVerification.Missing
}
new WPB_GQB_WPForms_Custom_Smart_Tags();
