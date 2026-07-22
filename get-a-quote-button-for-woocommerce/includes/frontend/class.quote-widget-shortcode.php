<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Multi-Product Quote Widget
 *
 * Renders the [wpb-quote-widget] mini-cart-style icon: a badge showing the
 * current quote item count that, on hover/click, reveals a small dropdown
 * listing each item (thumbnail, name, price, remove) plus a link to the
 * full [wpb-quote-list] page. Backed by the same WPB_GQB_Quote_List session
 * storage as the rest of the multi-product quote system.
 *
 * Shared, single render path for every placement (shortcode, Elementor
 * widget, classic widget, and the "Quote Widget" Gutenberg block) - all of
 * them ultimately call self::render(). Plain usage (no $atts) inherits the
 * global style settings from Settings > Multi-Product Quote as before; the
 * Gutenberg block is the only caller that passes per-instance overrides.
 */
class WPB_GQB_Quote_Widget_Shortcode {

	/**
	 * Maps shortcode/block attribute keys to the CSS custom property they
	 * override. These are the exact same custom properties already output
	 * globally by WPB_GQB_Enqueue_Scripts::quote_list_dynamic_styles() (for
	 * the pre-existing color/radius keys) or defined purely in frontend.css
	 * with a hardcoded fallback (for the typography/spacing keys added
	 * alongside the Gutenberg block's expanded controls) - either way, an
	 * instance-level override just needs higher CSS specificity (an inline
	 * style beats `:root`), so adding a new override here never requires
	 * new PHP rendering logic, only a new entry in this map + a `var()` in
	 * frontend.css.
	 *
	 * `icon_custom_url` and `btn_text` are intentionally NOT in this map -
	 * they're not CSS values, they're handled directly in render()/the template.
	 *
	 * @var array<string, string>
	 */
	const STYLE_ATT_TO_CSS_VAR = array(
		'icon_color'           => '--wpb-gqb-quote-widget-icon-color',
		'icon_size'            => '--wpb-gqb-quote-widget-icon-size',
		'panel_bg_color'       => '--wpb-gqb-quote-widget-panel-bg',
		'panel_border_color'   => '--wpb-gqb-quote-widget-panel-border',
		'panel_border_radius'  => '--wpb-gqb-quote-widget-panel-radius',
		'panel_max_width'      => '--wpb-gqb-quote-widget-panel-max-width',
		'heading_font_size'    => '--wpb-gqb-quote-widget-heading-font-size',
		'heading_font_weight'  => '--wpb-gqb-quote-widget-heading-font-weight',
		'heading_spacing'      => '--wpb-gqb-quote-widget-heading-spacing',
		'item_name_color'      => '--wpb-gqb-quote-widget-item-name-color',
		'item_price_color'     => '--wpb-gqb-quote-widget-item-price-color',
		'item_name_font_size'  => '--wpb-gqb-quote-widget-item-name-font-size',
		'item_price_font_size' => '--wpb-gqb-quote-widget-item-price-font-size',
		'item_spacing'         => '--wpb-gqb-quote-widget-item-spacing',
		'btn_bg_color'         => '--wpb-gqb-quote-widget-btn-bg',
		'btn_text_color'       => '--wpb-gqb-quote-widget-btn-color',
		'btn_hover_bg_color'   => '--wpb-gqb-quote-widget-btn-hover-bg',
		'btn_border_radius'    => '--wpb-gqb-quote-widget-btn-radius',
		'btn_padding'          => '--wpb-gqb-quote-widget-btn-padding',
		'btn_font_size'        => '--wpb-gqb-quote-widget-btn-font-size',
	);

	/**
	 * Register the shortcode.
	 *
	 * @return void
	 */
	public static function init() {
		add_shortcode( 'wpb-quote-widget', array( __CLASS__, 'render' ) );
	}

