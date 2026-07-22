<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * CF7 custom tag generator for WooCommerce product data
 *
 * @since 3.0
 */
class WPB_GQB_CF7_Product_Data_Tag_Generator {

	/**
	 * Classs constructor.
	 */
	public function __construct() {
		add_action( 'wpcf7_admin_init', array( $this, 'cf7_add_tag_generator_product_data' ), 99, 0 );
		add_action( 'admin_enqueue_scripts', array( $this, 'cf7_tag_generator_admin_enqueue_scripts' ) );
	}

	/**
	 * CF7 admin script for tag generator
	 *
	 * @param string $hook_suffix The current admin page.
	 * @return void
	 */
	public function cf7_tag_generator_admin_enqueue_scripts( $hook_suffix ) {
		wp_enqueue_script( 'wpb-gqb-cf7-tag-generator', plugins_url( '../admin/assets/js/wpb-gqb-cf7-tag-generator.js', __FILE__ ), array( 'jquery' ), '1.0', true );
	}

	public function cf7_add_tag_generator_product_data() {
		if ( ! class_exists( 'WPCF7_TagGenerator' ) ) {
			return;
		}

		$tag_generator = WPCF7_TagGenerator::get_instance();

		$tag_generator->add(
			'gqb_product_data',
			esc_html__( 'Quote Product Data', 'get-a-quote-button' ),
			array( $this, 'cf7_tag_generator_product_data' ),
			array( 'version' => '2' )
		);
	}

	public function cf7_tag_generator_product_data( $contact_form, $options ) {
		$field_types = array(
			'gqb_product_data' => array(
				'display_name' => esc_html__( 'Quote Product Data', 'get-a-quote-button' ),
				'heading'      => esc_html__( 'Get a quote button WooCommerce product data form-tag generator', 'get-a-quote-button' ),
				'description'  => esc_html__( 'Choose a product data that you want to send with this contact form.', 'get-a-quote-button' ),
			),
		);

		$tgg = new WPCF7_TagGeneratorGenerator( $options['content'] );
		?>
		<header class="description-box">
			<h3>
				<?php echo esc_html( $field_types['gqb_product_data']['heading'] ); ?>
			</h3>

			<p>
				<?php echo esc_html( $field_types['gqb_product_data']['description'] ); ?>
			</p>
		</header>

		<div class="control-box">
			<fieldset>
				<legend id="tag-generator-panel-gqbproductdata-data-type-legend">
					<?php echo esc_html__( 'Data type', 'get-a-quote-button' ); ?>
				</legend>

				<select name="product_data_type" aria-labelledby="tag-generator-panel-gqbproductdata-data-type-legend">
					<option value="product-id" selected="selected"><?php echo esc_html__( 'Product ID', 'get-a-quote-button' ); ?></option>
					<option value="product-url"><?php echo esc_html__( 'Product URL', 'get-a-quote-button' ); ?></option>
					<option value="product-title"><?php echo esc_html__( 'Product Title', 'get-a-quote-button' ); ?></option>
					<option value="product-description"><?php echo esc_html__( 'Product Description', 'get-a-quote-button' ); ?></option>
					<option value="product-short-description"><?php echo esc_html__( 'Product Short Description', 'get-a-quote-button' ); ?></option>
					<option value="product-sku"><?php echo esc_html__( 'Product SKU', 'get-a-quote-button' ); ?></option>
					<option value="product-price"><?php echo esc_html__( 'Product Price', 'get-a-quote-button' ); ?></option>
					<option value="total-price"><?php echo esc_html__( 'Total Price', 'get-a-quote-button' ); ?></option>
					<option value="product-qty"><?php echo esc_html__( 'Product Quantity', 'get-a-quote-button' ); ?></option>
					<option value="product-status"><?php echo esc_html__( 'Product Status', 'get-a-quote-button' ); ?></option>
					<option value="product-type"><?php echo esc_html__( 'Product Type', 'get-a-quote-button' ); ?></option>
					<option value="product-stock-status"><?php echo esc_html__( 'Product Stock Status', 'get-a-quote-button' ); ?></option>
					<option value="product-stock-quantity"><?php echo esc_html__( 'Product Stock Quantity', 'get-a-quote-button' ); ?></option>
					<option value="product-variations"><?php echo esc_html__( 'Selected Product Variations', 'get-a-quote-button' ); ?></option>
					<option value="product-variations-value-only"><?php echo esc_html__( 'Selected Product Variations Value Only', 'get-a-quote-button' ); ?></option>
					<option value="product-variation-sku"><?php echo esc_html__( 'Product Variation SKU', 'get-a-quote-button' ); ?></option>
					<option value="product-image-url"><?php echo esc_html__( 'Product Image URL', 'get-a-quote-button' ); ?></option>
					<option value="product-data-content"><?php echo esc_html__( 'Full Product Data Summary (all-in-one)', 'get-a-quote-button' ); ?></option>
					<option value="cart-content"><?php echo esc_html__( 'Cart Content (For cart page quote)', 'get-a-quote-button' ); ?></option>
					<?php if ( function_exists( 'wapf_pro' ) || function_exists( 'wapf' ) ) : ?>
						<option value="product-fields"><?php echo esc_html__( 'Product Fields by Studio Wombat', 'get-a-quote-button' ); ?></option>
					<?php endif; ?>
					<?php if ( defined( 'WCPA_FILE' ) ) : ?>
						<option value="wcpa-product-fields"><?php echo esc_html__( 'Product Fields by Acowebs', 'get-a-quote-button' ); ?></option>
					<?php endif; ?>
					<?php do_action( 'wpb_gqb_product_data_type_options' ); ?>
				</select>

			</fieldset>

			<?php
			$tgg->print(
				'field_type',
				array(
					'with_required'  => false,
					'select_options' => array(
						'gqb_product_data' => $field_types['gqb_product_data']['display_name'],
					),
				)
			);

			$tgg->print( 'field_name' );

			$tgg->print( 'class_attr' );
			?>
		</div>

		<footer class="insert-box">
			<?php
			$tgg->print( 'insert_box_content' );

			$tgg->print( 'mail_tag_tip' );
			?>
		</footer>
		<?php
	}
}
new WPB_GQB_CF7_Product_Data_Tag_Generator();
