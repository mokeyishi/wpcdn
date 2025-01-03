<?php

class cfgroup_form
{

    public $used_types;
    public $assets_loaded;
    public $session;


    public function __construct() {
        $this->used_types = [];
        $this->assets_loaded = false;

        add_action( 'init', [ $this, 'init' ], 100 );
        add_action( 'admin_head', [ $this, 'head_scripts' ] );
        add_action( 'admin_print_footer_scripts', [ $this, 'footer_scripts' ] );
        add_action( 'admin_notices', [ $this, 'admin_notice' ] );
    }


    /**
     * Initialize the session and save the form
     * @since 1.8.5
     */
    public function init() {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            return;
        }

        if ( isset( $_POST['wp-preview'] ) && 'dopreview' == $_POST['wp-preview'] ) {
            return;
        }

        $this->session = new cfgroup_session();

        // Save the form
        if ( isset( $_POST['cfgroup']['save'] ) ) {
            if ( wp_verify_nonce( $_POST['cfgroup']['save'], 'cfgroup_save_input' ) ) {
                $session = $this->session->get();

                if ( empty( $session ) ) {
                    die( 'Your session has expired.' );
                }

                $field_data = isset( $_POST['cfgroup']['input'] ) ? $_POST['cfgroup']['input'] : [];
                $post_data = [];

                // Form settings are session-based for added security
                $post_id = (int) $session['post_id'];
                $field_groups = isset( $session['field_groups'] ) ? $session['field_groups'] : [];

                // Sanitize field groups
                foreach ( $field_groups as $key => $val ) {
                    $field_groups[$key] = (int) $val;
                }

                // Title
                if ( isset( $_POST['cfgroup']['post_title'] ) ) {
                    $post_data['post_title'] = stripslashes( $_POST['cfgroup']['post_title'] );
                }

                // Content
                if ( isset( $_POST['cfgroup']['post_content'] ) ) {
                    $post_data['post_content'] = stripslashes( $_POST['cfgroup']['post_content'] );
                }

                // New posts
                if ( $post_id < 1 ) {
                    // Post type
                    if ( isset( $session['post_type'] ) ) {
                        $post_data['post_type'] = $session['post_type'];
                    }

                    // Post status
                    if ( isset( $session['post_status'] ) ) {
                        $post_data['post_status'] = $session['post_status'];
                    }
                }
                else {
                    $post_data['ID'] = $post_id;
                }

                $options = [
                    'format'        => 'input',
                    'field_groups'  => $field_groups
                ];

                // Hook parameters
                $hook_params = [
                    'field_data'    => $field_data,
                    'post_data'     => $post_data,
                    'options'       => $options,
                ];

                // Pre-save hook
                do_action( 'cfgroup_pre_save_input', $hook_params );

                // Save the input values
                $hook_params['post_data']['ID'] = CFG()->save(
                    $field_data,
                    $post_data,
                    $options
                );

                // After-save hook
                do_action( 'cfgroup_after_save_input', $hook_params );

                // Delete expired sessions
                $this->session->cleanup();

                // Redirect public forms
                if ( true === $session['front_end'] ) {
                    $redirect_url = $_SERVER['REQUEST_URI'];
                    if ( ! empty( $session['confirmation_url'] ) ) {
                        $redirect_url = $session['confirmation_url'];
                    }

                    header( 'Location: ' . $redirect_url );
                    exit;
                }
            }
        }
    }


    /**
     * Load form dependencies
     * @since 1.8.5
     */
    public function load_assets() {
        if ( $this->assets_loaded ) {
            return;
        }

        $this->assets_loaded = true;

        add_action( 'wp_head', [ $this, 'head_scripts' ] );
        add_action( 'wp_footer', [ $this, 'footer_scripts' ], 25 );

        // We force loading the uncompressed version of TinyMCE. This ensures we load 'wp-tinymce-root' and then 'wp-tinymce', 
        // which prevents issue where the TinyMCE editor is unusable in some scenarios
        $wp_scripts = wp_scripts();
        $wp_scripts->remove( 'wp-tinymce' );
        wp_register_tinymce_scripts( $wp_scripts, true );

        wp_enqueue_script( 'jquery-ui-core' );
        wp_enqueue_script( 'jquery-ui-sortable' );
        wp_enqueue_script( 'cfgroup-validation', CFG_URL . '/assets/js/validation.js', [ 'jquery' ], CFG_VERSION );
        wp_enqueue_script( 'jquery-powertip', CFG_URL . '/assets/js/jquery-powertip/jquery.powertip.min.js', [ 'jquery' ], CFG_VERSION );
        wp_enqueue_style( 'jquery-powertip', CFG_URL . '/assets/js/jquery-powertip/jquery.powertip.css', [], CFG_VERSION );
        wp_enqueue_style( 'cfgroup-input', CFG_URL . '/assets/css/input.css', [], CFG_VERSION );
    }


    /**
     * Handle front-end validation
     * @since 1.8.8
     */
    function head_scripts() {
    ?>

<script>
var CFG = CFG || {};
CFG['get_field_value'] = {};
CFG['repeater_buffer'] = [];
</script>

    <?php
    }


    /**
     * Allow for custom client-side validators
     * @since 1.9.5
     */
    function footer_scripts() {
        do_action( 'cfgroup_custom_validation' );
    }


    /**
     * Add an admin notice to be displayed in the event of
     * validation errors
     * @since 2.6
     */
    function admin_notice() {
        $screen = get_current_screen();

        if ( !isset($screen->base) || $screen->base !== 'post' ) {
            return;
        }

        echo '<div class="notice notice-error" id="cfgroup-validation-admin-notice" style="display: none;"><p><strong>';
        echo __( 'One (or more) of your fields had validation errors. More information is available below.', 'admin-site-enhancements' );
        echo '</strong></p></div>';
    }


    /**
     * Render the HTML input form
     * @param array $params
     * @return string form HTML code
     * @since 1.8.5
     */
    public function render( $params ) {
        global $post;

        $defaults = [
            'post_id'               => false, // false = new entries
            'field_groups'          => [], // group IDs, required for new entries
            'post_title'            => false,
            'post_content'          => false,
            'post_status'           => 'draft',
            'post_type'             => 'post',
            'excluded_fields'       => [],
            'confirmation_message'  => '',
            'confirmation_url'      => '',
            'submit_label'          => __( 'Submit', 'admin-site-enhancements' ),
            'front_end'             => true,
        ];

        $params = array_merge( $defaults, $params );
        // vi( $params );
        $input_fields = [];

        // Keep track of field validators
        CFG()->validators = [];

        $post_id = (int) $params['post_id'];

        if ( 0 < $post_id ) {
            $post = get_post( $post_id );
        }

        if ( empty( $params['field_groups'] ) ) {
            $field_groups = CFG()->api->get_matching_groups( $post_id, true );
            $field_groups = array_keys( $field_groups );
        }
        else {
            $field_groups = $params['field_groups'];
        }

        if ( ! empty( $field_groups ) ) {
            $input_fields = CFG()->api->get_input_fields( [
                'group_id' => $field_groups
            ] );
        }

        // Hook to allow for overridden field settings
        $input_fields = apply_filters( 'cfgroup_pre_render_fields', $input_fields, $params );

        // vi( $input_fields );

        // The SESSION should contain all applicable field group IDs. Since add_meta_box only
        // passes 1 field group at a time, we use CFG()->group_ids from admin_head.php
        // to store all group IDs needed for the SESSION.
        $all_group_ids = ( false === $params['front_end'] ) ? CFG()->group_ids : $field_groups;

        $session_data = [
            'post_id'               => $post_id,
            'post_type'             => $params['post_type'],
            'post_status'           => $params['post_status'],
            'field_groups'          => $all_group_ids,
            'confirmation_message'  => $params['confirmation_message'],
            'confirmation_url'      => $params['confirmation_url'],
            'front_end'             => $params['front_end'],
        ];

        // Set the SESSION
        $this->session->set( $session_data );

        if ( false !== $params['front_end'] ) {
        ?>

            <div class="cfgroup_input no_box">
                <form id="post" method="post" action="">

                    <?php
                    }

                    if ( false !== $params['post_title'] ) {
                    ?>

                    <div class="field" data-validator="required">
                        <label><?php echo $params['post_title']; ?></label>
                        <input type="text" name="cfgroup[post_title]" value="<?php echo empty( $post_id ) ? '' : esc_attr( $post->post_title ); ?>" />
                    </div>

                    <?php
                    }

                    if ( false !== $params['post_content'] ) {
                    ?>

                    <div class="field">
                        <label><?php echo $params['post_content']; ?></label>
                        <textarea name="cfgroup[post_content]"><?php echo empty( $post_id ) ? '' : esc_textarea( $post->post_content ); ?></textarea>
                    </div>

                    <?php
                    }
                    
                    $is_first_field = false;

                    // Detect tabs
                    $tabs = [];
                    $is_first_tab = true;
                    foreach ( $input_fields as $key => $field ) {
                        if ( 'tab' == $field->type ) {
                            $tabs[] = $field;
                        }
                    }
                                        
                    do_action( 'cfgroup_form_before_fields', $params, [
                        'group_ids'     => $all_group_ids,
                        'input_fields'  => $input_fields
                    ] );
                    
                    if ( empty( $tabs ) ) {
                        echo '<div class="fields-wrapper">';                        
                    }
                    
                    // vi( $input_fields );

                    // Add any necessary head scripts
                    foreach ( $input_fields as $key => $field ) {
                        
                        // Exclude fields
                        if ( in_array( $field->name, (array) $params['excluded_fields'] ) ) {
                            continue;
                        }

                        // Skip missing field types
                        if ( ! isset( CFG()->fields[ $field->type ] ) ) {
                            continue;
                        }

                        // Output tabs
                        if ( 'tab' == $field->type && $is_first_tab ) {
                            echo '<div class="cfgroup-tabs">';
                            foreach ( $tabs as $key => $tab ) {
                                echo '<div class="cfgroup-tab" rel="' . $tab->name . '">' . $tab->label . '</div>';
                            }
                            echo '</div>';
                            $is_first_tab = false;
                        }

                        // Keep track of active field types
                        if ( ! isset( $this->used_types[ $field->type ] ) ) {
                            CFG()->fields[ $field->type ]->input_head( $field );
                            $this->used_types[ $field->type ] = true;
                        }

                        $validator = '';

                        if ( in_array( $field->type, [ 'relationship', 'user', 'repeater' ] ) ) {
                            $min = empty( $field->options['limit_min'] ) ? 0 : (int) $field->options['limit_min'];
                            $max = empty( $field->options['limit_max'] ) ? 0 : (int) $field->options['limit_max'];
                            $validator = "limit|$min,$max";
                        }

                        if ( isset( $field->options['required'] ) && 0 < (int) $field->options['required'] ) {
                            if ( 'date' == $field->type ) {
                                $validator = 'valid_date';
                            }
                            elseif ( 'color' == $field->type ) {
                                $validator = 'valid_color';
                            }
                            else {
                                $validator = 'required';
                            }
                        }

                        if ( ! empty( $validator ) ) {
                            CFG()->validators[ $field->name ] = [
                                'rule'  => $validator,
                                'type'  => $field->type
                            ];
                        }

                        // Ignore sub-fields
                        if ( 1 > (int) $field->parent_id ) {

                            // Tab handling
                            if ( 'tab' == $field->type ) {

                                // Close the previous tab
                                if ( $field->name != $tabs[0]->name ) {
                                    echo '</div>'; // Close .fields-wrapper
                                    echo '</div>'; // Close previous tab
                                }
                                echo '<div class="cfgroup-tab-content cfgroup-tab-content-' . esc_attr( $field->name ) . '">';

            					if ( ! empty( $field->notes ) ) {
            						echo '<div class="cfgroup-tab-notes">' . esc_html( $field->notes ) . '</div>';
            					}

                                echo '<div class="fields-wrapper">';
                            } 
                            // Render fields other than tabs
                            else {
                                switch ( $field->type ) {
                                    case 'line_break';
                                        $additional_classes = ' row-line-break';
                                        break;

                                    case 'heading';
                                        $additional_classes = ' row-heading';
                                        break;
                                        
                                    default:
                                        $additional_classes = '';
                                }

                                ?>

                                <div class="field-column-<?php echo $field->column_width; ?><?php echo esc_attr( $additional_classes ); ?>">
                                    <div class="field field-<?php echo esc_attr( $field->name ); ?>" data-type="<?php echo esc_attr( $field->type ); ?>" data-name="<?php echo esc_attr( $field->name ); ?>">
                                        <?php if ( 'repeater' == $field->type ) : ?>
                                        <a href="javascript:;" class="cfgroup_repeater_toggle" title="<?php esc_html_e( 'Toggle row visibility', 'admin-site-enhancements' ); ?>"></a>
                                        <?php endif; ?>

                                        <?php if ( ! empty( $field->label ) && 'line_break' != $field->type ) : ?>
                                        <label class="field-label"><?php echo esc_html( $field->label ); ?></label>
                                        <?php endif; ?>

                                        <?php if ( ! empty( $field->notes ) ) : ?>
                                        <p class="notes"><?php echo esc_html( $field->notes ); ?></p>
                                        <?php endif; ?>

                                        <div class="cfgroup_<?php echo esc_attr( $field->type ); ?>">

                                            <?php
                                            CFG()->create_field( [
                                                'id'            => $field->id,
                                                'group_id'      => $field->group_id,
                                                'type'          => $field->type,
                                                'input_name'    => "cfgroup[input][$field->id][value]",
                                                'input_class'   => $field->type,
                                                'options'       => $field->options,
                                                'value'         => $field->value,
                                                'notes'         => $field->notes,
                                                'column_width'  => $field->column_width,
                                            ] );
                                            ?>

                                        </div>
                                    </div>
                                </div>

                                <?php

                            }
                                                        
                        }
                    }

                    // Make sure to close tabs
                    if ( ! empty( $tabs ) ) {
                        echo '</div>'; // Close .fields-wrapper
                        echo '</div>'; // Close tabs
                    } else {
                        echo '</div>'; // Close .fields-wrapper                                                
                    }
                    
                    do_action( 'cfgroup_form_after_fields', $params, [
                        'group_ids'     => $all_group_ids,
                        'input_fields'  => $input_fields
                    ] );
                    ?>

                    <script>
                    (function($) {
                        CFG.field_rules = CFG.field_rules || {};
                        $.extend( CFG.field_rules, <?php echo json_encode( CFG()->validators ); ?> );
                    })(jQuery);
                    </script>
                    <input type="hidden" name="cfgroup[save]" value="<?php echo wp_create_nonce( 'cfgroup_save_input' ); ?>" />
                    <input type="hidden" name="cfgroup[session_id]" value="<?php echo $this->session->session_id; ?>" />

                    <?php if ( false !== $params['front_end'] ) : ?>

                    <input type="submit" value="<?php echo esc_attr( $params['submit_label'] ); ?>" />
                </form>
            </div>

        <?php
            endif;
        }
}

CFG()->form = new cfgroup_form();