	/**
	 * Shortcode handler / shared render entry point.
	 *
	 * @param array|string $atts {
	 *     Optional. All keys default to '' (inherit the matching global
	 *     setting / built-in default). Colors accept any valid CSS color value.
	 *
	 *     @type string $icon_type            'default' or 'custom'. Defaults to the global "Multi-Product Quote > Quote Widget" icon type setting.
	 *     @type string $icon_style          'receipt', 'bag', or 'list'. Ignored unless $icon_type is 'default'. Defaults to the global "Multi-Product Quote > Quote Widget" icon setting.
	 *     @type string $icon_custom_url      URL of a custom uploaded icon image, replaces the built-in icon glyph entirely when $icon_type is 'custom'. Defaults to the global icon setting when not passed explicitly.
	 *     @type int    $icon_custom_id       Attachment ID of $icon_custom_url, used to detect an SVG upload for icon-color recoloring. Defaults to the global icon setting when not passed explicitly.
	 *     @type string $icon_color
	 *     @type string $icon_size            e.g. '24px'.
	 *     @type string $panel_bg_color
	 *     @type string $panel_border_color
	 *     @type string $panel_border_radius  e.g. '10px'.
	 *     @type string $panel_max_width      e.g. '300px'.
	 *     @type string $heading_font_size
	 *     @type string $heading_font_weight
	 *     @type string $heading_spacing      Space below the "X items in Quote List" heading.
	 *     @type string $item_name_color
	 *     @type string $item_price_color
	 *     @type string $item_name_font_size
	 *     @type string $item_price_font_size
	 *     @type string $item_spacing         Vertical gap between item rows.
	 *     @type string $btn_text             Overrides the "View Quote List" button text.
	 *     @type string $btn_bg_color
	 *     @type string $btn_text_color
	 *     @type string $btn_hover_bg_color
	 *     @type string $btn_border_radius
	 *     @type string $btn_padding          e.g. '11px 16px'.
	 *     @type string $btn_font_size
	 *     @type string $wrapper_attributes   Internal use only (the Gutenberg
	 *                                         block): a pre-built, already-escaped
	 *                                         HTML attribute string (class +
	 *                                         style already merged via
	 *                                         get_block_wrapper_attributes())
	 *                                         to use verbatim on the root
	 *                                         element instead of building one.
	 * }
	 * @return string
	 */
	public static function render( $atts = array() ) {
		if ( ! class_exists( 'WPB_GQB_Quote_List' ) || ! function_exists( 'WC' ) ) {
			return '';
		}

		if ( wpb_gqb_get_option( 'wpb_gqb_quote_system_type', 'quote_settings', 'single' ) !== 'multi' ) {
			return '';
		}

		$raw_atts = (array) $atts;

		// The Gutenberg block always resolves its own icon (it ships a
		// dedicated upload control and always passes `wrapper_attributes`,
		// an internal-only key nothing else sets) - only the shortcode,
		// Elementor widget, and classic widget calls fall back to the
		// global "Multi-Product Quote > Quote Widget" icon settings.
		$is_block_call = ! empty( $raw_atts['wrapper_attributes'] );
		// The Gutenberg block only ever resolves its own icon based on
		// whether it has a custom icon set (it has no separate "type"
		// toggle of its own), matching its pre-existing behavior.
		$icon_type_default       = $is_block_call ? 'custom' : wpb_gqb_get_option( 'wpb_gqb_quote_widget_icon_type', 'quote_settings', 'default' );
		$icon_style_default      = $is_block_call ? 'receipt' : wpb_gqb_get_option( 'wpb_gqb_quote_widget_icon_style', 'quote_settings', 'receipt' );
		$icon_custom_url_default = $is_block_call ? '' : wpb_gqb_get_option( 'wpb_gqb_quote_widget_icon_custom_url', 'quote_settings', '' );
		$icon_custom_id_default  = $is_block_call ? 0 : (int) wpb_gqb_get_option( 'wpb_gqb_quote_widget_icon_custom_id', 'quote_settings', 0 );

		$atts = shortcode_atts(
			array(
				'icon_type'            => $icon_type_default,
				'icon_style'           => $icon_style_default,
				'icon_custom_url'      => $icon_custom_url_default,
				'icon_custom_id'       => $icon_custom_id_default,
				'icon_color'           => '',
				'icon_size'            => '',
				'panel_bg_color'       => '',
				'panel_border_color'   => '',
				'panel_border_radius'  => '',
				'panel_max_width'      => '',
				'heading_font_size'    => '',
				'heading_font_weight'  => '',
				'heading_spacing'      => '',
				'item_name_color'      => '',
				'item_price_color'     => '',
				'item_name_font_size'  => '',
				'item_price_font_size' => '',
				'item_spacing'         => '',
				'btn_text'             => '',
				'btn_bg_color'         => '',
				'btn_text_color'       => '',
				'btn_hover_bg_color'   => '',
				'btn_border_radius'    => '',
				'btn_padding'          => '',
				'btn_font_size'        => '',
				'wrapper_attributes'   => '',
			),
			$raw_atts,
			'wpb-quote-widget'
		);

		$icon_style      = in_array( $atts['icon_style'], array( 'receipt', 'bag', 'list' ), true ) ? $atts['icon_style'] : 'receipt';
		$icon_type       = in_array( $atts['icon_type'], array( 'default', 'custom' ), true ) ? $atts['icon_type'] : 'default';
		$use_custom_icon = ( 'custom' === $icon_type ) && ( '' !== $atts['icon_custom_url'] );

		$quote_page_id = wpb_gqb_get_quote_list_page_id();

		$args = array(
			'items'              => WPB_GQB_Quote_List::get_items(),
			'count'              => WPB_GQB_Quote_List::get_items_count(),
			'quote_url'          => $quote_page_id > 0 ? get_permalink( $quote_page_id ) : '',
			'icon_style'         => $icon_style,
			'use_custom_icon'    => $use_custom_icon,
			'icon_custom_markup' => $use_custom_icon ? self::get_custom_icon_markup( $atts['icon_custom_url'], (int) $atts['icon_custom_id'] ) : '',
			'btn_text'           => $atts['btn_text'],
			'style_overrides'    => self::build_style_overrides( $atts ),
			'wrapper_attributes' => $atts['wrapper_attributes'],
		);

		ob_start();

		wc_get_template( 'quote-list/quote-widget.php', $args, 'get-a-quote-button/', WPB_GQB_PLUGIN_WOO_TEMPLATE_PATH );

		return ob_get_clean();
	}

