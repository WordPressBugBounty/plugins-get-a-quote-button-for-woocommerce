<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Elementor Class
 */
class WPB_GQB_Elementor_widgets {

	/**
	 * Class Constructor
	 */
	function __construct() {
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );
	}

	/**
	 * Register Elementor Widgets
	 */
	public function register_widgets( $widgets_manager ) {
		include_once __DIR__ . '/widgets/quote-button.php';
		include_once __DIR__ . '/widgets/quote-hook.php';
		include_once __DIR__ . '/widgets/quote-list.php';
		include_once __DIR__ . '/widgets/quote-widget.php';

		$widgets_manager->register( new WPB_Widget_Woo_Quote_Hook() );
		$widgets_manager->register( new WPB_Widget_Woo_Quote_Button() );
		$widgets_manager->register( new WPB_Widget_Woo_Quote_List() );
		$widgets_manager->register( new WPB_Widget_Woo_Quote_Widget() );
	}
}
new WPB_GQB_Elementor_widgets();
