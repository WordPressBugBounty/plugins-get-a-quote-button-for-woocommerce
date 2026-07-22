<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Showing the Generated Quote Buttons Class
 */
class WPB_GQB_Quote_Buttons {

	/**
	 * Database table name for the shortcodes
	 *
	 * @var string
	 */
	private static $table;

	/**
	 * Query cache key
	 *
	 * @var string
	 */
	private static $cache_key = 'wpb_gqb_frontend_quote_buttons';

	/**
	 * Query Cache Group
	 *
	 * @var string
	 */
	private static $cache_group = 'wpb_gqb_frontend';

	/**
	 * Class init
	 */
	public static function init() {
		global $wpdb;
		self::$table = $wpdb->prefix . 'wpb_quote_button_shortcodes';

		add_action(
			apply_filters( 'wpb_gqb_show_the_buttons_action_hook', 'wp' ),
			array( __CLASS__, 'show_quote_buttons' ),
			99
		);
	}

	/**
	 * Fetch all quote buttons
	 *
	 * @return array
	 */
	public static function get_quote_buttons() {
		global $wpdb;

		// Check table existence only once per request
		static $table_exists = null;

		if ( $table_exists === null ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', self::$table ) ) === self::$table;
		}

		if ( ! $table_exists ) {
			return null;
		}

		$buttons = wp_cache_get( self::$cache_key, self::$cache_group );

		if ( false === $buttons ) {
			$table_name = esc_sql( self::$table );

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$query = $wpdb->prepare( "SELECT * FROM `$table_name` WHERE 1 = %d", 1 );

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
			$buttons = $wpdb->get_results( $query );

			// Cache the result for faster access next time.
			wp_cache_set( self::$cache_key, $buttons, self::$cache_group, HOUR_IN_SECONDS );
		}

		return $buttons;
	}

	/**
	 * Get a single quote button by ID (frontend).
	 *
	 * @param int $id button ID.
	 * @return object|null button object or null if not found.
	 */
	public static function get_quote_button( $id = 0 ) {
		global $wpdb;

		if ( ! $id ) {
			return null;
		}

		// Check table existence only once per request
		static $table_exists = null;

		if ( $table_exists === null ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', self::$table ) ) === self::$table;
		}

		if ( ! $table_exists ) {
			return null;
		}

		$cache_key = 'wpb_gqb_frontend_quote_button_' . absint( $id );

		// Try to get cached data first.
		$shortcode = wp_cache_get( $cache_key, self::$cache_group );
		if ( false !== $shortcode ) {
			return $shortcode;
		}

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
		$query = $wpdb->prepare( 'SELECT * FROM ' . self::$table . ' WHERE id = %d', $id );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
		$shortcode = $wpdb->get_row( $query );

		// Cache the result (null is also valid to cache).
		wp_cache_set( $cache_key, $shortcode, self::$cache_group, HOUR_IN_SECONDS );

		return $shortcode;
	}

	/**
	 * Quote Button ShortCode
	 *
	 * @param int    $button_id The quote button ID
	 * @param string $classes Additional button css classes
	 * @return void
	 */
	public static function get_quote_shortcode( $button_id, $classes = '' ) {
		return do_shortcode( '[wpb-quote-button sid="' . esc_attr( $button_id ) . '" class="' . esc_attr( $classes ) . '"]' );
	}

	/**
	 * Showing the Quote Buttons
	 *
	 * @return void
	 */
	public static function show_quote_buttons() {
		$buttons             = self::get_quote_buttons();
		$related_show        = wpb_gqb_get_option( 'woo_related_upsell_cross_loop_show_quote_form', 'woo_settings' );
		$loop_location_hooks = array(
			'woocommerce_before_shop_loop_item',
			'woocommerce_before_shop_loop_item_title',
			'woocommerce_shop_loop_item_title',
			'woocommerce_after_shop_loop_item_title',
			'woocommerce_after_shop_loop_item',
		);

		if ( $buttons && ! empty( $buttons ) ) {
			foreach ( $buttons as $button ) {

				if ( isset( $button->location ) && $button->location != '' && 'shortcode_use' !== $button->show_btn ) {
					add_action(
						$button->location,
						function () use ( $button, $loop_location_hooks, $related_show ) {
							// Remove all the buttons if checked the disable button meta (_wpb_gqb_disable)
							$wpb_gqb_disable = get_post_meta( get_the_ID(), '_wpb_gqb_disable', true );
							if ( isset( $wpb_gqb_disable ) && $wpb_gqb_disable == 'yes' ) {
								return false;
							}

							if ( in_array( $button->location, $loop_location_hooks ) && 'on' !== $related_show && is_singular( 'product' ) ) {
								return false;
							}

							if ( 'cart_page' === $button->show_btn ) {
								// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_quote_shortcode() returns do_shortcode() output, already-escaped HTML from our own shortcode handler.
								echo self::get_quote_shortcode( $button->id, 'wpb-gqb-cart-page-button' );
							} else {
								// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_quote_shortcode() returns do_shortcode() output, already-escaped HTML from our own shortcode handler.
								echo self::get_quote_shortcode( $button->id );
							}
						},
						( $button->priority ? $button->priority : '' )
					);
				}
			}
		}
	}
}

WPB_GQB_Quote_Buttons::init();
