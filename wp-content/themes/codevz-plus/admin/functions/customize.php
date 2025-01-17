<?php if ( ! defined( 'ABSPATH' ) ) { die; } // Cannot access pages directly.
/**
 *
 * WP Customize custom panel
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 */
if( ! class_exists( 'WP_Customize_Panel_Codevz' ) ) {
  class WP_Customize_Panel_Codevz extends WP_Customize_Panel {
    public $type = 'csf';
  }
}

/**
 *
 * WP Customize custom section
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 */
if( ! class_exists( 'WP_Customize_Section_Codevz' ) ) {
  class WP_Customize_Section_Codevz extends WP_Customize_Section {
    public $type = 'csf';
  }
}

/**
 *
 * WP Customize custom control
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 */
if( ! class_exists( 'WP_Customize_Control_Codevz' ) ) {
  class WP_Customize_Control_Codevz extends WP_Customize_Control {

    public $type   = 'csf';
    public $field  = '';
    public $unique = '';

    public function render_content() {

      $unallows   = array( 'wysiwyg' );
      $field_id   = ( ! empty( $this->field['id'] ) ) ? $this->field['id'] : '';
      $complex    = apply_filters( 'codevz/customize/complex', array( 'checkbox', 'sorter', 'image_select', 'background', 'typography', 'fieldset', 'group', 'repeater', 'tabbed', 'accordion' ) );
      $custom     = ( ! empty( $this->field['customizer'] ) ) ? true : false;
      $is_complex = ( in_array( $this->field['type'], $complex ) ) ? true : false;
      $class      = ( $is_complex || $custom ) ? ' codevz-customize-complex' : '';
      $atts       = ( $is_complex || $custom ) ? ' data-unique-id="'. esc_attr( $this->unique ) .'" data-option-id="'. esc_attr( $field_id ) .'"' : '';

      if( ! $is_complex && ! $custom ) {
        $this->field['attributes']['data-customize-setting-link'] = $this->settings['default']->id;
      }

      $this->field['name'] = $this->settings['default']->id;

      if( in_array( $this->field['type'], $unallows ) ) { $this->field['_notice'] = true; }

      echo '<div class="codevz-customize-field'. esc_attr( $class ) .'"'. do_shortcode( $atts ) .'>';

      $this->field[ 'echo' ] = true;

      codevz_add_field( $this->field, $this->value(), $this->unique, 'customize' );

      echo '</div>';

    }

  }
}
