<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Plugin Enqueue Scripts Class
 */
class WPB_GQB_Enqueue_Scripts {

	/**
	 * Class Constructor
	 */
	function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ) );
	}

	/**
	 * Frontend scripts and styles
	 *
	 * @return void
	 */
	public function frontend_scripts() {
		do_action( 'cfturnstile_enqueue_scripts' ); // Enqueue Cloudflare turnstile script if enabled.
		wp_enqueue_script( 'google-recaptcha' ); // Enqueue Google reCAPTCHA script if enabled.

		// Force CF7 scripts if needed.
		if ( wpb_gqb_get_option( 'wpb_gqb_force_cf7_scripts', 'form_settings' ) == 'on' ) {
			if ( function_exists( 'wpcf7_enqueue_scripts' ) ) {
				wpcf7_enqueue_scripts();
			}
			if ( function_exists( 'wpcf7_enqueue_styles' ) ) {
				wpcf7_enqueue_styles();
			}
		}

		// All styles goes here.
		wp_enqueue_style(
			'wpb-get-a-quote-button-sweetalert2',
			plugins_url( 'assets/css/sweetalert2.min.css', __FILE__ ),
			array(),
			WPB_GQB_VERSION
		);
		wp_enqueue_style(
			'wpb-get-a-quote-button-styles',
			plugins_url( 'assets/css/frontend.css', __FILE__ ),
			array(),
			WPB_GQB_VERSION
		);

		// All scripts goes here.
		wp_enqueue_script(
			'wpb-get-a-quote-button-sweetalert2',
			plugins_url( 'assets/js/sweetalert2.all.min.js', __FILE__ ),
			array( 'jquery' ),
			WPB_GQB_VERSION,
			true
		);
		wp_enqueue_script(
			'wpb-get-a-quote-button-scripts',
			plugins_url( 'assets/js/frontend.js', __FILE__ ),
			array( 'jquery', 'wp-util' ),
			WPB_GQB_VERSION,
			true
		);

		$this->js_variables();
		$this->dynamic_styles();
		$this->quote_list_dynamic_styles();
	}

	/**
	 * JavaScript variable for frontend
	 */
	public function js_variables() {
		$cf7_submission_redirect = wpb_gqb_get_option( 'wpb_gqb_cf7_submission_redirect', 'form_settings', '' );
		$quote_list_page_id      = wpb_gqb_get_quote_list_page_id();

		// Powers the "Show Quote List on Badge Hover" setting: reveals the
		// same [wpb-quote-widget] dropdown on hover of *any*
		// .wpb-gqb-quote-count element (e.g. a nav menu item), not just the
		// dedicated widget instance. Pre-rendering the panel markup here
		// (once, per page load) lets frontend.js just extract/clone it
		// instead of firing an extra AJAX request on first hover.
		$quote_count_hover_dropdown = wpb_gqb_get_option( 'wpb_gqb_quote_system_type', 'quote_settings', 'single' ) === 'multi'
			&& wpb_gqb_get_option( 'wpb_gqb_show_quote_count_badge', 'quote_settings', 'on' ) === 'on'
			&& wpb_gqb_get_option( 'wpb_gqb_show_quote_count_hover_dropdown', 'quote_settings', 'off' ) === 'on';

		$data = array(
			'ajaxurl'                      => admin_url( 'admin-ajax.php' ),
			'quote_nonce'                  => wp_create_nonce( 'wpb_gqb_quote_actions' ),
			'form_submit_close_popup'      => esc_attr( wpb_gqb_get_option( 'form_submit_close_popup', 'popup_settings' ) ),
			'variations_collection'        => esc_attr( wpb_gqb_get_option( 'wpb_gqb_variations_collection', 'woo_settings', 'found_variation' ) ),
			'cf7_submission_redirect'      => ( $cf7_submission_redirect ? esc_url( get_permalink( $cf7_submission_redirect ) ) : '' ),
			'quote_system_type'            => esc_attr( wpb_gqb_get_option( 'wpb_gqb_quote_system_type', 'quote_settings', 'single' ) ),
			'quote_list_url'               => ( $quote_list_page_id > 0 ? esc_url( get_permalink( $quote_list_page_id ) ) : '' ),
			'quote_redirect_after_add'     => esc_attr( wpb_gqb_get_option( 'wpb_gqb_quote_redirect_after_add', 'quote_settings', 'off' ) ),
			'quote_show_count_badge'       => esc_attr( wpb_gqb_get_option( 'wpb_gqb_show_quote_count_badge', 'quote_settings', 'on' ) ),
			'quote_count_hover_dropdown'   => $quote_count_hover_dropdown ? 'on' : 'off',
			'quote_count_hover_panel_html' => ( $quote_count_hover_dropdown && class_exists( 'WPB_GQB_Quote_Widget_Shortcode' ) ) ? WPB_GQB_Quote_Widget_Shortcode::render() : '',
			'quote_clear_after_submit'     => esc_attr( wpb_gqb_get_option( 'wpb_gqb_clear_quote_after_submit', 'quote_settings', 'on' ) ),
			'quote_added_alert_type'       => esc_attr( wpb_gqb_get_option( 'wpb_gqb_quote_added_alert_type', 'quote_settings', 'top_right' ) ),
			'quote_list_display_mode'      => esc_attr( wpb_gqb_get_option( 'wpb_gqb_quote_list_display_mode', 'quote_settings', 'popup' ) ),
			'quote_count'                  => class_exists( 'WPB_GQB_Quote_List' ) ? WPB_GQB_Quote_List::get_items_count() : 0,
			'i18n'                         => array(
				'addedToQuote'  => esc_html( wpb_gqb_get_option( 'wpb_gqb_quote_added_message_text', 'quote_settings', esc_html__( 'Added to your quote list.', 'get-a-quote-button' ) ) ),
				'viewQuote'     => esc_html( wpb_gqb_get_option( 'wpb_gqb_quote_view_list_link_text', 'quote_settings', esc_html__( 'View Quote List', 'get-a-quote-button' ) ) ),
				'quoteAddError' => esc_html__( 'Unable to add this product to your quote list.', 'get-a-quote-button' ),
			),
		);

		wp_localize_script( 'wpb-get-a-quote-button-scripts', 'WPB_GQB_Vars', $data );
	}

	/**
	 * Convert spacing object to CSS property string with shorthand optimization.
	 *
	 * @since 1.0.0
	 * @param string|array $spacing_data JSON string or array with top, right, bottom, left values.
	 * @param string       $property     CSS property name (padding, margin, etc.).
	 * @param string       $default      Default value for missing sides.
	 * @param bool         $important    Whether to add !important flag.
	 * @param bool         $optimize     Whether to use CSS shorthand optimization.
	 * @return string CSS property string or empty string if invalid.
	 */
	public function spacing_to_css( $spacing_data, $property = 'padding', $default = '', $optimize = true, $important = false ) {
		// If it's a JSON string, decode it
		if ( is_string( $spacing_data ) ) {
			$spacing_data = json_decode( $spacing_data, true );
		}

		// Validate data
		if ( empty( $spacing_data ) || ! is_array( $spacing_data ) ) {
			return '';
		}

		// Extract values with fallback to default
		$top    = isset( $spacing_data['top'] ) && $spacing_data['top'] !== '' ? esc_attr( $spacing_data['top'] ) : $default;
		$right  = isset( $spacing_data['right'] ) && $spacing_data['right'] !== '' ? esc_attr( $spacing_data['right'] ) : $default;
		$bottom = isset( $spacing_data['bottom'] ) && $spacing_data['bottom'] !== '' ? esc_attr( $spacing_data['bottom'] ) : $default;
		$left   = isset( $spacing_data['left'] ) && $spacing_data['left'] !== '' ? esc_attr( $spacing_data['left'] ) : $default;

		// Check if all values are empty - return empty string if so
		if ( $top === '' && $right === '' && $bottom === '' && $left === '' ) {
			return '';
		}

		// Sanitize property name
		$property = sanitize_key( $property );

		// Add !important flag if needed
		$important_flag = $important ? ' !important' : '';

		// Count non-empty values
		$non_empty_count = 0;
		$non_empty_sides = array();

		if ( $top !== '' ) {
			++$non_empty_count;
			$non_empty_sides['top'] = $top;
		}
		if ( $right !== '' ) {
			++$non_empty_count;
			$non_empty_sides['right'] = $right;
		}
		if ( $bottom !== '' ) {
			++$non_empty_count;
			$non_empty_sides['bottom'] = $bottom;
		}
		if ( $left !== '' ) {
			++$non_empty_count;
			$non_empty_sides['left'] = $left;
		}

		// If only one side has a value, use individual property
		if ( $non_empty_count === 1 ) {
			$side  = key( $non_empty_sides );
			$value = current( $non_empty_sides );
			return sprintf( '%s-%s: %s%s;', $property, $side, $value, $important_flag );
		}

		// Replace empty values with 0 for shorthand (only for multiple values)
		$top    = $top !== '' ? $top : '0';
		$right  = $right !== '' ? $right : '0';
		$bottom = $bottom !== '' ? $bottom : '0';
		$left   = $left !== '' ? $left : '0';

		// Optimize shorthand if enabled
		if ( $optimize ) {
			// All sides equal: padding: 10px;
			if ( $top === $right && $right === $bottom && $bottom === $left ) {
				return sprintf( '%s: %s%s;', $property, $top, $important_flag );
			}

			// Top/bottom and left/right equal: padding: 10px 20px;
			if ( $top === $bottom && $right === $left ) {
				return sprintf( '%s: %s %s%s;', $property, $top, $right, $important_flag );
			}

			// Left/right equal: padding: 10px 20px 30px;
			if ( $right === $left ) {
				return sprintf( '%s: %s %s %s%s;', $property, $top, $right, $bottom, $important_flag );
			}
		}

		// Return full syntax
		return sprintf( '%s: %s %s %s %s%s;', $property, $top, $right, $bottom, $left, $important_flag );
	}

	/**
	 * Get spacing CSS from option value.
	 *
	 * @since 1.0.0
	 * @param string $option_name Option name.
	 * @param string $section     Settings section.
	 * @param string $property    CSS property (padding, margin, etc.).
	 * @param mixed  $default     Default value.
	 * @param bool   $important   Whether to add !important flag.
	 * @param bool   $optimize    Whether to use CSS shorthand optimization.
	 * @return string CSS property string.
	 */
	public function get_spacing_css_from_option( $option_name, $section, $property = 'padding', $default = null, $important = false, $optimize = true ) {
		$spacing_value = wpb_gqb_get_option( $option_name, $section, $default );
		return $this->spacing_to_css( $spacing_value, $property, '0', $optimize, $important );
	}

	/**
	 * Expose a { top, right, bottom, left } spacing option value as four
	 * CSS custom properties, one per side, defaulting to $default when unset.
	 *
	 * @param string $option_name Option name.
	 * @param string $section     Settings section.
	 * @param string $prefix      Custom property name prefix, without the leading "--".
	 * @param string $default     Value to use for sides missing from the option.
	 * @return string
	 */
	public function get_spacing_vars_from_option( $option_name, $section, $prefix, $default = '0' ) {
		$spacing_data = wpb_gqb_get_option( $option_name, $section, null );

		if ( is_string( $spacing_data ) ) {
			$spacing_data = json_decode( $spacing_data, true );
		}

		if ( ! is_array( $spacing_data ) ) {
			$spacing_data = array();
		}

		$sides = array( 'top', 'right', 'bottom', 'left' );
		$vars  = '';

		foreach ( $sides as $side ) {
			$value = isset( $spacing_data[ $side ] ) && $spacing_data[ $side ] !== '' ? esc_attr( $spacing_data[ $side ] ) : $default;
			$vars .= "--{$prefix}-{$side}: {$value};";
		}

		return $vars;
	}

	/**
	 * Build a single CSS custom property declaration ("--name: value;").
	 *
	 * Frontend.css consumes these via var(--name, fallback), so a value
	 * that's missing/empty simply falls through to the CSS-side fallback
	 * rather than being declared here.
	 *
	 * @param string $name  Custom property name, without the leading "--".
	 * @param mixed  $value Value to assign.
	 * @return string
	 */
	public function css_var( $name, $value ) {
		if ( $value === null || $value === '' ) {
			return '';
		}

		return "--{$name}: " . esc_attr( $value ) . ';';
	}

	/**
	 * Build a CSS custom property for a length value (radius, font-size,
	 * icon-size, etc.), appending a default unit when the stored value is
	 * a bare number - e.g. values saved by the number/unit controls before
	 * they combined value+unit into one string, which would otherwise emit
	 * an invalid, unitless CSS length.
	 *
	 * @param string     $name  Custom property name, without the leading "--".
	 * @param string|int $value Value to assign.
	 * @param string     $unit  Unit to append when $value has none. Default 'px'.
	 * @return string
	 */
	public function css_length_var( $name, $value, $unit = 'px' ) {
		if ( is_numeric( $value ) ) {
			$value .= $unit;
		}

		return $this->css_var( $name, $value );
	}

	/**
	 * Expose a { width, style, color } border option value as three CSS
	 * custom properties (width/style/color). Width defaults to 0 when
	 * unset, which renders no visible border regardless of style/color -
	 * mirroring the previous "no width means no border" behavior.
	 *
	 * @param string|array $border_data JSON string or array with width, style, color.
	 * @param string       $prefix      Custom property name prefix, without the leading "--".
	 * @return string
	 */
	public function border_to_css_vars( $border_data, $prefix ) {
		if ( is_string( $border_data ) ) {
			$border_data = json_decode( $border_data, true );
		}

		if ( empty( $border_data ) || ! is_array( $border_data ) || empty( $border_data['width'] ) ) {
			return "--{$prefix}-width: 0;";
		}

		$width = esc_attr( $border_data['width'] );
		$style = ! empty( $border_data['style'] ) ? esc_attr( $border_data['style'] ) : 'solid';
		$color = ! empty( $border_data['color'] ) ? esc_attr( $border_data['color'] ) : 'transparent';

		return "--{$prefix}-width: {$width}; --{$prefix}-style: {$style}; --{$prefix}-color: {$color};";
	}

	/**
	 * Expose a { hOffset, vOffset, blur, spread, color } box-shadow option
	 * value as a single CSS custom property holding the full shorthand.
	 * Unlike border, box-shadow has no real longhand properties to split
	 * into, so this stays one var consumed directly as `box-shadow: var(...)`.
	 *
	 * @param string|array $shadow_data JSON string or array with hOffset, vOffset, blur, spread, color.
	 * @param string       $prefix      Custom property name, without the leading "--".
	 * @return string
	 */
	public function box_shadow_to_css_vars( $shadow_data, $prefix ) {
		if ( is_string( $shadow_data ) ) {
			$shadow_data = json_decode( $shadow_data, true );
		}

		if ( empty( $shadow_data ) || ! is_array( $shadow_data ) || ! array_filter( $shadow_data ) ) {
			return "--{$prefix}: none;";
		}

		$h_offset = ! empty( $shadow_data['hOffset'] ) ? esc_attr( $shadow_data['hOffset'] ) : '0';
		$v_offset = ! empty( $shadow_data['vOffset'] ) ? esc_attr( $shadow_data['vOffset'] ) : '0';
		$blur     = ! empty( $shadow_data['blur'] ) ? esc_attr( $shadow_data['blur'] ) : '0';
		$spread   = ! empty( $shadow_data['spread'] ) ? esc_attr( $shadow_data['spread'] ) : '0';
		$color    = ! empty( $shadow_data['color'] ) ? esc_attr( $shadow_data['color'] ) : 'rgba(0, 0, 0, 0.2)';

		return "--{$prefix}: {$h_offset} {$v_offset} {$blur} {$spread} {$color};";
	}

	/**
	 * Dynamic styles for frontend.
	 *
	 * Emits CSS custom properties on :root; the actual selectors/rules
	 * that consume them (via var(--name, fallback)) live in frontend.css.
	 *
	 * Button margin/padding and popup padding stay as directly-generated
	 * rules rather than variables: they're optional overlays (unset by
	 * default) that must fall through to inherited theme/SweetAlert2
	 * spacing when not configured, which a var() numeric fallback can't
	 * express without forcing them to 0.
	 *
	 * @return void
	 */
	public function dynamic_styles() {
		$vars  = $this->css_var( 'wpb-gqb-btn-color', wpb_gqb_get_option( 'wpb_gqb_btn_color', 'btn_settings', '#ffffff' ) );
		$vars .= $this->css_var( 'wpb-gqb-btn-bg', wpb_gqb_get_option( 'wpb_gqb_btn_bg_color', 'btn_settings', '#17a2b8' ) );
		$vars .= $this->css_var( 'wpb-gqb-btn-hover-color', wpb_gqb_get_option( 'wpb_gqb_btn_hover_color', 'btn_settings', '#ffffff' ) );
		$vars .= $this->css_var( 'wpb-gqb-btn-hover-bg', wpb_gqb_get_option( 'wpb_gqb_btn_bg_hover_color', 'btn_settings', '#138496' ) );
		$vars .= $this->css_length_var( 'wpb-gqb-btn-radius', wpb_gqb_get_option( 'wpb_gqb_btn_border_radius', 'btn_settings', '3px' ) );

		// Hover border falls back to the normal-state border when unset, so the
		// button's border doesn't disappear on hover unless a hover border is configured.
		$btn_border            = wpb_gqb_get_option( 'wpb_gqb_btn_border', 'btn_settings', '' );
		$btn_hover_border      = wpb_gqb_get_option( 'wpb_gqb_btn_hover_border', 'btn_settings', '' );
		$btn_border_data       = is_string( $btn_border ) ? json_decode( $btn_border, true ) : $btn_border;
		$btn_hover_border_data = is_string( $btn_hover_border ) ? json_decode( $btn_hover_border, true ) : $btn_hover_border;
		$vars                 .= $this->border_to_css_vars( $btn_border_data, 'wpb-gqb-btn-border' );
		$vars                 .= $this->border_to_css_vars(
			! empty( $btn_hover_border_data['width'] ) ? $btn_hover_border_data : $btn_border_data,
			'wpb-gqb-btn-hover-border'
		);

		// Hover box-shadow falls back to the normal-state shadow when unset, same
		// rationale as the hover border above.
		$btn_box_shadow            = wpb_gqb_get_option( 'wpb_gqb_btn_box_shadow', 'btn_settings', '' );
		$btn_hover_box_shadow      = wpb_gqb_get_option( 'wpb_gqb_btn_hover_box_shadow', 'btn_settings', '' );
		$btn_box_shadow_data       = is_string( $btn_box_shadow ) ? json_decode( $btn_box_shadow, true ) : $btn_box_shadow;
		$btn_hover_box_shadow_data = is_string( $btn_hover_box_shadow ) ? json_decode( $btn_hover_box_shadow, true ) : $btn_hover_box_shadow;
		$vars                     .= $this->box_shadow_to_css_vars( $btn_box_shadow_data, 'wpb-gqb-btn-shadow' );
		$vars                     .= $this->box_shadow_to_css_vars(
			( is_array( $btn_hover_box_shadow_data ) && array_filter( $btn_hover_box_shadow_data ) ) ? $btn_hover_box_shadow_data : $btn_box_shadow_data,
			'wpb-gqb-btn-hover-shadow'
		);

		$vars .= $this->css_length_var( 'wpb-gqb-btn-font-size', wpb_gqb_get_option( 'wpb_gqb_btn_font_size', 'btn_settings', '15px' ) );
		$vars .= $this->css_var( 'wpb-gqb-btn-font-weight', wpb_gqb_get_option( 'wpb_gqb_btn_font_weight', 'btn_settings', '600' ) );
		$vars .= $this->css_var( 'wpb-gqb-btn-line-height', wpb_gqb_get_option( 'wpb_gqb_btn_line_height', 'btn_settings', '' ) ?: 'normal' );
		$vars .= $this->css_var( 'wpb-gqb-popup-bg', wpb_gqb_get_option( 'wpb_gqb_popup_bg_color', 'popup_settings', '#ffffff' ) );
		$vars .= $this->css_length_var( 'wpb-gqb-popup-radius', wpb_gqb_get_option( 'wpb_gqb_popup_border_radius', 'popup_settings', '5px' ) );

		$btn_padding   = $this->get_spacing_css_from_option( 'wpb_gqb_btn_padding', 'btn_settings', 'padding', null, true );
		$btn_margin    = $this->get_spacing_css_from_option( 'wpb_gqb_btn_margin', 'btn_settings', 'margin', null, true );
		$popup_padding = $this->get_spacing_css_from_option( 'wpb_gqb_popup_padding', 'popup_settings', 'padding' );

		$custom_css = ":root { {$vars} }

        .wpb-get-a-quote-button-btn {
            {$btn_margin}
        }

        .wpb-get-a-quote-button-btn.wpb-get-a-quote-button-btn-custom {
            {$btn_padding}
        }

        .wpb-gqf-popup.swal2-container .swal2-popup.wpb-gqf-popup-padding {{$popup_padding}}
        ";

		wp_add_inline_style( 'wpb-get-a-quote-button-styles', $custom_css );
	}

	/**
	 * Dynamic styles for the Multi-Product Quote List page, the
	 * .wpb-gqb-quote-count badge, and the "Added to Quote" alert.
	 *
	 * Only output when Multi-Product mode is active, since these selectors
	 * only exist on the front end when that quote system type is enabled.
	 *
	 * All of these fields naturally fall back to a CSS initial value (0,
	 * transparent, none) when unset, so - unlike dynamic_styles() - the
	 * whole set can be expressed as :root custom properties consumed by
	 * static rules in frontend.css, including the table/sidebar width
	 * split (via calc()). Only the stacked-vs-sidebar layout itself stays
	 * a template class, since that's a structural switch var() can't drive.
	 *
	 * @return void
	 */
	public function quote_list_dynamic_styles() {
		if ( wpb_gqb_get_option( 'wpb_gqb_quote_system_type', 'quote_settings', 'single' ) !== 'multi' ) {
			return;
		}

		$table_width   = (int) wpb_gqb_get_option( 'wpb_gqb_quote_list_table_width', 'quote_settings', 70 );
		$table_width   = max( 50, min( 85, $table_width ) );
		$sidebar_width = 100 - $table_width;

		$vars  = $this->css_var( 'wpb-gqb-quote-list-max-width', wpb_gqb_get_option( 'wpb_gqb_quote_list_max_width', 'quote_settings', '' ) ?: '100%' );
		$vars .= $this->get_spacing_vars_from_option( 'wpb_gqb_quote_list_padding', 'quote_settings', 'wpb-gqb-quote-list-padding' );
		$vars .= $this->css_length_var( 'wpb-gqb-quote-list-radius', wpb_gqb_get_option( 'wpb_gqb_quote_list_border_radius', 'quote_settings', '' ) ?: '0' );
		$vars .= $this->css_var( 'wpb-gqb-quote-list-bg', wpb_gqb_get_option( 'wpb_gqb_quote_list_bg_color', 'quote_settings', '' ) ?: 'transparent' );
		$vars .= $this->border_to_css_vars( wpb_gqb_get_option( 'wpb_gqb_quote_list_border', 'quote_settings', '' ), 'wpb-gqb-quote-list-border' );
		$vars .= $this->css_var( 'wpb-gqb-quote-list-text-color', wpb_gqb_get_option( 'wpb_gqb_quote_list_text_color', 'quote_settings', '#1a1a1a' ) );
		$vars .= $this->css_var( 'wpb-gqb-quote-list-accent', wpb_gqb_get_option( 'wpb_gqb_quote_list_accent_color', 'quote_settings', '#17a2b8' ) );
		$vars .= $this->css_length_var( 'wpb-gqb-quote-list-font-size', wpb_gqb_get_option( 'wpb_gqb_quote_list_font_size', 'quote_settings', '15px' ) );
		$vars .= $this->css_var( 'wpb-gqb-quote-list-font-weight', wpb_gqb_get_option( 'wpb_gqb_quote_list_font_weight', 'quote_settings', '400' ) );
		$vars .= $this->css_var( 'wpb-gqb-quote-list-line-height', wpb_gqb_get_option( 'wpb_gqb_quote_list_line_height', 'quote_settings', '' ) ?: '1.5' );
		$vars .= $this->css_var( 'wpb-gqb-quote-list-item-border', wpb_gqb_get_option( 'wpb_gqb_quote_list_item_border_color', 'quote_settings', 'rgba(0, 0, 0, 0.07)' ) );
		$vars .= $this->css_length_var( 'wpb-gqb-quote-list-item-radius', wpb_gqb_get_option( 'wpb_gqb_quote_list_item_border_radius', 'quote_settings', '8px' ) );
		$vars .= $this->css_var( 'wpb-gqb-quote-list-item-name-color', wpb_gqb_get_option( 'wpb_gqb_quote_list_item_name_color', 'quote_settings', '#1a1a1a' ) );
		$vars .= $this->css_var( 'wpb-gqb-quote-list-item-price-color', wpb_gqb_get_option( 'wpb_gqb_quote_list_item_price_color', 'quote_settings', 'rgba(0, 0, 0, 0.65)' ) );
		$vars .= $this->css_var( 'wpb-gqb-quote-list-summary-bg', wpb_gqb_get_option( 'wpb_gqb_quote_list_summary_bg_color', 'quote_settings', '#fafafa' ) );
		$vars .= $this->get_spacing_vars_from_option( 'wpb_gqb_quote_list_summary_padding', 'quote_settings', 'wpb-gqb-quote-list-summary-padding', '24px' );
		$vars .= $this->border_to_css_vars(
			wpb_gqb_get_option(
				'wpb_gqb_quote_list_summary_border',
				'quote_settings',
				array(
					'width' => '1px',
					'style' => 'solid',
					'color' => 'rgba(0, 0, 0, 0.08)',
				)
			),
			'wpb-gqb-quote-list-summary-border'
		);
		$vars .= $this->css_length_var( 'wpb-gqb-quote-list-summary-radius', wpb_gqb_get_option( 'wpb_gqb_quote_list_summary_border_radius', 'quote_settings', '10px' ) );
		$vars .= $this->css_var( 'wpb-gqb-quote-list-table-width', $table_width . '%' );
		$vars .= $this->css_var( 'wpb-gqb-quote-list-sidebar-width', $sidebar_width . '%' );

		$vars .= $this->css_var( 'wpb-gqb-quote-count-bg', wpb_gqb_get_option( 'wpb_gqb_quote_count_bg_color', 'quote_settings', '#e0e0e0' ) );
		$vars .= $this->css_var( 'wpb-gqb-quote-count-color', wpb_gqb_get_option( 'wpb_gqb_quote_count_text_color', 'quote_settings', '#1a1a1a' ) );
		$vars .= $this->css_length_var( 'wpb-gqb-quote-count-radius', wpb_gqb_get_option( 'wpb_gqb_quote_count_border_radius', 'quote_settings', '999px' ) );
		$vars .= $this->css_length_var( 'wpb-gqb-quote-count-font-size', wpb_gqb_get_option( 'wpb_gqb_quote_count_font_size', 'quote_settings', '11px' ) );
		$vars .= $this->css_var( 'wpb-gqb-quote-count-font-weight', wpb_gqb_get_option( 'wpb_gqb_quote_count_font_weight', 'quote_settings', '600' ) );
		$vars .= $this->css_var( 'wpb-gqb-quote-count-line-height', wpb_gqb_get_option( 'wpb_gqb_quote_count_line_height', 'quote_settings', '' ) ?: '16px' );

		$vars .= $this->css_var( 'wpb-gqb-quote-alert-success-bg', wpb_gqb_get_option( 'wpb_gqb_quote_alert_success_bg_color', 'quote_settings', '#eafaf1' ) );
		$vars .= $this->css_var( 'wpb-gqb-quote-alert-success-color', wpb_gqb_get_option( 'wpb_gqb_quote_alert_success_color', 'quote_settings', '#2fb866' ) );
		$vars .= $this->css_var( 'wpb-gqb-quote-alert-error-bg', wpb_gqb_get_option( 'wpb_gqb_quote_alert_error_bg_color', 'quote_settings', '#fdeceb' ) );
		$vars .= $this->css_var( 'wpb-gqb-quote-alert-error-color', wpb_gqb_get_option( 'wpb_gqb_quote_alert_error_color', 'quote_settings', '#ea5455' ) );
		$vars .= $this->css_var( 'wpb-gqb-quote-alert-link-color', wpb_gqb_get_option( 'wpb_gqb_quote_alert_link_color', 'quote_settings', '#17a2b8' ) );

		$vars .= $this->css_var( 'wpb-gqb-quote-widget-icon-color', wpb_gqb_get_option( 'wpb_gqb_quote_widget_icon_color', 'quote_settings', '#1a1a1a' ) );
		$vars .= $this->css_length_var( 'wpb-gqb-quote-widget-icon-size', wpb_gqb_get_option( 'wpb_gqb_quote_widget_icon_size', 'quote_settings', '24px' ) );
		$vars .= $this->css_var( 'wpb-gqb-quote-widget-panel-bg', wpb_gqb_get_option( 'wpb_gqb_quote_widget_panel_bg_color', 'quote_settings', '#ffffff' ) );
		$vars .= $this->css_var( 'wpb-gqb-quote-widget-panel-border', wpb_gqb_get_option( 'wpb_gqb_quote_widget_panel_border_color', 'quote_settings', 'rgba(0, 0, 0, 0.08)' ) );
		$vars .= $this->css_length_var( 'wpb-gqb-quote-widget-panel-radius', wpb_gqb_get_option( 'wpb_gqb_quote_widget_panel_border_radius', 'quote_settings', '10px' ) );
		$vars .= $this->css_var( 'wpb-gqb-quote-widget-item-name-color', wpb_gqb_get_option( 'wpb_gqb_quote_widget_item_name_color', 'quote_settings', '#1a1a1a' ) );
		$vars .= $this->css_var( 'wpb-gqb-quote-widget-item-price-color', wpb_gqb_get_option( 'wpb_gqb_quote_widget_item_price_color', 'quote_settings', 'rgba(0, 0, 0, 0.65)' ) );
		$vars .= $this->css_var( 'wpb-gqb-quote-widget-btn-bg', wpb_gqb_get_option( 'wpb_gqb_quote_widget_btn_bg_color', 'quote_settings', '#17a2b8' ) );
		$vars .= $this->css_var( 'wpb-gqb-quote-widget-btn-color', wpb_gqb_get_option( 'wpb_gqb_quote_widget_btn_text_color', 'quote_settings', '#ffffff' ) );
		$vars .= $this->css_var( 'wpb-gqb-quote-widget-btn-hover-bg', wpb_gqb_get_option( 'wpb_gqb_quote_widget_btn_hover_bg_color', 'quote_settings', '#128293' ) );

		wp_add_inline_style( 'wpb-get-a-quote-button-styles', ":root { {$vars} }" );
	}
}
new WPB_GQB_Enqueue_Scripts();
