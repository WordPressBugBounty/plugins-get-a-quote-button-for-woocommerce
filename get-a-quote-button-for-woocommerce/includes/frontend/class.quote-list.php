<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Multi-Product Quote List
 *
 * Session-backed storage for the multi-product quote system, mirroring how
 * WC_Cart stores its line items in WC()->session, but kept entirely separate
 * from the real WooCommerce cart.
 */
class WPB_GQB_Quote_List {

	/**
	 * WC session key used to store the quote items.
	 *
	 * @var string
	 */
	const SESSION_KEY = 'wpb_gqb_quote_items';

	/**
	 * Whether a WooCommerce session is available to read/write.
	 *
	 * @return bool
	 */
	protected static function session_ready() {
		return function_exists( 'WC' ) && WC()->session;
	}

	/**
	 * Get all quote items.
	 *
	 * @return array
	 */
	public static function get_items() {
		if ( ! self::session_ready() ) {
			return array();
		}

		$items = WC()->session->get( self::SESSION_KEY, array() );

		return is_array( $items ) ? $items : array();
	}

	/**
	 * Get a single quote item by its key.
	 *
	 * @param string $key Item key.
	 * @return array|null
	 */
	public static function get_item( $key ) {
		$items = self::get_items();

		return isset( $items[ $key ] ) ? $items[ $key ] : null;
	}

	/**
	 * Total quantity across all quote items.
	 *
	 * @return int
	 */
	public static function get_items_count() {
		$count = 0;

		foreach ( self::get_items() as $item ) {
			$count += isset( $item['quantity'] ) ? absint( $item['quantity'] ) : 0;
		}

		return $count;
	}

	/**
	 * Generate a stable key for a quote item so identical adds (same product,
	 * variation and options) increment quantity instead of creating duplicate rows.
	 *
	 * @param int   $product_id
	 * @param int   $variation_id
	 * @param array $variation
	 * @param array $wapf_fields
	 * @param array $wcpa_fields
	 * @return string
	 */
	public static function generate_item_key( $product_id, $variation_id, $variation, $wapf_fields, $wcpa_fields ) {
		return md5( wp_json_encode( array( $product_id, $variation_id, $variation, $wapf_fields, $wcpa_fields ) ) );
	}

	/**
	 * Add a product to the quote list (or increase its quantity if it's already there).
	 *
	 * @param array $args {
	 *     @type int   $product_id
	 *     @type int   $variation_id
	 *     @type array $variation    Selected variation attributes.
	 *     @type int   $quantity
	 *     @type array $wapf_fields  Advanced Product Fields custom data.
	 *     @type array $wcpa_fields  Product Addons custom data.
	 *     @type float $price        Optional resolved unit price snapshot (e.g. from a selected variation).
	 * }
	 * @return array|WP_Error The saved item on success.
	 */
	public static function add_item( $args ) {
		if ( ! self::session_ready() ) {
			return new WP_Error( 'wpb_gqb_no_session', esc_html__( 'Unable to start a quote session.', 'get-a-quote-button' ) );
		}

		$product_id   = absint( $args['product_id'] ?? 0 );
		$variation_id = absint( $args['variation_id'] ?? 0 );
		$variation    = is_array( $args['variation'] ?? null ) ? $args['variation'] : array();
		$quantity     = max( 1, absint( $args['quantity'] ?? 1 ) );
		$wapf_fields  = is_array( $args['wapf_fields'] ?? null ) ? $args['wapf_fields'] : array();
		$wcpa_fields  = is_array( $args['wcpa_fields'] ?? null ) ? $args['wcpa_fields'] : array();
		$custom_price = isset( $args['price'] ) && is_numeric( $args['price'] ) ? floatval( $args['price'] ) : null;

		$product = wc_get_product( $variation_id ? $variation_id : $product_id );

		if ( ! $product_id || ! $product ) {
			return new WP_Error( 'wpb_gqb_invalid_product', esc_html__( 'Invalid product.', 'get-a-quote-button' ) );
		}

		$items = self::get_items();
		$key   = self::generate_item_key( $product_id, $variation_id, $variation, $wapf_fields, $wcpa_fields );

		if ( isset( $items[ $key ] ) ) {
			$items[ $key ]['quantity'] = absint( $items[ $key ]['quantity'] ) + $quantity;
		} else {
			$items[ $key ] = array(
				'key'          => $key,
				'product_id'   => $product_id,
				'variation_id' => $variation_id,
				'variation'    => $variation,
				'quantity'     => $quantity,
				'wapf_fields'  => $wapf_fields,
				'wcpa_fields'  => $wcpa_fields,
				'price'        => $custom_price,
				'added_at'     => time(),
			);
		}

		$items[ $key ]['quantity'] = apply_filters( 'wpb_gqb_quote_item_quantity', $items[ $key ]['quantity'], $key, $items[ $key ] );

		self::save_items( $items );

		return $items[ $key ];
	}

