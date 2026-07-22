<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Classic (Appearance > Widgets / widget-area) wrapper around the
 * [wpb-quote-widget] shortcode, for users on classic widget areas or
 * block themes with "Classic Widgets" support, who don't use Elementor.
 */
class WPB_GQB_Quote_Widget_Classic extends WP_Widget {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			'wpb_gqb_quote_widget',
			esc_html__( 'WPB Quote Widget', 'get-a-quote-button' ),
			array(
				'description'                 => esc_html__( 'Displays a small icon with the current quote item count that opens a quick-view dropdown of items (used when "Quote System Type" is set to Multi-Product in the plugin settings).', 'get-a-quote-button' ),
				'customize_selective_refresh' => true,
			)
		);
	}

	/**
	 * Front-end display.
	 *
	 * @param array $args     Widget area arguments (before/after widget/title).
	 * @param array $instance Saved widget settings.
	 * @return void
	 */
	public function widget( $args, $instance ) {
		if ( ! class_exists( 'WPB_GQB_Quote_Widget_Shortcode' ) ) {
			return;
		}

		$title = ! empty( $instance['title'] ) ? apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base ) : '';

        // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- before/after widget/title come from the theme's register_sidebar() config, not user input (matches core WP_Widget subclasses); the shortcode output is already escaped at render time.
		echo $args['before_widget'];

		if ( $title ) {
			echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
		}

		echo do_shortcode( '[wpb-quote-widget]' );

		echo $args['after_widget'];
        // phpcs:enable
	}

	/**
	 * Admin widget form.
	 *
	 * @param array $instance Saved widget settings.
	 * @return void
	 */
	public function form( $instance ) {
		$title    = isset( $instance['title'] ) ? $instance['title'] : '';
		$is_multi = class_exists( 'WPB_GQB_Quote_List' ) && function_exists( 'WC' ) && function_exists( 'wpb_gqb_get_option' ) && 'multi' === wpb_gqb_get_option( 'wpb_gqb_quote_system_type', 'quote_settings', 'single' );
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title (optional):', 'get-a-quote-button' ); ?></label>
			<input
				class="widefat"
				id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
				type="text"
				value="<?php echo esc_attr( $title ); ?>"
			/>
		</p>
		<?php if ( ! $is_multi ) : ?>
			<p>
				<em><?php esc_html_e( 'This widget only shows on the front end when "Quote System Type" is set to Multi-Product in Settings > Multi-Product Quote.', 'get-a-quote-button' ); ?></em>
			</p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Sanitize widget settings on save.
	 *
	 * @param array $new_instance New settings.
	 * @param array $old_instance Previous settings.
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		$instance          = array();
		$instance['title'] = ! empty( $new_instance['title'] ) ? sanitize_text_field( $new_instance['title'] ) : '';

		return $instance;
	}
}

add_action(
	'widgets_init',
	function () {
		register_widget( 'WPB_GQB_Quote_Widget_Classic' );
	}
);
