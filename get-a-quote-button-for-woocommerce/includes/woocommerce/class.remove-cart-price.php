<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WooCommerce Remove Product Cart & Price Class
 */
class WPB_GQB_WC_Remove_Cart_Price {

	/**
	 * Quote Button ShortCodes
	 *
	 * @var array
	 */
	private $quote_buttons;

	/**
	 * Hide cart type
	 *
	 * @var string
	 */
	private $hide_cart_type;

	/**
	 * Hide price type
	 *
	 * @var string
	 */
	private $hide_price_type;

	/**
	 * Constructor
	 */
	function __construct() {
		$this->quote_buttons   = WPB_GQB_Quote_Buttons::get_quote_buttons();
		$this->hide_cart_type  = wpb_gqb_get_option( 'wpb_gqb_hide_cart_type', 'hide_cart_settings', 'programmatically' );
		$this->hide_price_type = wpb_gqb_get_option( 'wpb_gqb_hide_price_type', 'hide_price_settings', 'programmatically' );

		add_filter( 'body_class', array( $this, 'add_body_classes' ) );

		// Hide the Cart Button
		add_filter( 'wpb_gqb_show_cart_btn', array( $this, 'remove_woocommerce_cart_button' ), 10, 2 );

		if ( $this->hide_cart_type === 'programmatically' ) {
			add_filter( 'woocommerce_is_purchasable', array( $this, 'remove_woocommerce_cart_button' ), 10000, 2 );
			// Override WooCommerce Templates for Hiding the cart
			add_filter( 'woocommerce_locate_template', array( $this, 'woocommerce_locate_template' ), 10, 3 );
			// Marking the product as not purchasable makes WooCommerce fall back to a "Read more" link
			// in product loops (shop/archive/category pages) - remove that fallback too.
			add_filter( 'woocommerce_loop_add_to_cart_link', array( $this, 'remove_woocommerce_loop_add_to_cart_link' ), 10, 2 );
		}

		// Hide the price
		add_filter( 'wpb_gqb_show_price', array( $this, 'remove_woocommerce_price' ), 10, 2 );
		if ( $this->hide_price_type === 'programmatically' ) {
			add_filter( 'woocommerce_get_price_html', array( $this, 'remove_woocommerce_price' ), 100, 2 );
		}

		// "Selected Variations" shortcodes can only know which variation to hide
		// cart/price for once the shopper picks it (AJAX, no page reload), so that
		// case is handled client-side - output the matching variation ids for JS to use.
		add_action( 'woocommerce_single_product_summary', array( $this, 'output_variation_hide_data' ), 4 );
	}

	/**
	 * Array flat
	 *
	 * @param array $array The array to flat
	 * @return array
	 */
	public function array_flatten( $array ) {
		if ( ! is_array( $array ) ) {
			return false;
		}
		$result = array();

		foreach ( $array as $key => $value ) {
			if ( is_array( $value ) ) {
				$result = array_merge( $result, $this->array_flatten( $value ) );
			} else {
				$result[ $key ] = $value;
			}
		}
		return $result;
	}

	/**
	 * Adding body classes for hiding the cart and price using CSS
	 *
	 * @param array $classes The body classes
	 * @return void
	 */
	public function add_body_classes( $classes ) {
		if ( ! is_cart() ) {
			if ( function_exists( 'is_wpb_gqb_show_cart_btn' ) && ! is_wpb_gqb_show_cart_btn() ) {
				$classes[] = 'wpb-gqb-hide-the-cart';
				$classes[] = 'wpb-gqb-hide-the-cart-type-' . esc_attr( $this->hide_cart_type );
			}

			if ( function_exists( 'is_wpb_gqb_show_price' ) && ! is_wpb_gqb_show_price() ) {
				$classes[] = 'wpb-gqb-hide-the-price';
				$classes[] = 'wpb-gqb-hide-the-price-type-' . esc_attr( $this->hide_price_type );
			}
		}

		return $classes;
	}

