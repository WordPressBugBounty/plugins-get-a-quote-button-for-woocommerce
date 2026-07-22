<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WooCommerce Product Meta Class
 */
class WPB_GQB_WC_Product_Meta {

	/**
	 * Constructor
	 */
	function __construct() {
		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'add_meta_fields' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'save_product_meta' ), 10, 2 );
	}

	/**
	 * Add meta box to the WooCommerce product
	 */
	public function add_meta_fields() {
		?>
		<div class="options_group">
			<?php
			woocommerce_wp_checkbox(
				array(
					'id'            => '_wpb_gqb_disable',
					'wrapper_class' => 'show_if_simple show_if_variable WPB_GQB_disable',
					'label'         => esc_html__( 'Disable Quote Button?', 'get-a-quote-button' ),
					'description'   => esc_html__( 'Disable quote button for this product', 'get-a-quote-button' ),
				)
			);
			?>
		</div>
		<?php
	}

	/**
	 * Save meta box data.
	 *
	 * @param int     $post_id WP post id.
	 * @param WP_Post $post Post object.
	 */
	public function save_product_meta( $post_id, $post ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce verifies the product meta nonce before firing this hook.
		$wpb_gqb_disable = isset( $_POST['_wpb_gqb_disable'] ) ? 'yes' : 'no';
		update_post_meta( $post_id, '_wpb_gqb_disable', $wpb_gqb_disable );
	}
}
new WPB_GQB_WC_Product_Meta();
