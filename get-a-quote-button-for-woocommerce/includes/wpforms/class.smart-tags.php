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
	 * Field ID used for the synthetic "Quote Details" field baked into a
	 * WPForms entry by add_quote_data_to_entry_fields() below. Deliberately
	 * far outside the range of a form's own real field IDs (which WPForms
	 * enumerates starting at 1), so it can never collide with one.
	 */
	const QUOTE_DATA_ENTRY_FIELD_ID = 999901;

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

		// Save the same quote data alongside the entry, wherever WPForms
		// (Pro) stores entries.
		add_filter( 'wpforms_process_filter', array( $this, 'add_quote_data_to_entry_fields' ), 10, 3 );
		add_filter( 'wpforms_emails_notifications_field_ignored', array( $this, 'exclude_quote_data_field_from_email' ), 10, 2 );
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

			$content = str_replace( '{gqb_cart_content}', $this->normalize_block_line_breaks( $cart_content ), $content );
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

		return str_replace( '{gqb_quote_list_content}', $this->normalize_block_line_breaks( $quote_content ), $content );
	}

	/**
	 * Normalize a data block's line breaks to CRLF before it is substituted
	 * into a notification. WPForms renders every template except "Plain text"
	 * as HTML and turns CRLF - and only CRLF - into <br/> when it does (see
	 * WPForms\Emails\Notifications::process_message()), so a block using bare
	 * LF would arrive there as one run-on line.
	 *
	 * @param string $block The built data block.
	 * @return string
	 */
	private function normalize_block_line_breaks( $block ) {
		return str_replace( array( "\r\n", "\n" ), "\r\n", (string) $block );
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
		$send_with_email = 'on' === wpb_gqb_get_option( 'wpb_gqb_auto_send_product_data', 'woo_settings', 'off' );
		$save_to_entry   = 'on' === wpb_gqb_get_option( 'wpb_gqb_save_quote_data_to_entry', 'woo_settings', 'off' );

		// The hidden field is the single carrier for both destinations
		// (email body and, further down, the saved entry) - bake it in as
		// long as either one wants it; append_auto_product_data_to_mail()
		// and add_quote_data_to_entry_fields() each re-check their own
		// setting independently before actually using it.
		if ( ! $send_with_email && ! $save_to_entry ) {
			wpb_gqb_quote_debug_log( '60_wpf_hidden_field:setting_off' );

			return;
		}

		// Only true while rendering the form inside our own "fire_contact_form" AJAX request.
		if ( ! isset( $_POST['wpb_quote_list'] ) ) {
			wpb_gqb_quote_debug_log( '60_wpf_hidden_field:not_in_quote_request' );

			return;
		}

		$block = $this->get_auto_quote_data_block();

		wpb_gqb_quote_debug_log( '61_wpf_hidden_field:block_built', array( 'block_length' => strlen( (string) $block ) ) );

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
		$send_with_email = 'on' === wpb_gqb_get_option( 'wpb_gqb_auto_send_product_data', 'woo_settings', 'off' );

		wpb_gqb_quote_debug_log(
			'70_wpf_mail_append:entry',
			array(
				'send_with_email_setting' => $send_with_email,
				'hidden_field_present'    => ! empty( $_POST['_wpb_gqb_auto_product_data'] ),
				'message_present'         => ! empty( $message ),
				'template'                => $template,
			)
		);

		// The hidden field can also be present purely because "Save Quote
		// Data To Entry" is on (see add_auto_product_data_hidden_field()) -
		// don't put it in the email unless the email setting itself is on.
		if ( ! $send_with_email || empty( $_POST['_wpb_gqb_auto_product_data'] ) || empty( $message ) ) {
			return $message;
		}

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- decodes the plain-text quote summary baked into the hidden field above, not code obfuscation.
		$block = base64_decode( wp_unslash( $_POST['_wpb_gqb_auto_product_data'] ), true );

		wpb_gqb_quote_debug_log( '71_wpf_mail_append:decoded', array( 'block_length' => strlen( (string) $block ) ) );

		if ( ! $block ) {
			return $message;
		}

		$heading = wpb_gqb_get_option( 'wpb_gqb_quote_data_heading', 'quote_settings', esc_html__( 'Quote Details', 'get-a-quote-button' ) );

		// Every WPForms email template except "Plain text" is sent as
		// text/html, so the block's line breaks have to become <br> there (on
		// top of keeping whatever formatting the quote-data templates carry) -
		// otherwise it arrives as one run-on line.
		if ( $this->is_html_email_template( $template ) ) {
			$message = rtrim( $message ) . "\r\n" . '<p><strong>' . wpb_gqb_template_to_html( $heading ) . '</strong><br>' . wpb_gqb_template_to_html( $block ) . '</p>';
		} else {
			$block   = str_replace( "\n", "\r\n", wpb_gqb_template_to_plain_text( $block ) );
			$message = rtrim( $message ) . "\r\n\r\n" . wpb_gqb_template_to_plain_text( $heading ) . ":\r\n" . $block;
		}

		wpb_gqb_quote_debug_log( '72_wpf_mail_append:message_updated', array( 'new_message_length' => strlen( $message ) ) );

		return $message;
	}

	/**
	 * Whether the notification currently being built is sent as HTML. WPForms
	 * sends every template except "Plain text" (`none`) as text/html - see
	 * WPForms\Emails\Notifications::get_content_type(). Falls back to
	 * assuming plain text when that helper isn't available, so an older
	 * WPForms keeps today's behaviour rather than getting raw markup.
	 *
	 * @param string $template The email template name passed by WPForms.
	 * @return bool
	 */
	private function is_html_email_template( $template ) {
		if ( ! class_exists( '\WPForms\Emails\Helpers' ) ) {
			return false;
		}

		return ! \WPForms\Emails\Helpers::is_plain_text_template( $template );
	}

	/**
	 * Add the same baked quote-data block (submitted via the hidden field
	 * above) as a synthetic entry field, so it's saved and displayed
	 * alongside the submission wherever WPForms stores entries (WPForms
	 * Pro's Entries screen). Hooked to `wpforms_process_filter`, which runs
	 * right before WPForms saves the entry - whatever is present in the
	 * returned $fields array from here on is treated exactly like a real
	 * form field for storage/display purposes.
	 *
	 * @param array $fields    Processed form fields, keyed by field ID.
	 * @param array $entry     Raw submitted entry data ($_POST['wpforms']).
	 * @param array $form_data Form data/settings.
	 * @return array
	 */
	public function add_quote_data_to_entry_fields( $fields, $entry, $form_data ) {
		$setting = wpb_gqb_get_option( 'wpb_gqb_save_quote_data_to_entry', 'woo_settings', 'off' );

		if ( 'on' !== $setting || empty( $_POST['_wpb_gqb_auto_product_data'] ) ) {
			return $fields;
		}

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- decodes the plain-text quote summary baked into the hidden field by add_auto_product_data_hidden_field(), not code obfuscation.
		$block = base64_decode( wp_unslash( $_POST['_wpb_gqb_auto_product_data'] ), true );

		wpb_gqb_quote_debug_log( '80_wpf_entry_field:decoded', array( 'block_length' => strlen( (string) $block ) ) );

		if ( ! $block ) {
			return $fields;
		}

		$heading = wpb_gqb_get_option( 'wpb_gqb_quote_data_heading', 'quote_settings', esc_html__( 'Quote Details', 'get-a-quote-button' ) );

		$fields[ self::QUOTE_DATA_ENTRY_FIELD_ID ] = array(
			// Entries are stored and displayed as plain text, so any
			// formatting the quote-data templates carry is flattened here
			// rather than saved as raw markup.
			'name'  => wpb_gqb_template_to_plain_text( $heading ),
			'value' => wpb_gqb_template_to_plain_text( $block ),
			'id'    => self::QUOTE_DATA_ENTRY_FIELD_ID,
			'type'  => 'text',
		);

		wpb_gqb_quote_debug_log( '81_wpf_entry_field:added' );

		return $fields;
	}

	/**
	 * Keep the synthetic "Quote Details" entry field (added above) out of
	 * the {all_fields} block in notification emails - when "Automatically
	 * Send Quote Data With The Email" is also on, append_auto_product_data_to_mail()
	 * already appends the same block to the email body directly, so showing
	 * it again via {all_fields} would just duplicate it.
	 *
	 * @param bool  $ignored Whether the field should be excluded.
	 * @param array $field   The field being rendered into the email.
	 * @return bool
	 */
	public function exclude_quote_data_field_from_email( $ignored, $field ) {
		if ( isset( $field['id'] ) && (int) $field['id'] === self::QUOTE_DATA_ENTRY_FIELD_ID ) {
			return true;
		}

		return $ignored;
	}

	// phpcs:enable WordPress.Security.NonceVerification.Missing
}
new WPB_GQB_WPForms_Custom_Smart_Tags();
