<?php

class cfgroup_color extends cfgroup_field
{

    function __construct() {
        $this->name = 'color';
        $this->label = __( 'Color', 'admin-site-enhancements' );
    }


    function options_html( $key, $field ) {
    ?>
        <tr class="field_option field_option_<?php echo $this->name; ?>">
            <td class="label">
                <label><?php _e( 'Default Value', 'admin-site-enhancements' ); ?></label>
            </td>
            <td>
                <?php
                    CFG()->create_field( [
                        'type' => 'text',
                        'input_name' => "cfgroup[fields][$key][options][default_value]",
                        'value' => $this->get_option( $field, 'default_value' ),
                    ] );
                ?>
            </td>
        </tr>
    <?php
    }


    function input_head( $field = null ) {
        wp_register_script( 'miniColors', CFG_URL . '/includes/fields/color/jquery.miniColors.min.js' );
        wp_enqueue_script( 'miniColors' );
    ?>
        <link rel="stylesheet" type="text/css" href="<?php echo CFG_URL; ?>/includes/fields/color/color.css" />
        <script>
        (function($) {
            $(document).on('focus', '.cfgroup_color input.color', function() {
                if (!$(this).hasClass('ready')) {
                    $(this).addClass('ready').minicolors();
                }
            });

            $(function() {
                $('.cfgroup_color input.color').addClass('ready').minicolors();
            });
        })(jQuery);
        </script>
    <?php
    }
}
