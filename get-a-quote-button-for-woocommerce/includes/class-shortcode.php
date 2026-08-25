<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Shortcode Class
 */
class WPB_GQB_Shortcode_Handler {

	/**
	 * Form plugin
	 *
	 * @var string
	 */
	private $form_plugin;

	/**
	 * Constructor
	 */
	public function __construct() {
		add_shortcode( 'wpb-quote-button', array( $this, 'contact_form_button_shortcode' ) );
		add_shortcode( 'wpb-quote-button-hook', array( $this, 'contact_form_button_hook' ) );
		add_shortcode( 'wpb-quote-button-cart-hook', array( $this, 'cart_page_quote_button_hook' ) );

		$this->form_plugin = wpb_gqb_get_option( 'wpb_gqb_form_plugin', 'form_settings', 'wpcf7' );

		if ( 'wpcf7' === $this->form_plugin ) {
			$this->form_plugin = 'wpcf7_contact_form';
		} else {
			// Must run before `wp_enqueue_scripts` (WPForms decides there whether
			// to enqueue its CSS/JS) - the popup form only exists via AJAX, so
			// WPForms never sees a `[wpforms]` shortcode in the page content to
			// trigger that on its own. Adding this filter from inside the
			// shortcode callback itself is too late, since `the_content()` runs
			// after `wp_enqueue_scripts`.
			add_filter( 'wpforms_global_assets', '__return_true' );
		}
	}

	/**
	 * Add WPForms form to footer for conditional logic
	 *
	 * @param int $form_id
	 *
	 * @return string
	 */
	public function get_wpforms_add_form( $form_id ) {
		if ( ! $form_id ) {
			return '';
		}

		if ( ! defined( 'WPFORMS_VERSION' ) ) {
			return '';
		}

		if ( $this->form_plugin !== 'wpforms' ) {
			return '';
		}

		if ( get_post_type( $form_id ) !== 'wpforms' ) {
			return '';
		}

		if ( apply_filters( 'wpb_gqb_wpforms_add_form_to_footer', true ) === false ) {
			return '';
		}

		add_action(
			'wp_footer',
			function () use ( $form_id ) {
				?>
			<div class="wpb-gqb-popup-content" style="display: none!important; visibility: hidden!important; opacity: 0!important;" data-form-id="<?php echo esc_attr( $form_id ); ?>">
				<?php echo do_shortcode( '[wpforms id="' . $form_id . '"]' ); ?>
			</div>
				<?php
			}
		);
	}

	/**
	 * enqueue CF7 Scripts
	 *
	 * @return void
	 */
	public function enqueue_wpcf7_scripts() {
		if ( function_exists( 'wpcf7_enqueue_scripts' ) ) {
			wpcf7_enqueue_scripts();
		}

		if ( function_exists( 'wpcf7_enqueue_styles' ) ) {
			wpcf7_enqueue_styles();
		}
	}

	/**
	 * Shortcode handler for quote button hook
	 *
	 * @param  array  $atts
	 * @param  string $content
	 *
	 * @return string
	 */
	public function contact_form_button_hook( $atts, $content = '' ) {
		$this->enqueue_wpcf7_scripts();
		ob_start();
		echo wp_kses_post( do_action( 'wpb_gqb_quote_button_hook' ) );
		return ob_get_clean();
	}

	/**
	 * Shortcode handler for cart page quote button hook
	 *
	 * @param  array  $atts
	 * @param  string $content
	 *
	 * @return string
	 */
	public function cart_page_quote_button_hook() {
		$this->enqueue_wpcf7_scripts();
		ob_start();
		echo wp_kses_post( do_action( 'wpb_gqb_block_cart_page_hook' ) );
		return ob_get_clean();
	}

