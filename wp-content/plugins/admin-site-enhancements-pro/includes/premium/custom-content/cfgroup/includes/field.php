<?php

class cfgroup_field
{
    public $name;
    public $label;
    public $options;

    /**
     * Constructor
     * @param object $parent 
     * @since 1.0.5
     */
    function __construct() {
        $this->name = 'text';
        $this->label = __( 'Text', 'admin-site-enhancements' );
    }


    /**
     * Generate the field HTML
     * @param object $field 
     * @since 1.0.5
     */
    function html( $field ) {
    ?>
        <input type="text" name="<?php echo esc_attr( $field->input_name ); ?>" class="<?php echo esc_attr( $field->input_class ); ?>" value="<?php echo esc_attr( $field->value ); ?>" />
    <?php
    }


    /**
     * Generate settings HTML for the field group edit screen
     * @param int $key The unique field identifier
     * @param object $field 
     * @since 1.0.5
     */
    function options_html( $key, $field ) {
    ?>
        <tr class="field_option field_option_<?php echo $this->name; ?>">
            <td class="label validation-label">
                <label><?php _e( 'Validation', 'admin-site-enhancements' ); ?></label>
            </td>
            <td>
                <?php
                    CFG()->create_field( [
                        'type'          => 'true_false',
                        'input_name'    => "cfgroup[fields][$key][options][required]",
                        'input_class'   => 'true_false',
                        'value'         => $this->get_option( $field, 'required' ),
                        'options'       => [ 'message' => __( 'This is a required field', 'admin-site-enhancements' ) ],
                    ] );
                ?>
            </td>
        </tr>
    <?php
    }


    /**
     * Add necessary field scripts or CSS (triggered once per pageload)
     * @param mixed $field The field object (optional)
     * @since 1.0.5
     */
    function input_head( $field = null ) {

    }


    /**
     * Format the value directly after database load
     * 
     * Values are retrieved from the database as an array, even for field types that
     * don't expect arrays. For field types that should return array values, make
     * sure to override this method and return $value.
     * 
     * @param mixed $value 
     * @param mixed $field The field object (optional)
     * @return mixed The field value
     * @since 1.6.9
     */
    function prepare_value( $value, $field = null ) {
        if ( isset( $value[0] ) ) {
            return $value[0];        
        } else {
            return $value;
        }
    }


    /**
     * Format the value for use with $cfgroup->get
     * @param mixed $value 
     * @param mixed $field The field object (optional)
     * @return mixed
     * @since 1.0.5
     */
    function format_value_for_api( $value, $field = null ) {
        return $value;
    }


    /**
     * Format the value for use with HTML input elements
     * @param mixed $value 
     * @param mixed $field The field object (optional)
     * @return mixed
     * @since 1.0.5
     */
    function format_value_for_input( $value, $field = null ) {
        return $value;
    }


    /**
     * Format the value before saving to DB
     * @param mixed $value 
     * @param mixed $field The field object (optional)
     * @return mixed
     * @since 1.4.2
     */
    function pre_save( $value, $field = null ) {
        return $value;
    }


    /**
     * Modify field settings before saving to DB
     * @param object $field
     * @return object
     * @since 1.6.8
     */
    function pre_save_field( $field ) {
        return $field;
    }


    /**
     * Helper method to retrieve a field setting
     * @param object $field 
     * @param string $option_name 
     * @param mixed $default_value 
     * @return mixed
     * @since 1.4.3
     */
    function get_option( $field, $option_name, $default_value = '' ) {
        if ( isset( $field->options[ $option_name ] ) ) {
            if ( is_string( $field->options[ $option_name ] ) ) {
                return esc_attr( $field->options[ $option_name ] );
            }
            return $field->options[ $option_name ];
        }
        return $default_value;
    }
}
