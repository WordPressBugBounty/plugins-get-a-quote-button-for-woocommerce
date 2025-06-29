<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WPB_GQB_WeDevs_Settings_API' ) ) :
	/**
	 * The weDevs Settings API wrapper class
	 *
	 * @version 1.4.1 (22-Jun-2025)
	 */
	class WPB_GQB_WeDevs_Settings_API {

		/**
		 * Settings sections array
		 *
		 * @var array
		 */
		protected $settings_sections = array();

		/**
		 * Settings fields array
		 *
		 * @var array
		 */
		protected $settings_fields = array();

		/**
		 * WP kses allowed HTML for the form input
		 *
		 * @return array
		 */
		public function kses_allowed_html() {
			$output = array(
				'input'    => array(
					'type'               => array(),
					'class'              => array(),
					'id'                 => array(),
					'name'               => array(),
					'value'              => array(),
					'placeholder'        => array(),
					'checked'            => array(),
					'min'                => array(),
					'max'                => array(),
					'step'               => array(),
					'data-default-color' => array(),
				),
				'select'   => array(
					'class' => array(),
					'id'    => array(),
					'name'  => array(),
				),
				'option'   => array(
					'value'    => array(),
					'selected' => array(),
				),
				'div'      => array(
					'class' => array(),
				),
				'fieldset' => array(
					'class' => array(),
				),
				'label'    => array(
					'for' => array(),
				),
			);

			$output = array_merge( $output, wp_kses_allowed_html( 'post' ) );

			return $output;
		}

		/**
		 * Enqueue scripts and styles
		 */
		public function admin_enqueue_scripts() {
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_style( 'wpb-gqb-settings', plugins_url( 'assets/css/settings.css', __FILE__ ), array(), '1.0' );

			wp_enqueue_media();
			wp_enqueue_script( 'wp-color-picker' );
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'wpb-gqb-settings', plugins_url( 'assets/js/settings.js', __FILE__ ), array( 'jquery' ), '1.0', true );
		}

		/**
		 * Set settings sections
		 *
		 * @param array $sections setting sections array.
		 */
		public function set_sections( $sections ) {
			$this->settings_sections = $sections;

			return $this;
		}

		/**
		 * Add a single section
		 *
		 * @param array $section setting section.
		 */
		public function add_section( $section ) {
			$this->settings_sections[] = $section;

			return $this;
		}

		/**
		 * Set settings fields
		 *
		 * @param array $fields settings fields array.
		 */
		public function set_fields( $fields ) {
			$this->settings_fields = $fields;

			return $this;
		}

		/**
		 * Add field.
		 *
		 * @param string $section section id.
		 * @param string $field field id.
		 * @return array
		 */
		public function add_field( $section, $field ) {
			$defaults = array(
				'name'  => '',
				'label' => '',
				'desc'  => '',
				'type'  => 'text',
			);

			$arg                                 = wp_parse_args( $field, $defaults );
			$this->settings_fields[ $section ][] = $arg;

			return $this;
		}

		/**
		 * Initialize and registers the settings sections and fileds to WordPress
		 *
		 * Usually this should be called at `admin_init` hook.
		 *
		 * This function gets the initiated settings sections and fields. Then
		 * registers them to WordPress and ready for use.
		 */
		public function admin_init() {
			// register settings sections.
			foreach ( $this->settings_sections as $section ) {
				if ( false === get_option( $section['id'] ) ) {
					add_option( $section['id'] );
				}

				if ( isset( $section['desc'] ) && ! empty( $section['desc'] ) ) {
					$section['desc'] = '<div class="inside">' . $section['desc'] . '</div>';
					$callback        = function () use ( $section ) {
						echo esc_html( str_replace( '"', '\"', $section['desc'] ) );
					};
				} elseif ( isset( $section['callback'] ) ) {
					$callback = $section['callback'];
				} else {
					$callback = null;
				}

				add_settings_section( $section['id'], $section['title'], $callback, $section['id'] );
			}

			// register settings fields.
			foreach ( $this->settings_fields as $section => $field ) {
				foreach ( $field as $option ) {

					$name     = $option['name'];
					$type     = isset( $option['type'] ) ? $option['type'] : 'text';
					$label    = isset( $option['label'] ) ? $option['label'] : '';
					$callback = isset( $option['callback'] ) ? $option['callback'] : array( $this, 'callback_' . $type );

					$args = array(
						'id'                => $name,
						'class'             => isset( $option['class'] ) ? $option['class'] : $name,
						'label_for'         => "{$section}[{$name}]",
						'desc'              => isset( $option['desc'] ) ? $option['desc'] : '',
						'name'              => $label,
						'section'           => $section,
						'size'              => isset( $option['size'] ) ? $option['size'] : null,
						'options'           => isset( $option['options'] ) ? $option['options'] : '',
						'std'               => isset( $option['default'] ) ? $option['default'] : '',
						'default_unit'      => isset( $option['default_unit'] ) ? $option['default_unit'] : '',
						'sanitize_callback' => isset( $option['sanitize_callback'] ) ? $option['sanitize_callback'] : '',
						'type'              => $type,
						'placeholder'       => isset( $option['placeholder'] ) ? $option['placeholder'] : '',
						'min'               => isset( $option['min'] ) ? $option['min'] : '',
						'max'               => isset( $option['max'] ) ? $option['max'] : '',
						'step'              => isset( $option['step'] ) ? $option['step'] : '',
					);

					$pro_label = '';
					if(isset($option['pro']) && $option['pro']) {
						$args['class'] .= ' wpb-gqb-pro-field';
						$pro_label  = '<span class="wpb-gqb-pro-field-label">' . esc_html__( 'Pro', 'get-a-quote-button-for-woocommerce' ) . '</span>';
					}

					add_settings_field( "{$section}[{$name}]", $pro_label . $label, $callback, $section, $section, $args );
				}
			}

			// creates our settings in the options table.
			foreach ( $this->settings_sections as $section ) {
				register_setting( $section['id'], $section['id'], array( $this, 'sanitize_options' ) );
			}
		}

		/**
		 * Get field description for display
		 *
		 * @param array $args settings field args.
		 */
		public function get_field_description( $args ) {
			if ( ! empty( $args['desc'] ) ) {
				$desc = sprintf( '<p class="description">%s</p>', $args['desc'] );
			} else {
				$desc = '';
			}

			return $desc;
		}

		/**
		 * Displays a text field for a settings field
		 *
		 * @param array $args settings field args.
		 */
		public function callback_text( $args ) {

			$value       = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
			$size        = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';
			$type        = isset( $args['type'] ) ? $args['type'] : 'text';
			$placeholder = empty( $args['placeholder'] ) ? '' : ' placeholder="' . $args['placeholder'] . '"';

			$html  = sprintf( '<input type="%1$s" class="%2$s-text" id="%3$s[%4$s]" name="%3$s[%4$s]" value="%5$s"%6$s/>', $type, $size, $args['section'], $args['id'], $value, $placeholder );
			$html .= wp_kses_post( $this->get_field_description( $args ) );

			echo wp_kses( $html, $this->kses_allowed_html() );
		}

		/**
		 * Displays a number and unit field for a settings field
		 *
		 * @param array $args settings field args.
		 */
		public function callback_numberunit( $args ) {

			$value_number = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
			$value_unit   = esc_attr( $this->get_option( $args['id'] . '_unit', $args['section'], $args['default_unit'] ) );
			$size         = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';
			$placeholder  = empty( $args['placeholder'] ) ? '' : ' placeholder="' . $args['placeholder'] . '"';

			$html  = '<div class="wpb-numberunit-field">';
			$html .= sprintf( '<input type="number" class="%1$s-text" id="%2$s[%3$s]" name="%2$s[%3$s]" value="%4$s"%5$s/>', $size, $args['section'], $args['id'], $value_number, $placeholder );

			$html .= sprintf( '<select class="wpb-select-buttons" name="%1$s[%2$s_unit]" id="%1$s[%2$s_unit]">', $args['section'], $args['id'] );

			foreach ( $args['options'] as $key => $label ) {
				$html .= sprintf( '<option value="%s"%s>%s</option>', $key, selected( $value_unit, $key, false ), $label );
			}

			$html .= sprintf( '</select>' );

			$html .= '</div>';
			$html .= wp_kses_post( $this->get_field_description( $args ) );

			echo wp_kses( $html, $this->kses_allowed_html() );
		}

		/**
		 * Displays a speacing field for a settings field
		 *
		 * @param array $args settings field args.
		 */
		public function callback_spacing( $args ) {

			$value_top    = esc_attr( $this->get_option( $args['id'] . '_top', $args['section'], $args['std'] ) );
			$value_right  = esc_attr( $this->get_option( $args['id'] . '_right', $args['section'], $args['std'] ) );
			$value_bottom = esc_attr( $this->get_option( $args['id'] . '_bottom', $args['section'], $args['std'] ) );
			$value_left   = esc_attr( $this->get_option( $args['id'] . '_left', $args['section'], $args['std'] ) );
			$unit         = esc_attr( $this->get_option( $args['id'] . '_unit', $args['section'], $args['std'] ) );
			$size         = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'small';
			$placeholder  = empty( $args['placeholder'] ) ? '' : ' placeholder="' . $args['placeholder'] . '"';

			$html  = '<div class="wpb-spacing-field">';
			$html .= sprintf( '<div class="wpb-spacing-field-group"><label>%6$s</label><input type="number" class="%1$s-text" id="%2$s[%3$s]" name="%2$s[%3$s]" value="%4$s"%5$s/></div>', $size, $args['section'], $args['id'] . '_top', $value_top, $placeholder, esc_html__( 'Top', 'get-a-quote-button-for-woocommerce' ) );
			$html .= sprintf( '<div class="wpb-spacing-field-group"><label>%6$s</label><input type="number" class="%1$s-text" id="%2$s[%3$s]" name="%2$s[%3$s]" value="%4$s"%5$s/></div>', $size, $args['section'], $args['id'] . '_right', $value_right, $placeholder, esc_html__( 'Right', 'get-a-quote-button-for-woocommerce' ) );
			$html .= sprintf( '<div class="wpb-spacing-field-group"><label>%6$s</label><input type="number" class="%1$s-text" id="%2$s[%3$s]" name="%2$s[%3$s]" value="%4$s"%5$s/></div>', $size, $args['section'], $args['id'] . '_bottom', $value_bottom, $placeholder, esc_html__( 'Bottom', 'get-a-quote-button-for-woocommerce' ) );
			$html .= sprintf( '<div class="wpb-spacing-field-group"><label>%6$s</label><input type="number" class="%1$s-text" id="%2$s[%3$s]" name="%2$s[%3$s]" value="%4$s"%5$s/></div>', $size, $args['section'], $args['id'] . '_left', $value_left, $placeholder, esc_html__( 'Left', 'get-a-quote-button-for-woocommerce' ) );
			$html .= sprintf( '<div class="wpb-spacing-field-group"><label>%3$s</label><select class="wpb-select-buttons" name="%1$s[%2$s_unit]" id="%1$s[%2$s_unit]">', $args['section'], $args['id'], esc_html__( 'Unit', 'get-a-quote-button-for-woocommerce' ) );

			foreach ( $args['options'] as $key => $label ) {
				$html .= sprintf( '<option value="%s"%s>%s</option>', $key, selected( $unit, $key, false ), $label );
			}

			$html .= sprintf( '</select></div>' );
			$html .= '</div>';
			$html .= wp_kses_post( $this->get_field_description( $args ) );

			echo wp_kses( $html, $this->kses_allowed_html() );
		}

		/**
		 * Displays a url field for a settings field
		 *
		 * @param array $args settings field args.
		 */
		public function callback_url( $args ) {
			$this->callback_text( $args );
		}

		/**
		 * Displays a number field for a settings field
		 *
		 * @param array $args settings field args.
		 */
		public function callback_number( $args ) {
			$value       = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
			$size        = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';
			$type        = isset( $args['type'] ) ? $args['type'] : 'number';
			$placeholder = empty( $args['placeholder'] ) ? '' : ' placeholder="' . $args['placeholder'] . '"';
			$min         = ( '' === $args['min'] ) ? '' : ' min="' . $args['min'] . '"';
			$max         = ( '' === $args['max'] ) ? '' : ' max="' . $args['max'] . '"';
			$step        = ( '' === $args['step'] ) ? '' : ' step="' . $args['step'] . '"';

			$html  = sprintf( '<input type="%1$s" class="%2$s-number" id="%3$s[%4$s]" name="%3$s[%4$s]" value="%5$s"%6$s%7$s%8$s%9$s/>', $type, $size, $args['section'], $args['id'], $value, $placeholder, $min, $max, $step );
			$html .= wp_kses_post( $this->get_field_description( $args ) );

			echo wp_kses( $html, $this->kses_allowed_html() );
		}

		/**
		 * Displays a checkbox for a settings field
		 *
		 * @param array $args settings field args.
		 */
		public function callback_checkbox( $args ) {

			$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );

			$html  = '<div class="wpb-checkbox-wrapper">';
			$html .= sprintf( '<input type="hidden" name="%1$s[%2$s]" value="off" />', $args['section'], $args['id'] );
			$html .= sprintf('
				<label class="wpb-checkbox-switcher" for="wpuf-%1$s[%2$s]">
					<span class="wpb-checkbox-switch">
						<input type="checkbox" class="checkbox" id="wpuf-%1$s[%2$s]" name="%1$s[%2$s]" value="on" %3$s>
						<span class="wpb-checkbox-slider"></span>
					</span>
				</label>
			', $args['section'], $args['id'], checked( $value, 'on', false ) );
			$html .= sprintf( '<p class="description">%1$s</p>', $args['desc'] );
			$html .= '</div>';

			echo wp_kses( $html, $this->kses_allowed_html() );
		}

		/**
		 * Displays a multicheckbox for a settings field
		 *
		 * @param array $args settings field args.
		 */
		public function callback_multicheck( $args ) {

			$value = $this->get_option( $args['id'], $args['section'], $args['std'] );
			$html  = '<fieldset>';
			$html .= sprintf( '<input type="hidden" name="%1$s[%2$s]" value="" />', $args['section'], $args['id'] );
			foreach ( $args['options'] as $key => $label ) {
				$checked = isset( $value[ $key ] ) ? $value[ $key ] : '0';
				$html   .= sprintf( '<label for="wpuf-%1$s[%2$s][%3$s]">', $args['section'], $args['id'], $key );
				$html   .= sprintf( '<input type="checkbox" class="checkbox" id="wpuf-%1$s[%2$s][%3$s]" name="%1$s[%2$s][%3$s]" value="%3$s" %4$s />', $args['section'], $args['id'], $key, checked( $checked, $key, false ) );
				$html   .= sprintf( '%1$s</label><br>', $label );
			}

			$html .= wp_kses_post( $this->get_field_description( $args ) );
			$html .= '</fieldset>';

			echo wp_kses( $html, $this->kses_allowed_html() );
		}

		/**
		 * Displays a radio button for a settings field
		 *
		 * @param array $args settings field args.
		 */
		public function callback_radio( $args ) {

			$value = $this->get_option( $args['id'], $args['section'], $args['std'] );
			$html  = '<fieldset>';

			foreach ( $args['options'] as $key => $label ) {
				$html .= sprintf( '<label for="wpuf-%1$s[%2$s][%3$s]">', $args['section'], $args['id'], $key );
				$html .= sprintf( '<input type="radio" class="radio" id="wpuf-%1$s[%2$s][%3$s]" name="%1$s[%2$s]" value="%3$s" %4$s />', $args['section'], $args['id'], $key, checked( $value, $key, false ) );
				$html .= sprintf( '%1$s</label><br>', $label );
			}

			$html .= wp_kses_post( $this->get_field_description( $args ) );
			$html .= '</fieldset>';

			echo wp_kses( $html, $this->kses_allowed_html() );
		}

		/**
		 * Displays a selectbox for a settings field
		 *
		 * @param array $args settings field args.
		 */
		public function callback_select( $args ) {

			$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
			$size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';
			$html  = sprintf( '<select class="%1$s" name="%2$s[%3$s]" id="%2$s[%3$s]">', $size, $args['section'], $args['id'] );

			foreach ( $args['options'] as $key => $label ) {
				$html .= sprintf( '<option value="%s"%s>%s</option>', $key, selected( $value, $key, false ), $label );
			}

			$html .= sprintf( '</select>' );
			$html .= wp_kses_post( $this->get_field_description( $args ) );

			echo wp_kses( $html, $this->kses_allowed_html() );
		}

		/**
		 * Displays a textarea for a settings field
		 *
		 * @param array $args settings field args.
		 */
		public function callback_textarea( $args ) {

			$value       = esc_textarea( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
			$size        = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';
			$placeholder = empty( $args['placeholder'] ) ? '' : ' placeholder="' . $args['placeholder'] . '"';

			$html  = sprintf( '<textarea rows="5" cols="55" class="%1$s-text" id="%2$s[%3$s]" name="%2$s[%3$s]"%4$s>%5$s</textarea>', $size, $args['section'], $args['id'], $placeholder, $value );
			$html .= wp_kses_post( $this->get_field_description( $args ) );

			echo wp_kses( $html, $this->kses_allowed_html() );
		}

		/**
		 * Displays the html for a settings field
		 *
		 * @param array $args settings field args.
		 * @return void
		 */
		public function callback_html( $args ) {
			echo wp_kses_post( $this->get_field_description( $args ) );
		}

		/**
		 * Displays a rich text textarea for a settings field
		 *
		 * @param array $args settings field args.
		 */
		public function callback_wysiwyg( $args ) {

			$value = $this->get_option( $args['id'], $args['section'], $args['std'] );
			$size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : '500px';

			echo '<div style="max-width: ' . esc_html( $size ) . ';">';

			$editor_settings = array(
				'teeny'         => true,
				'textarea_name' => $args['section'] . '[' . $args['id'] . ']',
				'textarea_rows' => 10,
			);

			if ( isset( $args['options'] ) && is_array( $args['options'] ) ) {
				$editor_settings = array_merge( $editor_settings, $args['options'] );
			}

			wp_editor( $value, $args['section'] . '-' . $args['id'], $editor_settings );

			echo '</div>';

			echo wp_kses_post( $this->get_field_description( $args ) );
		}

		/**
		 * Displays a file upload field for a settings field
		 *
		 * @param array $args settings field args.
		 */
		public function callback_file( $args ) {

			$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
			$size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';
			$id    = $args['section'] . '[' . $args['id'] . ']';
			$label = isset( $args['options']['button_label'] ) ? $args['options']['button_label'] : esc_html__( 'Choose File', 'get-a-quote-button-for-woocommerce' );

			$html  = sprintf( '<input type="text" class="%1$s-text wpsa-url" id="%2$s[%3$s]" name="%2$s[%3$s]" value="%4$s"/>', $size, $args['section'], $args['id'], $value );
			$html .= '<input type="button" class="button wpsa-browse" value="' . $label . '" />';
			$html .= wp_kses_post( $this->get_field_description( $args ) );

			echo wp_kses( $html, $this->kses_allowed_html() );
		}

		/**
		 * Displays a password field for a settings field
		 *
		 * @param array $args settings field args.
		 */
		public function callback_password( $args ) {

			$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
			$size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';

			$html  = sprintf( '<input type="password" class="%1$s-text" id="%2$s[%3$s]" name="%2$s[%3$s]" value="%4$s"/>', $size, $args['section'], $args['id'], $value );
			$html .= wp_kses_post( $this->get_field_description( $args ) );

			echo wp_kses( $html, $this->kses_allowed_html() );
		}

		/**
		 * Displays a color picker field for a settings field
		 *
		 * @param array $args settings field args.
		 */
		public function callback_color( $args ) {

			$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
			$size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';

			$html  = sprintf( '<input type="text" class="%1$s-text wp-color-picker-field" id="%2$s[%3$s]" name="%2$s[%3$s]" value="%4$s" data-default-color="%5$s" />', $size, $args['section'], $args['id'], $value, $args['std'] );
			$html .= wp_kses_post( $this->get_field_description( $args ) );

			echo wp_kses( $html, $this->kses_allowed_html() );
		}

		/**
		 * Displays a select box for creating the pages select box
		 *
		 * @param array $args settings field args.
		 */
		public function callback_pages( $args ) {

			$dropdown_args = array(
				'selected' => esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) ),
				'name'     => $args['section'] . '[' . $args['id'] . ']',
				'id'       => $args['section'] . '[' . $args['id'] . ']',
				'echo'     => 0,
			);

			echo wp_kses( wp_dropdown_pages( $dropdown_args ), $this->kses_allowed_html() );
		}

		/**
		 * Sanitize callback for Settings API
		 *
		 * @param array $options settings options to sanitize.
		 * @return mixed
		 */
		public function sanitize_options( $options ) {

			if ( ! $options ) {
				return $options;
			}

			foreach ( $options as $option_slug => $option_value ) {
				$sanitize_callback = $this->get_sanitize_callback( $option_slug );

				// If callback is set, call it.
				if ( $sanitize_callback ) {
					$options[ $option_slug ] = call_user_func( $sanitize_callback, $option_value );
					continue;
				}
			}

			return $options;
		}

		/**
		 * Get sanitization callback for given option slug
		 *
		 * @param string $slug option slug.
		 *
		 * @return mixed string or bool false.
		 */
		public function get_sanitize_callback( $slug = '' ) {
			if ( empty( $slug ) ) {
				return false;
			}

			// Iterate over registered fields and see if we can find proper callback.
			foreach ( $this->settings_fields as $section => $options ) {
				foreach ( $options as $option ) {
					if ( $option['name'] !== $slug ) {
						continue;
					}

					// Return the callback name.
					return isset( $option['sanitize_callback'] ) && is_callable( $option['sanitize_callback'] ) ? $option['sanitize_callback'] : false;
				}
			}

			return false;
		}

		/**
		 * Get the value of a settings field
		 *
		 * @param string $option  settings field name.
		 * @param string $section the section name this field belongs to.
		 * @param string $default_value default text if it's not found.
		 * @return string
		 */
		public function get_option( $option, $section, $default_value = '' ) {

			$options = get_option( $section );

			if ( isset( $options[ $option ] ) ) {
				return $options[ $option ];
			}

			return $default_value;
		}

		/**
		 * Show navigations as tab
		 *
		 * Shows all the settings section labels as tab
		 */
		public function show_navigation() {
			$html = '<h2 class="nav-tab-wrapper">';

			$count = count( $this->settings_sections );

			// don't show the navigation if only one section exists.
			if ( 1 === $count ) {
				return;
			}

			foreach ( $this->settings_sections as $tab ) {
				$html .= sprintf( 
					'<a href="#%1$s" class="nav-tab" id="%1$s-tab">%2$s %3$s</a>', 
					esc_attr( $tab['id'] ), 
					esc_html( $tab['title'] ),
	  				isset( $tab['pro'] ) && $tab['pro'] ? '<span class="wpb-pro-label">' . esc_html__( 'Pro', 'get-a-quote-button-for-woocommerce' ) . '</span>' : ''
				);
			}

			$html .= '</h2>';

			echo wp_kses_post( $html );
		}

		/**
		 * Show the section settings forms
		 *
		 * This function displays every sections in a different form
		 */
		public function show_forms() {
			?>
			<div class="metabox-holder wpb-plugin-settings">
				<?php foreach ( $this->settings_sections as $form ) { ?>
					<div id="<?php echo esc_attr( $form['id'] ); ?>" class="group<?php echo esc_attr( isset($form['pro']) && $form['pro'] ? ' wpb-pro-only' : '' ); ?>" style="display: none;">
						<form method="post" action="options.php">
							<?php
							do_action( 'wsa_form_top_' . $form['id'], $form );
							settings_fields( $form['id'] );
							do_settings_sections( $form['id'] );
							do_action( 'wsa_form_bottom_' . $form['id'], $form );
							if ( isset( $this->settings_fields[ $form['id'] ] ) ) :
								?>
							<div class="wpb-submit-button">
								<?php submit_button(); ?>
							</div>
							<?php endif; ?>
						</form>
					</div>
				<?php } ?>
			</div>
			<?php
		}
	}

endif;