	/**
	 * Shortcode handler for quote button
	 *
	 * @param  array  $atts
	 * @param  string $content
	 *
	 * @return string
	 */
	public function contact_form_button_shortcode( $atts, $content = '' ) {
		global $product;

		$matched_variation_ids = array();

		if ( is_array( $atts ) && array_key_exists( 'sid', $atts ) && $atts['sid'] ) {
			$pre_item = WPB_GQB_Quote_Buttons::get_quote_button( $atts['sid'] );

			if ( is_object( $pre_item ) && isset( $pre_item->show_btn ) && 'selected_variations' === $pre_item->show_btn && ! empty( $pre_item->product_variations ) ) {
				$rules = maybe_unserialize( $pre_item->product_variations );

				foreach ( (array) $rules as $rule ) {
					if ( isset( $rule['productId'] ) && (int) $rule['productId'] === (int) get_the_id() ) {
						$matched_variation_ids = isset( $rule['variationIds'] ) ? array_map( 'absint', (array) $rule['variationIds'] ) : array();
						break;
					}
				}

				if ( ! empty( $matched_variation_ids ) ) {
					$atts['variation_match_ids'] = implode( ',', $matched_variation_ids );
				}
			}
		}

		ob_start();
		self::contact_form_button( $atts );
		$content .= ob_get_clean();

		if ( is_array( $atts ) && array_key_exists( 'sid', $atts ) && $atts['sid'] ) {
			$item         = WPB_GQB_Quote_Buttons::get_quote_button( $atts['sid'] );
			$products     = array();
			$product_cats = array();

			// Check is exist before output.
			if ( ! is_object( $item ) || empty( (array) $item ) ) {
				return;
			}

			// Check shortcode status before output.
			if ( isset( $item->shortcode_status ) && $item->shortcode_status !== 'active' ) {
				return;
			}

			if ( isset( $item->show_btn ) && $item->show_btn === 'selected_variations' ) {
				// Button only renders (hidden) for the product(s) this rule targets; JS
				// reveals it once the shopper picks one of the matched variation ids.
				return empty( $matched_variation_ids ) ? '' : $content;
			}

			if ( isset( $item->show_btn ) && $item->show_btn == 'selected_products' ) {

				if ( isset( $item->user_login_status ) && $item->user_login_status != '' ) {
					if ( $item->user_login_status == 'not_logged_in' && is_user_logged_in() ) {
						return '';
					}

					if ( $item->user_login_status == 'logged_in' && ! is_user_logged_in() ) {
						return '';
					}

					if ( $item->user_login_status == 'logged_in' && is_user_logged_in() && $item->the_user_role != '' ) {
						if ( ! current_user_can( $item->the_user_role ) ) {
							return '';
						}
					}
				}

				if ( isset( $item->products ) && $item->products != '' ) {
					$products = maybe_unserialize( $item->products );

					if ( in_array( get_the_id(), $products ) || is_single( $products ) ) {
						return $content;
					}
				}

				if ( isset( $item->product_cats ) && $item->product_cats != '' ) {
					$product_cats = maybe_unserialize( $item->product_cats );

					if ( has_term( $product_cats, 'product_cat' ) ) {

						if ( isset( $item->stock_status ) && $item->stock_status != '' ) {
							if ( $item->stock_status == 'outofstock' && isset( $product ) && $product->get_type() == 'variable' ) {
								return $content;
							} elseif ( get_post_meta( get_the_id(), '_stock_status', true ) == $item->stock_status ) {
								return $content;
							}
						} else {
							return $content;
						}
					}
				}

				if ( isset( $item->product_tags ) && $item->product_tags != '' ) {
					$product_tags = maybe_unserialize( $item->product_tags );

					if ( has_term( $product_tags, 'product_tag' ) ) {
						if ( isset( $item->stock_status ) && $item->stock_status != '' ) {
							if ( $item->stock_status == 'outofstock' && isset( $product ) && $product->get_type() == 'variable' ) {
								return $content;
							} elseif ( get_post_meta( get_the_id(), '_stock_status', true ) == $item->stock_status ) {
								return $content;
							}
						} else {
							return $content;
						}
					}
				}

				if ( isset( $item->featured_products ) && $item->featured_products == 'yes' ) {
					if ( $product->is_featured() ) {
						return $content;
					}
				}

				if ( isset( $item->no_price_products ) && 'yes' === $item->no_price_products ) {
					if ( $product ) {
						if ( $product->get_price() === '' ) {
							if ( $product->is_type( 'variable' ) ) {
								$variations = $product->get_children();
								$has_price  = false;

								foreach ( $variations as $variation_id ) {
									$variation = wc_get_product( $variation_id );
									if ( $variation && $variation->get_price() ) {
										$has_price = true;
										break;
									}
								}

								if ( ! $has_price ) {
									return $content;
								}
							} else {
								return $content;
							}
						}
					}
				}

				if ( isset( $item->product_type ) && $item->product_type != '' ) {
					$product = wc_get_product( get_the_id() );

					if ( ! $product ) {
						return;
					}

					$current_type = $product->get_type();

					// Direct type match
					if ( $current_type == $item->product_type ) {
						return $content;
					}

					// Check simple product sub-types
					if ( $current_type === 'simple' ) {
						if ( ( $item->product_type === 'downloadable' && $product->is_downloadable() ) ||
							( $item->product_type === 'virtual' && $product->is_virtual() )
						) {
							return $content;
						}
					}
				}

				if ( isset( $item->stock_status ) && $item->stock_status != '' ) {

					if ( $item->stock_status == 'outofstock' && isset( $product ) && $product->get_type() == 'variable' ) {
						return $content;
					} elseif ( get_post_meta( get_the_id(), '_stock_status', true ) == $item->stock_status ) {
						return $content;
					}
				}
			} else {
				return $content;
			}
		} else {
			return $content;
		}
	}

