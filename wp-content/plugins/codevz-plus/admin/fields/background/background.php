<?php if ( ! defined( 'ABSPATH' ) ) { die; } // Cannot access pages directly.
/**
 * EDITED BY CODEVZ
 * Field: Background
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 */
if( ! class_exists( 'Codevz_Field_background' ) ) {
  class Codevz_Field_background extends Codevz_Fields {

    public function __construct( $field, $value = '', $unique = '', $where = '' ) {
      parent::__construct( $field, $value, $unique, $where );
    }

    public function output() {

      $value_defaults = array(
        'image'       => '',
        'repeat'      => '',
        'position'    => '',
        'attachment'  => '',
        'size'        => '',
        'color'       => '',
        'color2'      => '',
        'color3'      => '',
        'orientation' => '90deg',
      );

      $this->value  = wp_parse_args( $this->element_value(), $value_defaults );

      if( isset( $this->field['settings'] ) ) { extract( $this->field['settings'] ); }

      $upload_type  = ( isset( $upload_type  ) ) ? $upload_type  : 'image';
      $button_title = ( isset( $button_title ) ) ? $button_title : esc_html__( 'Upload', 'codevz-plus' );
      $frame_title  = ( isset( $frame_title  ) ) ? $frame_title  : esc_html__( 'Upload', 'codevz-plus' );
      $insert_title = ( isset( $insert_title ) ) ? $insert_title : esc_html__( 'Select', 'codevz-plus' );
      $wrap_class   = ( isset( $this->field['wrap_class'] ) ) ? $this->field['wrap_class'] : '';

      echo '<div class="clr mb10">';
      echo '<div class="col s5">';
      codevz_add_field( array(
          'echo'        => true,
          'wrap_class'  => $wrap_class,
          'pseudo'      => true,
          'id'          => $this->field['id'].'_color',
          'type'        => 'color_picker',
          'name'        => $this->element_name('[color]'),
          'title'       => esc_html__( 'Color', 'codevz-plus' ),
          'attributes'  => array(
            'data-atts' => 'bgcolor',
          ),
          'default'     => ( isset( $this->field['default']['color'] ) ) ? $this->field['default']['color'] : '',
          'rgba'        => ( isset( $this->field['rgba'] ) && $this->field['rgba'] === false ) ? false : '',
      ), $this->value['color'], '', 'field/background' );
      echo '</div>';

      echo '<div class="col s7"><div class="codevz-field codevz-field-upload codevz-pseudo-field '.  esc_attr( $wrap_class ) .'">';
      echo '<div class="codevz-title"><h4>' . esc_html__( 'Image', 'codevz-plus' ) . '</h4></div>';
      echo '<div class="codevz-fieldset">';
      echo '<div class="codevz-table-cell"><input type="text" name="'. esc_attr( $this->element_name( '[image]' ) ) .'" value="'. esc_attr( $this->value['image'] ) .'"'. wp_kses_post( (string) $this->element_class() . $this->element_attributes() ) .'/></div>';
      echo '<div class="codevz-table-cell"><a href="#" class="button codevz-button" data-frame-title="'. esc_attr( $frame_title ) .'" data-upload-type="'. esc_attr( $upload_type ) .'" data-insert-title="'. esc_attr( $insert_title ) .'">'. esc_html( $button_title ) .'</a></div>';
      echo '</div></div></div>';

      echo '</div>';

      echo '<div class="clr cz_bg_advanced" style="display:none">';

      echo '<div class="clr cz_hr"></div>';

      // CODEVZ
      echo '<div class="col s5 col_first">';
      codevz_add_field( array(
          'echo'        => true,
          'wrap_class'  => $wrap_class,
          'pseudo'      => true,
          'id'          => $this->field['id'].'_color2',
          'type'        => 'color_picker',
          'name'        => $this->element_name('[color2]'),
          'title'       => esc_html__( 'Color', 'codevz-plus' ) . ' 2',
          'attributes'  => array(
            'data-atts' => 'bgcolor',
          ),
          'default'     => ( isset( $this->field['default']['color2'] ) ) ? $this->field['default']['color2'] : '',
          'rgba'        => ( isset( $this->field['rgba'] ) && $this->field['rgba'] === false ) ? false : '',
          'dependency'  => array( 'background_color', '!=', '' ),
      ), $this->value['color2'], '', 'field/background' );
      echo '</div>';

      echo '<div class="col s5">';
      codevz_add_field( array(
          'echo'        => true,
          'wrap_class'  => $wrap_class,
          'pseudo'      => true,
          'id'          => $this->field['id'].'_color3',
          'type'        => 'color_picker',
          'name'        => $this->element_name('[color3]'),
          'title'       => esc_html__( 'Color', 'codevz-plus' ) . ' 3',
          'attributes'  => array(
            'data-atts' => 'bgcolor',
          ),
          'default'     => ( isset( $this->field['default']['color3'] ) ) ? $this->field['default']['color3'] : '',
          'rgba'        => ( isset( $this->field['rgba'] ) && $this->field['rgba'] === false ) ? false : '',
          'dependency'  => array( 'background_color', '!=', '' ),
      ), $this->value['color3'], '', 'field/background' );
      echo '</div>';

      echo '<div class="col s2" style="width: 16%">';
      codevz_add_field( array(
          'echo'        => true,
          'wrap_class'  => $wrap_class,
          'pseudo'      => true,
          'id'          => $this->field['id'].'_orientation',
          'type'        => 'slider',
          'name'        => $this->element_name('[orientation]'),
          'title'       => '',
          'attributes'  => array(
            'placeholder' => esc_html__( 'Orientation', 'codevz-plus' ),
          ),
          'options'     => array( 'unit' => 'deg', 'step' => 1, 'min' => 0, 'max' => 360 ),
          'default'     => '90deg',
          'rgba'        => ( isset( $this->field['rgba'] ) && $this->field['rgba'] === false ) ? false : '',
          'dependency'  => array( 'background_color', '!=', '' ),
      ), $this->value['orientation'], '', 'field/background' );
      echo '</div>';

      codevz_add_field( array(
          'echo'        => true,
          'type'        => 'content',
          'content'     => '<div class="clr cz_hr"></div>',
          'dependency'  => array( 'background', '!=', '' ),
      ), $this->value['repeat'], '', 'field/background' );

      // background attributes
      echo '<fieldset>';

      codevz_add_field( array(
          'echo'        => true,
          'wrap_class'  => $wrap_class,
          'pseudo'      => true,
          'type'        => 'select',
          'name'        => $this->element_name( '[layer]' ),
          'options'     => array(
            ''  => esc_html__( 'Color on image', 'codevz-plus' ),
            '1' => esc_html__( 'Image on color', 'codevz-plus' ),
          ),
          'attributes'  => array(
            'data-atts' => 'repeat',
          ),
          'dependency' => array( 'background', '!=', '' ),
      ), $this->value['repeat'], '', 'field/background' );

      codevz_add_field( array(
          'echo'        => true,
          'wrap_class'  => $wrap_class,
          'pseudo'      => true,
          'type'        => 'select',
          'name'        => $this->element_name( '[repeat]' ),
          'options'     => array(
            ''          => 'repeat',
            'repeat-x'  => 'repeat-x',
            'repeat-y'  => 'repeat-y',
            'no-repeat' => 'no-repeat',
            'inherit'   => 'inherit',
          ),
          'attributes'  => array(
            'data-atts' => 'repeat',
          ),
          'dependency' => array( 'background', '!=', '' ),
      ), $this->value['repeat'], '', 'field/background' );

      codevz_add_field( array(
          'echo'            => true,
          'wrap_class'      => $wrap_class,
          'pseudo'          => true,
          'type'            => 'select',
          'name'            => $this->element_name( '[position]' ),
          'options'         => array(
            ''              => 'left top',
            'left center'   => 'left center',
            'left bottom'   => 'left bottom',
            'right top'     => 'right top',
            'right center'  => 'right center',
            'right bottom'  => 'right bottom',
            'center top'    => 'center top',
            'center center' => 'center center',
            'center bottom' => 'center bottom'
          ),
          'attributes'      => array(
            'data-atts'     => 'position',
          ),
          'dependency'  => array( 'background', '!=', '' ),
      ), $this->value['position'], '', 'field/background' );

      codevz_add_field( array(
          'echo'        => true,
          'wrap_class'  => $wrap_class,
          'pseudo'      => true,
          'type'        => 'select',
          'name'        => $this->element_name( '[attachment]' ),
          'options'     => array(
            ''          => 'scroll',
            'fixed'     => 'fixed',
          ),
          'attributes'  => array(
            'data-atts' => 'attachment',
          ),
          'dependency'  => array( 'background', '!=', '' ),
      ), $this->value['attachment'], '', 'field/background' );

      codevz_add_field( array(
          'echo'        => true,
          'wrap_class'  => $wrap_class,
          'pseudo'      => true,
          'type'        => 'select',
          'name'        => $this->element_name( '[size]' ),
          'options'     => array(
            ''          => 'size',
            'cover'     => 'cover',
            'contain'   => 'contain',
            'inherit'   => 'inherit',
            'initial'   => 'initial',
          ),
          'attributes'  => array(
            'data-atts' => 'size',
          ),
          'dependency'  => array( 'background', '!=', '' ),
      ), $this->value['size'], '', 'field/background' );

      echo '</fieldset></div>';

      echo '<a class="button cz_advance_bg" href="#">' . esc_html__( 'Advanced', 'codevz-plus' ) . '<i class="fas fa-angle-down"></i></a>';

    }
  }
}
