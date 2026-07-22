<?php

/**
 * Multi-product quote list
 *
 * This template can be overridden by copying it to
 * yourtheme/get-a-quote-button/quote-list/quote-list.php.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package Get_A_Quote_Button_Pro\Templates
 * @version 2.0.1
 */

defined( 'ABSPATH' ) || exit;

/**
 * @var array      $items
 * @var array       $totals
 * @var int|string $form_id
 * @var string     $form_plugin
 */

$items = isset( $items ) ? $items : array();
// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- template arg extracted by wc_get_template(), not a WP global.
$totals      = isset( $totals ) ? $totals : array(
	'subtotal' => 0,
	'total'    => 0,
);
$show_prices = wpb_gqb_get_option( 'wpb_gqb_show_prices_on_quote_list', 'quote_settings', 'auto' ) !== 'never';

$text_empty            = wpb_gqb_get_option( 'wpb_gqb_quote_list_empty_text', 'quote_settings', __( 'Your quote list is currently empty.', 'get-a-quote-button' ) );
$text_product_label    = wpb_gqb_get_option( 'wpb_gqb_quote_list_product_label', 'quote_settings', __( 'Product', 'get-a-quote-button' ) );
$text_total_label      = wpb_gqb_get_option( 'wpb_gqb_quote_list_total_label', 'quote_settings', __( 'Total', 'get-a-quote-button' ) );
$text_price_on_request = wpb_gqb_get_option( 'wpb_gqb_quote_list_price_on_request_text', 'quote_settings', __( 'Price on request', 'get-a-quote-button' ) );
$text_on_request       = wpb_gqb_get_option( 'wpb_gqb_quote_list_on_request_text', 'quote_settings', __( 'On request', 'get-a-quote-button' ) );
/* translators: %s: amount saved. */
$text_save_badge      = wpb_gqb_get_option( 'wpb_gqb_quote_list_save_badge_text', 'quote_settings', __( 'Save %s', 'get-a-quote-button' ) );
$text_summary_title   = wpb_gqb_get_option( 'wpb_gqb_quote_list_summary_title', 'quote_settings', __( 'Quote Summary', 'get-a-quote-button' ) );
$text_items_label     = wpb_gqb_get_option( 'wpb_gqb_quote_list_items_label', 'quote_settings', __( 'Items', 'get-a-quote-button' ) );
$text_estimated_total = wpb_gqb_get_option( 'wpb_gqb_quote_list_estimated_total_label', 'quote_settings', __( 'Estimated Total', 'get-a-quote-button' ) );
$text_send_btn        = wpb_gqb_get_option( 'wpb_gqb_quote_list_send_btn_text', 'quote_settings', __( 'Send Quote', 'get-a-quote-button' ) );
$text_remove          = wpb_gqb_get_option( 'wpb_gqb_quote_list_remove_text', 'quote_settings', __( 'Remove', 'get-a-quote-button' ) );
$text_decrease_qty    = wpb_gqb_get_option( 'wpb_gqb_quote_list_decrease_qty_label', 'quote_settings', __( 'Decrease quantity', 'get-a-quote-button' ) );
$text_increase_qty    = wpb_gqb_get_option( 'wpb_gqb_quote_list_increase_qty_label', 'quote_settings', __( 'Increase quantity', 'get-a-quote-button' ) );
$text_form_required   = wpb_gqb_get_option( 'wpb_gqb_quote_list_form_required_text', 'quote_settings', __( 'Form id required.', 'get-a-quote-button' ) );
$text_plugin_required = wpb_gqb_get_option( 'wpb_gqb_quote_list_plugin_required_text', 'quote_settings', __( 'Get a Quote Button required the Contact Form 7 or WPForms plugin to work with.', 'get-a-quote-button' ) );
$layout_type          = wpb_gqb_get_option( 'wpb_gqb_quote_list_layout_type', 'quote_settings', 'sidebar' );

do_action( 'wpb_gqb_before_quote_list', $items );
?>

