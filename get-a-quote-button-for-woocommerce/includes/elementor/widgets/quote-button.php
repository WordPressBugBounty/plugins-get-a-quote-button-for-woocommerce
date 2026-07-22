<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // If this file is called directly, abort.
}

class WPB_Widget_Woo_Quote_Button extends \Elementor\Widget_Base {

	public function get_name() {
		return 'wpb_gqb_button';
	}

	public function get_title() {
		return esc_html__( 'WPB Quote Button', 'get-a-quote-button' );
	}

	public function get_icon() {
		return 'eicon-button';
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
			'gqb_settings',
			array(
				'label' => esc_html__( 'Quote Button Settings', 'get-a-quote-button' ),
			)
		);

		$this->add_control(
			'form_id',
			array(
				'label'   => esc_html__( 'Select a CF7 Form', 'get-a-quote-button' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => wp_list_pluck(
					get_posts(
						array(
							'post_type'   => 'wpcf7_contact_form',
							'numberposts' => -1,
						)
					),
					'post_title',
					'ID'
				),
			)
		);

		$this->add_control(
			'text',
			array(
				'label'   => esc_html__( 'Button Text', 'get-a-quote-button' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => esc_html__( 'Get a Quote', 'get-a-quote-button' ),
			)
		);

		$this->add_control(
			'btn_size',
			array(
				'label'   => esc_html__( 'Button Size', 'get-a-quote-button' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'medium',
				'options' => array(
					'small'  => esc_html__( 'Small', 'get-a-quote-button' ),
					'medium' => esc_html__( 'Medium', 'get-a-quote-button' ),
					'large'  => esc_html__( 'Large', 'get-a-quote-button' ),
				),
			)
		);

		$this->add_control(
			'form_style',
			array(
				'label'        => esc_html__( 'Enable Form Style', 'get-a-quote-button' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'get-a-quote-button' ),
				'label_off'    => esc_html__( 'No', 'get-a-quote-button' ),
				'return_value' => 'on',
				'default'      => 'on',
			)
		);

		$this->add_control(
			'width',
			array(
				'label'   => esc_html__( 'Form Width (px)', 'get-a-quote-button' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'step'    => 1,
				'default' => 500,
			)
		);

		$this->add_responsive_control(
			'align',
			array(
				'label'        => esc_html__( 'Alignment', 'get-a-quote-button' ),
				'type'         => \Elementor\Controls_Manager::CHOOSE,
				'options'      => array(
					'left'    => array(
						'title' => esc_html__( 'Left', 'get-a-quote-button' ),
						'icon'  => 'eicon-text-align-left',
					),
					'center'  => array(
						'title' => esc_html__( 'Center', 'get-a-quote-button' ),
						'icon'  => 'eicon-text-align-center',
					),
					'right'   => array(
						'title' => esc_html__( 'Right', 'get-a-quote-button' ),
						'icon'  => 'eicon-text-align-right',
					),
					'justify' => array(
						'title' => esc_html__( 'Justified', 'get-a-quote-button' ),
						'icon'  => 'eicon-text-align-justify',
					),
				),
				'prefix_class' => 'elementor%s-align-',
				'default'      => '',
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'gqb_style_settings',
			array(
				'label' => esc_html__( 'Style Settings', 'get-a-quote-button' ),
				'tab'   => \Elementor\Controls_Manager::TAB_SETTINGS,
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'typography',
				'selector' => '{{WRAPPER}} .wpb-get-a-quote-button-btn',
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Text_Shadow::get_type(),
			array(
				'name'     => 'text_shadow',
				'selector' => '{{WRAPPER}} .wpb-get-a-quote-button-btn',
			)
		);

		$this->start_controls_tabs( 'tabs_button_style' );

		$this->start_controls_tab(
			'tab_button_normal',
			array(
				'label' => esc_html__( 'Normal', 'get-a-quote-button' ),
			)
		);

		$this->add_control(
			'button_text_color',
			array(
				'label'     => esc_html__( 'Text Color', 'get-a-quote-button' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => array(
					'{{WRAPPER}} .wpb-get-a-quote-button-btn' => 'fill: {{VALUE}}; color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'background_color',
			array(
				'label'     => esc_html__( 'Background Color', 'get-a-quote-button' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wpb-get-a-quote-button-btn' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_button_hover',
			array(
				'label' => esc_html__( 'Hover', 'get-a-quote-button' ),
			)
		);

		$this->add_control(
			'hover_color',
			array(
				'label'     => esc_html__( 'Text Color', 'get-a-quote-button' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wpb-get-a-quote-button-btn:hover, {{WRAPPER}} .wpb-get-a-quote-button-btn:focus' => 'color: {{VALUE}};',
					'{{WRAPPER}} .wpb-get-a-quote-button-btn:hover svg, {{WRAPPER}} .wpb-get-a-quote-button-btn:focus svg' => 'fill: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'button_background_hover_color',
			array(
				'label'     => esc_html__( 'Background Color', 'get-a-quote-button' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wpb-get-a-quote-button-btn:hover, {{WRAPPER}} .wpb-get-a-quote-button-btn:focus' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'button_hover_border_color',
			array(
				'label'     => esc_html__( 'Border Color', 'get-a-quote-button' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'condition' => array(
					'border_border!' => '',
				),
				'selectors' => array(
					'{{WRAPPER}} .wpb-get-a-quote-button-btn:hover, {{WRAPPER}} .wpb-get-a-quote-button-btn:focus' => 'border-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			array(
				'name'      => 'border',
				'selector'  => '{{WRAPPER}} .wpb-get-a-quote-button-btn',
				'separator' => 'before',
			)
		);

		$this->add_control(
			'border_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'get-a-quote-button' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .wpb-get-a-quote-button-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'button_box_shadow',
				'selector' => '{{WRAPPER}} .wpb-get-a-quote-button-btn',
			)
		);

		$this->add_responsive_control(
			'text_padding',
			array(
				'label'      => esc_html__( 'Padding', 'get-a-quote-button' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .wpb-get-a-quote-button-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
				'separator'  => 'before',
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();

		echo do_shortcode( '[wpb-quote-button elementor="true" id="' . esc_attr( $settings['form_id'] ) . '" text="' . esc_attr( $settings['text'] ) . '" btn_size="' . esc_attr( $settings['btn_size'] ) . '" form_style="' . esc_attr( $settings['form_style'] ) . '" width="' . esc_attr( $settings['width'] ) . '"]' );
	}
}
