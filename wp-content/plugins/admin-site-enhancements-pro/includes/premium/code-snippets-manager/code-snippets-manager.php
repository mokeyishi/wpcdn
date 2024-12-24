<?php

/**
 * Forked from Simple Custom CSS and JS v3.44 by SilkyPress.com
 * @link https://wordpress.org/plugins/custom-css-js/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'Code_Snippets_Manager' ) ) :
	/**
	 * Main Code_Snippets_Manager Class
	 *
	 * @class Code_Snippets_Manager
	 */
	final class Code_Snippets_Manager {

		public $search_tree         = false;
		protected static $_instance = null;
		private $settings           = array();


		/**
		 * Main Code_Snippets_Manager Instance
		 *
		 * Ensures only one instance of Code_Snippets_Manager is loaded or can be loaded
		 *
		 * @static
		 * @return Code_Snippets_Manager - Main instance
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Cloning is forbidden.
		 */
		public function __clone() {
			_doing_it_wrong( __FUNCTION__, __( 'An error has occurred. Please reload the page and try again.' ), '1.0' );
		}

		/**
		 * Unserializing instances of this class is forbidden.
		 */
		public function __wakeup() {
			_doing_it_wrong( __FUNCTION__, __( 'An error has occurred. Please reload the page and try again.' ), '1.0' );
		}

		/**
		 * Code_Snippets_Manager Constructor
		 *
		 * @access public
		 */
		public function __construct() {

			$options = get_option( ASENHA_SLUG_U );
			$extra_options 		= get_option( ASENHA_SLUG_U . '_extra', array() );

			include_once 'includes/admin-install.php';
			// register_activation_hook( __FILE__, array( 'Code_Snippets_Manager_Install', 'install' ) );
			add_action( 'init', array( 'Code_Snippets_Manager_Install', 'create_roles' ) );
			add_action( 'init', array( 'Code_Snippets_Manager_Install', 'register_post_type' ) );
			add_action( 'init', array( 'Code_Snippets_Manager_Install', 'register_category' ) );

			// Make sure we only flush rewrite rules once as it's an expensive operation
			$flush_rewrite_rules_needed = $options['code_snippets_manager_flush_rewrite_rules_needed'];

			if ( $flush_rewrite_rules_needed ) {
				flush_rewrite_rules();
				$options['code_snippets_manager_flush_rewrite_rules_needed'] = false;
				update_option( ASENHA_SLUG_U, $options, true );
			}

			$this->set_constants();

			if ( is_admin() ) {
				include_once 'includes/admin-screens.php';
				include_once 'includes/admin-config.php';
			}

			$this->search_tree 	= isset( $extra_options['code_snippets_manager_tree'] ) ? $extra_options['code_snippets_manager_tree'] : array();
			$this->settings    	= isset( $extra_options['code_snippets_manager_settings'] ) ? $extra_options['code_snippets_manager_settings'] : array();

			if ( ! isset( $this->settings['remove_comments'] ) ) {
				$this->settings['remove_comments'] = false;
			}

			if ( ! $this->search_tree || count( $this->search_tree ) == 0 ) {
				return false;
			}

			if ( is_null( self::$_instance ) ) {

				// For CSS, JS, HTML snippets
				$this->print_code_actions();
				
				// For PHP snippets. plugins_loaded hook preceeds setup_theme, after_setup_theme, init, wp_loaded, admin_menu and admin_init
				add_action( 'plugins_loaded', array( $this, 'execute_php_snippet' ) );
				// $this->execute_php_snippet();

				if ( isset ( $this->search_tree['jquery'] ) && true === $this->search_tree['jquery'] ) {
					add_action( 'wp_enqueue_scripts', 'Code_Snippets_Manager::wp_enqueue_scripts' );
				}

			}
			
		}

		/**
		 * Add the appropriate wp actions
		 */
		public function print_code_actions() {
			foreach ( $this->search_tree as $_key => $_value ) {

				// For CSS, JS and HTML snippets
				if ( strpos( $_key, '-css-' ) !== false || strpos( $_key, '-js-' ) !== false || strpos( $_key, '-html-' ) !== false ) {

					$action = 'wp_';
					if ( strpos( $_key, 'admin' ) !== false ) {
						$action = 'admin_';
					}
					if ( strpos( $_key, 'login' ) !== false ) {
						$action = 'login_';
					}
					if ( strpos( $_key, 'header' ) !== false ) {
						$action .= 'head';
					} elseif ( strpos( $_key, 'body_open' ) !== false ) {
						$action .= 'body_open';
					} else {
						$action .= 'footer';
					}

					$priority = ( 'wp_footer' === $action ) ? 40 : 10;
					
					add_action( $action, array( $this, 'print_' . $_key ), $priority );				
				}
				
			}
		}

		/**
		 * Print the custom code.
		 */
		public function __call( $function, $args ) {

			if ( strpos( $function, 'print_' ) === false ) {
				return false;
			}

			$function = str_replace( 'print_', '', $function );

			if ( ! isset( $this->search_tree[ $function ] ) ) {
				return false;
			}

			$args = $this->search_tree[ $function ];

			if ( ! is_array( $args ) || count( $args ) == 0 ) {
				return false;
			}

			$where = strpos( $function, 'external' ) !== false ? 'external' : 'internal';
			$type  = strpos( $function, 'css' ) !== false ? 'css' : '';
			$type  = strpos( $function, 'js' ) !== false ? 'js' : $type;
			$type  = strpos( $function, 'html' ) !== false ? 'html' : $type;
			$tag   = array( 'css' => 'style', 'js' => 'script' );

			$type_attr = ( 'js' === $type && ! current_theme_supports( 'html5', 'script' ) ) ? ' type="text/javascript"' : '';
			$type_attr = ( 'css' === $type && ! current_theme_supports( 'html5', 'style' ) ) ? ' type="text/css"' : $type_attr;

			$upload_url = str_replace( array( 'https://', 'http://' ), '//', CSM_UPLOAD_URL ) . '/';

			if ( 'internal' === $where ) {

				$before = $this->settings['remove_comments'] ? '' : '<!-- start Code Snippets Manager -->' . PHP_EOL;
				$after  = $this->settings['remove_comments'] ? '' : '<!-- end Code Snippets Manager -->' . PHP_EOL;

				if ( 'css' === $type || 'js' === $type ) {
					$before .= '<' . $tag[ $type ] . ' ' . $type_attr . '>' . PHP_EOL;
					$after   = '</' . $tag[ $type ] . '>' . PHP_EOL . $after;
				}

			}

			foreach ( $args as $_filename ) {

				if ( 'internal' ===  $where && ( strstr( $_filename, 'css' ) || strstr( $_filename, 'js' ) ) ) {
					if ( $this->settings['remove_comments'] || empty( $type_attr ) ) {
						$code_snippet = @file_get_contents( CSM_UPLOAD_DIR . '/' . $_filename );
						if ( $this->settings['remove_comments'] ) {
								$code_snippet = str_replace( array( 
										'<!-- start Code Snippets Manager -->' . PHP_EOL, 
										'<!-- end Code Snippets Manager -->' . PHP_EOL 
								), '', $code_snippet );
						}
						if ( empty( $type_attr ) ) {
							$code_snippet = str_replace( array( ' type="text/javascript"', ' type="text/css"' ), '', $code_snippet );
						}
						echo $code_snippet;
					} else {
						echo @file_get_contents( CSM_UPLOAD_DIR . '/' . $_filename );
					}
				}

				if ( 'internal' === $where && ! strstr( $_filename, 'css' ) && ! strstr( $_filename, 'js' ) ) {
					$post = get_post( $_filename );
					echo $before . $post->post_content . $after;
				}

				if ( 'external' === $where && 'js' === $type ) {
					echo PHP_EOL . "<script{$type_attr} src='{$upload_url}{$_filename}'></script>" . PHP_EOL;
				}

				if ( 'external' === $where && 'css' === $type ) {
					$shortfilename = preg_replace( '@\.css\?v=.*$@', '', $_filename );
					echo PHP_EOL . "<link rel='stylesheet' id='{$shortfilename}-css' href='{$upload_url}{$_filename}'{$type_attr} media='all' />" . PHP_EOL;
				}

				if ( 'external' === $where && 'html' === $type ) {
					$_filename = str_replace( '.html', '', $_filename );
					$post      = get_post( $_filename );
					echo $post->post_content . PHP_EOL;
				}
			}
		}


		/**
		 * Enqueue the jQuery library, if necessary
		 */
		public static function wp_enqueue_scripts() {
			wp_enqueue_script( 'jquery' );
		}

		
		/**
		 * Execute PHP code
		 */
		public function execute_php_snippet() {
			$upload_dir = wp_upload_dir();
						
			foreach ( $this->search_tree as $_key => $_values ) {
				// For PHP snippets
				if ( strpos( $_key, '-php-' ) !== false ) {
					foreach( $_values as $_filename ) {
						
						$file_path = $upload_dir['basedir'] . '/code-snippets/' . $_filename;

						$post_id = absint( str_replace( '.php', '', $_filename ) );
						$options = $this->get_options( $post_id );

						// CHeck if safe mode is enabled
						$safe_mode_via_constant = defined( 'CSM_SAFE_MODE' ) ? CSM_SAFE_MODE : false;

						if ( ( isset( $_GET['safemode'] ) && sanitize_text_field( $_GET['safemode'] ) == '1' ) 
							|| ( $safe_mode_via_constant )
							) {
							$safe_mode_is_enabled = true;
						} else {
							$safe_mode_is_enabled = false;							
						}

						// Check if snippet is active
						$is_snippet_active = ( 'no' != get_post_meta( $post_id, '_active', true ) ) ? true : false;
						
						$validation_result = 'No validation has been performed yet';
						$execution_result = 'No execution has been performed yet';

						// Safe mode is not enabled
						if ( ! $safe_mode_is_enabled ) {
							// PHP snippet is active
							if ( $is_snippet_active ) {

								// Get code and parse it as string
								$php_code = file_get_contents( $upload_dir['basedir'] . '/code-snippets/' . $_filename );

							    // Clean up, so code is in proper form for eval(), i.e. without opening and closing php tags
							    $php_code = trim( $php_code );
							    $php_code = ltrim( $php_code, '<?php' );
							    $php_code = rtrim( $php_code, '?>' );

								$wp_config = new ASENHA\Classes\WP_Config_Transformer;

								// We're mainly using the custom wp_die_handler to handle fatal errors during PHP snippet editing
								// However, some fatal error does not trigger the wp_die screen, so, we catch it with
								// a custom shutdown function. This ensures we deactivate the PHP snippet and enable Safe Mode
								$args = array(
									'origin'	=> 'ase_csm', // ASE Code Snippets Manager (csm)
									'post_id'	=> $post_id,
									'wp_config'	=> $wp_config,
								);
								register_shutdown_function( array( $this, 'csm_shutdown_handler' ), $args );

							    // Basic validation for code's PHP syntax
								$validator = new ASENHA\Classes\PHP_Validator( $php_code );
								$validation_result = $validator->validate();

								if ( false === $validation_result ) {
									// No validation error were returned, code looks fine. Let's try to execute.
									// If fatal error occurs, it will be handled above. Safe mode will be enabled.
									// ob_start();
									try {
										$execution_result = eval( $php_code );
									} catch ( ParseError $parse_error ) {
										$execution_result = $parse_error;
									}
									// ob_end_clean();
								} else {
									// Do not execute the code
									$execution_result = 'Code was not executed due to validation error.';
								}

							} else {							
								$execution_result = 'PHP snippet is inactive, so code is not executed.';
							}							
						} else {
							$execution_result = 'Code was not executed due to safe mode being enabled.';
						}

						// Code has gone through validation and/or execution. Let's check if we have valid error.
						if ( false !== $validation_result || null !== $execution_result ) {

							$error_message = '';

							if ( is_array( $validation_result ) ) {

						        // Deactivate PHP snippet
						        // update_post_meta( $post_id, '_active', 'no' );

								update_post_meta( $post_id, 'php_snippet_has_error', true );
								update_post_meta( $post_id, 'php_snippet_error_type', 'non-fatal' );
								update_post_meta( $post_id, 'php_snippet_error_code', 'unknown' );
								
								$message = rtrim( $validation_result['message'], '.' );
								$line = intval( $validation_result['line'] ) - 1;
								$error_message = $message . ' on line ' . $line;

								update_post_meta( $post_id, 'php_snippet_error_message', '<span class="error-message">' . $error_message . '</span>' );
								update_post_meta( $post_id, 'php_snippet_error_via', 'validator' );
								
							}
							
							if ( is_object( $execution_result ) ) {

						        // Deactivate PHP snippet
						        // update_post_meta( $post_id, '_active', 'no' );

								update_post_meta( $post_id, 'php_snippet_has_error', true );
								update_post_meta( $post_id, 'php_snippet_error_type', 'non-fatal' );
								update_post_meta( $post_id, 'php_snippet_error_code', 'unknown' );

								$message = $execution_result->getMessage();
								$line = $execution_result->getLine();
								$error_message = $message . ' on line ' . $line;

								update_post_meta( $post_id, 'php_snippet_error_message', '<span class="error-message">' . $error_message . '</span>' );
								update_post_meta( $post_id, 'php_snippet_error_via', 'eval' );
									
							}
							
						} else {
							
							// No errors were found during snippet execution
							update_post_meta( $post_id, 'php_snippet_has_error', false );
							update_post_meta( $post_id, 'php_snippet_error_type', '' );
							update_post_meta( $post_id, 'php_snippet_error_code', '' );
							update_post_meta( $post_id, 'php_snippet_error_message', '' );
							update_post_meta( $post_id, 'php_snippet_error_via', '' );

						}

					}
					
				}				
			}
		}
		
		
		/**
		 * Handle fatal error caused by faulty PHP snippets
		 */
		public function csm_shutdown_handler( $args ) {
			
		    $error_raw = error_get_last();

			$origin = isset( $args['origin'] ) ? $args['origin'] : '';
			$post_id = isset( $args['post_id'] ) ? $args['post_id'] : '';
			$wp_config = isset( $args['wp_config'] ) ? $args['wp_config'] : '';

		    // Only process if there's an actual error, and origin is 
		    // from PHP code snippets handled by ASE Code Snippets Manager
		    if ( $error_raw !== NULL && isset( $args['origin'] ) && 'ase_csm' == $origin ) {

		        $file 			= $error_raw["file"];			    
				$is_error_from_csm_snippet = ( false !== strpos( $file, '/premium/code-snippets-manager/' ) ) ? true : false;
			    
			    if ( $is_error_from_csm_snippet ) {

			        $code 			= $error_raw["type"]; // Ref: https://www.php.net/manual/en/errorfunc.constants.php#109430
			        $fatal_error_codes = array( 1, 16, 256 );
			        if ( in_array( intval( $code ), $fatal_error_codes ) ) {
			        	$type = 'fatal';
			        } else {
			        	$type = 'non-fatal';
			        }
				    
			        $line 			= $error_raw["line"];
			        $message_full 	= $error_raw["message"]; // includes stack trace
			        $message_parts 	= explode( ' in /', $message_full );
			        $message 		= $message_parts[0];
					$error_message = $message . ' on line ' . $line;
			        		        
			        if ( 'fatal' == $type ) {

				        $message_parts 	= explode( 'Stack trace:', $message_full );
				        $message_stack_trace = $message_parts[1];
				        $snippet_edit_url = get_edit_post_link( $post_id );

				        // Record error info in PHP snippet post meta
						update_post_meta( $post_id, 'php_snippet_has_error', true );
						update_post_meta( $post_id, 'php_snippet_error_type', $type );
						update_post_meta( $post_id, 'php_snippet_error_code', $code );
						update_post_meta( $post_id, 'php_snippet_error_message', '<span class="error-message">' . $error_message . '</span><span class="stack-trace">Stack trace:</span>' . ltrim( nl2br( str_replace( ABSPATH, '/', $message_stack_trace ) ), '<br />' ) );
						update_post_meta( $post_id, 'php_snippet_error_via', 'shutdown' );

				        // Deactivate PHP snippet
				        update_post_meta( $post_id, '_active', 'no' );

					    // We have a fatal error making the site inaccessible, let's enable safe mode, halt PHP snippets execution, and make the site accessible again

						$wp_config_options = array(
							'add'       => true, // Add the config if missing.
							'raw'       => true, // Display value in raw format without quotes.
							'normalize' => false, // Normalize config output using WP Coding Standards.
						);

						$update_success = $wp_config->update( 'constant', 'CSM_SAFE_MODE', 'true', $wp_config_options );
						
						if ( $update_success ) {
							// Prevent showing fatal error screen by redirecting back to snippet edit screen
							// wp_safe_redirect( get_edit_post_link( $post_id ) );
							// exit;
						}
									        	
			        }
			        			    	
			    }

		    }

		}

		/**
		 * Set constants for later use
		 */
		public function set_constants() {
			$dir       = wp_upload_dir();
			$constants = array(
				'CSM_VERSION'     => ASENHA_VERSION,
				'CSM_UPLOAD_DIR'  => $dir['basedir'] . '/code-snippets',
				'CSM_UPLOAD_URL'  => $dir['baseurl'] . '/code-snippets',
				'CSM_PLUGIN_FILE' => __FILE__,
			);
			foreach ( $constants as $_key => $_value ) {
				if ( ! defined( $_key ) ) {
					define( $_key, $_value );
				}
			}
		}

	}

endif;

if ( ! function_exists( 'Code_Snippets_Manager' ) ) {
	/**
	 * Returns the main instance of Code_Snippets_Manager
	 *
	 * @return Code_Snippets_Manager
	 */
	function Code_Snippets_Manager() {
		return Code_Snippets_Manager::instance();
	}

	Code_Snippets_Manager();
}