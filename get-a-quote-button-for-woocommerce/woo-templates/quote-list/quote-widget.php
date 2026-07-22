<?php

/**
 * Multi-product quote widget (hover dropdown)
 *
 * This template can be overridden by copying it to
 * yourtheme/get-a-quote-button/quote-list/quote-widget.php.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package Get_A_Quote_Button_Pro\Templates
 * @version 2.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * @var array  $items
 * @var int    $count
 * @var string $quote_url
 * @var string $icon_style         'receipt' | 'bag' | 'list'. Ignored when $use_custom_icon is true.
 * @var bool   $use_custom_icon    Whether to render $icon_custom_markup instead of the built-in glyph.
 * @var string $icon_custom_markup Pre-built, pre-sanitized HTML for the custom icon (<img> or an inlined <svg>), or ''.
 * @var string $btn_text           Overrides the "View Quote List" button text, or ''.
 * @var string $style_overrides    Pre-built `--css-var:value;` string (per-instance overrides), or ''.
 * @var string $wrapper_attributes Pre-built, already-escaped root element attribute string (Gutenberg block only), or ''.
 */

$items              = isset( $items ) ? $items : array();
$count              = isset( $count ) ? absint( $count ) : 0;
$quote_url          = isset( $quote_url ) ? $quote_url : '';
$icon_style         = isset( $icon_style ) ? $icon_style : 'receipt';
$use_custom_icon    = ! empty( $use_custom_icon );
$icon_custom_markup = isset( $icon_custom_markup ) ? $icon_custom_markup : '';
$btn_text_override  = isset( $btn_text ) ? $btn_text : '';
$style_overrides    = isset( $style_overrides ) ? $style_overrides : '';
$wrapper_attributes = isset( $wrapper_attributes ) ? $wrapper_attributes : '';
$show_prices        = wpb_gqb_get_option( 'wpb_gqb_show_prices_on_quote_list', 'quote_settings', 'auto' ) !== 'never';

$text_title        = wpb_gqb_get_option( 'wpb_gqb_quote_widget_title_text', 'quote_settings', __( '{count} items in Quote List', 'get-a-quote-button' ) );
$text_empty        = wpb_gqb_get_option( 'wpb_gqb_quote_widget_empty_text', 'quote_settings', __( 'Your quote list is empty.', 'get-a-quote-button' ) );
$text_view_list    = '' !== $btn_text_override ? $btn_text_override : wpb_gqb_get_option( 'wpb_gqb_quote_widget_view_list_btn_text', 'quote_settings', __( 'View Quote List', 'get-a-quote-button' ) );
$text_remove       = wpb_gqb_get_option( 'wpb_gqb_quote_list_remove_text', 'quote_settings', __( 'Remove', 'get-a-quote-button' ) );
$text_price_on_req = wpb_gqb_get_option( 'wpb_gqb_quote_list_price_on_request_text', 'quote_settings', __( 'Price on request', 'get-a-quote-button' ) );

if ( $wrapper_attributes ) {
	// Gutenberg block: this string already contains the block's own
	// class(es)/inline style - including align/spacing support output and
	// our per-instance CSS var overrides, merged via get_block_wrapper_attributes()
	// in class.quote-widget-block.php - so it's used verbatim.
	$root_attributes = $wrapper_attributes;
} else {
	$root_attributes = 'class="wpb-gqb-quote-widget"';

	if ( $style_overrides ) {
		$root_attributes .= ' style="' . esc_attr( $style_overrides ) . '"';
	}
}