	/**
	 * Generic function for displaying the button
	 *
	 * @param  array $args
	 *
	 * @return void
	 */
	public function contact_form_button( $args = array() ) {

		global $product;

		if ( function_exists( 'wpcf7_enqueue_scripts' ) ) {
			wpcf7_enqueue_scripts();
		}

		if ( function_exists( 'wpcf7_enqueue_styles' ) ) {
			wpcf7_enqueue_styles();
		}

		$is_multi_quote = 'multi' === wpb_gqb_get_option( 'wpb_gqb_quote_system_type', 'quote_settings', 'single' );

		$defaults = array(
			'sid'                 => '',
			'id'                  => 'wpcf7_contact_form' === $this->form_plugin ? wpb_gqb_get_option( 'wpb_gqb_cf7_form_id', 'form_settings' ) : wpb_gqb_get_option( 'wpb_gqb_wpforms_form_id', 'form_settings' ),
			'class'               => '',
			'text'                => $is_multi_quote
				? wpb_gqb_get_option( 'wpb_gqb_add_to_quote_btn_text', 'quote_settings', esc_html__( 'Add to Quote', 'get-a-quote-button' ) )
				: wpb_gqb_get_option( 'wpb_gqb_btn_text', 'btn_settings', esc_html__( 'Get a Quote', 'get-a-quote-button' ) ),
			'btn_size'            => wpb_gqb_get_option( 'wpb_gqb_btn_size', 'btn_settings', 'large' ),
			'btn_width'           => wpb_gqb_get_option( 'wpb_gqb_btn_width', 'btn_settings', 'default' ),
			'form_style'          => ( wpb_gqb_get_option( 'wpb_gqb_form_style', 'popup_settings', 'on' ) === 'on' ? true : false ),
			'allow_outside_click' => ( wpb_gqb_get_option( 'wpb_gqb_allow_outside_click', 'popup_settings' ) === 'on' ? true : false ),
			'allow_esc_key'       => ( wpb_gqb_get_option( 'wpb_gqb_allow_esc_key', 'popup_settings' ) === 'on' ? true : false ),
			'width'               => wpb_gqb_get_option( 'wpb_gqb_popup_width', 'popup_settings', '500px' ),
			'elementor'           => 'false',
		);

		if ( is_singular() || is_object( $product ) ) {
			$defaults['post_id'] = get_the_id();
		}

		$args = wp_parse_args( $args, $defaults );

		if ( $args['sid'] ) {
			$item = WPB_GQB_Quote_Buttons::get_quote_button( $args['sid'] );

			if ( ! isset( $item->form_id ) ) {
				return false;
			}

			if ( $args['elementor'] == 'false' ) {

				$multilang_support = wpb_gqb_get_option( 'wpb_gqb_multilang_support', 'form_settings' );

				if ( function_exists( 'icl_object_id' ) && 'on' === $multilang_support ) {

					$btn_text_lan = $item->{'btn_text_' . ICL_LANGUAGE_CODE};
					$form_id_lan  = $item->{'form_id_' . ICL_LANGUAGE_CODE};

					if ( isset( $form_id_lan ) && $form_id_lan != '0' && $form_id_lan != '' ) {
						$args['id'] = $form_id_lan;
					} else {
						$args['id'] = $item->form_id;
					}

					if ( isset( $btn_text_lan ) && $btn_text_lan != '' ) {
						$args['text'] = $btn_text_lan;
					} else {
						$args['text'] = $item->btn_text;
					}
				} else {
					$args['id']   = $item->form_id;
					$args['text'] = $item->btn_text;
				}

				if ( $item->btn_size ) {
					$args['btn_size'] = $item->btn_size;
				}

				if ( is_object( $product ) && $product->get_type() == 'variable' && $item->stock_status == 'outofstock' ) {
					$args['class'] = 'wpb-gqb-variable-show-only-for-outofstock';
				}
			}
		}

		$classes = array(
			$is_multi_quote ? 'wpb-gqb-add-to-quote-btn' : 'wpb-get-a-quote-button-form-fire',
			'wpb-get-a-quote-button-btn',
		);
		if ( isset( $args['btn_size'] ) && $args['btn_size'] !== 'custom' ) {
			$classes[] = 'wpb-get-a-quote-button-btn-default';
		}
		$classes[] = 'wpb-get-a-quote-button-btn-' . esc_attr( $args['btn_size'] );

		if ( isset( $args['btn_width'] ) && 'fullwidth' === $args['btn_width'] ) {
			$classes[] = 'wpb-get-a-quote-button-btn-fullwidth';
		}

		if ( isset( $args['post_id'] ) ) {
			$classes[] = 'wpb-get-a-quote-button-btn-product-id-' . esc_attr( $args['post_id'] );
		}

		$classes[] = 'wpb-get-a-quote-button-btn-form-id-' . esc_attr( $args['id'] );
		$classes[] = 'wpb-get-a-quote-button-variable-gray-out-' . esc_attr( wpb_gqb_get_option( 'wpb_gqb_variable_gray_out', 'woo_settings' ) == 'on' ? 'on' : 'off' );

		if ( $args['sid'] ) {
			$classes[] = 'wpb-get-a-quote-button-btn-shortcode-id-' . esc_attr( $args['sid'] );
		}

		if ( $args['class'] ) {
			$classes[] = $args['class'];
		}

		if ( is_object( $product ) ) {
			if ( $product->get_type() == 'variable' ) {
				$classes[] = 'wpb-gqb-product-type-variable';
			}
		}

		if ( ! empty( $args['variation_match_ids'] ) ) {
			$classes[] = 'wpb-gqb-selected-variation';
		}

		$classes = apply_filters( 'wpb_gqb_button_classes', $classes );

		// Output conditional logic for WPForms Pro
		$this->get_wpforms_add_form( $args['id'] );

		if ( defined( 'WPCF7_PLUGIN' ) || defined( 'WPFORMS_VERSION' ) ) {
			if ( $args['id'] ) {
				echo wp_kses_post(
					apply_filters(
						'wpb_gqb_button_html',
						sprintf(
							'<button data-id="%s" data-post_id="%s" data-form="%s" data-form_style="%s" data-cart-page="%s" data-allow_outside_click="%s" data-allow_esc_key="%s" data-width="%s" data-selected-variations="%s" class="%s">%s</button>',
							esc_attr( $args['id'] ),
							isset( $args['post_id'] ) ? esc_attr( $args['post_id'] ) : '',
							esc_attr( $this->form_plugin ),
							esc_attr( $args['form_style'] ),
							esc_attr( is_cart() ? true : false ),
							esc_attr( $args['allow_outside_click'] ),
							esc_attr( $args['allow_esc_key'] ),
							esc_attr( $args['width'] ),
							esc_attr( $args['variation_match_ids'] ?? '' ),
							implode( ' ', $classes ),
							esc_html( $args['text'] )
						),
						$args
					)
				);
			} else {
				printf(
					'<div class="wpb-get-a-quote-button-alert wpb-get-a-quote-button-alert-inline wpb-get-a-quote-button-alert-error">%s</div>',
					esc_html__( 'Form id required.', 'get-a-quote-button' )
				);
			}
		} else {
			printf(
				'<div class="wpb-get-a-quote-button-alert wpb-get-a-quote-button-alert-inline wpb-get-a-quote-button-alert-error">%s</div>',
				esc_html__( 'Get a Quote Button required the Contact Form 7 or WPForms plugin to work with.', 'get-a-quote-button' )
			);
		}
	}
}
new WPB_GQB_Shortcode_Handler();
