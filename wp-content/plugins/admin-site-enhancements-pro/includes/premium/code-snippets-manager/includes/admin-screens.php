<?php
/**
 * Code Snippets Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// use ScssPhp\ScssPhp\Compiler;

/**
 * Code_Snippets_Manager_Admin
 */
class Code_Snippets_Manager_Admin {

	/**
	 * Default options for a new page
	 */
	private $default_options = array(
		'type'     => 'header',
		'linking'  => 'internal',
		'side'     => 'frontend',
		'priority' => 5,
		'language' => 'css',
	);

	/**
	 * Array with the options for a specific code-snippets-manager post
	 */
	private $options = array();

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'current_screen', array( $this, 'current_screen' ) );
		add_action( 'admin_notices', array( $this, 'create_uploads_directory' ) );
		add_action( 'edit_form_after_title', array( $this, 'codemirror_editor' ) );
		add_action( 'add_meta_boxes_asenha_code_snippet', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'options_save_meta_box_data' ) );
		add_action( 'wp_restore_post_revision', array( $this, 'restore_snippet_revision_in_file' ), 10, 2 );
		add_action( 'trashed_post', array( $this, 'trash_post' ) );
		add_action( 'untrashed_post', array( $this, 'trash_post' ) );
		add_action( 'wp_ajax_csm_disable_safe_mode', array( $this, 'wp_ajax_csm_disable_safe_mode' ) );
		add_action( 'wp_ajax_csm_active_code', array( $this, 'wp_ajax_csm_active_code' ) );
		add_action( 'wp_ajax_csm_permalink', array( $this, 'wp_ajax_csm_permalink' ) );
		add_action( 'post_submitbox_start', array( $this, 'post_submitbox_start' ) );
		add_action( 'restrict_manage_posts', array( $this, 'restrict_manage_posts' ) );
		add_action( 'load-post.php', array( $this, 'contextual_help' ) );
		add_action( 'load-post-new.php', array( $this, 'contextual_help' ) );
		add_action( 'edit_form_before_permalink', array( $this, 'edit_form_before_permalink' ) );
		add_action( 'before_delete_post', array( $this, 'before_delete_post' ) );

		// Add some custom actions/filters
		add_filter( 'manage_asenha_code_snippet_posts_columns', array( $this, 'manage_custom_posts_columns' ) );
		add_action( 'manage_asenha_code_snippet_posts_custom_column', array( $this, 'manage_posts_columns' ), 10, 2 );
		add_filter( 'manage_edit-asenha_code_snippet_sortable_columns', array( $this, 'manage_edit_posts_sortable_columns' ) );
		add_action( 'posts_orderby', array( $this, 'posts_orderby' ), 10, 2 );
		add_action( 'posts_join_paged', array( $this, 'posts_join_paged' ), 10, 2 );
		add_action( 'posts_where_paged', array( $this, 'posts_where_paged' ), 10, 2 );
		add_filter( 'post_row_actions', array( $this, 'post_row_actions' ), 10, 2 );
		add_filter( 'parse_query', array( $this, 'parse_query' ), 10 );
		add_filter( 'wp_statuses_get_supported_post_types', array( $this, 'wp_statuses_get_supported_post_types' ), 20 );

		add_action( 'current_screen', array( $this, 'current_screen_2' ), 100 );
	}

	/**
	 * Add submenu pages
	 */
	function admin_menu() {
		$menu_slug    = 'edit.php?post_type=asenha_code_snippet';
		// add_menu_page(
		// 	__( 'Code Snippets', 'admin-site-enhancements' ),
		// 	__( 'Code Snippets', 'admin-site-enhancements' ),
		// 	'publish_code_snippetss',
		// 	$menu_slug,
		// 	'',
		// 	'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCIgdmlld0JveD0iMCAwIDIwIDIwIj48cGF0aCBmaWxsPSJjdXJyZW50Q29sb3IiIGZpbGwtcnVsZT0iZXZlbm9kZCIgZD0iTTYuMjggNS4yMmEuNzUuNzUgMCAwIDEgMCAxLjA2TDIuNTYgMTBsMy43MiAzLjcyYS43NS43NSAwIDAgMS0xLjA2IDEuMDZMLjk3IDEwLjUzYS43NS43NSAwIDAgMSAwLTEuMDZsNC4yNS00LjI1YS43NS43NSAwIDAgMSAxLjA2IDBabTcuNDQgMGEuNzUuNzUgMCAwIDEgMS4wNiAwbDQuMjUgNC4yNWEuNzUuNzUgMCAwIDEgMCAxLjA2bC00LjI1IDQuMjVhLjc1Ljc1IDAgMCAxLTEuMDYtMS4wNkwxNy40NCAxMGwtMy43Mi0zLjcyYS43NS43NSAwIDAgMSAwLTEuMDZabS0yLjM0My0zLjIwOWEuNzUuNzUgMCAwIDEgLjYxMi44NjdsLTIuNSAxNC41YS43NS43NSAwIDAgMS0xLjQ3OC0uMjU1bDIuNS0xNC41YS43NS43NSAwIDAgMSAuODY2LS42MTJaIiBjbGlwLXJ1bGU9ImV2ZW5vZGQiLz48L3N2Zz4=',
		// 	21
		// );

		// Remove default "Add New"
		$submenu_slug = 'post-new.php?post_type=asenha_code_snippet';
		remove_submenu_page( $menu_slug, $submenu_slug );
		
		$title = __( 'Add CSS / SCSS Snippet', 'admin-site-enhancements' );
		add_submenu_page( $menu_slug, $title, $title, 'publish_code_snippetss', $submenu_slug . '&amp;language=css', '', 1 );

		$title = __( 'Add JS Snippet', 'admin-site-enhancements' );
		add_submenu_page( $menu_slug, $title, $title, 'publish_code_snippetss', $submenu_slug . '&amp;language=js', '', 2 );

		$title = __( 'Add HTML Snippet', 'admin-site-enhancements' );
		add_submenu_page( $menu_slug, $title, $title, 'publish_code_snippetss', $submenu_slug . '&amp;language=html', '', 3 );

		$title = __( 'Add PHP Snippet', 'admin-site-enhancements' );
		add_submenu_page( $menu_slug, $title, $title, 'publish_code_snippetss', $submenu_slug . '&amp;language=php', '', 4 );

	}


	/**
	 * Enqueue the scripts and styles
	 */
	public function admin_enqueue_scripts( $hook_suffix ) {
		
		$screen = get_current_screen();

		// Only for code-snippets-manager post type
		if ( $screen->post_type != 'asenha_code_snippet' ) {
			return false;
		}

		// We force loading the uncompressed version of TinyMCE. This ensures we load 'wp-tinymce-root' and then 'wp-tinymce', 
		// which prevents issue where the Visual editor for the snippet description is unusable in some scenarios
		$wp_scripts = wp_scripts();
		$wp_scripts->remove( 'wp-tinymce' );
		wp_register_tinymce_scripts( $wp_scripts, true );

		// Some handy variables
		// $a  = plugins_url( '/', CSM_PLUGIN_FILE ) . 'assets';
		$a = ASENHA_URL . 'includes/premium/code-snippets-manager/assets';
		$f = ASENHA_URL . 'includes/premium/code-snippets-manager/assets/font';
		$cm = ASENHA_URL . 'includes/premium/code-snippets-manager/assets/codemirror';
		$t = ASENHA_URL . 'includes/premium/code-snippets-manager/assets/codemirror/theme';
		$v  = CSM_VERSION;

		wp_enqueue_script( 'csm-tipsy', $a . '/jquery.tipsy.js', array( 'jquery' ), $v, false );
		wp_enqueue_style( 'csm-tipsy', $a . '/tipsy.css', array(), $v );
		wp_enqueue_script( 'csm-cookie', $a . '/js.cookie.js', array( 'jquery' ), $v, false );
		wp_register_script( 'csm-admin', $a . '/csm_admin.js', array( 'jquery', 'jquery-ui-resizable' ), $v, false );
		wp_localize_script( 'csm-admin', 'CSM', $this->cm_localize() );
		wp_enqueue_script( 'csm-admin' );
		wp_enqueue_style( 'csm-admin', $a . '/csm_admin.css', array(), $v );

		// Only for the new/edit Code's page
		if ( $hook_suffix == 'post-new.php' || $hook_suffix == 'post.php' ) {
			wp_deregister_script( 'wp-codemirror' );

			wp_enqueue_style( 'jquery-ui', 'https://code.jquery.com/ui/1.12.0/themes/base/jquery-ui.css', array(), $v );
			wp_enqueue_script( 'csm-codemirror', $cm . '/lib/codemirror.js', array( 'jquery' ), $v, false );
			wp_enqueue_style( 'csm-codemirror', $cm . '/lib/codemirror.css', array(), $v );
			// wp_enqueue_script( 'csm-admin_url_rules', $a . '/csm_admin-url_rules.js', array( 'jquery' ), $v, false );

			// Font
			wp_enqueue_style( 'csm-font', $f . '/font.css', array(), $v );

			// Themes
			wp_enqueue_style( 'cm-theme-monokai-mod', $t . '/monokai-mod.css', array(), $v );
			
			// Add the language modes
			$cmm = ASENHA_URL . 'includes/premium/code-snippets-manager/assets/codemirror/mode/';
			wp_enqueue_script( 'cm-xml', $cmm . 'xml/xml.js', array( 'csm-codemirror' ), $v, false );
			wp_enqueue_script( 'cm-js', $cmm . 'javascript/javascript.js', array( 'csm-codemirror' ), $v, false );
			wp_enqueue_script( 'cm-css', $cmm . 'css/css.js', array( 'csm-codemirror' ), $v, false );
			wp_enqueue_script( 'cm-htmlmixed', $cmm . 'htmlmixed/htmlmixed.js', array( 'csm-codemirror' ), $v, false );
			wp_enqueue_script( 'cm-clike', $cmm . 'php/clike.js', array( 'csm-codemirror' ), $v, false );
			wp_enqueue_script( 'cm-php', $cmm . 'php/php.js', array( 'csm-codemirror' ), $v, false );

			$cma = ASENHA_URL . 'includes/premium/code-snippets-manager/assets/codemirror/addon/';
			wp_enqueue_script( 'csm-closebrackets', $cma . 'edit/closebrackets.js', array( 'csm-codemirror' ), $v, false );
			wp_enqueue_script( 'csm-matchbrackets', $cma . 'edit/matchbrackets.js', array( 'csm-codemirror' ), $v, false );
			wp_enqueue_script( 'csm-matchtags', $cma . 'edit/matchtags.js', array( 'csm-codemirror' ), $v, false );
			wp_enqueue_script( 'cm-dialog', $cma . 'dialog/dialog.js', array( 'csm-codemirror' ), $v, false );
			wp_enqueue_script( 'cm-search', $cma . 'search/search.js', array( 'csm-codemirror' ), $v, false );
			wp_enqueue_script( 'cm-searchcursor', $cma . 'search/searchcursor.js', array( 'csm-codemirror' ), $v, false );
			wp_enqueue_script( 'cm-jump-to-line', $cma . 'search/jump-to-line.js', array( 'csm-codemirror' ), $v, false );
			// wp_enqueue_script( 'cm-match-highlighter', $cma . 'search/match-highlighter.js', array( 'csm-codemirror' ), $v, false );
			wp_enqueue_script( 'csm-fullscreen', $cma . 'display/fullscreen.js', array( 'csm-codemirror' ), $v, false );
			wp_enqueue_style( 'cm-dialog', $cma . 'dialog/dialog.css', array(), $v );
			wp_enqueue_script( 'csm-formatting', $cm . '/lib/util/formatting.js', array( 'csm-codemirror' ), $v, false );
			wp_enqueue_script( 'csm-comment', $cma . 'comment/comment.js', array( 'csm-codemirror' ), $v, false );
			wp_enqueue_script( 'csm-active-line', $cma . 'selection/active-line.js', array( 'csm-codemirror' ), $v, false );

			// Hint Addons
			wp_enqueue_script( 'csm-hint', $cma . 'hint/show-hint.js', array( 'csm-codemirror' ), $v, false );
			wp_enqueue_script( 'csm-hint-js', $cma . 'hint/javascript-hint.js', array( 'csm-codemirror' ), $v, false );
			wp_enqueue_script( 'csm-hint-xml', $cma . 'hint/xml-hint.js', array( 'csm-codemirror' ), $v, false );
			wp_enqueue_script( 'csm-hint-html', $cma . 'hint/html-hint.js', array( 'csm-codemirror' ), $v, false );
			wp_enqueue_script( 'csm-hint-css', $cma . 'hint/css-hint.js', array( 'csm-codemirror' ), $v, false );
			wp_enqueue_script( 'csm-hint-anyword', $cma . 'hint/anyword-hint.js', array( 'csm-codemirror' ), $v, false );
			wp_enqueue_style( 'csm-hint', $cma . 'hint/show-hint.css', array(), $v );

			// Fold Addons
			wp_enqueue_script( 'csm-fold-brace', $cma . 'fold/brace-fold.js', array( 'csm-codemirror' ), $v, false );
			wp_enqueue_script( 'csm-fold-comment', $cma . 'fold/comment-fold.js', array( 'csm-codemirror' ), $v, false );
			wp_enqueue_script( 'csm-fold-code', $cma . 'fold/foldcode.js', array( 'csm-codemirror' ), $v, false );
			wp_enqueue_script( 'csm-fold-gutter', $cma . 'fold/foldgutter.js', array( 'csm-codemirror' ), $v, false );
			wp_enqueue_script( 'csm-fold-indent', $cma . 'fold/indent-fold.js', array( 'csm-codemirror' ), $v, false );
			wp_enqueue_script( 'csm-fold-markdown', $cma . 'fold/markdown-fold.js', array( 'csm-codemirror' ), $v, false );
			wp_enqueue_script( 'csm-fold-xml', $cma . 'fold/xml-fold.js', array( 'csm-codemirror' ), $v, false );
			wp_enqueue_style( 'csm-fold-gutter', $cma . 'fold/foldgutter.css', array(), $v );

			// remove the assets from other plugins so it doesn't interfere with CodeMirror
			// global $wp_scripts;
			// if ( is_array( $wp_scripts->registered ) && count( $wp_scripts->registered ) != 0 ) {
			// 	foreach ( $wp_scripts->registered as $_key => $_value ) {
			// 		if ( ! isset( $_value->src ) ) {
			// 			continue;
			// 		}

			// 		if ( strstr( $_value->src, 'wp-content/plugins' ) !== false
			// 		&& strstr( $_value->src, 'plugins/admin-site-enhancements/' ) === false
			// 		&& strstr( $_value->src, 'plugins/advanced-custom-fields/' ) === false
			// 		&& strstr( $_value->src, 'plugins/wp-jquery-update-test/' ) === false
			// 		&& strstr( $_value->src, 'plugins/enable-jquery-migrate-helper/' ) === false
			// 		&& strstr( $_value->src, 'plugins/tablepress/' ) === false
			// 		&& strstr( $_value->src, 'plugins/advanced-custom-fields-pro/' ) === false ) {
			// 			unset( $wp_scripts->registered[ $_key ] );
			// 		}
			// 	}
			// }
			// remove the CodeMirror library added by the Product Slider for WooCommerce plugin by ShapedPlugin
			// wp_enqueue_style( 'spwps-codemirror', $a . '/empty.css', '1.0' );
			// wp_enqueue_script( 'spwps-codemirror', $a . '/empty.js', array(), '1.0', true );
		}
	}


	/**
	 * Send variables to the csm_admin.js script
	 */
	public function cm_localize() {
		global $pagenow;

        $extra_options = get_option( ASENHA_SLUG_U . '_extra', array() );
        $settings = isset( $extra_options['code_snippets_manager_settings'] ) ? $extra_options['code_snippets_manager_settings'] : array();

		$vars = array(
			'autocomplete'   => isset( $settings['csm_autocomplete'] ) && ! $settings['csm_autocomplete'] ? false : true,
			'active'         => __( 'Active', 'admin-site-enhancements' ),
			'inactive'       => __( 'Inactive', 'admin-site-enhancements' ),
			'activate'       => __( 'Activate', 'admin-site-enhancements' ),
			'deactivate'     => __( 'Deactivate', 'admin-site-enhancements' ),
			'active_title'   => __( 'The code is active. Click to deactivate it', 'admin-site-enhancements' ),
			'deactive_title' => __( 'The code is inactive. Click to activate it', 'admin-site-enhancements' ),
			'page_now'		 => $pagenow,

			/* CodeMirror options */
			'codemirror' => array(
				'indentUnit'       => 4,
				'indentWithTabs'   => true,
				'inputStyle'       => 'contenteditable',
				'lineNumbers'      => true,
				'lineWrapping'     => true,
				'styleActiveLine'  => true,
				'continueComments' => true,
				'extraKeys'        => array(
					'Ctrl-Space' => 'autocomplete',
					'Cmd-Space'  => 'autocomplete',
					'Ctrl-/'     => 'toggleComment',
					'Cmd-/'      => 'toggleComment',
					'Alt-F'      => 'findPersistent',
					'Ctrl-F'     => 'findPersistent',
					'Cmd-F'      => 'findPersistent',
					'Ctrl-J'     =>  'toMatchingTag',
				),
				'direction'        => 'ltr', // Code is shown in LTR even in RTL languages.
				'gutters'          => array( 'CodeMirror-lint-markers' ),
				'matchBrackets'    => true,
				'matchTags'        => array( 'bothTags' => true ),
				'autoCloseBrackets' => true,
				'autoCloseTags'    => true,
			)
		);

		return apply_filters( 'csm_code_editor_settings', $vars);
	}

	public function add_meta_boxes() {
		$options = $this->get_options( get_the_ID() );
		
		// Add snippet options / notes meta box
		if ( 'php' != $options['language'] ) {
			$meta_box_title = 'Snippet Options';
		} else {
			$meta_box_title = 'Notes';			
		}

		add_meta_box( 
			'code-snippet-options', 
			$meta_box_title, 
			array( $this, 'code_snippet_options_meta_box_callback' ), 
			'asenha_code_snippet', 
			'side', 
			'low' 
		);
		
		// Add WP Editor meta box for snippet description

		add_meta_box( 
			'code-snippet-description', 
			'Description', 
			array( $this, 'code_snippet_description_meta_box_callback' ), 
			'asenha_code_snippet', 
			'advanced', 
			'high' 
		);

		remove_meta_box( 'slugdiv', 'asenha_code_snippet', 'normal' );
	}



	/**
	 * Get options for a specific code-snippets-manager post
	 */
	private function get_options( $post_id ) {
		if ( isset( $this->options[ $post_id ] ) ) {
			return $this->options[ $post_id ];
		}

		$options = get_post_meta( $post_id );
		if ( empty( $options ) || ! isset( $options['options'][0] ) ) {
			$this->options[ $post_id ] = $this->default_options;
			return $this->default_options;
		}

		$options                   = @unserialize( $options['options'][0] );
		$this->options[ $post_id ] = $options;
		return $options;
	}


	/**
	 * Reformat the `edit` or the `post` screens
	 */
	function current_screen( $current_screen ) {

		if ( $current_screen->post_type != 'asenha_code_snippet' ) {
			return false;
		}

		// All snippets
		if ( $current_screen->base == 'edit' ) {
			add_action( 'admin_head', array( $this, 'current_screen_edit' ) );
		}

		// Edit snippet
		if ( $current_screen->base == 'post' ) {
			add_action( 'admin_head', array( $this, 'current_screen_post' ) );
		}

		wp_deregister_script( 'autosave' );
	}



	/**
	 * Add the buttons in the `edit` screen
	 */
	function add_new_buttons() {
		$current_screen = get_current_screen();

		if ( ( isset( $current_screen->action ) && $current_screen->action == 'add' ) || $current_screen->post_type != 'asenha_code_snippet' ) {
			return false;
		}
		?>
	<div class="updated buttons">
	<a href="post-new.php?post_type=asenha_code_snippet&language=css" class="custom-btn custom-css-btn"><?php _e( 'Add CSS/SCSS code', 'admin-site-enhancements' ); ?></a>
	<a href="post-new.php?post_type=asenha_code_snippet&language=js" class="custom-btn custom-js-btn"><?php _e( 'Add JS code', 'admin-site-enhancements' ); ?></a>
	<a href="post-new.php?post_type=asenha_code_snippet&language=html" class="custom-btn custom-js-btn"><?php _e( 'Add HTML code', 'admin-site-enhancements' ); ?></a>
		<!-- a href="post-new.php?post_type=asenha_code_snippet&language=php" class="custom-btn custom-php-btn">Add PHP code</a -->
	</div>
		<?php
	}



	/**
	 * Add new columns in the `edit` screen
	 */
	function manage_custom_posts_columns( $columns ) {		
		$columns = array(
			'cb'        	=> '<input type="checkbox" />',
			'active'    	=> __( 'Status', 'admin-site-enhancements' ),
			'type'      	=> __( 'Type', 'admin-site-enhancements' ),
			'csm_options'   => __( 'Options' ),
			'title'     	=> __( 'Title' ),
			'description'   => __( 'Description' ),
			'asenha_code_snippet_category'   => __( 'Categories' ),
			// 'published' 	=> __( 'Published' ),
			'csm_modified'  => __( 'Modified', 'admin-site-enhancements' ),
			'author'    	=> __( 'Author' ),
		);
		
		return $columns;
	}


	/**
	 * Fill the data for the new added columns in the `edit` screen
	 */
	function manage_posts_columns( $column, $post_id ) {

		if ( 'type' === $column ) {
			$options = $this->get_options( $post_id );
			echo '<a href="' . admin_url( 'edit.php?post_status=all&post_type=asenha_code_snippet&language_filter=' . $options['language'] ) . '" class="button button-small snippet-language">' . $options['language'] . '</a>';
		}

		if ( 'csm_options' === $column ) {
			$options = $this->get_options( $post_id );

			// Load snippet
			$linking = ( isset( $options['linking'] ) ) ? $options['linking'] : '';
			$linking_label = ( ! empty( $linking ) && $linking == 'external' ) ? 'As file' : 'Inline';

			// Position on page
			$type = ( isset( $options['type'] ) ) ? $options['type'] : '';
			if ( 'header' === $type ) {
				$type_label = 'in &lt;head&gt;';
			} elseif ( 'body_open' === $type ) {
				$type_label = 'after &lt;body&gt;';				
			} elseif ( 'footer' === $type ) {
				$type_label = 'in &lt;footer&gt;';				
			} else {
				$type_label = '';
			}

			// On which part of the site
			$sides = ( isset( $options['side'] ) ) ? $options['side'] : '';
			$sides = explode( ',', $sides );
			if ( in_array( 'login', $sides ) && ! in_array( 'frontend', $sides ) && ! in_array( 'admin', $sides ) ) {
				$sides_suffix = 'page';
			} else if ( in_array( 'login', $sides ) && ( in_array( 'frontend', $sides ) || in_array( 'admin', $sides ) ) ) {
				$sides_suffix = 'pages';
			} else {
				$sides_suffix = 'pages';				
			}
			if ( count( $sides ) == 2 ) {
				$sides_label = implode( ' and ', $sides ) . ' ' . $sides_suffix;			
			} else {
				$sides_label = implode( ', ', $sides ) . ' ' . $sides_suffix;			
			}
			
			// Combined option labels
			$options_labels = esc_html( $linking_label ) . ' ' . esc_html( $type_label ) . '<br /> On ' . esc_html( $sides_label );
			
			if ( $options['language'] != 'php' ) {
				echo $options_labels;
			} else {
				echo 'On page load';
			}
		}

		if ( 'description' === $column ) {
			$description = wp_strip_all_tags( get_post_meta( $post_id, 'code_snippet_description', true ) );
			$word_limit = 14;
			$description = implode(" ", array_slice( explode(" ", $description), 0, $word_limit ) );
			if ( ! empty( $description ) ) {
				$description .= '...';
			}
			echo $description;
		}

		if ( 'published' === $column ) {
			$post = get_post( $post_id );

			if ( '0000-00-00 00:00:00' === $post->post_date ) {
				$t_time    = __( 'Unpublished' );
				$h_time    = $t_time;
				$time_diff = 0;
			} else {
				$time      = get_post_time( 'U', false, $post );
				$time_diff = time() - $time;

				if ( $time && $time_diff > 0 && $time_diff < DAY_IN_SECONDS ) {
					/* translators: %s: Human-readable time difference. */
					$h_time = sprintf( __( '%s ago' ), human_time_diff( $time ) );
				} else {
					$h_time = get_the_time( __( 'Y/m/d' ), $post );
				}
			}

			 echo $h_time;
		}

		if ( 'csm_modified' === $column ) {
			$post = get_post( $post_id );

			if ( '0000-00-00 00:00:00' === $post->post_date ) {
				$t_time    = __( 'Unpublished' );
				$modified_time    = $t_time;
			} else {				
				$modified_time = get_the_modified_time( __( 'F j, Y' ), $post ) . ' at ' . get_the_modified_time( __( 'H:i' ), $post );
			}

			 echo $modified_time;
		}

		if ( 'active' === $column ) {
			$options = $this->get_options( $post_id );
			$url = wp_nonce_url( admin_url( 'admin-ajax.php?action=csm_active_code&code_id=' . $post_id ), 'csm-active-code-' . $post_id );
			$spinner = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><path fill="#999" d="M12,1A11,11,0,1,0,23,12,11,11,0,0,0,12,1Zm0,19a8,8,0,1,1,8-8A8,8,0,0,1,12,20Z" opacity=".25"/><path fill="#999" d="M12,4a8,8,0,0,1,7.89,6.7A1.53,1.53,0,0,0,21.38,12h0a1.5,1.5,0,0,0,1.48-1.75,11,11,0,0,0-21.72,0A1.5,1.5,0,0,0,2.62,12h0a1.53,1.53,0,0,0,1.49-1.3A8,8,0,0,1,12,4Z"><animateTransform attributeName="transform" dur="0.75s" repeatCount="indefinite" type="rotate" values="0 12 12;360 12 12"/></path></svg>';
			if ( $this->is_active( $post_id ) ) {
				$active_title = __( 'The code is active. Click to deactivate it', 'admin-site-enhancements' );
				// https://icon-sets.iconify.design/la/toggle-on/
				$status_icon  = '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32"><path fill="currentColor" d="M9 7c-4.96 0-9 4.035-9 9s4.04 9 9 9h14c4.957 0 9-4.043 9-9s-4.043-9-9-9zm14 2c3.879 0 7 3.121 7 7s-3.121 7-7 7s-7-3.121-7-7s3.121-7 7-7z"/></svg>';
			} else {
				$active_title = __( 'The code is inactive. Click to activate it', 'admin-site-enhancements' );
				// https://icon-sets.iconify.design/la/toggle-off/
				$status_icon  = '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32"><path fill="#999" d="M9 7c-.621 0-1.227.066-1.813.188a9.238 9.238 0 0 0-.875.218A9.073 9.073 0 0 0 .72 12.5c-.114.27-.227.531-.313.813A8.848 8.848 0 0 0 0 16c0 .93.145 1.813.406 2.656c.004.008-.004.024 0 .032A9.073 9.073 0 0 0 5.5 24.28c.27.114.531.227.813.313A8.83 8.83 0 0 0 9 24.999h14c4.957 0 9-4.043 9-9s-4.043-9-9-9zm0 2c3.879 0 7 3.121 7 7s-3.121 7-7 7s-7-3.121-7-7c0-.242.008-.484.031-.719A6.985 6.985 0 0 1 9 9zm5.625 0H23c3.879 0 7 3.121 7 7s-3.121 7-7 7h-8.375C16.675 21.348 18 18.828 18 16c0-2.828-1.324-5.348-3.375-7z"/></svg>';
			}
			$error_indicator = '';
			if ( 'php' == $options['language'] ) {
				$has_error = get_post_meta( $post_id, 'php_snippet_has_error', true );
				if ( $has_error ) {
					$error_indicator = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 32 32"><circle cx="16" cy="16" r="8" fill="#d63638"/></svg>';
				} 
			}
			
			echo '<a href="' . esc_url( $url ) . '" class="csm_activate_deactivate snippet-status-icon-link" data-code-id="' . $post_id . '" title="' . $active_title . '"><span class="has-error">' . $error_indicator . '</span><span class="snippet-status">' . $status_icon . '<span class="snippet-status-spinner" style="display:none;">' . $spinner . '</span></span></a>';
		}
	}


	/**
	 * Make the 'Modified' column sortable
	 */
	function manage_edit_posts_sortable_columns( $columns ) {
		$columns['active']    		= 'active';
		$columns['type']      		= 'type';
		$columns['csm_modified']  	= 'csm_modified';
		$columns['published'] 		= 'published';
		return $columns;

	}


	/**
	 * List table: Change the query in order to filter by code type
	 */
	function parse_query( $query ) {

		global $wpdb;
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return $query;
		}

		if ( ! isset( $query->query['post_type'] ) ) {
			return $query;
		}

		if ( 'asenha_code_snippet' !== $query->query['post_type'] ) {
			return $query;
		}

		$filter = filter_input( INPUT_GET, 'language_filter' );
		if ( ! is_string( $filter ) || strlen( $filter ) == 0 ) {
			return $query;
		}
		$filter = '%' . $wpdb->esc_like( $filter ) . '%';

		$post_id_query = "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value LIKE %s";
		$post_ids      = $wpdb->get_col( $wpdb->prepare( $post_id_query, 'options', $filter ) );
		if ( ! is_array( $post_ids ) || count( $post_ids ) == 0 ) {
			$post_ids = array( -1 );
		}
		$query->query_vars['post__in'] = $post_ids;

		return $query;
	}


	/**
	 * The "Publish"/"Update" button is missing if the "LH Archived Post Status" plugins is installed.
	 */
	function wp_statuses_get_supported_post_types( $post_types ) {
		unset( $post_types['asenha_code_snippet'] );
		return $post_types;
	}


	/**
	 * List table: add a filter by code type
	 */
	function restrict_manage_posts( $post_type ) {
		if ( 'asenha_code_snippet' !== $post_type ) {
			return;
		}
		
		// Output snippet types dropdown filter

		$languages = array(
			'css'  => __( 'CSS', 'admin-site-enhancements' ),
			'js'   => __( 'JS', 'admin-site-enhancements' ),
			'html' => __( 'HTML', 'admin-site-enhancements' ),
			'php' => __( 'PHP', 'admin-site-enhancements' ),
		);

		echo '<label class="screen-reader-text" for="code-snippets-manager-filter">' . esc_html__( 'Filter Code Type', 'admin-site-enhancements' ) . '</label>';
		echo '<select name="language_filter" id="code-snippets-manager-filter">';
		echo '<option  value="">' . __( 'All Types', 'admin-site-enhancements' ) . '</option>';
		foreach ( $languages as $_lang => $_label ) {
			$selected = selected( filter_input( INPUT_GET, 'language_filter' ), $_lang, false );
			echo '<option ' . $selected . ' value="' . $_lang . '">' . $_label . '</option>';
		}
		echo '</select>';

		// Output snippet categories dropdown filter

		$post_taxonomies = get_object_taxonomies( 'asenha_code_snippet', 'objects' );
		$snippet_category = $post_taxonomies['asenha_code_snippet_category'];

		wp_dropdown_categories( array(
			'show_option_all'	=> 'All Categories',
			'orderby'			=> 'name',
			'order'				=> 'ASC',
			'hide_empty'		=> false,
			'hide_if_empty'		=> true,
			'selected'			=> sanitize_text_field( ( isset( $_GET[$snippet_category->query_var] ) && ! empty( $_GET[$snippet_category->query_var] ) ) ? $_GET[$snippet_category->query_var] : '' ), 
			'hierarchical'		=> true,
			'name'				=> $snippet_category->query_var,
			'taxonomy'			=> $snippet_category->name,
			'value_field'		=> 'slug',
		) );
	}


	/**
	 * Order table by Type and Active columns
	 */
	function posts_orderby( $orderby, $query ) {
		if ( ! is_admin() ) {
			return $orderby;
		}
		global $wpdb;

		if ( 'asenha_code_snippet' === $query->get( 'post_type' ) && 'type' === $query->get( 'orderby' ) ) {
			$orderby = "REGEXP_SUBSTR( {$wpdb->prefix}postmeta.meta_value, 'js|html|css') " . $query->get( 'order' );
		}
		if ( 'asenha_code_snippet' === $query->get( 'post_type' ) && 'active' === $query->get( 'orderby' ) ) {
			$orderby = "coalesce( postmeta1.meta_value, 'p' ) " . $query->get( 'order' );
		}
		return $orderby;
	}


	/**
	 * Order table by Type and Active columns
	 */
	function posts_join_paged( $join, $query ) {
		if ( ! is_admin() ) {
			return $join;
		}
		global $wpdb;

		if ( 'asenha_code_snippet' === $query->get( 'post_type' ) && 'type' === $query->get( 'orderby' ) ) {
			$join = "LEFT JOIN {$wpdb->prefix}postmeta ON {$wpdb->prefix}posts.ID = {$wpdb->prefix}postmeta.post_id";
		}

		if ( 'asenha_code_snippet' === $query->get( 'post_type' ) && 'active' === $query->get( 'orderby' ) ) {
			$join = "LEFT JOIN (SELECT post_id AS ID, meta_value FROM {$wpdb->prefix}postmeta WHERE meta_key = '_active' ) as postmeta1 USING( ID )";
		}
		return $join;
	}


	/**
	 * Order table by Type and Active columns
	 */
	function posts_where_paged( $where, $query ) {
		if ( ! is_admin() ) {
			return $where;
		}
		global $wpdb;

		if ( 'asenha_code_snippet' === $query->get( 'post_type' ) && 'type' === $query->get( 'orderby' ) ) {
			$where .= " AND {$wpdb->prefix}postmeta.meta_key = 'options'";
		}
		return $where;
	}

	/**
	 * Disable safe mode
	 */
	function wp_ajax_csm_disable_safe_mode() {
		 if ( isset( $_REQUEST ) && isset( $_REQUEST['code_id'] ) ) {
			$wp_config_options = array(
				'add'       => true, // Add the config if missing.
				'raw'       => true, // Display value in raw format without quotes.
				'normalize' => false, // Normalize config output using WP Coding Standards.
			);

			$wp_config = new ASENHA\Classes\WP_Config_Transformer;
			$success = $wp_config->update( 'constant', 'CSM_SAFE_MODE', 'false', $wp_config_options );
			if ( $success ) {
				sleep(2);
                echo json_encode( array( 
                    'success' => true 
                ) );
			} else {
                echo json_encode( array( 
                    'success' => false 
                ) );				
			}
		 }
	}

	/**
	 * Activate/deactivate a code
	 *
	 * @return void
	 */
	function wp_ajax_csm_active_code() {
		if ( ! isset( $_GET['code_id'] ) ) {
			die();
		}

		$code_id = absint( $_GET['code_id'] );

		$response = 'error';
		if ( check_admin_referer( 'csm-active-code-' . $code_id ) ) {

			if ( 'asenha_code_snippet' === get_post_type( $code_id ) ) {
				$active = get_post_meta( $code_id, '_active', true );
				$active = ( $active !== 'no' ) ? $active = 'yes' : 'no';

				update_post_meta( $code_id, '_active', $active === 'yes' ? 'no' : 'yes' );
				$this->build_search_tree();
			}
		}
		echo $active;

		die();
	}

	/**
	 * Check if a code is active
	 *
	 * @return bool
	 */
	function is_active( $post_id ) {
		return get_post_meta( $post_id, '_active', true ) !== 'no';
	}

	/**
	 * Reformat the `edit` screen
	 */
	function current_screen_edit() {
		?>
		<script type="text/javascript">
			 /* <![CDATA[ */
			jQuery(window).ready(function($){
				var h1 = '<?php _e( 'Code Snippets', 'admin-site-enhancements' ); ?> ';
				h1 += '<a href="post-new.php?post_type=asenha_code_snippet&language=css" class="page-title-action"><?php _e( 'Add CSS / SCSS Snippet', 'admin-site-enhancements' ); ?></a>';
				h1 += '<a href="post-new.php?post_type=asenha_code_snippet&language=js" class="page-title-action"><?php _e( 'Add JS Snippet', 'admin-site-enhancements' ); ?></a>';
				h1 += '<a href="post-new.php?post_type=asenha_code_snippet&language=html" class="page-title-action"><?php _e( 'Add HTML Snippet', 'admin-site-enhancements' ); ?></a>';
				h1 += '<a href="post-new.php?post_type=asenha_code_snippet&language=php" class="page-title-action"><?php _e( 'Add PHP Snippet', 'admin-site-enhancements' ); ?></a>';
				$("#wpbody-content h1").html(h1);
			});

		</script>
		<?php
	}


	/**
	 * Reformat the `post` screen
	 */
	function current_screen_post() {

		$this->remove_unallowed_metaboxes();

		$strings = array(
			'Add CSS / SCSS Snippet'   	=> __( 'Add CSS / SCSS Snippet', 'admin-site-enhancements' ),
			'Add JS Snippet'    		=> __( 'Add JS Snippet', 'admin-site-enhancements' ),
			'Add HTML Snippet'  		=> __( 'Add HTML Snippet', 'admin-site-enhancements' ),
			'Add PHP Snippet'  			=> __( 'Add PHP Snippet', 'admin-site-enhancements' ),
			'Edit CSS / SCSS Snippet'  	=> __( 'Edit CSS / SCSS Snippet', 'admin-site-enhancements' ),
			'Edit JS Snippet'   		=> __( 'Edit JS Snippet', 'admin-site-enhancements' ),
			'Edit HTML Snippet' 		=> __( 'Edit HTML Snippet', 'admin-site-enhancements' ),
			'Edit PHP Snippet' 			=> __( 'Edit PHP Snippet', 'admin-site-enhancements' ),
		);

		if ( isset( $_GET['post'] ) ) {
			$action  = 'Edit';
			$post_id = esc_attr( $_GET['post'] );
			// $snippet_status_class = ( $this->is_active( $post_id ) ) ? 'active' : 'inactive';
		} else {
			$action  = 'Add';
			$post_id = false;
		}
		$language = $this->get_language( $post_id );
		if ( 'css' == $language ) {
			$language = 'css / scss';
		}

		$title = $action . ' ' . strtoupper( $language ) . ' Snippet';
		$title = ( isset( $strings[ $title ] ) ) ? $strings[ $title ] : $strings['Add CSS / SCSS Snippet'];

		if ( $action == 'Edit' ) {
			// $title .= ' <span class="snippet-status ' . $snippet_status_class . '">' . $snippet_status . '</span>';
			// $title .= ' <a href="post-new.php?post_type=asenha_code_snippet&language=css" class="page-title-action">' . __( 'Add CSS Snippet', 'admin-site-enhancements' ) . '</a> ';
			// $title .= '<a href="post-new.php?post_type=asenha_code_snippet&language=js" class="page-title-action">' . __( 'Add JS Snippet', 'admin-site-enhancements' ) . '</a>';
			// $title .= '<a href="post-new.php?post_type=asenha_code_snippet&language=html" class="page-title-action">' . __( 'Add HTML Snippet', 'admin-site-enhancements' ) . '</a>';
			// $title .= '<a href="post-new.php?post_type=asenha_code_snippet&language=php" class="page-title-action">' . __( 'Add PHP Snippet', 'admin-site-enhancements' ) . '</a>';
		}

		?>
		<style type="text/css">
			#post-body-content, .edit-form-section { position: static !important; }
			#ed_toolbar { display: none; }
			#postdivrich { display: none; }
		</style>
		<script type="text/javascript">
			 /* <![CDATA[ */
			jQuery(window).ready(function($){
				$("#wpbody-content h1").html('<?php echo $title; ?>');
				$("#message.updated.notice").html('<p><?php _e( 'Snippet updated', 'admin-site-enhancements' ); ?></p>');

				var from_top = -$("#normal-sortables").height();
				if ( from_top != 0 ) {
					$(".csm_only_premium-first").css('margin-top', from_top.toString() + 'px' );
				} else {
					$(".csm_only_premium-first").hide();
				}
			});
			/* ]]> */
		</script>
		<?php
	}


	/**
	 * Remove unallowed metaboxes from code-snippets-manager edit page
	 *
	 * Use the code-snippets-manager-meta-boxes filter to add/remove allowed metaboxdes on the page
	 */
	function remove_unallowed_metaboxes() {
		global $wp_meta_boxes;

		// Side boxes
		$allowed = array( 
			'submitdiv', 
			'code-snippet-options', 
			'code-snippet-description', 
			'asenha_code_snippet_categorydiv' 
		);

		$allowed = apply_filters( 'asenha_code_snippet-meta-boxes', $allowed );

		foreach ( $wp_meta_boxes['asenha_code_snippet']['side'] as $_priority => $_boxes ) {
			foreach ( $_boxes as $_key => $_value ) {
				if ( ! in_array( $_key, $allowed ) ) {
					unset( $wp_meta_boxes['asenha_code_snippet']['side'][ $_priority ][ $_key ] );
				}
			}
		}

		// Normal boxes. vsm-post-meta is for Entity Viewer plugin's meta box.
		$allowed = array( 
			'slugdiv', 
			'previewdiv', 
			'url-rules', 
			'revisionsdiv', 
			'vsm-post-meta', 
			'code-snippet-options', 
			'code-snippet-description' 
		);

		$allowed = apply_filters( 'asenha_code_snippet-meta-boxes-normal', $allowed );

		if ( isset( $wp_meta_boxes['asenha_code_snippet']['normal'] ) ) {
			foreach ( $wp_meta_boxes['asenha_code_snippet']['normal'] as $_priority => $_boxes ) {
				foreach ( $_boxes as $_key => $_value ) {
					if ( ! in_array( $_key, $allowed ) ) {
						unset( $wp_meta_boxes['asenha_code_snippet']['normal'][ $_priority ][ $_key ] );
					}
				}
			}
		}

		// Advanced meta boxes.
		$allowed = array( 
			'code-snippet-options', 
			'code-snippet-description' 
		);

		$allowed = apply_filters( 'asenha_code_snippet-meta-boxes-advanced', $allowed );

		if ( isset( $wp_meta_boxes['asenha_code_snippet']['advanced'] ) ) {
			foreach ( $wp_meta_boxes['asenha_code_snippet']['advanced'] as $_priority => $_boxes ) {
				foreach ( $_boxes as $_key => $_value ) {
					if ( ! in_array( $_key, $allowed ) ) {
						unset( $wp_meta_boxes['asenha_code_snippet']['advanced'][ $_priority ][ $_key ] );
					}
				}
			}
		}

	}



	/**
	 * Add the codemirror editor in the `post` screen
	 */
	public function codemirror_editor( $post ) {

		$current_screen = get_current_screen();

		if ( $current_screen->post_type != 'asenha_code_snippet' ) {
			return false;
		}

		if ( empty( $post->post_title ) && empty( $post->post_content ) ) {
			$new_post = true;
			$post_id  = false;
		} else {
			$new_post = false;
			if ( ! isset( $_GET['post'] ) ) {
				$_GET['post'] = $post->id;
			}
			$post_id = esc_attr( $_GET['post'] );
		}
		$language = $this->get_language( $post_id );

        $extra_options = get_option( ASENHA_SLUG_U . '_extra', array() );
        $settings = isset( $extra_options['code_snippets_manager_settings'] ) ? $extra_options['code_snippets_manager_settings'] : array();

		// Replace the htmlentities (https://wordpress.org/support/topic/annoying-bug-in-text-editor/), but only selectively
		if ( isset( $settings['csm_htmlentities'] ) && $settings['csm_htmlentities'] == 1 && strstr( $post->post_content, '&' ) ) {

			// First the ampresands
			$post->post_content = str_replace( '&amp', htmlentities( '&amp' ), $post->post_content );

			// Then the rest of the entities
			$html_flags = defined( 'ENT_HTML5' ) ? ENT_QUOTES | ENT_HTML5 : ENT_QUOTES;
			$entities   = get_html_translation_table( HTML_ENTITIES, $html_flags );
			unset( $entities[ array_search( '&amp;', $entities ) ] );
			$regular_expression = str_replace( ';', '', '/(' . implode( '|', $entities ) . ')/i' );
			preg_match_all( $regular_expression, $post->post_content, $matches );
			if ( isset( $matches[0] ) && count( $matches[0] ) > 0 ) {
				foreach ( $matches[0] as $_entity ) {
					$post->post_content = str_replace( $_entity, htmlentities( $_entity ), $post->post_content );
				}
			}
		}

		if ( isset( $settings['csm_htmlentities2'] ) && $settings['csm_htmlentities2'] == 1 ) {
			$post->post_content = htmlentities( $post->post_content );
		}

		switch ( $language ) {
			case 'js':
				$code_mirror_mode   = 'text/javascript';
				$code_mirror_before = '<script type="text/javascript">';
				$code_mirror_after  = '</script>';
				break;
			case 'html':
				$code_mirror_mode   = 'html';
				$code_mirror_before = '';
				$code_mirror_after  = '';
				break;
			case 'php':
				if ( $new_post ) {
					$post->post_content = '<?php' . PHP_EOL;
				}
				$code_mirror_mode   = 'php';
				$code_mirror_before = '';
				$code_mirror_after  = '';
				
				if ( ! $new_post ) {
					// Check if snippet has an error, or caused an error in the previous execution
					$php_snippet_has_error = get_post_meta( $post_id, 'php_snippet_has_error', true );
					$php_snippet_error_type = get_post_meta( $post_id, 'php_snippet_error_type', true );
					$php_snippet_error_message = get_post_meta( $post_id, 'php_snippet_error_message', true );
					$is_safe_mode_enabled = defined( 'CSM_SAFE_MODE' ) ? CSM_SAFE_MODE : false;

					if ( $php_snippet_has_error ) {
						$error_message_div = '<div class="php-snippet-error">';
										
						if ( 'fatal' == $php_snippet_error_type ) {
							$error_message_div .= '<div class="php-snippet-error-intro">This snippet caused the following <span class="php-error-status fatal">fatal error</span>:</div>';
						} else {
							$error_message_div .= '<div class="php-snippet-error-intro">This snippet caused the following <span class="php-error-status non-fatal">non-fatal error</strong>:</span></div>';
						}
						
						$error_message_div .= '<div class="php-snippet-error-message">' . $php_snippet_error_message . '</div>';

						if ( 'fatal' == $php_snippet_error_type ) {
							$error_message_div .= '<div class="php-snippet-next-action">This has <strong>triggered safe mode</strong> to be enabled and all PHP snippets execution are currently stopped. Please <strong>fix the code</strong> causing the error, <strong>update</strong> the snippet, and only then, <strong>activate</strong> the snippet and <a id="disable-csm-safe-mode-link" href="#"><strong>disable safe mode</strong></a>.</div>';						
						} else {
							$error_message_div .= '<div class="php-snippet-next-action">Please <strong>fix the code</strong> causing the error, and <strong>update</strong> the snippet. If you plan on doing that later, you can <strong>deactivate</strong> the snippet for now so it will stop triggering the error.</div>';							
						}

						$error_message_div .= '</div>';
					} else {
						$error_message_div = '';
					}
				}

				break;
			default:
				$code_mirror_mode   = 'text/css';
				$code_mirror_before = '<style type="text/css">';
				$code_mirror_after  = '</style>';

		}
		
		?>
				<div id="code-snippet-description-wrapper" style="margin-top: 8px; margin-bottom: 8px;border:1px solid #c3c4c7;"></div>
				<div class="code-mirror-buttons">
				<div class="button-left" id="csm-fullscreen-button" alt="<?php _e( 'Distraction-free writing mode', 'code-snippets-manager-pro' ); ?>"><span rel="tipsy" original-title="<?php _e( 'Fullscreen', 'code-snippets-manager-pro' ); ?>"><button role="presentation" type="button" tabindex="-1"><i class="csm-i-fullscreen"></i> <span>Go fullscreen</span></button></span></div>
				<div class="button-right"><!--<span rel="tipsy" original-title="<?php // _e( 'Beautify Code', 'code-snippets-manager-pro' ); ?>"><button type="button" tabindex="-1" id="csm-beautifier"><i class="csm-i-beautifier"></i></button></span>--></div>
				<!--<div class="button-left"><span rel="tipsy" original-title="<?php // _e( 'Editor Settings', 'code-snippets-manager-pro' ); ?>"><button type="button" tabindex="-1" id="csm-settings"><i class="csm-i-settings"></i></button></span></div>-->

<input type="hidden" name="fullscreen" id="csm-fullscreen-hidden" value="false" />
<!-- div class="button-right" id="csm-search-button" alt="Search"><button role="presentation" type="button" tabindex="-1"><i class="csm-i-find"></i></button></div -->

				</div>
				<?php 
				if ( isset( $error_message_div ) && 'php' == $language ) {
					echo $error_message_div;
				}
				?>
				<div class="code-mirror-before"><div><?php echo htmlentities( $code_mirror_before ); ?></div></div>
				<textarea class="wp-editor-area" id="csm_content" mode="<?php echo htmlentities( $code_mirror_mode ); ?>" name="content" style="width:100%;min-height:500px;margin-top:0;color:#272822;background-color:#272822;border-radius:0;"><?php echo $post->post_content; ?></textarea>
				<div class="code-mirror-after"><div><?php echo htmlentities( $code_mirror_after ); ?></div></div>

				<table id="post-status-info"><tbody><tr>
					<td class="autosave-info">
					<span class="autosave-message">&nbsp;</span>
				<?php
				if ( 'auto-draft' != $post->post_status ) {
					echo '<span id="last-edit">';
					if ( $last_user = get_userdata( get_post_meta( $post->ID, '_edit_last', true ) ) ) {
						printf( __( 'Last edited by %1$s on %2$s at %3$s', 'code-snippets-manager-pro' ), esc_html( $last_user->display_name ), mysql2date( get_option( 'date_format' ), $post->post_modified ), mysql2date( get_option( 'time_format' ), $post->post_modified ) );
					} else {
						printf( __( 'Last edited on %1$s at %2$s', 'code-snippets-manager-pro' ), mysql2date( get_option( 'date_format' ), $post->post_modified ), mysql2date( get_option( 'time_format' ), $post->post_modified ) );
					}
					echo '</span>';
				}
				?>
					</td>
				</tr></tbody></table>


				<input type="hidden" id="update-post_<?php echo $post->ID; ?>" value="<?php echo wp_create_nonce( 'update-post_' . $post->ID ); ?>" />
		<?php

	}



	/**
	 * Show the options form in the `post` screen
	 */
	function code_snippet_options_meta_box_callback( $post ) {

		$options = $this->get_options( $post->ID );
		if ( ! isset( $options['preprocessor'] ) ) {
			$options['preprocessor'] = 'none';
		}

		if ( isset( $_GET['language'] ) ) {
			$options['language'] = $this->get_language();
		}

		$meta = $this->get_options_meta();
		if ( $options['language'] == 'html' ) {
			$meta = $this->get_options_meta_html();
		}
		if ( $options['language'] == 'php' ) {
			$meta = $this->get_options_meta_php();
		}

		$options['multisite'] = false;

		wp_nonce_field( 'options_save_meta_box_data', 'code-snippets-manager_meta_box_nonce' );

		?>
			<div class="options_meta_box <?php echo esc_html( $options['language'] ); ?>">
			<?php

			$output = '';

			foreach ( $meta as $_key => $a ) {
				$close_div = false;

				if ( ( $_key == 'preprocessor' && $options['language'] == 'css' ) ||
					( $_key == 'linking' && $options['language'] == 'html' ) ||
					in_array( $_key, ['priority', 'minify', 'multisite' ] ) ) {
					$close_div = true;
					$output   .= '<div class="csm_opaque">';
				}

				// Don't show Pre-processors for JavaScript and PHP Codes
				if ( ( $options['language'] == 'js' && $_key == 'preprocessor' ) 
				|| ( $options['language'] == 'php' && $_key == 'preprocessor' )
				) {
					continue;
				}

				$output .= '<h3 class="' . $options['language'] . ' ' . $_key . '">' . esc_attr( $a['title'] ) . '</h3>' . PHP_EOL;

				$output .= $this->render_input( $_key, $a, $options );

				if ( $close_div ) {
					$output .= '</div>';
				}
			}

			echo $output;

			?>

			<input type="hidden" name="code_snippet_language" value="<?php echo $options['language']; ?>" />

			<div style="clear: both;"></div>

			</div>

			<?php
	}

	/**
	 * Add description meta box
	 * 
	 * @since 6.2.0
	 */
	function code_snippet_description_meta_box_callback( $post ) {
		$content = get_post_meta( $post->ID, 'code_snippet_description', true );
		$editor_settings = array(
			'wpautop' 			=> true,
			'media_buttons'		=> false,
			'tinymce'			=> true,
			'quicktags'			=> false,
			'teeny'				=> false, // minimal editor, less buttons/options in TinyMCE
			'drag_drop_upload'	=> false,
			'textarea_rows'		=> 4,
			'tinymce'			=> array(
				'toolbar1'		=> 'bold,italic,underline,strikethrough,forecolor,blockquote,bullist,numlist,link,unlink,indent,outdent,undo,redo,charmap,pastetext,removeformat,code,fullscreen',
				'content_css'	=> 	ASENHA_URL . 'includes/premium/code-snippets-manager/assets/csm_tinymce.css',
			),
		);
		wp_editor( $content, 'code_snippet_description', $editor_settings );
	}

	/**
	 * Get an array with all the information for building the code's options
	 */
	function get_options_meta() {
		$options = array(
			'linking'      => array(
				'title'   => __( 'Load snippet', 'admin-site-enhancements' ),
				'type'    => 'radio',
				'default' => 'internal',
				'values'  => array(
					'external' => array(
						'title'    => __( 'As a file', 'admin-site-enhancements' ),
						'dashicon' => 'media-code',
					),
					'internal' => array(
						'title'    => __( 'Inline', 'admin-site-enhancements' ),
						'dashicon' => 'editor-alignleft',
					),
				),
			),
			'type'         => array(
				'title'   => __( 'Position on page', 'admin-site-enhancements' ),
				'type'    => 'radio',
				'default' => 'header',
				'values'  => array(
					'header' => array(
						'title'    => __( '&lt;head&gt;', 'admin-site-enhancements' ),
						'dashicon' => 'arrow-up-alt2',
					),
					'footer' => array(
						'title'    => __( '&lt;footer&gt;', 'admin-site-enhancements' ),
						'dashicon' => 'arrow-down-alt2',
					),
				),
			),
			'side'         => array(
				'title'   => __( 'On which part of the site?', 'admin-site-enhancements' ),
				'type'    => 'checkbox',
				'default' => 'frontend',
				'values'  => array(
					'frontend' => array(
						'title'    => __( 'Frontend', 'admin-site-enhancements' ),
						'dashicon' => 'tagcloud',
					),
					'admin'    => array(
						'title'    => __( 'Admin', 'admin-site-enhancements' ),
						'dashicon' => 'id',
					),
					'login'    => array(
						'title'    => __( 'Login page', 'admin-site-enhancements' ),
						'dashicon' => 'admin-network',
					),
				),
			),
		);

		return $options;
	}


	/**
	 * Get an array with all the information for building the code's options
	 */
	function get_options_meta_html() {
		$options = array(
			'type'         => array(
				'title'   => __( 'Position on page', 'admin-site-enhancements' ),
				'type'    => 'radio',
				'default' => 'header',
				'values'  => array(
					'header' => array(
						'title'    => __( '&lt;head&gt;', 'admin-site-enhancements' ),
						'dashicon' => 'arrow-up-alt2',
					),
					'footer' => array(
						'title'    => __( '&lt;footer&gt;', 'admin-site-enhancements' ),
						'dashicon' => 'arrow-down-alt2',
					),
				),
			),
			'side'     => array(
				'title'   => __( 'On which part of the site?', 'admin-site-enhancements' ),
				'type'    => 'checkbox',
				'default' => 'frontend',
				'values'  => array(
					'frontend' => array(
						'title'    => __( 'Frontend', 'admin-site-enhancements' ),
						'dashicon' => 'tagcloud',
					),
					'admin'    => array(
						'title'    => __( 'Admin', 'admin-site-enhancements' ),
						'dashicon' => 'id',
					),
				),
			),
		);

		if ( function_exists( 'wp_body_open' ) ) {
			$tmp = $options['type']['values'];
			unset( $options['type']['values'] );
			$options['type']['values']['header']    = $tmp['header'];
			$options['type']['values']['body_open'] = array(
				'title'    => __( '&lt;body&gt;', 'admin-site-enhancements' ),
				'dashicon' => 'editor-code',
			);
			$options['type']['values']['footer']    = $tmp['footer'];
		}

		return $options;
	}



	/**
	 * Get an array with all the information for building the code's options
	 */
	function get_options_meta_php() {
		$options = array(
			'side'     => array(
				'title'   => __( 'On which part of the site?', 'admin-site-enhancements' ),
				'type'    => 'checkbox',
				'default' => 'sitewide',
				'values'  => array(
					'sitewide'    => array(
						'title'    => __( 'Everywhere', 'admin-site-enhancements' ),
						'dashicon' => 'id',
					),
				),
			),
			'notes'     => array(
				'title'   => '',
				'type'    => 'html',
				'default' => 'Some notes here...',
				'values'  => array(
					'notes'    => array(
						'title'    => '<p>The snippet will be executed on page load via the <code>plugins_loaded</code> hook.</p>
										Use the proper condition(s) in your code for fine-grained control. e.g. <code>is_admin()</code>, <code>is_single()</code>, etc.</p><p>When fatal error occurs and your site is not accessible, <a href="https://www.wpase.com/documentation/code-snippets-manager/" target="_blank">edit wp-config.php</a> to regain access.</p>',
						'dashicon' => 'id',
					),
				),
			),
		);

		return $options;
	}
	
	

	/**
	 * Save the post and the metadata
	 */
	function options_save_meta_box_data( $post_id ) {

		// The usual checks
		if ( ! isset( $_POST['code-snippets-manager_meta_box_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_POST['code-snippets-manager_meta_box_nonce'], 'options_save_meta_box_data' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( isset( $_POST['post_type'] ) && 'asenha_code_snippet' != $_POST['post_type'] ) {
			return;
		}
		
		// Save snippet description
		
		if ( isset( $_POST['code_snippet_description'] ) ) {
			update_post_meta( $post_id, 'code_snippet_description', wp_kses_post( $_POST['code_snippet_description'] ) );
		}

		// Update the post's meta
		$defaults = array(
			'type'     => 'header',
			'linking'  => 'internal',
			'side'     => 'frontend',
			'language' => 'css',
			'priority' => 5,
		);

		if ( $_POST['code_snippet_language'] == 'html' ) {
			$defaults = array(
				'type'     => 'header',
				'linking'  => 'both',
				'side'     => 'frontend',
				'language' => 'html',
				'priority' => 5,
			);
		}

		if ( $_POST['code_snippet_language'] == 'php' ) {
			$defaults = array(
				'type'     => 'none',
				'linking'  => 'external',
				'side'     => 'sitewide',
				'language' => 'php',
				'priority' => 5,
			);
		}

		foreach ( $defaults as $_field => $_default ) {
			$options[ $_field ] = isset( $_POST[ 'code_snippet_' . $_field ] ) ? esc_attr( strtolower( $_POST[ 'code_snippet_' . $_field ] ) ) : $_default;
		}

		$options['side'] = [];
		foreach ( ['frontend', 'admin', 'login', 'sitewide'] as $_side ) {
			if ( isset( $_POST[ 'code_snippet_side-' . $_side ] ) && $_POST[ 'code_snippet_side-' . $_side ] == '1' ) {
				$options['side'][] = $_side;
			}
		}
		if ( count( $options['side'] ) === 0 ) {
			if ( $_POST['code_snippet_language'] == 'php' ) {
				$options['side'] = ['sitewide'];			
			} else {
				$options['side'] = ['frontend'];			
			}
		}
		$options['side'] = implode(',', $options['side'] );

		$options['language'] = in_array( $options['language'], array( 'html', 'css', 'js', 'php' ), true ) ? $options['language'] : $defaults['language'];

		update_post_meta( $post_id, 'options', $options );

		if ( $options['language'] == 'html' ) {
			$this->build_search_tree();
			return;
		}

		if ( $options['language'] == 'js' ) {
			// Replace the default comment
			if ( preg_match( '@/\* Add your JavaScript code here[\s\S]*?End of comment \*/@im', $_POST['content'] ) ) {
				$_POST['content'] = preg_replace( '@/\* Add your JavaScript code here[\s\S]*?End of comment \*/@im', '/* Default comment here */', $_POST['content'] );
			}

			// For other locales remove all the comments
			if ( substr( get_locale(), 0, 3 ) !== 'en_' ) {
				$_POST['content'] = preg_replace( '@/\*[\s\S]*?\*/@', '', $_POST['content'] );
			}
		}

		// Save the Code Snippet in a file in `wp-content/uploads/code-snippets`
		if ( $options['linking'] == 'internal' ) {

			// $before = '<!-- start Code Snippets Manager -->' . PHP_EOL;
			$before = '';
			// $after  = '<!-- end Code Snippets Manager -->' . PHP_EOL;
			$after  = '';
			if ( $options['language'] == 'css' ) {
				$before .= '<style type="text/css">' . PHP_EOL;
				$after   = '</style>' . PHP_EOL . $after;
			}
			if ( $options['language'] == 'js' ) {
				if ( ! preg_match( '/<script\b[^>]*>([\s\S]*?)<\/script>/im', $_POST['content'] ) ) {
					$before .= '<script type="text/javascript">' . PHP_EOL;
					$after   = '</script>' . PHP_EOL . $after;
				} else {
					// the content has a <script> tag, then remove the comments so they don't show up on the frontend
					$_POST['content'] = preg_replace( '@/\*[\s\S]*?\*/@', '', $_POST['content'] );
				}
			}
		}

		if ( $options['linking'] == 'external' ) {
			$before = '/******* Do not edit this file *******' . PHP_EOL .
			'Code Snippets Manager' . PHP_EOL .
			'Saved: ' . date( 'M d Y | H:i:s' ) . ' */' . PHP_EOL;
			$after  = '';
		}
		
		if ( $options['language'] == 'php' ) {
			$before = '';
			$after = '';
		}

		if ( wp_is_writable( CSM_UPLOAD_DIR ) ) {
			$file_name    = $post_id . '.' . $options['language'];
			
			if ( $options['language'] == 'css' ) {
				// Try to compile SCSS if it's part of the CSS code
				$code_snippet = $this->scss_compiler( stripslashes( $_POST['content'] ) );
			} else {
				$code_snippet = stripslashes( $_POST['content'] );
			}
						
			$file_content = $before . $code_snippet . $after;
			@file_put_contents( CSM_UPLOAD_DIR . '/' . $file_name, $file_content );

			// save the file as the Permalink slug
			$slug = get_post_meta( $post_id, '_slug', true );
			if ( $slug ) {
				@file_put_contents( CSM_UPLOAD_DIR . '/' . sanitize_file_name( $slug ) . '.' . $options['language'], $file_content );
			}
		}

		$this->build_search_tree();
	}
	
	/**
	 * Restore the revision in the code snippet file
	 * 
	 * @since 7.1.5
	 */
	public function restore_snippet_revision_in_file( $post_id, $revision_id ) {
		if ( wp_is_writable( CSM_UPLOAD_DIR ) ) {
			$options = get_post_meta( $post_id, 'options', true );

			if ( isset( $options['language'] ) ) {
				$file_path = CSM_UPLOAD_DIR . '/' . $post_id . '.' . $options['language'];

				if ( is_file( $file_path ) ) {
					$post = get_post( $post_id );
					$post_content = $post->post_content;
					
					if ( 'css' == $options['language'] ) {
						// Try to compile SCSS if it's part of the CSS code
						$code_snippet = $this->scss_compiler( stripslashes( $post_content ) );
					} else {
						$code_snippet = stripslashes( $post_content );
					}
					
					$options = get_post_meta( $post_id, 'options', true );

					if ( $options['linking'] == 'internal' ) {

						// $before = '<!-- start Code Snippets Manager -->' . PHP_EOL;
						$before = '';
						// $after  = '<!-- end Code Snippets Manager -->' . PHP_EOL;
						$after  = '';
						if ( $options['language'] == 'css' ) {
							$before .= '<style type="text/css">' . PHP_EOL;
							$after   = '</style>' . PHP_EOL . $after;
						}
						if ( $options['language'] == 'js' ) {
							if ( ! preg_match( '/<script\b[^>]*>([\s\S]*?)<\/script>/im', $post_content ) ) {
								$before .= '<script type="text/javascript">' . PHP_EOL;
								$after   = '</script>' . PHP_EOL . $after;
							} else {
								// the content has a <script> tag, then remove the comments so they don't show up on the frontend
								$post_content = preg_replace( '@/\*[\s\S]*?\*/@', '', $post_content );
							}
						}
					}

					if ( $options['linking'] == 'external' ) {
						$before = '/******* Do not edit this file *******' . PHP_EOL .
						'Code Snippets Manager' . PHP_EOL .
						'Saved: ' . date( 'M d Y | H:i:s' ) . ' */' . PHP_EOL;
						$after  = '';
					}
					
					if ( $options['language'] == 'php' ) {
						$before = '';
						$after = '';
					}

					$file_content = $before . $code_snippet . $after;
					@file_put_contents( $file_path, $file_content );
				}				
			}
		}

		$this->build_search_tree();
	}
	
	/**
	 * SCSS Compiler Function
	 * 
	 * @since 6.3.0
	 */
	public function scss_compiler( $scss ) {

		// SCSS compiler
		// $compiler = new Compiler();
		$compiler = new \ScssPhp\ScssPhp\Compiler();
		$compiled_css = $compiler->compileString( $scss )->getCss();
		return $compiled_css;

	}

	/**
	 * Create the code-snippets-manager dir in uploads directory
	 *
	 * Show a message if the directory is not writable
	 *
	 * Create an empty index.php file inside
	 */
	function create_uploads_directory() {
		$current_screen = get_current_screen();

		// Check if we are editing a code-snippets-manager post
		if ( $current_screen->base != 'post' || $current_screen->post_type != 'asenha_code_snippet' ) {
			return false;
		}

		$dir = CSM_UPLOAD_DIR;

		// Create the dir if it doesn't exist
		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );
		}

		// Show a message if it couldn't create the dir
		if ( ! file_exists( $dir ) ) :
			?>
			 <div class="notice notice-error is-dismissible">
			 <p><?php 
			 printf( 
			 	/* translators: %s is directory slug 'code-snippets-manager' */
			 	__( 'The %s directory could not be created', 'admin-site-enhancements' ), 
			 	'<b>code-snippets-manager</b>' 
			 	); 
			 	?>
		 	</p>
			 <p><?php _e( 'Please run the following commands in order to make the directory', 'admin-site-enhancements' ); ?>: <br /><strong>mkdir <?php echo $dir; ?>; </strong><br /><strong>chmod 777 <?php echo $dir; ?>;</strong></p>
			</div>
			<?php
			return;
endif;

		// Show a message if the dir is not writable
		if ( ! wp_is_writable( $dir ) ) :
			?>
			 <div class="notice notice-error is-dismissible">
			 <p><?php 
			 	printf( 
			 		/* translators: %s is the directory path */
			 		__( 'The %s directory is not writable, therefore the code snippet files cannot be saved.', 'admin-site-enhancements' ), 
			 		'<b>' . $dir . '</b>' 
			 		); ?></p>
			 <p><?php _e( 'Please run the following command to make the directory writable', 'admin-site-enhancements' ); ?>:<br /><strong>chmod 777 <?php echo $dir; ?> </strong></p>
			</div>
			<?php
			return;
endif;

		// Write a blank index.php
		if ( ! file_exists( $dir . '/index.php' ) ) {
			$content = '<?php' . PHP_EOL . '// Silence is golden.';
			@file_put_contents( $dir . '/index.php', $content );
		}
	}


	/**
	 * Build a tree where you can quickly find the needed code-snippets-manager posts
	 *
	 * @return void
	 */
	private function build_search_tree() {

		// Retrieve all the code-snippets-manager codes
		$posts = query_posts( 'post_type=asenha_code_snippet&post_status=publish&nopaging=true' );

		$tree = array();
		foreach ( $posts as $_post ) {
			if ( ! $this->is_active( $_post->ID ) ) {
				continue;
			}

			$options = $this->get_options( $_post->ID );

			$filename = $_post->ID . '.' . $options['language'];

			if ( $options['linking'] == 'external' && $options['language'] != 'php' ) {
				$filename .= '?v=' . rand( 1, 10000 );
			}

			// Add the code file names to the branches, example: frontend-css-header-external
			$sides = explode( ',', $options['side'] );
			$branch = $options['language'] . '-' . $options['type'] . '-' . $options['linking'];
			foreach ( $sides as $_side ) {
				$tree[ $_side . '-' . $branch ][] = $filename;
			}

			// Mark to enqueue the jQuery library, if necessary
			if ( $options['language'] === 'js' ) {
				$_post->post_content = preg_replace( '@/\* Add your JavaScript code here[\s\S]*?End of comment \*/@im', '/* Default comment here */', $_post->post_content );
				if ( preg_match( '/jquery\s*(\(|\.)/i', $_post->post_content ) && ! isset( $tree['jquery'] ) ) {
					$tree['jquery'] = true;
				}
			}

		}

		// Save the tree in the database
		$extra_options = get_option( ASENHA_SLUG_U . '_extra', array() );
		$extra_options['code_snippets_manager_tree'] = $tree;
		update_option( ASENHA_SLUG_U. '_extra', $extra_options, true );
	}

	/**
	 * Rebuilt the tree when you trash or restore a custom code
	 */
	function trash_post( $post_id ) {
		$this->build_search_tree();
	}

	/**
	 * Render the checkboxes, radios, selects and inputs
	 */
	function render_input( $_key, $a, $options ) {
		$language = $this->get_language();
		
		$name   = 'code_snippet_' . $_key;
		$output = '';

		// Show radio type options
		if ( $a['type'] === 'radio' ) {
			$output .= '<div class="radio-group ' . $language . ' ' . $_key . '">' . PHP_EOL;
			foreach ( $a['values'] as $__key => $__value ) {
				$id        = $name . '-' . $__key;
				$dashicons = isset( $__value['dashicon'] ) ? 'dashicons-before dashicons-' . $__value['dashicon'] : '';
				$selected  = ( isset( $a['disabled'] ) && $a['disabled'] ) ? ' disabled="disabled"' : '';
				$selected .= ( $__key == $options[ $_key ] ) ? ' checked="checked" ' : '';
				$output   .= '<input type="radio" ' . $selected . 'value="' . $__key . '" name="' . $name . '" id="' . $id . '">' . PHP_EOL;
				$output   .= '<label class="' . $dashicons . '" for="' . $id . '"> ' . esc_attr( $__value['title'] ) . '</label><br />' . PHP_EOL;
			}
			$output .= '</div>' . PHP_EOL;
		}

		// Show checkbox type options
		if ( $a['type'] == 'checkbox' ) {
			$output .= '<div class="radio-group ' . $language . ' ' . $_key . '">' . PHP_EOL;
			if ( isset( $a['values'] ) && count( $a['values'] ) > 0 ) {
				$current_values = explode(',', $options[ $_key ] );
				foreach ( $a['values'] as $__key => $__value ) {
					$id        = $name . '-' . $__key;
					$dashicons = isset( $__value['dashicon'] ) ? 'dashicons-before dashicons-' . $__value['dashicon'] : '';
					$selected  = ( isset( $a['disabled'] ) && $a['disabled'] ) ? ' disabled="disabled"' : '';
					$selected .= ( in_array( $__key, $current_values ) ) ? ' checked="checked" ' : '';
					$output   .= '<input type="checkbox" ' . $selected . ' value="1" name="' . $id . '" id="' . $id . '">' . PHP_EOL;
					$output   .= '<label class="' . $dashicons . '" for="' . $id . '"> ' . esc_attr( $__value['title'] ) . '</label><br />' . PHP_EOL;
				}
			} else {
				$dashicons = isset( $a['dashicon'] ) ? 'dashicons-before dashicons-' . $a['dashicon'] : '';
				$selected  = ( isset( $options[ $_key ] ) && $options[ $_key ] == '1' ) ? ' checked="checked" ' : '';
				$selected .= ( isset( $a['disabled'] ) && $a['disabled'] ) ? ' disabled="disabled"' : '';
				$output   .= '<input type="checkbox" ' . $selected . ' value="1" name="' . $name . '" id="' . $name . '">' . PHP_EOL;
				$output   .= '<label class="' . $dashicons . '" for="' . $name . '"> ' . esc_attr( $a['title'] ) . '</label>' . PHP_EOL;
			}
			$output .= '</div>' . PHP_EOL;
		}

		// Show select type options
		if ( $a['type'] == 'select' ) {
			$output .= '<div class="radio-group ' . $language . ' ' . $_key . '">' . PHP_EOL;
			$output .= '<select name="' . $name . '" id="' . $name . '">' . PHP_EOL;
			foreach ( $a['values'] as $__key => $__value ) {
				$selected = ( isset( $options[ $_key ] ) && $options[ $_key ] == $__key ) ? ' selected="selected"' : '';
				$output  .= '<option value="' . $__key . '"' . $selected . '>' . esc_attr( $__value ) . '</option>' . PHP_EOL;
			}
			$output .= '</select>' . PHP_EOL;
			$output .= '</div>' . PHP_EOL;
		}

		// Show html
		if ( $a['type'] === 'html' ) {
			$output .= '<div class="html-description ' . $language . ' ' . $_key . '">' . PHP_EOL;
			foreach ( $a['values'] as $__key => $__value ) {
				$id        = $name . '-' . $__key;
				$dashicons = isset( $__value['dashicon'] ) ? 'dashicons-before dashicons-' . $__value['dashicon'] : '';
				$output   .= $__value['title'] . PHP_EOL;
			}
			$output .= '</div>' . PHP_EOL;
		}

		return $output;

	}


	/**
	 * Get the language for the current post
	 */
	function get_language( $post_id = false ) {
		if ( $post_id == false ) {
			if ( isset( $_GET['post'] ) ) {
				$post_id = intval( $_GET['post'] );
			}
		}
		if ( $post_id !== false ) {
			$options  = $this->get_options( $post_id );
			$language = $options['language'];
		} else {
			$language = isset( $_GET['language'] ) ? esc_attr( strtolower( $_GET['language'] ) ) : 'css';
		}
		if ( ! in_array( $language, array( 'css', 'js', 'html', 'php' ) ) ) {
			$language = 'css';
		}

		return $language;
	}


	/**
	 * Show the activate/deactivate link in the row's action area
	 */
	function post_row_actions( $actions, $post ) {
		if ( 'asenha_code_snippet' !== $post->post_type ) {
			return $actions;
		}

		$url = wp_nonce_url( admin_url( 'admin-ajax.php?action=csm_active_code&code_id=' . $post->ID ), 'csm-active-code-' . $post->ID );
		if ( $this->is_active( $post->ID ) ) {
			$active_title = __( 'The code is active. Click to deactivate it', 'admin-site-enhancements' );
			$active_text  = __( 'Deactivate', 'admin-site-enhancements' );
		} else {
			$active_title = __( 'The code is inactive. Click to activate it', 'admin-site-enhancements' );
			$active_text  = __( 'Activate', 'admin-site-enhancements' );
		}
		$actions['activate'] = '<a href="' . esc_url( $url ) . '" title="' . $active_title . '" class="csm_activate_deactivate" data-code-id="' . $post->ID . '">' . $active_text . '</a>';

		return $actions;
	}


	/**
	 * Show the activate/deactivate link in admin.
	 */
	public function post_submitbox_start() {
		global $post;

		if ( ! is_object( $post ) ) {
			return;
		}

		if ( 'asenha_code_snippet' !== $post->post_type ) {
			return;
		}

		if ( ! isset( $_GET['post'] ) ) {
			return;
		}

		$url = wp_nonce_url( admin_url( 'admin-ajax.php?action=csm_active_code&code_id=' . $post->ID ), 'csm-active-code-' . $post->ID );

		if ( $this->is_active( $post->ID ) ) {
			// https://icon-sets.iconify.design/zondicons/checkmark/
			$icon   = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 20 20"><path fill="currentColor" d="m0 11l2-2l5 5L18 3l2 2L7 18z"/></svg>';
			$text   = __( 'Active', 'admin-site-enhancements' );
			$action = __( 'Deactivate', 'admin-site-enhancements' );
			$status_class = 'active';
		} else {
			$icon = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 20 20"><path fill="currentColor" d="M10 0C4.478 0 0 4.478 0 10s4.478 10 10 10s10-4.478 10-10S15.522 0 10 0Zm0 18.304A8.305 8.305 0 0 1 3.56 4.759l11.681 11.68A8.266 8.266 0 0 1 10 18.305Zm6.44-3.063L4.759 3.561a8.305 8.305 0 0 1 11.68 11.68Z"/></svg>';
			$text   = __( 'Inactive', 'admin-site-enhancements' );
			$action = __( 'Activate', 'admin-site-enhancements' );
			$status_class = 'inactive';
		}
		?>
		<div id="activate-action"><span class="snippet-status <?php echo esc_attr( $status_class ); ?>" style="font-weight: 600;">
		<?php echo $icon; ?>
		<?php echo $text; ?></span>
		<a class="button button-small csm_activate_deactivate" data-code-id="<?php echo $post->ID; ?>" href="<?php echo esc_url( $url ); ?>"><?php echo $action; ?></a>
		</div>
		<?php
	}


	/**
	 * Show the Permalink edit form
	 */
	function edit_form_before_permalink( $filename = '', $permalink = '', $filetype = 'css' ) {
		if ( isset( $_GET['language'] ) ) {
			$filetype = strtolower(trim($_GET['language']));
		}

		if ( ! in_array( $filetype, array( 'css', 'js', 'php' ) ) ) {
			return;
		}

		if ( ! is_string( $filename ) ) {
			global $post;
			if ( ! is_object( $post ) ) {
				return;
			}
			if ( 'asenha_code_snippet' !== $post->post_type ) {
				return;
			}

			$post    = $filename;
			$slug    = get_post_meta( $post->ID, '_slug', true );
			$options = get_post_meta( $post->ID, 'options', true );

			if ( is_array( $options ) && isset( $options['language'] ) ) {
				$filetype = $options['language'];
			}
			if ( $filetype === 'html' ||  $filetype === 'php' ) {
				return;
			}
			if ( ! @file_exists( CSM_UPLOAD_DIR . '/' . $slug . '.' . $filetype ) ) {
				$slug = false;
			}
			$filename = ( $slug ) ? $slug : $post->ID;
		}

		if ( empty( $permalink ) ) {
			$permalink = CSM_UPLOAD_URL . '/' . $filename . '.' . $filetype;
		}

		?>
		<div class="inside">
			<?php if ( $filetype === 'css' || $filetype === 'js' ) : ?>
			<div id="edit-slug-box" class="hide-if-no-js">
				<strong>Permalink:</strong>
				<span id="sample-permalink"><a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( CSM_UPLOAD_URL ) . '/'; ?><span id="editable-post-name"><?php echo esc_html( $filename ); ?></span>.<?php echo esc_html( $filetype ); ?></a></span>
				&lrm;<span id="csm-edit-slug-buttons"><button type="button" class="csm-edit-slug button button-small hide-if-no-js" aria-label="Edit permalink">Edit</button></span>
				<span id="editable-post-name-full" data-filetype="<?php echo $filetype; ?>"><?php echo esc_html( $filename ); ?></span>
			</div>
			<?php endif; ?>
			<?php wp_nonce_field( 'csm-permalink', 'csm-permalink-nonce' ); ?>
		</div>
		<?php
	}


	/**
	 * AJAX save the Permalink slug
	 */
	function wp_ajax_csm_permalink() {

		if ( ! isset( $_POST['csm_permalink_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_POST['csm_permalink_nonce'], 'csm-permalink' ) ) {
			return;
		}

		$code_id   = isset( $_POST['code_id'] ) ? intval( $_POST['code_id'] ) : 0;
		$permalink = isset( $_POST['permalink'] ) ? $_POST['permalink'] : null;
		$slug      = isset( $_POST['new_slug'] ) ? trim( sanitize_file_name( $_POST['new_slug'] ) ) : null;
		$filetype  = isset( $_POST['filetype'] ) ? $_POST['filetype'] : 'css';
		if ( empty( $slug ) ) {
			$slug = (string) $code_id;
		} else {
			update_post_meta( $code_id, '_slug', $slug );
		}
		$this->edit_form_before_permalink( $slug, $permalink, $filetype );

		wp_die();
	}


	/**
	 * Show contextual help for the Code Snippet edit page
	 */
	public function contextual_help() {
		$screen = get_current_screen();

		if ( $screen->id != 'asenha_code_snippet' ) {
			return;
		}

		$screen->add_help_tab(
			array(
				'id'      => 'csm-editor_shortcuts',
				'title'   => __( 'Editor Shortcuts', 'code-snippets-manager-pro' ),
				'content' =>
							  '<p><table id="commands">
				            <tr>
				            <td><strong>Find</strong></td>
				            <td> <span class="commands win">Win: <code>Ctrl</code> + <code>F</code></span><span class="commands mac">Mac: <code>Command</code> + <code>F</code></span></td>
				            </tr>
				            <tr>
				            <td><strong>Replace</strong></td>
				            <td> <span class="commands win">Win: <code>Shift</code> + <code>Ctrl</code> + <code>F</code></span><span class="commands mac">Mac: <code>Command</code> + <code>Option</code> + <code>F</code></span></td>
				            </tr>
				            <tr>
				            <td><strong>Save</strong></td>
				            <td> <span class="commands win">Win: <code>Ctrl</code> + <code>S</code></span><span class="commands mac">Mac: <code>Command</code> + <code>S</code></span></td>
				            </tr>
				            <tr>
				            <td><strong>Comment line/block</strong></td>
				            <td> <span class="commands win">Win: <code>Ctrl</code> + <code>/</code></span><span class="commands mac">Mac: <code>Command</code> + <code>/</code></span></td>
				            </tr>
				            <tr>
				            <td><strong>Code folding</strong></td>
				            <td> <span class="commands win">Win: <code>Ctrl</code> + <code>Q</code></span><span class="commands mac">Mac: <code>Ctrl</code> + <code>Q</code></span></td>
				            </tr>
				            <tr>
				            <td><strong>Exit fullscreen</strong></td>
				            <td> <span class="commands win">Win: <code>Esc</code></span><span class="commands mac">Mac: <code>Esc</code></span></td>
				            </tr>
				            </table></p>',
			)
		);

	}


	/**
	 * Remove the JS/CSS/PHP file from the disk when deleting the post
	 */
	function before_delete_post( $postid ) {
		global $post;
		if ( ! is_object( $post ) ) {
			return;
		}
		if ( 'asenha_code_snippet' !== $post->post_type ) {
			return;
		}
		if ( ! wp_is_writable( CSM_UPLOAD_DIR ) ) {
			return;
		}

		$options = get_post_meta( $postid, 'options', true );
		if ( ! is_array( $options ) ) {
			return;
		}

		$options['language'] = ( isset( $options['language'] ) ) ? strtolower( $options['language'] ) : 'css';
		$options['language'] = in_array( $options['language'], array( 'html', 'js', 'css', 'php' ), true ) ? $options['language'] : 'css';

		$slug = get_post_meta( $postid, '_slug', true );
		$slug = sanitize_file_name( $slug );

		$file_name = $postid . '.' . $options['language'];

		@unlink( CSM_UPLOAD_DIR . '/' . $file_name );

		if ( ! empty( $slug ) ) {
			@unlink( CSM_UPLOAD_DIR . '/' . $slug . '.' . $options['language'] );
		}
	}


	/**
	 * Fix for bug: white page Edit Code Snippet for WordPress 5.0 with Classic Editor
	 */
	function current_screen_2() {
		$screen = get_current_screen();

		if ( $screen->post_type != 'asenha_code_snippet' ) {
			return false;
		}

		remove_filter( 'use_block_editor_for_post', array( 'Classic_Editor', 'choose_editor' ), 100, 2 );
		add_filter( 'use_block_editor_for_post', '__return_false', 100 );
		add_filter( 'use_block_editor_for_post_type', '__return_false', 100 );
	}
}

return new Code_Snippets_Manager_Admin();