	/**
	 * Shared logic for processing buttons and determining if a product should be hidden
	 *
	 * @param int $post_id Product ID
	 * @return bool True if the product should be hidden, false otherwise
	 */
	private function should_hide_product( $post_id ) {
		$buttons              = $this->quote_buttons;
		$products             = $product_cats = $product_tags = array();
		$product_stock_status = '';

		if ( $buttons && ! empty( $buttons ) ) {
			foreach ( $buttons as $button ) {
				if ( isset( $button->shortcode_status ) && $button->shortcode_status !== 'active' ) {
					continue;
				}

				if ( isset( $button->location ) && $button->location !== '' && $button->show_btn === 'selected_products' ) {
					if ( isset( $button->products ) && $button->products !== '' ) {
						$products[] = maybe_unserialize( $button->products );
					}

					if ( isset( $button->product_cats ) && $button->product_cats !== '' ) {
						$product_cats[] = maybe_unserialize( $button->product_cats );
					}

					if ( isset( $button->product_tags ) && $button->product_tags !== '' ) {
						$product_tags[] = maybe_unserialize( $button->product_tags );
					}

					if ( isset( $button->stock_status ) && $button->stock_status !== '' ) {
						$product_stock_status = $button->stock_status;
					}
				}
			}

			$products     = $this->array_flatten( $products );
			$product_cats = $this->array_flatten( $product_cats );
			$product_tags = $this->array_flatten( $product_tags );

			if ( ! empty( $products ) && in_array( $post_id, $products ) ) {
				return true;
			}

			if ( ! empty( $product_cats ) && has_term( $product_cats, 'product_cat', $post_id ) ) {
				if ( $product_stock_status !== '' ) {
					if ( get_post_meta( $post_id, '_stock_status', true ) === $product_stock_status ) {
						return true;
					}
				} else {
					return true;
				}
			}

			if ( ! empty( $product_tags ) && has_term( $product_tags, 'product_tag', $post_id ) ) {
				if ( $product_stock_status !== '' ) {
					if ( get_post_meta( $post_id, '_stock_status', true ) === $product_stock_status ) {
						return true;
					}
				} else {
					return true;
				}
			}

			if ( $product_stock_status !== '' && $product_stock_status === get_post_meta( $post_id, '_stock_status', true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if a single quote button shortcode's own product rules match a product
	 *
	 * @param object $button Quote button shortcode row.
	 * @param int    $post_id Product ID
	 * @return bool
	 */
	private function shortcode_matches_product( $button, $post_id ) {
		// "Selected Variations" hide rules only take effect once the shopper selects
		// one of the matched variations - handled client-side, see get_variation_hide_ids().
		if ( isset( $button->show_btn ) && $button->show_btn === 'selected_variations' ) {
			return false;
		}

		// Modes without product-scoping rules apply to all products
		if ( ! isset( $button->show_btn ) || $button->show_btn !== 'selected_products' ) {
			return true;
		}

		$products             = ( ! empty( $button->products ) ) ? (array) maybe_unserialize( $button->products ) : array();
		$product_cats         = ( ! empty( $button->product_cats ) ) ? (array) maybe_unserialize( $button->product_cats ) : array();
		$product_tags         = ( ! empty( $button->product_tags ) ) ? (array) maybe_unserialize( $button->product_tags ) : array();
		$product_stock_status = ( ! empty( $button->stock_status ) ) ? $button->stock_status : '';

		if ( ! empty( $products ) && in_array( $post_id, $products ) ) {
			return true;
		}

		if ( ! empty( $product_cats ) && has_term( $product_cats, 'product_cat', $post_id ) ) {
			if ( $product_stock_status === '' || get_post_meta( $post_id, '_stock_status', true ) === $product_stock_status ) {
				return true;
			}
		}

		if ( ! empty( $product_tags ) && has_term( $product_tags, 'product_tag', $post_id ) ) {
			if ( $product_stock_status === '' || get_post_meta( $post_id, '_stock_status', true ) === $product_stock_status ) {
				return true;
			}
		}

		if ( empty( $products ) && empty( $product_cats ) && empty( $product_tags ) && $product_stock_status !== '' && $product_stock_status === get_post_meta( $post_id, '_stock_status', true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if a product should be hidden because of a per-shortcode hide option
	 *
	 * @param int    $post_id Product ID
	 * @param string $field 'hide_cart' or 'hide_price'
	 * @return bool
	 */
	private function is_hidden_by_shortcode_option( $post_id, $field ) {
		$buttons = $this->quote_buttons;

		if ( ! $buttons || empty( $buttons ) ) {
			return false;
		}

		foreach ( $buttons as $button ) {
			if ( isset( $button->shortcode_status ) && $button->shortcode_status !== 'active' ) {
				continue;
			}

			if ( ! isset( $button->$field ) || $button->$field !== 'yes' ) {
				continue;
			}

			if ( $this->shortcode_matches_product( $button, $post_id ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Collect the variation ids that a "Selected Variations" shortcode's
	 * hide_cart/hide_price option applies to, for the given (parent) product.
	 *
	 * @param int    $post_id Parent product ID.
	 * @param string $field 'hide_cart' or 'hide_price'.
	 * @return int[]
	 */
	private function get_variation_hide_ids( $post_id, $field ) {
		$buttons = $this->quote_buttons;
		$ids     = array();

		if ( ! $buttons || empty( $buttons ) ) {
			return $ids;
		}

		foreach ( $buttons as $button ) {
			if ( isset( $button->shortcode_status ) && $button->shortcode_status !== 'active' ) {
				continue;
			}

			if ( ! isset( $button->show_btn ) || $button->show_btn !== 'selected_variations' ) {
				continue;
			}

			if ( ! isset( $button->$field ) || $button->$field !== 'yes' ) {
				continue;
			}

			if ( empty( $button->product_variations ) ) {
				continue;
			}

			$rules = maybe_unserialize( $button->product_variations );

			if ( ! is_array( $rules ) ) {
				continue;
			}

			foreach ( $rules as $rule ) {
				if ( isset( $rule['productId'] ) && (int) $rule['productId'] === (int) $post_id && ! empty( $rule['variationIds'] ) ) {
					$ids = array_merge( $ids, array_map( 'absint', (array) $rule['variationIds'] ) );
				}
			}
		}

		return array_unique( $ids );
	}

	/**
	 * Output the variation ids (for the current product) whose cart button/price
	 * should be hidden once selected. Read by frontend.js on the WooCommerce
	 * "found_variation" event to toggle visibility - see frontend.css for the
	 * matching .wpb-gqb-hide-variation-cart/-price rules.
	 *
	 * @return void
	 */
	public function output_variation_hide_data() {
		if ( ! is_product() ) {
			return;
		}

		global $product;

		if ( ! $product instanceof WC_Product || ! $product->is_type( 'variable' ) ) {
			return;
		}

		$post_id               = $product->get_id();
		$hide_cart_variations  = $this->get_variation_hide_ids( $post_id, 'hide_cart' );
		$hide_price_variations = $this->get_variation_hide_ids( $post_id, 'hide_price' );

		if ( empty( $hide_cart_variations ) && empty( $hide_price_variations ) ) {
			return;
		}

		printf(
			'<span class="wpb-gqb-variation-hide-data" style="display:none" data-hide-cart-variations="%1$s" data-hide-price-variations="%2$s"></span>',
			esc_attr( implode( ',', $hide_cart_variations ) ),
			esc_attr( implode( ',', $hide_price_variations ) )
		);
	}

	/**
	 * Remove WooCommerce Cart
	 *
	 * @param bool       $purchasable Whether the product is purchasable.
	 * @param WC_Product $product Product object.
	 * @return bool
	 */
	public function remove_woocommerce_cart_button( $purchasable, $product ) {
		$post_id = $product->get_id();

		// Remove cart for all the products
		if ( wpb_gqb_get_option( 'wpb_gqb_hide_cart_button_for_all', 'hide_cart_settings' ) === 'on' ) {
			return false;
		}

		// Remove cart for the featured products
		if ( wpb_gqb_get_option( 'wpb_gqb_hide_cart_button_for_featured', 'hide_cart_settings' ) === 'on' && $product->is_featured() ) {
			return false;
		}

		// Remove cart for the selected products
		if ( wpb_gqb_get_option( 'wpb_gqb_hide_cart_button_for_selected', 'hide_cart_settings' ) === 'on' && get_post_meta( $post_id, '_wpb_gqb_disable', true ) !== 'yes' ) {
			// Remove for the global quote button
			if ( is_product() && wpb_gqb_get_option( 'woo_single_show_quote_form', 'woo_settings', 'on' ) === 'on' ) {
				return false;
			}

			// Remove for the quote button shortcodes
			if ( $this->should_hide_product( $post_id ) ) {
				return false;
			}
		}

		// Remove cart based on the per-shortcode "Hide Cart Button" option
		if ( $this->is_hidden_by_shortcode_option( $post_id, 'hide_cart' ) ) {
			return false;
		}

		return $purchasable;
	}

	/**
	 * Remove WooCommerce Product Price
	 *
	 * @param string     $price Product price.
	 * @param WC_Product $product Product object.
	 * @return string|false
	 */
	public function remove_woocommerce_price( $price, $product ) {
		$post_id = $product->get_id();

		// Remove price for all the products
		if ( wpb_gqb_get_option( 'wpb_gqb_hide_price_for_all', 'hide_price_settings' ) === 'on' ) {
			return false;
		}

		// Remove price for the featured products
		if ( wpb_gqb_get_option( 'wpb_gqb_hide_price_for_featured', 'hide_price_settings' ) === 'on' && $product->is_featured() ) {
			return false;
		}

		// Remove price for the selected products
		if ( wpb_gqb_get_option( 'wpb_gqb_hide_price_for_selected', 'hide_price_settings' ) === 'on' && get_post_meta( $post_id, '_wpb_gqb_disable', true ) !== 'yes' ) {
			// Remove for the global quote button
			if ( is_product() && wpb_gqb_get_option( 'woo_single_show_quote_form', 'woo_settings', 'on' ) === 'on' ) {
				return false;
			}

			// Remove for the quote button shortcodes
			if ( $this->should_hide_product( $post_id ) ) {
				return false;
			}
		}

		// Remove price based on the per-shortcode "Hide Price" option
		if ( $this->is_hidden_by_shortcode_option( $post_id, 'hide_price' ) ) {
			return false;
		}

		return $price;
	}

	/**
	 * Remove the "Read more" fallback link WooCommerce renders in product loops
	 * (shop/archive/category pages) in place of Add to Cart when a product is
	 * marked not purchasable.
	 *
	 * @param string     $html Add to cart link HTML.
	 * @param WC_Product $product Product object.
	 * @return string
	 */
	public function remove_woocommerce_loop_add_to_cart_link( $html, $product ) {
		if ( function_exists( 'is_wpb_gqb_show_cart_btn' ) && ! is_wpb_gqb_show_cart_btn() ) {
			return '';
		}

		return $html;
	}

	/**
	 * Setup WooCommerce template overriding path for Cart Button Remove
	 *
	 * @param string $template Full file path of the template.
	 * @param string $template_name Template name.
	 * @param string $template_path Template path.
	 * @param string $template_path Default WooCommerce templates path.
	 * @return void
	 */
	public function woocommerce_locate_template( $template, $template_name, $template_path ) {

		global $woocommerce;

		$_template = $template;

		if ( ! $template_path ) {
			$template_path = $woocommerce->template_url;
		}

		$plugin_path = WPB_GQB_PLUGIN_WOO_TEMPLATE_PATH;

		// Look within passed path within the theme - this is priority
		$template = locate_template(
			array(
				$template_path . $template_name,
				$template_name,
			)
		);

		// Modification: Get the template from this plugin, if it exists
		if ( ! $template && file_exists( $plugin_path . $template_name ) ) {

			$template = $plugin_path . $template_name;
		}

		// Use default template
		if ( ! $template ) {

			$template = $_template;
		}

		// Return what we found
		return $template;
	}
}

new WPB_GQB_WC_Remove_Cart_Price();