	/**
	 * Build an inline `--css-var:value;` string from whichever style atts
	 * were actually set, for use as the root element's `style` attribute.
	 * Public so WPB_GQB_Quote_Widget_Block can reuse the same mapping when
	 * composing the block's get_block_wrapper_attributes() call.
	 *
	 * @param array $atts Resolved shortcode_atts() output (or an equivalent array).
	 * @return string Empty string when no overrides are present.
	 */
	public static function build_style_overrides( $atts ) {
		$css = '';

		foreach ( self::STYLE_ATT_TO_CSS_VAR as $att_key => $css_var ) {
			if ( ! isset( $atts[ $att_key ] ) || '' === $atts[ $att_key ] ) {
				continue;
			}

			$css .= $css_var . ':' . self::sanitize_css_value( $atts[ $att_key ] ) . ';';
		}

		return $css;
	}

	/**
	 * Build the HTML markup for a custom-uploaded icon. SVG uploads are
	 * inlined (sanitized via wp_kses()) instead of used via <img>, so the
	 * "Icon Color" setting can recolor them through `currentColor` exactly
	 * like it already does for the built-in glyphs - an <img src="foo.svg">
	 * can't be recolored with CSS. Any other image type (PNG/JPG/etc.)
	 * keeps using a plain <img> tag, which can't be recolored either way.
	 *
	 * @param string $url           Attachment URL.
	 * @param int    $attachment_id Attachment ID, used only to check its mime type.
	 * @return string
	 */
	protected static function get_custom_icon_markup( $url, $attachment_id ) {
		if ( empty( $url ) ) {
			return '';
		}

		$is_svg = $attachment_id > 0 && 'image/svg+xml' === get_post_mime_type( $attachment_id );

		if ( $is_svg ) {
			$file_path = get_attached_file( $attachment_id );
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- reads a local media-library file from disk, not a remote URL; wp_remote_get() doesn't apply here.
			$svg = ( $file_path && file_exists( $file_path ) ) ? file_get_contents( $file_path ) : false;

			if ( false !== $svg ) {
				$markup = self::inline_svg_markup( $svg );

				if ( '' !== $markup ) {
					return $markup;
				}
			}
		}

		return '<img class="wpb-gqb-quote-widget-icon" src="' . esc_url( $url ) . '" alt="" aria-hidden="true" />';
	}