?>
<div <?php echo $root_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- already escaped above (esc_attr()) or by get_block_wrapper_attributes(). ?>>
	<button
		type="button"
		class="wpb-gqb-quote-widget-toggle wpb-gqb-quote-count"
		data-count="<?php echo esc_attr( $count ); ?>"
		aria-haspopup="true"
		aria-expanded="false"
	>
		<?php if ( $use_custom_icon && '' !== $icon_custom_markup ) : ?>
			<?php echo $icon_custom_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-built in WPB_GQB_Quote_Widget_Shortcode::get_custom_icon_markup() (esc_url()'d <img> or wp_kses()'d inline <svg>). ?>
		<?php else : ?>
			<svg class="wpb-gqb-quote-widget-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
				<?php if ( 'bag' === $icon_style ) : ?>
					<path d="M6 8h12l-1 13H7L6 8z"></path>
					<path d="M9 8V6a3 3 0 0 1 6 0v2"></path>
				<?php elseif ( 'list' === $icon_style ) : ?>
					<path d="M8 6h12"></path>
					<path d="M8 12h12"></path>
					<path d="M8 18h12"></path>
					<circle cx="4" cy="6" r="1"></circle>
					<circle cx="4" cy="12" r="1"></circle>
					<circle cx="4" cy="18" r="1"></circle>
				<?php else : ?>
					<path d="M6 3h10l4 4v13a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z"></path>
					<path d="M16 3v4h4"></path>
					<path d="M8 10h8"></path>
					<path d="M8 13.5h8"></path>
					<path d="M8 17h5"></path>
				<?php endif; ?>
			</svg>
		<?php endif; ?>
		<span class="wpb-gqb-quote-count-badge"><?php echo esc_html( $count ); ?></span>
	</button>

	<div class="wpb-gqb-quote-widget-panel">
		<?php if ( empty( $items ) ) : ?>

			<div class="wpb-gqb-quote-widget-empty">
				<p class="wpb-gqb-quote-widget-empty-text"><?php echo esc_html( $text_empty ); ?></p>
			</div>

		<?php else : ?>

			<p class="wpb-gqb-quote-widget-title"><?php echo esc_html( str_replace( '{count}', $count, $text_title ) ); ?></p>

			<div class="wpb-gqb-quote-widget-items">
				<?php foreach ( $items as $item ) : ?>
					<?php
					$product = WPB_GQB_Quote_List::get_item_product( $item );

					if ( ! $product ) {
						continue;
					}

					$link_product    = $product->is_type( 'variation' ) ? wc_get_product( $product->get_parent_id() ) : $product;
					$unit_price      = WPB_GQB_Quote_List::get_item_price( $item );
					$item_show_price = $show_prices && function_exists( 'is_wpb_gqb_show_price_on_quote_list' ) ? is_wpb_gqb_show_price_on_quote_list( $product ) : $show_prices;
					?>
					<div class="wpb-gqb-quote-widget-item" data-key="<?php echo esc_attr( $item['key'] ); ?>">
						<a class="wpb-gqb-quote-widget-item-remove" href="#" data-key="<?php echo esc_attr( $item['key'] ); ?>" aria-label="<?php echo esc_attr( $text_remove ); ?>" title="<?php echo esc_attr( $text_remove ); ?>">&times;</a>

						<div class="wpb-gqb-quote-widget-item-thumb">
							<a href="<?php echo esc_url( $link_product ? $link_product->get_permalink() : '#' ); ?>">
								<?php echo wp_kses_post( $product->get_image( 'thumbnail' ) ); ?>
							</a>
						</div>

						<div class="wpb-gqb-quote-widget-item-body">
							<a class="wpb-gqb-quote-widget-item-name" href="<?php echo esc_url( $link_product ? $link_product->get_permalink() : '#' ); ?>"><?php echo esc_html( $product->get_name() ); ?></a>

							<?php if ( $item_show_price ) : ?>
								<div class="wpb-gqb-quote-widget-item-price"><?php echo wp_kses_post( wc_price( $unit_price ) ); ?></div>
							<?php elseif ( $show_prices ) : ?>
								<div class="wpb-gqb-quote-widget-item-price wpb-gqb-quote-widget-item-price-on-request"><?php echo esc_html( $text_price_on_req ); ?></div>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>

			<?php if ( $quote_url ) : ?>
				<a href="<?php echo esc_url( $quote_url ); ?>" class="wpb-gqb-quote-widget-view-btn"><?php echo esc_html( $text_view_list ); ?></a>
			<?php endif; ?>

		<?php endif; ?>
	</div>
</div>
