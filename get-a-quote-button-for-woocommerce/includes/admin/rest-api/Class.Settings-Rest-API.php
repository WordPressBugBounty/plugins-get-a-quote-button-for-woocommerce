<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPB_GQB_Plugin_Settings_REST_API {

	/**
	 * Option groups (each key = option_name in wp_options)
	 */
	private $option_groups = array(
		'form_settings',
		'woo_settings',
		'btn_settings',
		'popup_settings',
		'hide_cart_settings',
		'hide_price_settings',
		'quote_settings',
		// Add more as needed
	);

	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST API routes
	 */
	public function register_routes() {
		register_rest_route(
			'wpb-quote/v1',
			'/settings',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_settings' ),
				'permission_callback' => array( $this, 'permissions_check' ),
			)
		);

		register_rest_route(
			'wpb-quote/v1',
			'/settings',
			array(
				'methods'             => array( 'POST', 'PUT' ),
				'callback'            => array( $this, 'update_settings' ),
				'permission_callback' => array( $this, 'permissions_check' ),
			)
		);

		// Plugin Active Status endpoint (used by the General Settings tab to
		// know which form plugin(s) are installed/active on every tier).
		register_rest_route(
			'wpb-quote/v1',
			'/forms-status',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_forms_status' ),
				'permission_callback' => array( $this, 'permissions_check' ),
			)
		);

		// CF7 Forms endpoint
		register_rest_route(
			'wpb-quote/v1',
			'/cf7-forms',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_cf7_forms' ),
				'permission_callback' => array( $this, 'permissions_check' ),
			)
		);

		// WPForms Forms endpoint
		register_rest_route(
			'wpb-quote/v1',
			'/wpforms-forms',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_wpforms_forms' ),
				'permission_callback' => array( $this, 'permissions_check' ),
			)
		);
	}

	/**
	 * Get plugin active status for CF7, WPForms and WPML
	 */
	public function get_forms_status() {
		$status = array(
			'cf7_active'                 => defined( 'WPCF7_VERSION' ),
			'wpforms_active'             => class_exists( 'WPForms' ),
			'quote_form_plugin'          => $this->gqb_get_option( 'wpb_gqb_form_plugin', 'form_settings', 'wpcf7' ),
			'quote_multilanguage_enable' => ( 'on' === $this->gqb_get_option( 'wpb_gqb_multilang_support', 'form_settings' ) ? true : false ),
			'wpml_active'                => defined( 'ICL_SITEPRESS_VERSION' ),
			'languages'                  => array(),
		);

		// If WPML is active, fetch languages
		if ( $status['wpml_active'] && function_exists( 'apply_filters' ) ) {
			global $sitepress;
			if ( isset( $sitepress ) && method_exists( $sitepress, 'get_active_languages' ) ) {
				$langs = $sitepress->get_active_languages();

				// Reformat nicely: array of [ code => name ]
				$status['languages'] = array_map(
					function ( $lang ) {
						return array(
							'code' => $lang['code'],
							'name' => $lang['english_name'] ?? $lang['translated_name'] ?? $lang['native_name'] ?? $lang['code'],
						);
					},
					$langs
				);
			}
		}

		return $status;
	}

	/**
	 * Get CF7 Forms
	 */
	public function get_cf7_forms() {
		if ( ! defined( 'WPCF7_VERSION' ) ) {
			return new WP_Error( 'cf7_inactive', esc_html__( 'Contact Form 7 is not active', 'get-a-quote-button' ), array( 'status' => 404 ) );
		}

		$forms = get_posts(
			array(
				'post_type'      => 'wpcf7_contact_form',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
			)
		);

		return array_map(
			function ( $form ) {
				return array(
					'value' => $form->ID,
					'label' => get_the_title( $form->ID ),
				);
			},
			$forms
		);
	}

	/**
	 * Get WPForms Forms
	 */
	public function get_wpforms_forms() {
		if ( ! class_exists( 'WPForms' ) ) {
			return new WP_Error( 'wpforms_inactive', esc_html__( 'WPForms is not active', 'get-a-quote-button' ), array( 'status' => 404 ) );
		}

		$forms  = wpforms()->form->get(); // WPForms API
		$result = array();

		if ( $forms && is_array( $forms ) ) {
			foreach ( $forms as $form ) {
				$result[] = array(
					'value'    => absint( $form->ID ),
					'label'    => esc_html( $form->post_title ),
					'edit_url' => esc_url( admin_url( 'admin.php?page=wpforms-builder&view=fields&form_id=' . $form->ID ) ),
				);
			}
		}

		return $result;
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
	 * Check permissions
	 */
	public function permissions_check( $request ) {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Get settings (all or single section)
	 */
	public function get_settings( $request ) {
		$section = $request->get_param( 'section' );

		if ( $section ) {
			if ( ! in_array( $section, $this->option_groups, true ) ) {
				return rest_ensure_response( false );
			}

			$value = get_option( $section, null );
			if ( $value === null ) {
				// option not present in wp_options
				return rest_ensure_response( false );
			}

			return rest_ensure_response( array( $section => $value ) );
		}

		$response = array();
		foreach ( $this->option_groups as $option_name ) {
			$value = get_option( $option_name, null );
			if ( $value === null ) {
				// if any section missing, early return false
				return rest_ensure_response( false );
			}
			$response[ $option_name ] = $value;
		}

		return rest_ensure_response( $response );
	}

	/**
	 * Update settings (one or more sections)
	 */
	public function update_settings( $request ) {
		$params  = $request->get_json_params();
		$updated = array();

		if ( empty( $params ) || ! is_array( $params ) ) {
			return new WP_Error( 'invalid_data', esc_html__( 'Invalid data format.', 'get-a-quote-button' ), array( 'status' => 400 ) );
		}

		foreach ( $params as $section => $data ) {
			if ( in_array( $section, $this->option_groups, true ) ) {
				// Ensure the stored value is an array
				if ( ! is_array( $data ) ) {
					continue;
				}

				// Prepare data for database update & Sanitize each setting
				$sanitized = $this->prepare_settings_data( $data );

				update_option( $section, $sanitized );
				$updated[ $section ] = $sanitized;
			}
		}

		if ( empty( $updated ) ) {
			return new WP_Error( 'no_valid_section', esc_html__( 'No valid section found to update.', 'get-a-quote-button' ), array( 'status' => 400 ) );
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'updated' => $updated,
			)
		);
	}

	/**
	 * Prepare settings data for database operations (to match with the old settings options key)
	 */
	private function prepare_settings_data( $params ) {
		$data = array();

		// General Settings
		if ( isset( $params['formPlugin'] ) ) {
			$data['wpb_gqb_form_plugin'] = sanitize_text_field( $params['formPlugin'] );
		}

		if ( isset( $params['selectedFormCF7'] ) ) {
			$data['wpb_gqb_cf7_form_id'] = sanitize_text_field( $params['selectedFormCF7'] );
		}

		if ( isset( $params['selectedFormWpforms'] ) ) {
			$data['wpb_gqb_wpforms_form_id'] = sanitize_text_field( $params['selectedFormWpforms'] );
		}

		if ( isset( $params['redirectPage'] ) ) {
			$data['wpb_gqb_cf7_submission_redirect'] = sanitize_text_field( $params['redirectPage'] );
		}

		if ( isset( $params['cf7NameAttr'] ) ) {
			$data['wpb_gqb_cf7_form_name_attr'] = $params['cf7NameAttr'] ? 'on' : 'off';
		}

		if ( isset( $params['forceCf7Scripts'] ) ) {
			$data['wpb_gqb_force_cf7_scripts'] = $params['forceCf7Scripts'] ? 'on' : 'off';
		}

		if ( isset( $params['multiLanguage'] ) ) {
			$data['wpb_gqb_multilang_support'] = $params['multiLanguage'] ? 'on' : 'off';
		}

		// WooCommerce Settings
		if ( isset( $params['singleShow'] ) ) {
			$data['woo_single_show_quote_form'] = $params['singleShow'] ? 'on' : 'off';
		}
		if ( isset( $params['loopShow'] ) ) {
			$data['woo_loop_show_quote_form'] = $params['loopShow'] ? 'on' : 'off';
		}
		if ( isset( $params['relatedShow'] ) ) {
			$data['woo_related_upsell_cross_loop_show_quote_form'] = $params['relatedShow'] ? 'on' : 'off';
		}
		if ( isset( $params['btnPosition'] ) ) {
			$data['wpb_gqb_btn_position'] = sanitize_text_field( $params['btnPosition'] );
		}
		if ( isset( $params['showOnlyFor'] ) ) {
			$data['wpb_gqb_woo_show_only_for'] = sanitize_text_field( $params['showOnlyFor'] );
		}
		if ( isset( $params['showForGuest'] ) ) {
			$data['wpb_gqb_woo_btn_guest'] = $params['showForGuest'] ? 'on' : 'off';
		}
		if ( isset( $params['variableGrayOut'] ) ) {
			$data['wpb_gqb_variable_gray_out'] = $params['variableGrayOut'] ? 'on' : 'off';
		}
		if ( isset( $params['showProductData'] ) ) {
			$data['wpb_gqb_form_product_info'] = $params['showProductData'] ? 'show' : 'hide';
		}
		if ( isset( $params['editableDataFields'] ) ) {
			$data['wpb_gqb_form_product_info_editable'] = $params['editableDataFields'] ? 'yes' : 'no';
		}
		if ( isset( $params['autoSendProductData'] ) ) {
			$data['wpb_gqb_auto_send_product_data'] = $params['autoSendProductData'] ? 'on' : 'off';
		}
		if ( isset( $params['variationOn'] ) ) {
			$data['wpb_gqb_variations_collection'] = sanitize_text_field( $params['variationOn'] );
		}

		// Button Settings
		if ( isset( $params['btnText'] ) ) {
			$data['wpb_gqb_btn_text'] = sanitize_text_field( $params['btnText'] );
		}
		if ( isset( $params['btnSize'] ) ) {
			$data['wpb_gqb_btn_size'] = sanitize_text_field( $params['btnSize'] );
		}
		if ( isset( $params['btnWidth'] ) ) {
			$data['wpb_gqb_btn_width'] = sanitize_text_field( $params['btnWidth'] );
		}
		if ( isset( $params['btnBgColor'] ) ) {
			$data['wpb_gqb_btn_bg_color'] = sanitize_text_field( $params['btnBgColor'] );
		}
		if ( isset( $params['btnHoverBgColor'] ) ) {
			$data['wpb_gqb_btn_bg_hover_color'] = sanitize_text_field( $params['btnHoverBgColor'] );
		}
		if ( isset( $params['btnColor'] ) ) {
			$data['wpb_gqb_btn_color'] = sanitize_text_field( $params['btnColor'] );
		}
		if ( isset( $params['btnHoverColor'] ) ) {
			$data['wpb_gqb_btn_hover_color'] = sanitize_text_field( $params['btnHoverColor'] );
		}
		if ( isset( $params['borderRadius'] ) ) {
			$data['wpb_gqb_btn_border_radius'] = sanitize_text_field( $params['borderRadius'] );
		}
		if ( isset( $params['borderRadiusUnit'] ) ) {
			$data['wpb_gqb_btn_border_radius_unit'] = sanitize_text_field( $params['borderRadiusUnit'] );
		}
		if ( isset( $params['fontSize'] ) ) {
			$data['wpb_gqb_btn_font_size'] = sanitize_text_field( $params['fontSize'] );
		}
		if ( isset( $params['fontSizeUnit'] ) ) {
			$data['wpb_gqb_btn_font_size_unit'] = sanitize_text_field( $params['fontSizeUnit'] );
		}
		if ( isset( $params['fontWeight'] ) ) {
			$data['wpb_gqb_btn_font_weight'] = sanitize_text_field( $params['fontWeight'] );
		}
		if ( isset( $params['lineHeight'] ) ) {
			$data['wpb_gqb_btn_line_height'] = sanitize_text_field( $params['lineHeight'] );
		}
		if ( isset( $params['btnPadding'] ) && is_array( $params['btnPadding'] ) ) {
			$data['wpb_gqb_btn_padding'] = wp_json_encode( array_map( 'sanitize_text_field', $params['btnPadding'] ) );
		}
		if ( isset( $params['btnMargin'] ) && is_array( $params['btnMargin'] ) ) {
			$data['wpb_gqb_btn_margin'] = wp_json_encode( array_map( 'sanitize_text_field', $params['btnMargin'] ) );
		}
		if ( isset( $params['btnBorder'] ) && is_array( $params['btnBorder'] ) ) {
			$data['wpb_gqb_btn_border'] = wp_json_encode( array_map( 'sanitize_text_field', $params['btnBorder'] ) );
		}
		if ( isset( $params['btnHoverBorder'] ) && is_array( $params['btnHoverBorder'] ) ) {
			$data['wpb_gqb_btn_hover_border'] = wp_json_encode( array_map( 'sanitize_text_field', $params['btnHoverBorder'] ) );
		}
		if ( isset( $params['btnBoxShadow'] ) && is_array( $params['btnBoxShadow'] ) ) {
			$data['wpb_gqb_btn_box_shadow'] = wp_json_encode( array_map( 'sanitize_text_field', $params['btnBoxShadow'] ) );
		}
		if ( isset( $params['btnHoverBoxShadow'] ) && is_array( $params['btnHoverBoxShadow'] ) ) {
			$data['wpb_gqb_btn_hover_box_shadow'] = wp_json_encode( array_map( 'sanitize_text_field', $params['btnHoverBoxShadow'] ) );
		}

		// Popup Settings
		if ( isset( $params['showTitle'] ) ) {
			$data['wpb_gqb_show_title'] = $params['showTitle'] ? 'on' : 'off';
		}
		if ( isset( $params['formStyle'] ) ) {
			$data['wpb_gqb_form_style'] = $params['formStyle'] ? 'on' : 'off';
		}
		if ( isset( $params['allowOutsideClick'] ) ) {
			$data['wpb_gqb_allow_outside_click'] = $params['allowOutsideClick'] ? 'on' : 'off';
		}
		if ( isset( $params['allowEscapeKey'] ) ) {
			$data['wpb_gqb_allow_esc_key'] = $params['allowEscapeKey'] ? 'on' : 'off';
		}
		if ( isset( $params['closeOnSubmit'] ) ) {
			$data['form_submit_close_popup'] = $params['closeOnSubmit'] ? 'on' : 'off';
		}
		if ( isset( $params['popupBgColor'] ) ) {
			$data['wpb_gqb_popup_bg_color'] = sanitize_text_field( $params['popupBgColor'] );
		}
		if ( isset( $params['popupPadding'] ) && is_array( $params['popupPadding'] ) ) {
			$data['wpb_gqb_popup_padding'] = wp_json_encode( array_map( 'sanitize_text_field', $params['popupPadding'] ) );
		}
		if ( isset( $params['popupWidth'] ) ) {
			$data['wpb_gqb_popup_width'] = sanitize_text_field( $params['popupWidth'] );
		}
		if ( isset( $params['popupBorderRadius'] ) ) {
			$data['wpb_gqb_popup_border_radius'] = sanitize_text_field( $params['popupBorderRadius'] );
		}

		// Cart Hide Settings
		if ( isset( $params['cartHideMethod'] ) ) {
			$data['wpb_gqb_hide_cart_type'] = sanitize_text_field( $params['cartHideMethod'] );
		}
		if ( isset( $params['cartHideForAll'] ) ) {
			$data['wpb_gqb_hide_cart_button_for_all'] = $params['cartHideForAll'] ? 'on' : 'off';
		}
		if ( isset( $params['cartHideForSelected'] ) ) {
			$data['wpb_gqb_hide_cart_button_for_selected'] = $params['cartHideForSelected'] ? 'on' : 'off';
		}
		if ( isset( $params['cartHideForFeatured'] ) ) {
			$data['wpb_gqb_hide_cart_button_for_featured'] = $params['cartHideForFeatured'] ? 'on' : 'off';
		}

		// Price Hide Settings
		if ( isset( $params['priceHideMethod'] ) ) {
			$data['wpb_gqb_hide_price_type'] = sanitize_text_field( $params['priceHideMethod'] );
		}
		if ( isset( $params['priceHideForAll'] ) ) {
			$data['wpb_gqb_hide_price_for_all'] = $params['priceHideForAll'] ? 'on' : 'off';
		}
		if ( isset( $params['priceHideForSelected'] ) ) {
			$data['wpb_gqb_hide_price_for_selected'] = $params['priceHideForSelected'] ? 'on' : 'off';
		}
		if ( isset( $params['priceHideForFeatured'] ) ) {
			$data['wpb_gqb_hide_price_for_featured'] = $params['priceHideForFeatured'] ? 'on' : 'off';
		}

		// Multi-Product Quote System Settings
		if ( isset( $params['quoteSystemType'] ) ) {
			$data['wpb_gqb_quote_system_type'] = sanitize_text_field( $params['quoteSystemType'] );
		}
		if ( isset( $params['addToQuoteBtnText'] ) ) {
			$data['wpb_gqb_add_to_quote_btn_text'] = sanitize_text_field( $params['addToQuoteBtnText'] );
		}
		if ( isset( $params['quoteListPageId'] ) ) {
			$data['wpb_gqb_quote_list_page_id'] = absint( $params['quoteListPageId'] );
		}
		if ( isset( $params['redirectAfterAdd'] ) ) {
			$data['wpb_gqb_quote_redirect_after_add'] = $params['redirectAfterAdd'] ? 'on' : 'off';
		}
		if ( isset( $params['showQuoteCountBadge'] ) ) {
			$data['wpb_gqb_show_quote_count_badge'] = $params['showQuoteCountBadge'] ? 'on' : 'off';
		}
		if ( isset( $params['quoteCountHoverDropdown'] ) ) {
			$data['wpb_gqb_show_quote_count_hover_dropdown'] = $params['quoteCountHoverDropdown'] ? 'on' : 'off';
		}
		if ( isset( $params['showPricesOnQuoteList'] ) ) {
			$data['wpb_gqb_show_prices_on_quote_list'] = sanitize_text_field( $params['showPricesOnQuoteList'] );
		}
		if ( isset( $params['quoteListDisplayMode'] ) ) {
			$data['wpb_gqb_quote_list_display_mode'] = sanitize_text_field( $params['quoteListDisplayMode'] );
		}
		if ( isset( $params['clearQuoteAfterSubmit'] ) ) {
			$data['wpb_gqb_clear_quote_after_submit'] = $params['clearQuoteAfterSubmit'] ? 'on' : 'off';
		}
		if ( isset( $params['quoteAddedAlertType'] ) ) {
			$data['wpb_gqb_quote_added_alert_type'] = sanitize_text_field( $params['quoteAddedAlertType'] );
		}
		if ( isset( $params['quoteAddedMessageText'] ) ) {
			$data['wpb_gqb_quote_added_message_text'] = sanitize_text_field( $params['quoteAddedMessageText'] );
		}
		if ( isset( $params['quoteViewListLinkText'] ) ) {
			$data['wpb_gqb_quote_view_list_link_text'] = sanitize_text_field( $params['quoteViewListLinkText'] );
		}

		// Quote List Page Text
		if ( isset( $params['quoteListEmptyText'] ) ) {
			$data['wpb_gqb_quote_list_empty_text'] = sanitize_text_field( $params['quoteListEmptyText'] );
		}
		if ( isset( $params['quoteListProductLabel'] ) ) {
			$data['wpb_gqb_quote_list_product_label'] = sanitize_text_field( $params['quoteListProductLabel'] );
		}
		if ( isset( $params['quoteListTotalLabel'] ) ) {
			$data['wpb_gqb_quote_list_total_label'] = sanitize_text_field( $params['quoteListTotalLabel'] );
		}
		if ( isset( $params['quoteListPriceOnRequestText'] ) ) {
			$data['wpb_gqb_quote_list_price_on_request_text'] = sanitize_text_field( $params['quoteListPriceOnRequestText'] );
		}
		if ( isset( $params['quoteListOnRequestText'] ) ) {
			$data['wpb_gqb_quote_list_on_request_text'] = sanitize_text_field( $params['quoteListOnRequestText'] );
		}
		if ( isset( $params['quoteListSaveBadgeText'] ) ) {
			$data['wpb_gqb_quote_list_save_badge_text'] = sanitize_text_field( $params['quoteListSaveBadgeText'] );
		}
		if ( isset( $params['quoteListSummaryTitle'] ) ) {
			$data['wpb_gqb_quote_list_summary_title'] = sanitize_text_field( $params['quoteListSummaryTitle'] );
		}
		if ( isset( $params['quoteListPopupTitle'] ) ) {
			$data['wpb_gqb_quote_list_popup_title'] = sanitize_text_field( $params['quoteListPopupTitle'] );
		}
		if ( isset( $params['quoteListItemsLabel'] ) ) {
			$data['wpb_gqb_quote_list_items_label'] = sanitize_text_field( $params['quoteListItemsLabel'] );
		}
		if ( isset( $params['quoteListEstimatedTotalLabel'] ) ) {
			$data['wpb_gqb_quote_list_estimated_total_label'] = sanitize_text_field( $params['quoteListEstimatedTotalLabel'] );
		}
		if ( isset( $params['quoteListSendBtnText'] ) ) {
			$data['wpb_gqb_quote_list_send_btn_text'] = sanitize_text_field( $params['quoteListSendBtnText'] );
		}
		if ( isset( $params['quoteListRemoveText'] ) ) {
			$data['wpb_gqb_quote_list_remove_text'] = sanitize_text_field( $params['quoteListRemoveText'] );
		}
		if ( isset( $params['quoteListDecreaseQtyLabel'] ) ) {
			$data['wpb_gqb_quote_list_decrease_qty_label'] = sanitize_text_field( $params['quoteListDecreaseQtyLabel'] );
		}
		if ( isset( $params['quoteListIncreaseQtyLabel'] ) ) {
			$data['wpb_gqb_quote_list_increase_qty_label'] = sanitize_text_field( $params['quoteListIncreaseQtyLabel'] );
		}
		if ( isset( $params['quoteListFormRequiredText'] ) ) {
			$data['wpb_gqb_quote_list_form_required_text'] = sanitize_text_field( $params['quoteListFormRequiredText'] );
		}
		if ( isset( $params['quoteListPluginRequiredText'] ) ) {
			$data['wpb_gqb_quote_list_plugin_required_text'] = sanitize_text_field( $params['quoteListPluginRequiredText'] );
		}
		if ( isset( $params['quoteWidgetTitleText'] ) ) {
			$data['wpb_gqb_quote_widget_title_text'] = sanitize_text_field( $params['quoteWidgetTitleText'] );
		}
		if ( isset( $params['quoteWidgetEmptyText'] ) ) {
			$data['wpb_gqb_quote_widget_empty_text'] = sanitize_text_field( $params['quoteWidgetEmptyText'] );
		}
		if ( isset( $params['quoteWidgetViewListBtnText'] ) ) {
			$data['wpb_gqb_quote_widget_view_list_btn_text'] = sanitize_text_field( $params['quoteWidgetViewListBtnText'] );
		}

		// Automatic Quote Data Template (CF7 / WPForms dynamic tags)
		if ( isset( $params['quoteDataHeading'] ) ) {
			$data['wpb_gqb_quote_data_heading'] = sanitize_text_field( $params['quoteDataHeading'] );
		}
		if ( isset( $params['quoteListIntroText'] ) ) {
			$data['wpb_gqb_quote_list_intro_text'] = sanitize_text_field( $params['quoteListIntroText'] );
		}
		if ( isset( $params['quoteItemTemplate'] ) ) {
			$data['wpb_gqb_quote_item_template'] = sanitize_textarea_field( $params['quoteItemTemplate'] );
		}
		if ( isset( $params['quoteTotalTemplate'] ) ) {
			$data['wpb_gqb_quote_total_template'] = sanitize_textarea_field( $params['quoteTotalTemplate'] );
		}
		if ( isset( $params['singleProductTemplate'] ) ) {
			$data['wpb_gqb_single_product_template'] = sanitize_textarea_field( $params['singleProductTemplate'] );
		}

		// Quote List Style
		if ( isset( $params['quoteListLayoutType'] ) ) {
			$data['wpb_gqb_quote_list_layout_type'] = sanitize_text_field( $params['quoteListLayoutType'] );
		}
		if ( isset( $params['quoteListTableWidth'] ) ) {
			$data['wpb_gqb_quote_list_table_width'] = absint( $params['quoteListTableWidth'] );
		}
		if ( isset( $params['quoteListMaxWidth'] ) ) {
			$data['wpb_gqb_quote_list_max_width'] = sanitize_text_field( $params['quoteListMaxWidth'] );
		}
		if ( isset( $params['quoteListPadding'] ) && is_array( $params['quoteListPadding'] ) ) {
			$data['wpb_gqb_quote_list_padding'] = wp_json_encode( array_map( 'sanitize_text_field', $params['quoteListPadding'] ) );
		}
		if ( isset( $params['quoteListBorderRadius'] ) ) {
			$data['wpb_gqb_quote_list_border_radius'] = sanitize_text_field( $params['quoteListBorderRadius'] );
		}
		if ( isset( $params['quoteListBgColor'] ) ) {
			$data['wpb_gqb_quote_list_bg_color'] = sanitize_text_field( $params['quoteListBgColor'] );
		}
		if ( isset( $params['quoteListBorder'] ) && is_array( $params['quoteListBorder'] ) ) {
			$data['wpb_gqb_quote_list_border'] = wp_json_encode( array_map( 'sanitize_text_field', $params['quoteListBorder'] ) );
		}
		if ( isset( $params['quoteListTextColor'] ) ) {
			$data['wpb_gqb_quote_list_text_color'] = sanitize_text_field( $params['quoteListTextColor'] );
		}
		if ( isset( $params['quoteListAccentColor'] ) ) {
			$data['wpb_gqb_quote_list_accent_color'] = sanitize_text_field( $params['quoteListAccentColor'] );
		}
		if ( isset( $params['quoteListFontSize'] ) ) {
			$data['wpb_gqb_quote_list_font_size'] = sanitize_text_field( $params['quoteListFontSize'] );
		}
		if ( isset( $params['quoteListFontWeight'] ) ) {
			$data['wpb_gqb_quote_list_font_weight'] = sanitize_text_field( $params['quoteListFontWeight'] );
		}
		if ( isset( $params['quoteListLineHeight'] ) ) {
			$data['wpb_gqb_quote_list_line_height'] = sanitize_text_field( $params['quoteListLineHeight'] );
		}
		if ( isset( $params['quoteListItemBorderColor'] ) ) {
			$data['wpb_gqb_quote_list_item_border_color'] = sanitize_text_field( $params['quoteListItemBorderColor'] );
		}
		if ( isset( $params['quoteListItemBorderRadius'] ) ) {
			$data['wpb_gqb_quote_list_item_border_radius'] = sanitize_text_field( $params['quoteListItemBorderRadius'] );
		}
		if ( isset( $params['quoteListItemNameColor'] ) ) {
			$data['wpb_gqb_quote_list_item_name_color'] = sanitize_text_field( $params['quoteListItemNameColor'] );
		}
		if ( isset( $params['quoteListItemPriceColor'] ) ) {
			$data['wpb_gqb_quote_list_item_price_color'] = sanitize_text_field( $params['quoteListItemPriceColor'] );
		}
		if ( isset( $params['quoteListSummaryBgColor'] ) ) {
			$data['wpb_gqb_quote_list_summary_bg_color'] = sanitize_text_field( $params['quoteListSummaryBgColor'] );
		}
		if ( isset( $params['quoteListSummaryPadding'] ) && is_array( $params['quoteListSummaryPadding'] ) ) {
			$data['wpb_gqb_quote_list_summary_padding'] = wp_json_encode( array_map( 'sanitize_text_field', $params['quoteListSummaryPadding'] ) );
		}
		if ( isset( $params['quoteListSummaryBorder'] ) && is_array( $params['quoteListSummaryBorder'] ) ) {
			$data['wpb_gqb_quote_list_summary_border'] = wp_json_encode( array_map( 'sanitize_text_field', $params['quoteListSummaryBorder'] ) );
		}
		if ( isset( $params['quoteListSummaryBorderRadius'] ) ) {
			$data['wpb_gqb_quote_list_summary_border_radius'] = sanitize_text_field( $params['quoteListSummaryBorderRadius'] );
		}

		// Quote Count Badge Style
		if ( isset( $params['quoteCountBgColor'] ) ) {
			$data['wpb_gqb_quote_count_bg_color'] = sanitize_text_field( $params['quoteCountBgColor'] );
		}
		if ( isset( $params['quoteCountTextColor'] ) ) {
			$data['wpb_gqb_quote_count_text_color'] = sanitize_text_field( $params['quoteCountTextColor'] );
		}
		if ( isset( $params['quoteCountBorderRadius'] ) ) {
			$data['wpb_gqb_quote_count_border_radius'] = sanitize_text_field( $params['quoteCountBorderRadius'] );
		}
		if ( isset( $params['quoteCountFontSize'] ) ) {
			$data['wpb_gqb_quote_count_font_size'] = sanitize_text_field( $params['quoteCountFontSize'] );
		}
		if ( isset( $params['quoteCountFontWeight'] ) ) {
			$data['wpb_gqb_quote_count_font_weight'] = sanitize_text_field( $params['quoteCountFontWeight'] );
		}
		if ( isset( $params['quoteCountLineHeight'] ) ) {
			$data['wpb_gqb_quote_count_line_height'] = sanitize_text_field( $params['quoteCountLineHeight'] );
		}

		// Quote Widget Icon
		if ( isset( $params['quoteWidgetIconType'] ) ) {
			$data['wpb_gqb_quote_widget_icon_type'] = in_array( $params['quoteWidgetIconType'], array( 'default', 'custom' ), true ) ? $params['quoteWidgetIconType'] : 'default';
		}
		if ( isset( $params['quoteWidgetIconStyle'] ) ) {
			$data['wpb_gqb_quote_widget_icon_style'] = sanitize_text_field( $params['quoteWidgetIconStyle'] );
		}
		if ( isset( $params['quoteWidgetIconCustomUrl'] ) ) {
			$data['wpb_gqb_quote_widget_icon_custom_url'] = esc_url_raw( $params['quoteWidgetIconCustomUrl'] );
		}
		if ( isset( $params['quoteWidgetIconCustomId'] ) ) {
			$data['wpb_gqb_quote_widget_icon_custom_id'] = absint( $params['quoteWidgetIconCustomId'] );
		}

		// Quote Widget Style
		if ( isset( $params['quoteWidgetIconColor'] ) ) {
			$data['wpb_gqb_quote_widget_icon_color'] = sanitize_text_field( $params['quoteWidgetIconColor'] );
		}
		if ( isset( $params['quoteWidgetIconSize'] ) ) {
			$data['wpb_gqb_quote_widget_icon_size'] = sanitize_text_field( $params['quoteWidgetIconSize'] );
		}
		if ( isset( $params['quoteWidgetPanelBgColor'] ) ) {
			$data['wpb_gqb_quote_widget_panel_bg_color'] = sanitize_text_field( $params['quoteWidgetPanelBgColor'] );
		}
		if ( isset( $params['quoteWidgetPanelBorderColor'] ) ) {
			$data['wpb_gqb_quote_widget_panel_border_color'] = sanitize_text_field( $params['quoteWidgetPanelBorderColor'] );
		}
		if ( isset( $params['quoteWidgetPanelBorderRadius'] ) ) {
			$data['wpb_gqb_quote_widget_panel_border_radius'] = sanitize_text_field( $params['quoteWidgetPanelBorderRadius'] );
		}
		if ( isset( $params['quoteWidgetItemNameColor'] ) ) {
			$data['wpb_gqb_quote_widget_item_name_color'] = sanitize_text_field( $params['quoteWidgetItemNameColor'] );
		}
		if ( isset( $params['quoteWidgetItemPriceColor'] ) ) {
			$data['wpb_gqb_quote_widget_item_price_color'] = sanitize_text_field( $params['quoteWidgetItemPriceColor'] );
		}
		if ( isset( $params['quoteWidgetBtnBgColor'] ) ) {
			$data['wpb_gqb_quote_widget_btn_bg_color'] = sanitize_text_field( $params['quoteWidgetBtnBgColor'] );
		}
		if ( isset( $params['quoteWidgetBtnTextColor'] ) ) {
			$data['wpb_gqb_quote_widget_btn_text_color'] = sanitize_text_field( $params['quoteWidgetBtnTextColor'] );
		}
		if ( isset( $params['quoteWidgetBtnHoverBgColor'] ) ) {
			$data['wpb_gqb_quote_widget_btn_hover_bg_color'] = sanitize_text_field( $params['quoteWidgetBtnHoverBgColor'] );
		}

		// Added to Quote Alert Style
		if ( isset( $params['quoteAlertSuccessBgColor'] ) ) {
			$data['wpb_gqb_quote_alert_success_bg_color'] = sanitize_text_field( $params['quoteAlertSuccessBgColor'] );
		}
		if ( isset( $params['quoteAlertSuccessColor'] ) ) {
			$data['wpb_gqb_quote_alert_success_color'] = sanitize_text_field( $params['quoteAlertSuccessColor'] );
		}
		if ( isset( $params['quoteAlertErrorBgColor'] ) ) {
			$data['wpb_gqb_quote_alert_error_bg_color'] = sanitize_text_field( $params['quoteAlertErrorBgColor'] );
		}
		if ( isset( $params['quoteAlertErrorColor'] ) ) {
			$data['wpb_gqb_quote_alert_error_color'] = sanitize_text_field( $params['quoteAlertErrorColor'] );
		}
		if ( isset( $params['quoteAlertLinkColor'] ) ) {
			$data['wpb_gqb_quote_alert_link_color'] = sanitize_text_field( $params['quoteAlertLinkColor'] );
		}

		return $data;
	}
}

new WPB_GQB_Plugin_Settings_REST_API();
