<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // If this file is called directly, abort.
}

class WPB_Widget_Woo_Quote_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'wpb_gqb_quote_widget';
	}

	public function get_title() {
		return esc_html__( 'WPB Quote Widget', 'get-a-quote-button' );
	}

	public function get_icon() {
		return 'eicon-cart-medium';
	}

	public function is_reload_preview_required() {
		return true;
	}

	public function get_categories() {
		return array( 'general' );
	}

	public function get_script_depends() {
		return array( 'wpb-get-a-quote-button-sweetalert2' );
	}

	protected function register_controls() {
		$this->start_controls_section(
			'gqb_quote_widget_settings',
			array(
				'label' => esc_html__( 'Quote Widget Settings', 'get-a-quote-button' ),
			)
		);

		$this->add_control(
			'notice',
			array(
				'type' => \Elementor\Controls_Manager::RAW_HTML,
				'raw'  => esc_html__( 'Displays a small icon with the current quote item count (used when "Quote System Type" is set to Multi-Product in the plugin settings). Hovering/clicking the icon opens a quick-view list of items with a link to the full Quote List page.', 'get-a-quote-button' ),
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		echo do_shortcode( '[wpb-quote-widget]' );
	}
}
