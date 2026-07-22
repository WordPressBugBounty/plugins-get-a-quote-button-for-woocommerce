<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API for Quote Button Shortcodes
 */
class WPB_GQB_Quote_Button_ShortCodes_Rest_API {

	/**
	 * Database table name for the shortcodes
	 *
	 * @var string
	 */
	private $table;

	/**
	 * Query cache key
	 *
	 * @var string
	 */
	private static $cache_key = 'wpb_quote_button_shortcodes';

	public function __construct() {
		global $wpdb;
		$this->table = $wpdb->prefix . 'wpb_quote_button_shortcodes';
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Clear Frontend Cache
	 *
	 * @return void
	 */
	private static function clear_frontend_cache() {
		wp_cache_delete( 'wpb_gqb_frontend_quote_buttons', 'wpb_gqb_frontend' );
	}

	/**
	 * Clear cache for a specific shortcode frontend.
	 *
	 * @param int $id Shortcode ID.
	 */
	public static function clear_frontend_shortcode_cache( $id ) {
		if ( ! $id ) {
			return;
		}

		wp_cache_delete( 'wpb_gqb_frontend_quote_button_' . absint( $id ), 'wpb_gqb_frontend' );
	}

	/**
	 * Fetch a single shortcode row by ID, uncached. Shared by every endpoint
	 * that needs to read one row (get/create/update/delete/trash/duplicate).
	 *
	 * @param int $id Shortcode ID.
	 * @return array|null
	 */
	private function get_shortcode_row_by_id( $id ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- $this->table is a hardcoded, developer-controlled table name, not user input.
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $this->table . ' WHERE id = %d', $id ), ARRAY_A );
	}

	/**
	 * Whether a shortcode with the given name already exists.
	 *
	 * @param string $name Shortcode name.
	 * @return bool
	 */
	private function shortcode_name_exists( $name ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- $this->table is a hardcoded, developer-controlled table name, not user input.
		$count = $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . $this->table . ' WHERE name = %s', $name ) );

		return (int) $count > 0;
	}

	/**
	 * Register REST API routes
	 */
	public function register_routes() {
		register_rest_route(
			'wpb-quote/v1',
			'/shortcodes',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_shortcodes' ),
					'permission_callback' => function () {
						return current_user_can( 'manage_options' );
					},
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'create_shortcode' ),
					'permission_callback' => function () {
						return current_user_can( 'manage_options' );
					},
					'args'                => array(
						'name' => array(
							'required'          => true,
							'validate_callback' => function ( $param ) {
								return ! empty( trim( $param ) );
							},
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);

		register_rest_route(
			'wpb-quote/v1',
			'/shortcodes/(?P<id>\d+)',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_shortcode' ),
					'permission_callback' => '__return_true',
				),
				array(
					'methods'             => 'PUT',
					'callback'            => array( $this, 'update_shortcode' ),
					'permission_callback' => function () {
						return current_user_can( 'manage_options' );
					},
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $this, 'delete_shortcode' ),
					'permission_callback' => function () {
						return current_user_can( 'manage_options' );
					},
				),
			)
		);

		// Route for moving a shortcode to trash by ID
		register_rest_route(
			'wpb-quote/v1',
			'/shortcodes/(?P<id>\d+)/trash',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'trash_shortcode' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);

		// Route for duplicate a shortcode by ID
		register_rest_route(
			'wpb-quote/v1',
			'/shortcodes/(?P<id>\d+)/duplicate',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'duplicate_shortcode' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);

		// Bulk actions endpoint
		register_rest_route(
			'wpb-quote/v1',
			'/bulk-update',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'bulk_update_shortcodes' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);

		// WC Products Types
		register_rest_route(
			'wpb-quote/v1',
			'/product-types',
			array(
				'methods'             => 'GET',
				'callback'            => function () {
					$types       = wc_get_product_types();
					$arrow       = is_rtl() ? '← ' : '→ ';
					$extra_types = array(
						'downloadable' => $arrow . esc_html__( 'Downloadable', 'get-a-quote-button' ),
						'virtual'      => $arrow . esc_html__( 'Virtual', 'get-a-quote-button' ),
					);

					// Insert them right after "simple"
					$new_types = array();
					foreach ( $types as $key => $label ) {
						$clean_label       = trim( str_ireplace( 'product', '', $label ) );
						$new_types[ $key ] = $clean_label;
						if ( $key === 'simple' ) {
							$new_types = array_merge( $new_types, $extra_types );
						}
					}

					return rest_ensure_response( $new_types );
				},
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);
	}

	/**
	 * Get plugin settings option
	 *
	 * @param string $option The options section
	 * @param string $section The options key
	 * @param string $default Default value
	 * @return void
	 */
	public function gqb_get_option( $option, $section, $default = '' ) {

		$options = get_option( $section );

		if ( isset( $options[ $option ] ) ) {
			return $options[ $option ];
		}

		return $default;
	}

	/**
	 * Get all shortcodes (with cache)
	 */
	public function get_shortcodes( $request ) {
		global $wpdb;

		$data = wp_cache_get( self::$cache_key, 'wpb_gqb' );

		if ( false === $data ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- $this->table is a hardcoded, developer-controlled table name, not user input.
			$data = $wpdb->get_results( 'SELECT * FROM ' . $this->table . ' ORDER BY date DESC', ARRAY_A );

			wp_cache_set( self::$cache_key, $data, 'wpb_gqb', 3600 ); // 1h cache
		}

		return rest_ensure_response( $data );
	}

	/**
	 * Get single shortcode
	 */
	public function get_shortcode( $request ) {
		$id = absint( $request['id'] );

		$cache_key = self::$cache_key . '_' . $id;
		$data      = wp_cache_get( $cache_key, 'wpb_gqb' );

		if ( false === $data ) {
			$data = $this->get_shortcode_row_by_id( $id );

			if ( $data ) {
				// Fields stored as JSON arrays in DB
				$json_fields = array( 'product_cats', 'product_tags', 'products' );

				foreach ( $json_fields as $field ) {
					if ( ! empty( $data[ $field ] ) ) {
						$data[ $field ] = maybe_unserialize( $data[ $field ] );
						if ( ! is_array( $data[ $field ] ) ) {
							$data[ $field ] = array();
						}
					} else {
						$data[ $field ] = array();
					}
				}

				wp_cache_set( $cache_key, $data, 'wpb_gqb', 3600 );
			}
		}

		return rest_ensure_response( $data );
	}

	/**
	 * Create new shortcode
	 */
	public function create_shortcode( $request ) {
		global $wpdb;
		$params = $request->get_json_params();

		// Validate required fields
		if ( empty( $params['name'] ) ) {
			return new WP_Error( 'missing_fields', esc_html__( 'Name is a required field.', 'get-a-quote-button' ), array( 'status' => 400 ) );
		}

		if ( $this->shortcode_name_exists( $params['name'] ) ) {
			return new WP_Error( 'duplicate_name', esc_html__( 'A shortcode with this name already exists.', 'get-a-quote-button' ), array( 'status' => 409 ) );
		}

		// Prepare data for database insertion
		$data = $this->prepare_shortcode_data( $params );

		// Ensure shortcode_status defaults to 'active' if not provided.
		if ( empty( $data['shortcode_status'] ) ) {
			$data['shortcode_status'] = 'active';
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- $this->table is a hardcoded, developer-controlled table name, not user input.
		$result = $wpdb->insert( $this->table, $data );

		if ( $result === false ) {
			return new WP_Error( 'db_error', esc_html__( 'Failed to save shortcode to database.', 'get-a-quote-button' ), array( 'status' => 500 ) );
		}

		$insert_id = $wpdb->insert_id;

		$new_shortcode = $this->get_shortcode_row_by_id( $insert_id );

		// Clear cache
		wp_cache_delete( self::$cache_key, 'wpb_gqb' );
		self::clear_frontend_cache();

		return rest_ensure_response( $new_shortcode );
	}

	/**
	 * Update shortcode
	 */
	public function update_shortcode( $request ) {
		global $wpdb;
		$id     = absint( $request['id'] );
		$params = $request->get_json_params();

		$existing = $this->get_shortcode_row_by_id( $id );

		if ( ! $existing ) {
			return new WP_Error( 'not_found', esc_html__( 'Shortcode not found', 'get-a-quote-button' ), array( 'status' => 404 ) );
		}

		// Prepare data for database update
		$data = $this->prepare_shortcode_data( $params );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- $this->table is a hardcoded, developer-controlled table name, not user input.
		$result = $wpdb->update( $this->table, $data, array( 'id' => $id ) );

		if ( $result === false ) {
			return new WP_Error( 'db_error', esc_html__( 'Failed to update shortcode', 'get-a-quote-button' ), array( 'status' => 500 ) );
		}

		$updated_shortcode = $this->get_shortcode_row_by_id( $id );

		// Clear cache
		wp_cache_delete( self::$cache_key, 'wpb_gqb' );
		wp_cache_delete( self::$cache_key . '_' . $id, 'wpb_gqb' );
		self::clear_frontend_cache();
		self::clear_frontend_shortcode_cache( $id );

		return rest_ensure_response( $updated_shortcode );
	}

	/**
	 * Delete shortcode
	 */
	public function delete_shortcode( $request ) {
		global $wpdb;
		$id = absint( $request['id'] );

		$existing = $this->get_shortcode_row_by_id( $id );

		if ( ! $existing ) {
			return new WP_Error( 'not_found', esc_html__( 'Shortcode not found', 'get-a-quote-button' ), array( 'status' => 404 ) );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- $this->table is a hardcoded, developer-controlled table name, not user input.
		$result = $wpdb->delete( $this->table, array( 'id' => $id ) );

		if ( $result === false ) {
			return new WP_Error( 'db_error', esc_html__( 'Failed to delete shortcode', 'get-a-quote-button' ), array( 'status' => 500 ) );
		}

		// Clear cache
		wp_cache_delete( self::$cache_key, 'wpb_gqb' );
		wp_cache_delete( self::$cache_key . '_' . $id, 'wpb_gqb' );
		self::clear_frontend_cache();
		self::clear_frontend_shortcode_cache( $id );

		return rest_ensure_response(
			array(
				'deleted' => true,
				'id'      => $id,
			)
		);
	}

	/**
	 * Move a shortcode to trash (update status)
	 */
	public function trash_shortcode( $request ) {
		global $wpdb;
		$id = intval( $request['id'] );

		$shortcode = $this->get_shortcode_row_by_id( $id );

		if ( ! $shortcode ) {
			return new WP_Error( 'not_found', esc_html__( 'Shortcode not found.', 'get-a-quote-button' ), array( 'status' => 404 ) );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- $this->table is a hardcoded, developer-controlled table name, not user input.
		$updated = $wpdb->update(
			$this->table,
			array( 'shortcode_status' => 'trash' ),
			array( 'id' => $id ),
			array( '%s' ),
			array( '%d' )
		);

		if ( $updated === false ) {
			return new WP_Error( 'db_error', esc_html__( 'Failed to move shortcode to trash.', 'get-a-quote-button' ), array( 'status' => 500 ) );
		}

		// Clear cache
		wp_cache_delete( self::$cache_key, 'wpb_gqb' );
		wp_cache_delete( self::$cache_key . '_' . $id, 'wpb_gqb' );
		self::clear_frontend_cache();
		self::clear_frontend_shortcode_cache( $id );

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => esc_html__( 'Shortcode moved to trash successfully.', 'get-a-quote-button' ),
				'id'      => $id,
			)
		);
	}

	/**
	 * Duplicate a shortcode by ID (auto-copy all DB columns)
	 */
	public function duplicate_shortcode( $request ) {
		global $wpdb;

		$id = absint( $request['id'] );

		$row = $this->get_shortcode_row_by_id( $id );

		if ( ! $row ) {
			return new WP_Error( 'not_found', esc_html__( 'Shortcode not found.', 'get-a-quote-button' ), array( 'status' => 404 ) );
		}

		// Build a unique name for the duplicate
		$original_name = trim( $row['name'] );
		$candidate     = $original_name . ' (Copy)';
		$counter       = 1;

		while ( $this->shortcode_name_exists( $candidate ) ) {
			++$counter;
			$candidate = $original_name . " (Copy {$counter})";
			if ( $counter > 50 ) {
				break;
			}
		}

		// Make a copy of all fields dynamically
		$data = $row;

		// Remove ID (so MySQL auto-increments a new one)
		unset( $data['id'] );

		// Update only what we need to change
		$data['name']             = sanitize_text_field( $candidate );
		$data['date']             = current_time( 'mysql' );
		$data['shortcode_status'] = 'inactive';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- $this->table is a hardcoded, developer-controlled table name, not user input.
		$inserted = $wpdb->insert( $this->table, $data );

		if ( $inserted === false ) {
			return new WP_Error( 'db_error', esc_html__( 'Failed to duplicate shortcode.', 'get-a-quote-button' ), array( 'status' => 500 ) );
		}

		$new_id = $wpdb->insert_id;

		$new_row = $this->get_shortcode_row_by_id( $new_id );

		// Clear cache
		wp_cache_delete( self::$cache_key, 'wpb_gqb' );
		wp_cache_delete( self::$cache_key . '_' . $id, 'wpb_gqb' );
		wp_cache_delete( self::$cache_key . '_' . $new_id, 'wpb_gqb' );
		self::clear_frontend_cache();
		self::clear_frontend_shortcode_cache( $id );
		self::clear_frontend_shortcode_cache( $new_id );

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => esc_html__( 'Shortcode duplicated successfully!', 'get-a-quote-button' ),
				'new_row' => $new_row,
			)
		);
	}

	/**
	 * Bulk update shortcode statuses or delete from custom DB
	 */
	public function bulk_update_shortcodes( $request ) {
		global $wpdb;

		$action = sanitize_text_field( $request['action'] );
		$ids    = (array) $request['ids'];

		// Allowable bulk actions
		$allowed_actions = array( 'trash', 'active', 'inactive', 'empty_trash' );

		if ( ! in_array( $action, $allowed_actions, true ) ) {
			return new WP_Error( 'invalid_bulk_action', esc_html__( 'Invalid bulk action.', 'get-a-quote-button' ), array( 'status' => 400 ) );
		}

		if ( empty( $ids ) ) {
			return new WP_Error( 'no_items', esc_html__( 'No shortcodes selected.', 'get-a-quote-button' ), array( 'status' => 400 ) );
		}

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- $this->table is a hardcoded, developer-controlled table name; $ids_placeholder only ever holds repeated '%d' tokens (never request data), bound below via $wpdb->prepare( $query, $ids ), which accepts a single array as its replacement-values argument.
		$ids_placeholder = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

		switch ( $action ) {
			case 'trash':
				// Move to trash (soft delete)
				$query = $wpdb->prepare(
					'UPDATE ' . $this->table . "
                 SET shortcode_status = 'trash'
                 WHERE id IN ($ids_placeholder)",
					$ids
				);
				break;

			case 'active':
				$query = $wpdb->prepare(
					'UPDATE ' . $this->table . "
                 SET shortcode_status = 'active'
                 WHERE id IN ($ids_placeholder)",
					$ids
				);
				break;

			case 'inactive':
				$query = $wpdb->prepare(
					'UPDATE ' . $this->table . "
                 SET shortcode_status = 'inactive'
                 WHERE id IN ($ids_placeholder)",
					$ids
				);
				break;

			case 'empty_trash':
				// Delete only items currently in trash
				$query = $wpdb->prepare(
					'DELETE FROM ' . $this->table . "
                 WHERE shortcode_status = 'trash'
                 AND id IN ($ids_placeholder)",
					$ids
				);
				break;
		}
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared -- $query was already built via $wpdb->prepare() above in each switch branch.
		$result = $wpdb->query( $query );

		// Clear cache
		wp_cache_delete( self::$cache_key, 'wpb_gqb' );
		self::clear_frontend_cache();
		foreach ( $ids as $id ) {
			wp_cache_delete( self::$cache_key . '_' . $id, 'wpb_gqb' );
			self::clear_frontend_shortcode_cache( $id );
		}

		if ( $result === false ) {
			return new WP_Error( 'db_error', esc_html__( 'Failed to perform bulk action.', 'get-a-quote-button' ), array( 'status' => 500 ) );
		}

		$action_label = str_replace( '_', ' ', $action );

		return array(
			'success' => true,
			'action'  => $action,
			'count'   => (int) $result,
			'message' => sprintf(
				'Action "%s" applied to %d shortcode(s).',
				esc_html( $action_label ),
				(int) $result
			),
		);
	}

	/**
	 * Prepare shortcode data for database operations
	 */
	private function prepare_shortcode_data( $params ) {
		$data = array();

		// Basic fields - mapping to your actual database columns
		if ( isset( $params['name'] ) ) {
			$data['name'] = sanitize_text_field( $params['name'] );
		}

		if ( isset( $params['form_id'] ) ) {
			$data['form_id'] = sanitize_text_field( $params['form_id'] );
		}

		if ( 'on' === $this->gqb_get_option( 'wpb_gqb_multilang_support', 'form_settings' ) && function_exists( 'icl_get_languages' ) ) {
			foreach ( icl_get_languages() as $language ) {
				$form_id_key  = 'form_id_' . esc_attr( $language['code'] );
				$btn_text_key = 'btn_text_' . esc_attr( $language['code'] );

				if ( isset( $params[ $form_id_key ] ) ) {
					$data[ $form_id_key ] = sanitize_text_field( $params[ $form_id_key ] );
				}

				if ( isset( $params[ $btn_text_key ] ) ) {
					$data[ $btn_text_key ] = sanitize_text_field( $params[ $btn_text_key ] );
				}
			}
		}

		if ( isset( $params['location'] ) ) {
			$data['location'] = sanitize_text_field( $params['location'] );
		}

		if ( isset( $params['priority'] ) ) {
			$data['priority'] = intval( $params['priority'] );
		}

		if ( isset( $params['buttonText'] ) ) {
			$data['btn_text'] = sanitize_text_field( $params['buttonText'] );
		}

		if ( isset( $params['buttonSize'] ) ) {
			$data['btn_size'] = sanitize_text_field( $params['buttonSize'] );
		}

		if ( isset( $params['showButtonFor'] ) ) {
			$data['show_btn'] = sanitize_text_field( $params['showButtonFor'] );
		}

		// Array fields - store as serialized data
		if ( isset( $params['productCategories'] ) ) {
			$categories = (array) $params['productCategories'];
			if ( empty( $categories ) ) {
				$data['product_cats'] = '';
			} else {
				$sanitized_cats       = array_map( 'sanitize_text_field', $categories );
				$data['product_cats'] = maybe_serialize( $sanitized_cats );
			}
		}

		if ( isset( $params['productTags'] ) ) {
			$tags = (array) $params['productTags'];

			if ( empty( $tags ) ) {
				$data['product_tags'] = '';
			} else {
				$sanitized_tags       = array_map( 'sanitize_text_field', $tags );
				$data['product_tags'] = maybe_serialize( $sanitized_tags );
			}
		}

		if ( isset( $params['products'] ) ) {
			$products = (array) $params['products'];

			if ( empty( $products ) ) {
				$data['products'] = '';
			} else {
				$sanitized_products = array_map( 'sanitize_text_field', $products );
				$data['products']   = maybe_serialize( $sanitized_products );
			}
		}

		// Boolean/String fields
		if ( isset( $params['featuredOnly'] ) ) {
			$data['featured_products'] = $params['featuredOnly'] ? 'yes' : 'no'; // Your DB has 'featured_products'
		}

		if ( isset( $params['noPriceOnly'] ) ) {
			$data['no_price_products'] = $params['noPriceOnly'] ? 'yes' : 'no';
		}

		// Additional fields
		if ( isset( $params['productType'] ) ) {
			$data['product_type'] = sanitize_text_field( $params['productType'] );
		}

		if ( isset( $params['stockStatus'] ) ) {
			$data['stock_status'] = sanitize_text_field( $params['stockStatus'] );
		}

		if ( isset( $params['userStatus'] ) ) {
			$data['user_login_status'] = sanitize_text_field( $params['userStatus'] );
		}

		if ( isset( $params['userRoles'] ) ) {
			$data['the_user_role'] = sanitize_text_field( $params['userRoles'] );
		}

		if ( isset( $params['shortcodeStatus'] ) ) {
			$data['shortcode_status'] = sanitize_text_field( $params['shortcodeStatus'] );
		}

		if ( isset( $params['hideCart'] ) ) {
			$data['hide_cart'] = $params['hideCart'] ? 'yes' : 'no';
		}

		if ( isset( $params['hidePrice'] ) ) {
			$data['hide_price'] = $params['hidePrice'] ? 'yes' : 'no';
		}

		// Add date - your DB has 'date' field
		$data['date'] = current_time( 'mysql' );

		return $data;
	}
}

new WPB_GQB_Quote_Button_ShortCodes_Rest_API();