<div class="wpb-gqb-quote-list wpb-gqb-quote-list-layout-<?php echo esc_attr( $layout_type ); ?>">

	<?php if ( empty( $items ) ) : ?>

		<div class="wpb-gqb-quote-list-empty">
			<svg class="wpb-gqb-quote-list-empty-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
				<path d="M6 6h15l-1.5 9h-12z"></path>
				<path d="M6 6L4.5 3H2"></path>
				<circle cx="9.5" cy="19.5" r="1.25"></circle>
				<circle cx="17.5" cy="19.5" r="1.25"></circle>
			</svg>
			<p class="wpb-gqb-quote-list-empty-text"><?php echo esc_html( $text_empty ); ?></p>

			<?php do_action( 'wpb_gqb_quote_list_empty' ); ?>
		</div>

	<?php else : ?>

		<div class="wpb-gqb-quote-list-layout">

			<div class="wpb-gqb-quote-list-main">

				<div class="wpb-gqb-quote-list-heading-row">
					<span class="wpb-gqb-quote-list-heading-product"><?php echo esc_html( $text_product_label ); ?></span>
					<?php if ( $show_prices ) : ?>
						<span class="wpb-gqb-quote-list-heading-total"><?php echo esc_html( $text_total_label ); ?></span>
					<?php endif; ?>
				</div>

				<div class="wpb-gqb-quote-list-items">
					<?php foreach ( $items as $item ) : ?>
						<?php
						$product = WPB_GQB_Quote_List::get_item_product( $item );

						if ( ! $product ) {
							continue;
						}

						$link_product    = $product->is_type( 'variation' ) ? wc_get_product( $product->get_parent_id() ) : $product;
						$unit_price      = WPB_GQB_Quote_List::get_item_price( $item );
						$quantity        = isset( $item['quantity'] ) ? absint( $item['quantity'] ) : 0;
						$line_total      = $unit_price * $quantity;
						$item_show_price = $show_prices && function_exists( 'is_wpb_gqb_show_price_on_quote_list' ) ? is_wpb_gqb_show_price_on_quote_list( $product ) : $show_prices;
						$regular_price   = (float) $product->get_regular_price();
						$is_on_sale      = $item_show_price && $product->is_on_sale() && $regular_price > $unit_price;
						$save_amount     = $is_on_sale ? ( $regular_price - $unit_price ) * $quantity : 0;
						$custom_fields   = array_merge(
							is_array( $item['wapf_fields'] ?? null ) ? $item['wapf_fields'] : array(),
							is_array( $item['wcpa_fields'] ?? null ) ? $item['wcpa_fields'] : array()
						);
						?>
						<div class="wpb-gqb-quote-list-item" data-key="<?php echo esc_attr( $item['key'] ); ?>">
							<div class="wpb-gqb-quote-list-item-thumb">
								<a href="<?php echo esc_url( $link_product ? $link_product->get_permalink() : '#' ); ?>">
									<?php echo wp_kses_post( $product->get_image( 'thumbnail' ) ); ?>
								</a>
							</div>

							<div class="wpb-gqb-quote-list-item-body">
								<a class="wpb-gqb-quote-list-item-name" href="<?php echo esc_url( $link_product ? $link_product->get_permalink() : '#' ); ?>"><?php echo esc_html( $product->get_name() ); ?></a>

								<?php if ( $item_show_price ) : ?>
									<div class="wpb-gqb-quote-list-item-price">
										<?php if ( $is_on_sale ) : ?>
											<?php echo wp_kses_post( wc_format_sale_price( $regular_price, $unit_price ) ); ?>
										<?php else : ?>
											<?php echo wp_kses_post( wc_price( $unit_price ) ); ?>
										<?php endif; ?>
									</div>
								<?php elseif ( $show_prices ) : ?>
									<div class="wpb-gqb-quote-list-item-price wpb-gqb-quote-list-item-price-on-request"><?php echo esc_html( $text_price_on_request ); ?></div>
								<?php endif; ?>

								<?php if ( ! empty( $item['variation'] ) && is_array( $item['variation'] ) ) : ?>
									<div class="wpb-gqb-quote-list-item-meta">
										<?php foreach ( $item['variation'] as $attribute => $value ) : ?>
											<span class="wpb-gqb-quote-list-item-meta-row"><?php echo esc_html( ucfirst( str_replace( array( 'attribute_pa_', 'attribute_', 'pa_' ), '', $attribute ) ) ); ?>: <?php echo esc_html( $value ); ?></span>
										<?php endforeach; ?>
									</div>
								<?php endif; ?>

								<?php if ( ! empty( $custom_fields ) ) : ?>
									<div class="wpb-gqb-quote-list-item-meta">
										<?php foreach ( $custom_fields as $label => $value ) : ?>
											<span class="wpb-gqb-quote-list-item-meta-row"><?php echo esc_html( $label ); ?>: <?php echo esc_html( $value ); ?></span>
										<?php endforeach; ?>
									</div>
								<?php endif; ?>

								<div class="wpb-gqb-quote-list-item-footer">
									<div class="wpb-gqb-quote-list-qty-stepper">
										<button type="button" class="wpb-gqb-quote-list-qty-btn wpb-gqb-quote-list-qty-minus" data-key="<?php echo esc_attr( $item['key'] ); ?>" aria-label="<?php echo esc_attr( $text_decrease_qty ); ?>">&#8722;</button>
										<input
											type="number"
											class="wpb-gqb-quote-list-item-qty"
											min="1"
											step="1"
											inputmode="numeric"
											value="<?php echo esc_attr( $quantity ); ?>"
											data-key="<?php echo esc_attr( $item['key'] ); ?>"
										/>
										<button type="button" class="wpb-gqb-quote-list-qty-btn wpb-gqb-quote-list-qty-plus" data-key="<?php echo esc_attr( $item['key'] ); ?>" aria-label="<?php echo esc_attr( $text_increase_qty ); ?>">&#43;</button>
									</div>

									<a href="#" class="wpb-gqb-quote-list-item-remove" data-key="<?php echo esc_attr( $item['key'] ); ?>" aria-label="<?php echo esc_attr( $text_remove ); ?>" title="<?php echo esc_attr( $text_remove ); ?>">
										<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4 6h16"></path><path d="M9 6V4h6v2"></path><path d="M6 6l1 14h10l1-14"></path><path d="M10 10v7"></path><path d="M14 10v7"></path></svg>
										<span><?php echo esc_html( $text_remove ); ?></span>
									</a>
								</div>
							</div>

							<?php if ( $show_prices ) : ?>
								<div class="wpb-gqb-quote-list-item-total">
									<span class="wpb-gqb-quote-list-col-subtotal"><?php echo $item_show_price ? wp_kses_post( wc_price( $line_total ) ) : esc_html( $text_on_request ); ?></span>
									<?php if ( $save_amount > 0 ) : ?>
										<span class="wpb-gqb-quote-list-save-badge"><?php echo esc_html( str_replace( '%s', wp_strip_all_tags( wc_price( $save_amount ) ), $text_save_badge ) ); ?></span>
									<?php endif; ?>
								</div>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			</div>

			<aside class="wpb-gqb-quote-list-summary">
				<h3 class="wpb-gqb-quote-list-summary-title"><?php echo esc_html( $text_summary_title ); ?></h3>

				<div class="wpb-gqb-quote-list-summary-row">
					<span><?php echo esc_html( $text_items_label ); ?></span>
					<span class="wpb-gqb-quote-list-total-qty"><?php echo esc_html( WPB_GQB_Quote_List::get_items_count() ); ?></span>
				</div>

				<?php if ( $show_prices ) : ?>
					<div class="wpb-gqb-quote-list-summary-row wpb-gqb-quote-list-summary-row-total">
						<span><?php echo esc_html( $text_estimated_total ); ?></span>
						<span class="wpb-gqb-quote-list-total"><?php echo wp_kses_post( wc_price( $totals['total'] ) ); ?></span>
					</div>
				<?php endif; ?>

				<?php do_action( 'wpb_gqb_quote_list_before_send_button', $items, $totals ); ?>

				<?php $display_mode = wpb_gqb_get_option( 'wpb_gqb_quote_list_display_mode', 'quote_settings', 'popup' ); ?>

				<div class="wpb-gqb-quote-list-actions">
					<?php if ( defined( 'WPCF7_PLUGIN' ) || defined( 'WPFORMS_VERSION' ) ) : ?>
						<?php if ( $form_id ) : ?>
							<?php if ( 'inline' === $display_mode ) : ?>
								<div
									class="wpb-gqb-quote-list-inline-form"
									data-id="<?php echo esc_attr( $form_id ); ?>"
									data-form="<?php echo esc_attr( $form_plugin ); ?>"
									data-quote-list="true"
								></div>
							<?php else : ?>
								<button
									type="button"
									data-id="<?php echo esc_attr( $form_id ); ?>"
									data-form="<?php echo esc_attr( $form_plugin ); ?>"
									data-form_style="<?php echo esc_attr( wpb_gqb_get_option( 'wpb_gqb_form_style', 'popup_settings', 'on' ) === 'on' ? 'true' : 'false' ); ?>"
									data-allow_outside_click="<?php echo esc_attr( wpb_gqb_get_option( 'wpb_gqb_allow_outside_click', 'popup_settings' ) === 'on' ? 'true' : 'false' ); ?>"
									data-allow_esc_key="<?php echo esc_attr( wpb_gqb_get_option( 'wpb_gqb_allow_esc_key', 'popup_settings' ) === 'on' ? 'true' : 'false' ); ?>"
									data-width="<?php echo esc_attr( wpb_gqb_get_option( 'wpb_gqb_popup_width', 'popup_settings', '500px' ) ); ?>"
									data-quote-list="true"
									class="wpb-get-a-quote-button-form-fire wpb-get-a-quote-button-btn wpb-get-a-quote-button-btn-default wpb-gqb-send-quote-btn"
								><?php echo esc_html( apply_filters( 'wpb_gqb_send_quote_btn_text', $text_send_btn ) ); ?></button>
							<?php endif; ?>
						<?php else : ?>
							<div class="wpb-get-a-quote-button-alert wpb-get-a-quote-button-alert-inline wpb-get-a-quote-button-alert-error"><?php echo esc_html( $text_form_required ); ?></div>
						<?php endif; ?>
					<?php else : ?>
						<div class="wpb-get-a-quote-button-alert wpb-get-a-quote-button-alert-inline wpb-get-a-quote-button-alert-error"><?php echo esc_html( $text_plugin_required ); ?></div>
					<?php endif; ?>
				</div>
			</aside>

		</div>

	<?php endif; ?>

</div>

<?php do_action( 'wpb_gqb_after_quote_list', $items ); ?>
