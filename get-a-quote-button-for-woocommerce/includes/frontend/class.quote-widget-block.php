<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Gutenberg block wrapper around [wpb-quote-widget]
 * (get-a-quote-button/quote-widget).
 *
 * A dynamic block: the editor only builds a live-ish canvas preview, the
 * actual markup always comes from WPB_GQB_Quote_Widget_Shortcode::render()
 * via render_callback() below - the same render path used by the shortcode,
 * the Elementor widget, and the classic widget - so every placement stays
 * in sync with a single source of truth.
 *
 * Registered with the classic array-based register_block_type() (no
 * block.json file) because this plugin's webpack config defines explicit
 * entries rather than relying on @wordpress/scripts' block.json
 * auto-discovery (see webpack.config.js).
 */
class WPB_GQB_Quote_Widget_Block {

	/**
	 * Editor script handle.
	 *
	 * @var string
	 */
	const EDITOR_SCRIPT_HANDLE = 'wpb-gqb-quote-widget-block-editor';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register' ) );
	}

	/**
	 * Register the editor script and the block type.
	 *
	 * @return void
	 */
	public function register() {
		$asset_file = WPB_GQB_PLUGIN_PATH . 'includes/admin/assets/build/blocks/quote-widget/index.asset.php';

		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$asset = include $asset_file;

		wp_register_script(
			self::EDITOR_SCRIPT_HANDLE,
			plugins_url( 'includes/admin/assets/build/blocks/quote-widget/index.js', WPB_GQB_PLUGIN_FILE ),
			$asset['dependencies'],
			$asset['version'],
			true
		);

		wp_set_script_translations( self::EDITOR_SCRIPT_HANDLE, 'get-a-quote-button' );

		register_block_type(
			'get-a-quote-button/quote-widget',
			array(
				'api_version'     => 3,
				'editor_script'   => self::EDITOR_SCRIPT_HANDLE,
				'render_callback' => array( __CLASS__, 'render' ),
				'attributes'      => array(
					// Icon.
					'iconStyle'         => array(
						'type'    => 'string',
						'default' => 'receipt',
					),
					'iconCustomUrl'     => array(
						'type'    => 'string',
						'default' => '',
					),
					'iconCustomId'      => array(
						'type'    => 'number',
						'default' => 0,
					),
					'iconColor'         => array(
						'type'    => 'string',
						'default' => '',
					),
					'iconSize'          => array(
						'type'    => 'string',
						'default' => '',
					),
					// Dropdown panel.
					'panelBgColor'      => array(
						'type'    => 'string',
						'default' => '',
					),
					'panelBorderColor'  => array(
						'type'    => 'string',
						'default' => '',
					),
					'panelBorderRadius' => array(
						'type'    => 'string',
						'default' => '',
					),
					'panelMaxWidth'     => array(
						'type'    => 'string',
						'default' => '',
					),
					// phpcs:ignore Squiz.PHP.CommentedOutCode.Found -- plain descriptive comment, not commented-out code.
					// Heading ("X items in Quote List").
					'headingFontSize'   => array(
						'type'    => 'string',
						'default' => '',
					),
					'headingFontWeight' => array(
						'type'    => 'string',
						'default' => '',
					),
					'headingSpacing'    => array(
						'type'    => 'string',
						'default' => '',
					),
					// Items.
					'itemNameColor'     => array(
						'type'    => 'string',
						'default' => '',
					),
					'itemPriceColor'    => array(
						'type'    => 'string',
						'default' => '',
					),
					'itemNameFontSize'  => array(
						'type'    => 'string',
						'default' => '',
					),
					'itemPriceFontSize' => array(
						'type'    => 'string',
						'default' => '',
					),
					'itemSpacing'       => array(
						'type'    => 'string',
						'default' => '',
					),
					// "View Quote List" button.
					'btnText'           => array(
						'type'    => 'string',
						'default' => '',
					),
					'btnBgColor'        => array(
						'type'    => 'string',
						'default' => '',
					),
					'btnTextColor'      => array(
						'type'    => 'string',
						'default' => '',
					),
					'btnHoverBgColor'   => array(
						'type'    => 'string',
						'default' => '',
					),
					'btnBorderRadius'   => array(
						'type'    => 'string',
						'default' => '',
					),
					'btnPadding'        => array(
						'type'    => 'string',
						'default' => '',
					),
					'btnFontSize'       => array(
						'type'    => 'string',
						'default' => '',
					),
				),
				'supports'        => array(
					'html'    => false,
					'align'   => array( 'left', 'center', 'right' ),
					'spacing' => array(
						'margin'  => true,
						'padding' => true,
					),
				),
			)
		);
	}

	/**
	 * Block render callback.
	 *
	 * @param array $attributes Resolved block attributes (camelCase keys, defaults already applied by core).
	 * @return string
	 */
	public static function render( $attributes ) {
		if ( ! class_exists( 'WPB_GQB_Quote_Widget_Shortcode' ) ) {
			return '';
		}

		$atts = array(
			'icon_style'           => isset( $attributes['iconStyle'] ) ? $attributes['iconStyle'] : 'receipt',
			'icon_custom_url'      => isset( $attributes['iconCustomUrl'] ) ? $attributes['iconCustomUrl'] : '',
			'icon_custom_id'       => isset( $attributes['iconCustomId'] ) ? absint( $attributes['iconCustomId'] ) : 0,
			'icon_color'           => isset( $attributes['iconColor'] ) ? $attributes['iconColor'] : '',
			'icon_size'            => isset( $attributes['iconSize'] ) ? $attributes['iconSize'] : '',
			'panel_bg_color'       => isset( $attributes['panelBgColor'] ) ? $attributes['panelBgColor'] : '',
			'panel_border_color'   => isset( $attributes['panelBorderColor'] ) ? $attributes['panelBorderColor'] : '',
			'panel_border_radius'  => isset( $attributes['panelBorderRadius'] ) ? $attributes['panelBorderRadius'] : '',
			'panel_max_width'      => isset( $attributes['panelMaxWidth'] ) ? $attributes['panelMaxWidth'] : '',
			'heading_font_size'    => isset( $attributes['headingFontSize'] ) ? $attributes['headingFontSize'] : '',
			'heading_font_weight'  => isset( $attributes['headingFontWeight'] ) ? $attributes['headingFontWeight'] : '',
			'heading_spacing'      => isset( $attributes['headingSpacing'] ) ? $attributes['headingSpacing'] : '',
			'item_name_color'      => isset( $attributes['itemNameColor'] ) ? $attributes['itemNameColor'] : '',
			'item_price_color'     => isset( $attributes['itemPriceColor'] ) ? $attributes['itemPriceColor'] : '',
			'item_name_font_size'  => isset( $attributes['itemNameFontSize'] ) ? $attributes['itemNameFontSize'] : '',
			'item_price_font_size' => isset( $attributes['itemPriceFontSize'] ) ? $attributes['itemPriceFontSize'] : '',
			'item_spacing'         => isset( $attributes['itemSpacing'] ) ? $attributes['itemSpacing'] : '',
			'btn_text'             => isset( $attributes['btnText'] ) ? $attributes['btnText'] : '',
			'btn_bg_color'         => isset( $attributes['btnBgColor'] ) ? $attributes['btnBgColor'] : '',
			'btn_text_color'       => isset( $attributes['btnTextColor'] ) ? $attributes['btnTextColor'] : '',
			'btn_hover_bg_color'   => isset( $attributes['btnHoverBgColor'] ) ? $attributes['btnHoverBgColor'] : '',
			'btn_border_radius'    => isset( $attributes['btnBorderRadius'] ) ? $attributes['btnBorderRadius'] : '',
			'btn_padding'          => isset( $attributes['btnPadding'] ) ? $attributes['btnPadding'] : '',
			'btn_font_size'        => isset( $attributes['btnFontSize'] ) ? $attributes['btnFontSize'] : '',
		);

		$style_overrides = WPB_GQB_Quote_Widget_Shortcode::build_style_overrides( $atts );

		// get_block_wrapper_attributes() only auto-generates the block's own
		// `wp-block-...`/align/spacing classes - it has no idea about
		// `wpb-gqb-quote-widget`, which the widget's CSS (position/hover
		// bridge) and JS (mouseenter/mouseleave/click delegated handlers)
		// all key off of. Without explicitly merging it back in here, the
		// block's wrapper never gets that class, so the hover/click dropdown
		// silently never opens. Merges our per-instance CSS var overrides
		// in the same call and returns one fully-formed, already-escaped
		// attribute string.
		$extra_attributes = array( 'class' => 'wpb-gqb-quote-widget' );

		if ( $style_overrides ) {
			$extra_attributes['style'] = $style_overrides;
		}

		$wrapper_attributes = get_block_wrapper_attributes( $extra_attributes );

		$atts['wrapper_attributes'] = $wrapper_attributes;

		return WPB_GQB_Quote_Widget_Shortcode::render( $atts );
	}
}
new WPB_GQB_Quote_Widget_Block();
