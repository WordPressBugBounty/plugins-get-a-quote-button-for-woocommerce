<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPB_GQB_Database {

	private static $db_update_notices = array();

	const TABLE = 'wpb_quote_button_shortcodes';

	/**
	 * All DB updates - using lazy loading to avoid early translation calls
	 */
	private static function get_updates() {
		$updates = array(
			'shortcode_table_create'       => array(
				'description' => 'shortcode_table_create_desc', // Translation key instead of translated string
				'callback'    => 'install_db',
				'version'     => '1.0',
			),
			'add_no_price_products_column' => array(
				'description' => 'add_no_price_products_column_desc',
				'table'       => self::TABLE,
				'columns'     => array(
					'no_price_products' => "ENUM('yes','no') AFTER product_type",
				),
				'version'     => '1.1',
			),
			'wpml_db_update'               => array(
				'description' => 'wpml_db_update_desc',
				'callback'    => 'run_wpml_db_update',
				'version'     => '1.2',
			),
			'add_shortcode_status_column'  => array(
				'description' => 'add_shortcode_status_column_desc',
				'table'       => self::TABLE,
				'columns'     => array(
					'shortcode_status' => "ENUM('inactive', 'active', 'trash') NOT NULL DEFAULT 'active' AFTER the_user_role",
				),
				'version'     => '1.3',
			),
			'update_columns_data_types'    => array(
				'description' => 'update_columns_data_types_desc',
				'table'       => self::TABLE,
				'modify'      => array(
					'products'          => 'TEXT',
					'product_tags'      => 'TEXT',
					'product_cats'      => 'TEXT',
					'name'              => 'TINYTEXT',
					'btn_text'          => 'TINYTEXT',
					'show_btn'          => 'TINYTEXT',
					'btn_size'          => 'TINYTEXT',
					'featured_products' => 'TINYTEXT',
					'product_type'      => 'TINYTEXT',
					'stock_status'      => 'TINYTEXT',
					'user_login_status' => 'TINYTEXT',
					'the_user_role'     => 'TINYTEXT',
				),
				'version'     => '1.4',
			),
			'add_hide_cart_price_columns'  => array(
				'description' => 'add_hide_cart_price_columns_desc',
				'table'       => self::TABLE,
				'columns'     => array(
					'hide_cart'  => "ENUM('yes','no') NOT NULL DEFAULT 'no' AFTER shortcode_status",
					'hide_price' => "ENUM('yes','no') NOT NULL DEFAULT 'no' AFTER hide_cart",
				),
				'version'     => '1.5',
			),
			'add_product_variations_column' => array(
				'description' => 'add_product_variations_column_desc',
				'table'       => self::TABLE,
				'columns'     => array(
					'product_variations' => 'TEXT AFTER products',
				),
				'version'     => '1.6',
			),
		);

		return $updates;
	}

	/**
	 * Get translated description for an update
	 */
	private static function get_update_description( $key ) {
		$descriptions = array(
			'shortcode_table_create_desc'       => __( 'If the quote shortcode database table isn\'t there, create it.', 'get-a-quote-button' ),
			'add_no_price_products_column_desc' => __( 'Adding a new no_price_products column to the quote shortcodes table.', 'get-a-quote-button' ),
			'wpml_db_update_desc'               => __( 'Add WPML columns in the quote shortcodes database for multilingual support.', 'get-a-quote-button' ),
			'add_shortcode_status_column_desc'  => __( 'Adding a new shortcode_status column to the quote shortcodes table.', 'get-a-quote-button' ),
			'update_columns_data_types_desc'    => __( 'Update data types for products, product_tags, product_cats, btn_text, show_btn, and btn_size columns in the quote shortcode table.', 'get-a-quote-button' ),
			'add_hide_cart_price_columns_desc'  => __( 'Adding hide_cart and hide_price columns to the quote shortcodes table.', 'get-a-quote-button' ),
			'add_product_variations_column_desc' => __( 'Adding a new product_variations column to the quote shortcodes table.', 'get-a-quote-button' ),
		);

		return isset( $descriptions[ $key ] ) ? $descriptions[ $key ] : $key;
	}

	public static function init() {
		register_activation_hook( WPB_GQB_PLUGIN_FILE, array( __CLASS__, 'activate' ) );

		add_action( 'plugins_loaded', array( __CLASS__, 'install_db_for_multisites' ) );

		// Move update_db_check to init hook to avoid early translation loading
		add_action( 'init', array( __CLASS__, 'update_db_check' ) );

		add_action( 'admin_notices', array( __CLASS__, 'db_update_notice' ) );
		add_action( 'admin_init', array( __CLASS__, 'handle_db_update_request' ) );

		// Fallback for when WooCommerce is activated after this plugin.
		add_action( 'admin_init', array( __CLASS__, 'create_quote_list_page' ) );
	}

	/**
	 * Auto-create the "Quote List" page for the multi-product quote system,
	 * the same way WooCommerce provisions its own Cart/Checkout pages.
	 *
	 * wc_create_page() is idempotent: it no-ops if the stored page id already
	 * resolves to a valid, non-trashed page.
	 */
	public static function create_quote_list_page() {
		if ( ! function_exists( 'wc_create_page' ) ) {
			return;
		}

		wc_create_page(
			esc_sql( _x( 'quote-list', 'Page slug', 'get-a-quote-button' ) ),
			'wpb_gqb_quote_list_page_id',
			__( 'Quote List', 'get-a-quote-button' ),
			'[wpb-quote-list]'
		);
	}

	public static function get_table_name( $table = self::TABLE ) {
		global $wpdb;
		return $wpdb->prefix . $table;
	}

	/**
	 * Whether a column exists on a table.
	 *
	 * @param string $table  Table name.
	 * @param string $column Column name.
	 * @return bool
	 */
	private static function column_exists( $table, $column ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table is a plugin-controlled identifier (esc_sql()'d by the caller), not user input; MySQL identifiers can't be passed as prepare() placeholders.
		$result = $wpdb->get_results( $wpdb->prepare( "SHOW COLUMNS FROM `$table` LIKE %s", $column ) );
		return ! empty( $result );
	}

	/**
	 * Handle DB update button click
	 */
	public static function handle_db_update_request() {
		if ( ! isset( $_GET['wpb_gqb_db_update'] ) ) {
			return;
		}

		$action = sanitize_key( $_GET['wpb_gqb_db_update'] );

		if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'wpb_gqb_db_update_action_' . $action ) ) {
			wp_die( esc_html__( 'Security check failed.', 'get-a-quote-button' ) );
		}

		if ( ! isset( self::get_updates()[ $action ] ) ) {
			return;
		}

		$update = self::get_updates()[ $action ];

		// Run callback if defined
		if ( isset( $update['callback'] ) && is_callable( array( __CLASS__, $update['callback'] ) ) ) {
			call_user_func( array( __CLASS__, $update['callback'] ) );
		} elseif ( isset( $update['columns'] ) && isset( $update['table'] ) ) {
			self::run_column_updates( $update['table'], $update['columns'] );
		} elseif ( isset( $update['modify'] ) && isset( $update['table'] ) ) {
			self::run_column_updates( $update['table'], $update['modify'], true );
		}

		// Get translated description
		$description = self::get_update_description( $update['description'] );

		// Show success notice
		add_action(
			'admin_notices',
			function () use ( $description ) {
				echo '<div class="notice notice-success is-dismissible"><p>' .
				sprintf(
					/* translators: %s completed successfully. */
					esc_html__( '%s completed successfully.', 'get-a-quote-button' ),
					esc_html( $description )
				) .
				'</p></div>';
			}
		);

		// Clear Cache
		wp_cache_delete( 'wpb_quote_button_shortcodes', 'wpb_gqb' );

		// Update plugin DB version
		if ( isset( $update['version'] ) ) {
			update_option( 'wpb_qgb_db_version', $update['version'] );
		}
	}

	private static function run_column_updates( $table, $columns, $is_modify = false ) {
		global $wpdb;

		$table_name = self::get_table_name( $table );

		// Escape table name just to be safe
		$table_name = esc_sql( $table_name );

		foreach ( $columns as $col_name => $definition ) {
			// Sanitize column name to avoid injection
			$col_name = sanitize_key( $col_name );

			if ( $is_modify ) {
				if ( self::column_exists( $table_name, $col_name ) ) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table_name/$col_name are esc_sql()'d/sanitize_key()'d identifiers from the plugin's own update definitions, not user input; MySQL identifiers/column definitions can't be passed as prepare() placeholders.
					$wpdb->query( "ALTER TABLE `{$table_name}` MODIFY COLUMN `{$col_name}` {$definition}" );
				}
			} elseif ( ! self::column_exists( $table_name, $col_name ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table_name/$col_name are esc_sql()'d/sanitize_key()'d identifiers from the plugin's own update definitions, not user input; MySQL identifiers/column definitions can't be passed as prepare() placeholders.
				$wpdb->query( "ALTER TABLE `{$table_name}` ADD COLUMN `{$col_name}` {$definition}" );
			}
		}
	}

	public static function db_update_notice() {
		if ( empty( self::$db_update_notices ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only, only decides which admin notice to display; the actual update runs through handle_db_update_request(), which verifies the nonce.
		$current_action = isset( $_GET['wpb_gqb_db_update'] ) ? sanitize_key( $_GET['wpb_gqb_db_update'] ) : '';

		foreach ( self::$db_update_notices as $key => $notice ) {
			if ( $current_action && $current_action === $notice['action'] ) {
				continue;
			}

			$class = 'notice notice-info';

			// Translate the message when displaying
			$message = self::get_update_description( $notice['message'] );

			$action_url = wp_nonce_url(
				add_query_arg( array( 'wpb_gqb_db_update' => $notice['action'] ), admin_url() ),
				'wpb_gqb_db_update_action_' . $notice['action']
			);

			printf(
				'<div class="%1$s wpb-gqb-admin-notice"><div class="wpb-gqb-admin-notice-icon"><span class="dashicons dashicons-info"></span><h3 style="margin-bottom: 0;">%2$s</h3></div><p>%3$s</p><p><div class="wpb-notice-actions"><a href="%4$s" class="button button-primary"><span class="dashicons dashicons-update-alt"></span>%5$s</a></div></p></div>',
				esc_attr( $class ),
				esc_html__( 'The Get a Quote Button Plugin requires a database update.', 'get-a-quote-button' ),
				esc_html( $message ),
				esc_url( $action_url ),
				esc_html__( 'Run Update', 'get-a-quote-button' )
			);
		}
	}

	public static function update_db_check() {
		global $wpdb;

		$table_name = self::get_table_name();
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists    = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_name ) ) );
		$current_version = get_option( 'wpb_gqb_db_version', '1.0' );

		foreach ( self::get_updates() as $key => $update ) {

			// 1) Table creation notice
			if ( $key === 'shortcode_table_create' ) {
				if ( ! $table_exists ) {
					self::$db_update_notices[ $key ] = array(
						'message' => $update['description'], // Store the key, not the translation
						'action'  => $key,
					);
				}
				continue;
			}

			// 2) WPML callback notice (keeps previous WPML logic)
			if ( $key === 'wpml_db_update' ) {
				$db_update         = get_option( 'wpb_qgb_wpml_db_update' );
				$multilang_support = self::get_settings_option( 'wpb_gqb_multilang_support', 'form_settings' );
				if ( function_exists( 'icl_get_languages' ) && $db_update !== '1' && $multilang_support === 'on' ) {
					self::$db_update_notices[ $key ] = array(
						'message' => $update['description'],
						'action'  => $key,
					);
				}
				continue;
			}

			// If table does not exist, skip other checks (create-table notice will handle it)
			if ( ! $table_exists ) {
				continue;
			}

			// 3) Column add checks (only if update has a version and current is older)
			if ( isset( $update['columns'] ) && isset( $update['version'] ) && version_compare( $current_version, $update['version'], '<' ) ) {
				foreach ( $update['columns'] as $col_name => $definition ) {
					if ( ! self::column_exists( $table_name, $col_name ) ) {
						self::$db_update_notices[ $key ] = array(
							'message' => $update['description'],
							'action'  => $key,
						);
						break; // one missing column is enough to display notice
					}
				}
			}

			// 4) Column modify checks (detects type mismatches) — also version-gated
			if ( isset( $update['modify'] ) && isset( $update['version'] ) && version_compare( $current_version, $update['version'], '<' ) ) {
				foreach ( $update['modify'] as $col_name => $new_definition ) {
					if ( self::column_exists( $table_name, $col_name ) ) {
						// get current column type (Type field from SHOW COLUMNS)
						// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table_name is a plugin-controlled identifier, not user input; MySQL identifiers can't be passed as prepare() placeholders.
						$col_row      = $wpdb->get_row( $wpdb->prepare( "SHOW COLUMNS FROM `$table_name` WHERE Field = %s", $col_name ), ARRAY_A );
						$current_type = isset( $col_row['Type'] ) ? strtolower( $col_row['Type'] ) : '';

						// normalize new_definition for comparison
						$norm_new_def = strtolower( $new_definition );

						// Loose comparison: check if current type contains the new definition token
						if ( $current_type === '' || stripos( $current_type, $norm_new_def ) === false ) {
							self::$db_update_notices[ $key ] = array(
								'message' => $update['description'],
								'action'  => $key,
							);
							break; // one mismatch is enough to display notice
						}
					} else {
						// Column missing entirely — show notice
						self::$db_update_notices[ $key ] = array(
							'message' => $update['description'],
							'action'  => $key,
						);
						break;
					}
				}
			}
		}
	}

	/**
	 * WPML DB update
	 */
	private static function run_wpml_db_update() {
		global $wpdb;
		$table     = self::get_table_name();
		$db_update = get_option( 'wpb_qgb_wpml_db_update' );

		if ( function_exists( 'icl_get_languages' ) && $db_update !== '1' ) {
			$languages = icl_get_languages();
			if ( is_array( $languages ) && ! empty( $languages ) ) {
				foreach ( $languages as $lang ) {
					$lang_code    = sanitize_key( $lang['code'] );
					$btn_text_lan = 'btn_text_' . $lang_code;
					$form_id_lan  = 'form_id_' . $lang_code;

					if ( ! self::column_exists( $table, $btn_text_lan ) ) {
						// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table is plugin-controlled, $btn_text_lan is built from a sanitize_key()'d WPML language code, not user input; MySQL identifiers can't be passed as prepare() placeholders.
						$wpdb->query( "ALTER TABLE $table ADD COLUMN $btn_text_lan TINYTEXT NOT NULL AFTER btn_text" );
					}
					if ( ! self::column_exists( $table, $form_id_lan ) ) {
						// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table is plugin-controlled, $form_id_lan is built from a sanitize_key()'d WPML language code, not user input; MySQL identifiers can't be passed as prepare() placeholders.
						$wpdb->query( "ALTER TABLE $table ADD COLUMN $form_id_lan BIGINT(20) NOT NULL AFTER form_id" );
					}
				}
			}
		}
		update_option( 'wpb_qgb_wpml_db_update', '1' );
	}

	/**
	 * Create table
	 */
	public static function install_db() {
		global $wpdb;
		$table_name      = self::get_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            form_id BIGINT(20) UNSIGNED NOT NULL,
            name TINYTEXT NOT NULL,
            location VARCHAR(255) NOT NULL,
            priority BIGINT(20),
            btn_text TINYTEXT,
            show_btn TINYTEXT,
            btn_size TINYTEXT,
            product_cats TEXT,
            product_tags TEXT,
            products TEXT,
            product_variations TEXT,
            featured_products TINYTEXT,
            product_type TINYTEXT,
            no_price_products ENUM('yes','no'),
            stock_status TINYTEXT,
            user_login_status TINYTEXT,
            the_user_role TINYTEXT,
            shortcode_status ENUM('inactive', 'active', 'trash') NOT NULL DEFAULT 'active',
            hide_cart ENUM('yes','no') NOT NULL DEFAULT 'no',
            hide_price ENUM('yes','no') NOT NULL DEFAULT 'no',
            date TEXT,
            PRIMARY KEY (id)
        ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		update_option( 'wpb_qgb_db_version', '1.1' );
	}

	public static function activate() {
		self::install_db();
		self::create_quote_list_page();
	}

	public static function install_db_for_multisites() {
		global $wpdb;
		$db_installed = get_option( 'wpb_gqb_db_version' );
		$table_name   = self::get_table_name();
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_name ) ) );

		if ( ! $exists && ! $db_installed ) {
			self::install_db();
		}
	}

	public static function get_settings_option( $option, $section, $default = '' ) {
		$options = get_option( $section );
		return $options[ $option ] ?? $default;
	}
}
WPB_GQB_Database::init();