	/**
	 * Update the quantity of a quote item. A quantity of 0 removes it.
	 *
	 * @param string $key
	 * @param int    $quantity
	 * @return bool
	 */
	public static function update_item_qty( $key, $quantity ) {
		$items = self::get_items();

		if ( ! isset( $items[ $key ] ) ) {
			return false;
		}

		$quantity = absint( $quantity );

		if ( $quantity <= 0 ) {
			return self::remove_item( $key );
		}

		$items[ $key ]['quantity'] = $quantity;
		self::save_items( $items );

		return true;
	}

	/**
	 * Remove a quote item.
	 *
	 * @param string $key
	 * @return bool
	 */
	public static function remove_item( $key ) {
		$items = self::get_items();

		if ( ! isset( $items[ $key ] ) ) {
			return false;
		}

		unset( $items[ $key ] );
		self::save_items( $items );

		return true;
	}

	/**
	 * Empty the quote list.
	 *
	 * @return void
	 */
	public static function clear() {
		self::save_items( array() );
	}

	/**
	 * Persist quote items to the WC session.
	 *
	 * @param array $items
	 * @return void
	 */
	protected static function save_items( $items ) {
		if ( ! self::session_ready() ) {
			return;
		}

		WC()->session->set( self::SESSION_KEY, $items );
	}

	/**
	 * Resolve the live WC_Product for a quote item (variation if set, otherwise the parent product).
	 *
	 * @param array $item
	 * @return WC_Product|null
	 */
	public static function get_item_product( $item ) {
		$product_id = ! empty( $item['variation_id'] ) ? $item['variation_id'] : ( $item['product_id'] ?? 0 );

		return $product_id ? wc_get_product( $product_id ) : null;
	}

	/**
	 * Resolve the unit price for a quote item: a captured snapshot price (used for
	 * variable products / plugins that calculate a custom price on selection) if present,
	 * otherwise the product's current live price.
	 *
	 * @param array $item
	 * @return float
	 */
	public static function get_item_price( $item ) {
		if ( isset( $item['price'] ) && is_numeric( $item['price'] ) ) {
			return floatval( $item['price'] );
		}

		$product = self::get_item_product( $item );

		return $product ? floatval( $product->get_price() ) : 0.0;
	}

	/**
	 * Calculate quote list totals.
	 *
	 * @return array{subtotal: float, total: float}
	 */
	public static function get_totals() {
		$subtotal = 0.0;

		foreach ( self::get_items() as $item ) {
			$qty       = isset( $item['quantity'] ) ? absint( $item['quantity'] ) : 0;
			$subtotal += self::get_item_price( $item ) * $qty;
		}

		return apply_filters(
			'wpb_gqb_quote_list_totals',
			array(
				'subtotal' => $subtotal,
				'total'    => $subtotal,
			),
			self::get_items()
		);
	}
}