	/**
	 * Sanitize an uploaded SVG's raw markup for safe inline output, and
	 * rewrite it so the shared icon CSS (sizing + `currentColor` recolor)
	 * applies to it the same way it does to the built-in glyphs:
	 * - wp_kses() strips anything not on the SVG-safe allowlist below
	 *   (in particular, <script> tags and on*="" event handler attributes,
	 *   which aren't in the allowlist for any tag).
	 * - Hardcoded fill/stroke color values (anything other than "none")
	 *   are rewritten to "currentColor" so the Icon Color setting's
	 *   ancestor `color` can drive them.
	 * - The root <svg> tag gets the shared icon classes, dropping any
	 *   conflicting class/aria-hidden/focusable/width/height attributes
	 *   of its own first.
	 *
	 * @param string $svg Raw SVG file contents.
	 * @return string Empty string if nothing safe survives sanitization.
	 */
	protected static function inline_svg_markup( $svg ) {
		$svg = preg_replace( '/<\?xml.*?\?>/is', '', $svg );
		$svg = preg_replace( '/<!DOCTYPE[^>]*>/is', '', $svg );
		$svg = preg_replace( '/<!--.*?-->/s', '', $svg );

		$allowed_attrs = array(
			'class'               => true,
			'id'                  => true,
			'viewbox'             => true,
			'width'               => true,
			'height'              => true,
			'fill'                => true,
			'fill-rule'           => true,
			'clip-rule'           => true,
			'stroke'              => true,
			'stroke-width'        => true,
			'stroke-linecap'      => true,
			'stroke-linejoin'     => true,
			'stroke-dasharray'    => true,
			'opacity'             => true,
			'transform'           => true,
			'd'                   => true,
			'cx'                  => true,
			'cy'                  => true,
			'r'                   => true,
			'rx'                  => true,
			'ry'                  => true,
			'x'                   => true,
			'y'                   => true,
			'x1'                  => true,
			'x2'                  => true,
			'y1'                  => true,
			'y2'                  => true,
			'points'              => true,
			'offset'              => true,
			'stop-color'          => true,
			'stop-opacity'        => true,
			'gradientunits'       => true,
			'gradienttransform'   => true,
			'preserveaspectratio' => true,
			'aria-hidden'         => true,
			'focusable'           => true,
		);

		$allowed_tags = array(
			'svg'            => array_merge( $allowed_attrs, array( 'xmlns' => true ) ),
			'path'           => $allowed_attrs,
			'g'              => $allowed_attrs,
			'circle'         => $allowed_attrs,
			'ellipse'        => $allowed_attrs,
			'rect'           => $allowed_attrs,
			'line'           => $allowed_attrs,
			'polyline'       => $allowed_attrs,
			'polygon'        => $allowed_attrs,
			'defs'           => $allowed_attrs,
			'clippath'       => $allowed_attrs,
			'lineargradient' => $allowed_attrs,
			'radialgradient' => $allowed_attrs,
			'stop'           => $allowed_attrs,
			'title'          => array(),
			'desc'           => array(),
		);

		$svg = wp_kses( $svg, $allowed_tags );

		if ( '' === trim( $svg ) || false === stripos( $svg, '<svg' ) ) {
			return '';
		}

		// Recolor hardcoded fill/stroke values (but leave fill/stroke="none" alone, it's meaningful for outline-style icons).
		$svg = preg_replace_callback(
			'/\b(fill|stroke)\s*=\s*"([^"]*)"/i',
			function ( $matches ) {
				$value = trim( $matches[2] );

				if ( '' === $value || 'none' === strtolower( $value ) ) {
					return $matches[0];
				}

				return $matches[1] . '="currentColor"';
			},
			$svg
		);

		// Force the shared sizing/recolor class hooks onto the root <svg> tag.
		$svg = preg_replace_callback(
			'/<svg\b([^>]*)>/i',
			function ( $matches ) {
				$attrs       = $matches[1];
				$has_viewbox = (bool) preg_match( '/\bviewBox\s*=/i', $attrs );
				$strip_attrs = $has_viewbox ? 'class|aria-hidden|focusable|width|height' : 'class|aria-hidden|focusable';
				$attrs       = preg_replace( '/\s(' . $strip_attrs . ')\s*=\s*"[^"]*"/i', '', $attrs );

				return '<svg class="wpb-gqb-quote-widget-icon wpb-gqb-quote-widget-icon-custom-svg" aria-hidden="true" focusable="false"' . $attrs . '>';
			},
			$svg,
			1
		);

		return $svg;
	}

	/**
	 * Loosely sanitize a CSS custom property value coming from a shortcode
	 * attribute / block attribute (colors, sizes, shorthand padding, and
	 * font-weight keywords only - this widget never accepts url()/expression
	 * -bearing values here).
	 *
	 * @param string $value
	 * @return string
	 */
	protected static function sanitize_css_value( $value ) {
		return preg_replace( '/[^a-zA-Z0-9#%.\-, ()]/', '', (string) $value );
	}
}
WPB_GQB_Quote_Widget_Shortcode::init();
