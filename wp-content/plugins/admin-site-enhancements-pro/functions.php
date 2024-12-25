<?php

/**
 * Get default WordPress avatar URL by user email
 *
 * @link https://plugins.trac.wordpress.org/browser/simple-user-avatar/tags/4.3/admin/class-sua-admin.php
 * @since  6.2.0
 */
function get_default_avatar_url_by_email__premium_only( $user_email = '', $size = 96 ) {
	// Check the email provided
	if ( empty( $user_email ) || ! filter_var( $user_email, FILTER_VALIDATE_EMAIL ) ) {
		return null;
	}

	// Sanitize email and get md5
	$user_email     = sanitize_email( $user_email );
	$md5_user_email = md5( $user_email );

	// SSL Gravatar URL
	$url = 'https://secure.gravatar.com/avatar/' . $md5_user_email;

	// Add query args
	$url = add_query_arg( 's', $size, $url );
	$url = add_query_arg( 'd', 'mm', $url );
	$url = add_query_arg( 'r', 'g', $url );

	return esc_url( $url );
}

/**
 * Get kses ruleset extended to allow svg
 * 
 * @since 6.9.5
 */
function get_kses_with_svg_ruleset() {
	$kses_defaults = wp_kses_allowed_html( 'post' );

	$svg_args = array(
	    'svg'   => array(
	        'class'				=> true,
	        'aria-hidden'		=> true,
	        'aria-labelledby'	=> true,
	        'role'				=> true,
	        'xmlns'				=> true,
	        'width'				=> true,
	        'height'			=> true,
	        'viewbox'			=> true,
	        'viewBox'			=> true,
	    ),
	    'g'     => array( 
	    	'fill' 				=> true,
	    	'fill-rule' 		=> true,
	        'stroke'			=> true,
	        'stroke-linejoin'	=> true,
	        'stroke-width'		=> true,
	        'stroke-linecap'	=> true,
	    ),
	    'title' => array( 'title' => true ),
	    'path'  => array( 
	        'd'					=> true,
	        'fill'				=> true,
	        'stroke'			=> true,
	        'stroke-linejoin'	=> true,
	        'stroke-width'		=> true,
	        'stroke-linecap'	=> true,
	    ),
	    'rect'	=> array(
	    	'width'				=> true,
	    	'height'			=> true,
	    	'x'					=> true,
	    	'y'					=> true,
	    	'rx'				=> true,
	    	'ry'				=> true,
	    ),
	    'circle' => array(
	    	'cx'				=> true,
	    	'cy'				=> true,
	    	'r'				=> true,
	    ),
	);

	return array_merge( $kses_defaults, $svg_args );
	// Example usage: wp_kses( $the_svg_icon, get_kses_with_svg_ruleset() );	
}

/**
 * Get kses ruleset extended to allow style and script tags
 * 
 * @since 6.9.5
 */
function get_kses_with_style_src_ruleset() {
    $kses_defaults = wp_kses_allowed_html( 'post' );

    $style_script_args = array(
    	'style'		=> true,
    	'script'	=> array(
    		'src'	=> true,
    	),
    );
    
    return array_merge( $kses_defaults, $style_script_args );
	// Example usage: wp_kses( $the_html, get_kses_with_style_src_ruleset() );	
}

/**
 * Get kses ruleset extended to allow style and script tags
 * 
 * @since 6.9.5
 */
function get_kses_with_style_src_svg_ruleset() {
    $kses_defaults = wp_kses_allowed_html( 'post' );

    $style_script_svg_args = array(
    	'style'		=> true,
    	'script'	=> array(
    		'src'	=> true,
    	),
	    'svg'   => array(
	        'class'				=> true,
	        'aria-hidden'		=> true,
	        'aria-labelledby'	=> true,
	        'role'				=> true,
	        'xmlns'				=> true,
	        'width'				=> true,
	        'height'			=> true,
	        'viewbox'			=> true,
	        'viewBox'			=> true,
	    ),
	    'g'     => array( 
	    	'fill' 				=> true,
	    	'fill-rule' 		=> true,
	        'stroke'			=> true,
	        'stroke-linejoin'	=> true,
	        'stroke-width'		=> true,
	        'stroke-linecap'	=> true,
	    ),
	    'title' => array( 'title' => true ),
	    'path'  => array( 
	        'd'					=> true,
	        'fill'				=> true,
	        'stroke'			=> true,
	        'stroke-linejoin'	=> true,
	        'stroke-width'		=> true,
	        'stroke-linecap'	=> true,
	    ),
	    'rect'	=> array(
	    	'width'				=> true,
	    	'height'			=> true,
	    	'x'					=> true,
	    	'y'					=> true,
	    	'rx'				=> true,
	    	'ry'				=> true,
	    ),
	    'circle' => array(
	    	'cx'				=> true,
	    	'cy'				=> true,
	    	'r'				=> true,
	    ),
    );
    
    return array_merge( $kses_defaults, $style_script_svg_args );
	// Example usage: wp_kses( $the_html, get_kses_with_style_src_svg_ruleset() );	
}

/**
 * Get kses ruleset extended to allow input tags
 * 
 * @since 6.9.5
 */
function get_kses_with_custom_html_ruleset() {
    $kses_defaults = wp_kses_allowed_html( 'post' );

    $custom_html_args = array(
    	'input'	=> array(
    		'type'	=> true,
    		'id'	=> true,
    		'class'	=> true,
    		'name'	=> true,
    		'value'	=> true,
    		'style'	=> true,
    	)
    );
    
    return array_merge( $kses_defaults, $custom_html_args );
	// Example usage: wp_kses( $the_html, get_kses_with_style_src_ruleset() );	
}

/**
 * Export ASE's settings
 * 
 */
function asenha_settings_export__premium_only() {

	if ( empty( $_POST['asenha_export_action'] ) || 'export_settings' != $_POST['asenha_export_action'] ) {
		return;
	}

	if ( ! wp_verify_nonce( $_POST['asenha_export_nonce'], 'asenha_export_nonce' ) ) {
		wp_die( 'Invalid nonce. Please try again.', 'Error', array( 'response' => 403 ) );
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Permission denied. Please contact your site administrator to run the export process.', 'Error', array( 'response' => 403 ) );
	}
	
	$asenha_settings = get_option( ASENHA_SLUG_U, array() );
	
	// Prevent auto-check of "Discourage search engine" when the new site is a live site with a different URL
	$asenha_settings['live_site_url'] = '';
	
	ignore_user_abort( true );
	
	nocache_headers();
	header( 'Content-Type: application/json; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=admin-site-enhancements-ase-settings-' . date('Y-m-d') . '.json' );
	header( 'expires: 0' );
	
	echo json_encode( $asenha_settings );
	exit;
	
}

/**
 * Custom wp_die handler for when Code Snippets Manager is activated
 * Modified from _default_wp_die_handler() in WP v6.3.1
 * 
 * @since 5.8.0
 */
function _custom_wp_die_handler__premium_only( $message, $title = '', $args = [] ) {
	
	if ( is_object( $message ) && property_exists( $message, 'error_data' ) ) {
		
		$filepath_with_error = $message->error_data['internal_server_error']['error']['file'];
		$is_error_from_csm_snippet = ( false !== strpos( $filepath_with_error, '/premium/code-snippets-manager/' ) ) ? true : false;
		
		if ( $is_error_from_csm_snippet 
			&& is_user_logged_in() 
			&& current_user_can( 'manage_options' ) 
			) {

			$options_extra = get_option( ASENHA_SLUG_U . '_extra', array() );
			$php_snippet_post_id = $options_extra['last_edited_csm_php_snippet'];
		    $snippet_edit_url = get_edit_post_link( $php_snippet_post_id );

		    // Get error data
	        // Error type and codes. 
	        // Ref: https://www.php.net/manual/en/errorfunc.constants.php#109430
	        // Ref: https://logtivity.io/fatal-errors-wordpress/
	        // E_ERROR - 1
	        // E_WARNING - 2
	        // E_PARSE - 4
	        // E_NOTICE - 8
	        // E_CORE_ERROR - 16
	        // E_CORE_WARNING - 32
	        // E_COMPILE_ERROR - 64
	        // E_COMPILE_WARNING - 128
	        // E_USER_ERROR - 256
	        // E_USER_WARNING - 512
	        // E_USER_NOTICE - 1024
	        // E_STRICT - 2048
	        // E_RECOVERABLE_ERROR - 4096
	        // E_DEPRECATED - 8192
	        // E_USER_DEPRECATED - 16384 

		    $code = $message->error_data['internal_server_error']['error']['type']; 
		    $fatal_error_codes = array( 1, 16, 256 );
		    if ( in_array( intval( $code ), $fatal_error_codes ) ) {
		    	$type = 'fatal';
		    } else {
		    	$type = 'non-fatal';
		    }
		    $file 			= $message->error_data['internal_server_error']['error']['file'];
		    $line 			= $message->error_data['internal_server_error']['error']['line'];
		    $message_full 	= $message->error_data['internal_server_error']['error']['message']; // includes stack trace
		    $message_parts 	= explode( ' in /', $message_full );
		    $message 		= $message_parts[0];
			$error_message 	= $message . ' on line ' . $line;

		    $message_parts 	= explode( 'Stack trace:', $message_full );
		    $stack_trace = $message_parts[1];

		    // Record error info in PHP snippet post meta
			update_post_meta( $php_snippet_post_id, 'php_snippet_has_error', true );
			update_post_meta( $php_snippet_post_id, 'php_snippet_error_type', $type );
			update_post_meta( $php_snippet_post_id, 'php_snippet_error_code', $code );
			update_post_meta( $php_snippet_post_id, 'php_snippet_error_message', '<span class="error-message">' . $error_message . '</span><span class="stack-trace">Stack trace:</span>' . ltrim( nl2br( str_replace( ABSPATH, '/', $stack_trace ) ), '<br />' ) );
			update_post_meta( $php_snippet_post_id, 'php_snippet_error_via', 'wp_die_handler' );

		    // Deactivate PHP snippet
		    update_post_meta( $php_snippet_post_id, '_active', 'no' );

		    // Enable Safe Mode to stop PHP snippets execution
			$wp_config = new ASENHA\Classes\WP_Config_Transformer;
			$wp_config_options = array(
				'add'       => true, // Add the config if missing.
				'raw'       => true, // Display value in raw format without quotes.
				'normalize' => false, // Normalize config output using WP Coding Standards.
			);
			$is_safe_mode_enabled = $wp_config->update( 'constant', 'CSM_SAFE_MODE', 'true', $wp_config_options );

		    $redirect_delay_in_seconds = 30;
								
			$message = '<div class="wp-die-message">
							<p>A fatal error has just occurred due to the last edit you performed on the <strong>' . get_the_title( $php_snippet_post_id ) . '</strong> PHP snippet.</p>
							<p>Don\'t worry. Your site should remain accessible and functional. Safe Mode has been enabled and all PHP snippets execution has been stopped to prevent further errors.</p>
							<p>You will be automatically redirected to the edit screen of the offending PHP snippet with some info on the error to help you fix the code.</p>
							<p class="redirection-counter">Redirecting in <span id="countdown">' . $redirect_delay_in_seconds . '</span> seconds.</p>
						</div>
						<div class="admin-only">This message is only shown to site administrators.</div>';

		    // JS redirect script
		    $delayed_js_redirect_script = '<script type="text/javascript">

		    // Redirection countdown script: https://codepen.io/a55555a4444a333/pen/VVzKMO
		    // Total seconds
		    var seconds = ' . $redirect_delay_in_seconds . ';
		    
		    function countdown() {
		        seconds = seconds - 1;
		        if (seconds < 0) {
		            // Redirect link here
		            window.location = "' . $snippet_edit_url .'";
		        } else {
		            // Update remaining seconds
		            document.getElementById("countdown").innerHTML = seconds;
		            // Countdown with JS
		            window.setTimeout("countdown()", 1000);
		        }
		    }
		    
		    // Run countdown function
		    countdown();
			</script>';
			
		} else {

			list( $message, $title, $parsed_args ) = _wp_die_process_input( $message, $title, $args );

			if ( is_string( $message ) ) {
				if ( ! empty( $parsed_args['additional_errors'] ) ) {
					$message = array_merge(
						array( $message ),
						wp_list_pluck( $parsed_args['additional_errors'], 'message' )
					);
					$message = "<ul>\n\t\t<li>" . implode( "</li>\n\t\t<li>", $message ) . "</li>\n\t</ul>";
				}

				$message = sprintf(
					'<div class="wp-die-message">%s</div>',
					$message
				);
			}
			
		}

		$have_gettext = function_exists( '__' );

		if ( ! empty( $parsed_args['link_url'] ) && ! empty( $parsed_args['link_text'] ) ) {
			$link_url = $parsed_args['link_url'];
			if ( function_exists( 'esc_url' ) ) {
				$link_url = esc_url( $link_url );
			}
			$link_text = $parsed_args['link_text'];
			$message  .= "\n<p><a href='{$link_url}'>{$link_text}</a></p>";
		}

		if ( isset( $parsed_args['back_link'] ) && $parsed_args['back_link'] ) {
			$back_text = $have_gettext ? __( '&laquo; Back' ) : '&laquo; Back';
			$message  .= "\n<p><a href='javascript:history.back()'>$back_text</a></p>";
		}

	if ( ! did_action( 'admin_head' ) ) :
		if ( ! headers_sent() ) {
			header( "Content-Type: text/html; charset={$parsed_args['charset']}" );
			status_header( $parsed_args['response'] );
			nocache_headers();
		}

		$text_direction = $parsed_args['text_direction'];
		$dir_attr       = "dir='$text_direction'";

		/*
		 * If `text_direction` was not explicitly passed,
		 * use get_language_attributes() if available.
		 */
		if ( empty( $args['text_direction'] )
			&& function_exists( 'language_attributes' ) && function_exists( 'is_rtl' )
		) {
			$dir_attr = get_language_attributes();
		}
		?>
<!DOCTYPE html>
<html <?php echo esc_attr( $dir_attr ); ?>>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo esc_attr( $parsed_args['charset'] ); ?>" />
	<meta name="viewport" content="width=device-width">
		<?php
		if ( function_exists( 'wp_robots' ) && function_exists( 'wp_robots_no_robots' ) && function_exists( 'add_filter' ) ) {
			add_filter( 'wp_robots', 'wp_robots_no_robots' );
			wp_robots();
		}
		?>
	<title><?php echo esc_html( $title ); ?></title>
	<style type="text/css">
		html {
			background: #f1f1f1;
		}
		body {
			position: relative;
			background: #fff;
			border: 1px solid #ccd0d4;
			color: #444;
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
			margin: 2em auto;
			padding: 1em 2em;
			max-width: 700px;
			-webkit-box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
			box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
		}
		h1 {
			border-bottom: 1px solid #dadada;
			clear: both;
			color: #666;
			font-size: 24px;
			margin: 30px 0 0 0;
			padding: 0;
			padding-bottom: 7px;
		}
		#error-page {
			margin-top: 50px;
		}
		#error-page p,
		#error-page .wp-die-message {
			font-size: 14px;
			line-height: 1.5;
			margin: 25px 0 20px;
		}
		#error-page code {
			font-family: Consolas, Monaco, monospace;
		}
		<?php
		if ( $is_error_from_csm_snippet 
			&& is_user_logged_in() 
			&& current_user_can( 'manage_options' )
			) {
		?>
		#error-page p.redirection-counter {
			font-size: 1.25em;
			text-align: center;
			font-weight: bold;
		}
		#countdown {
			color: #fa7e1e;
		}
		.admin-only {
			border-top: 1px solid #ccc;
			padding-top: 8px;
			display: block;
			width: 100%;
			font-size: 13px;
			color: #999;
			text-align: center;
		}
		<?php
		}
		?>
		ul li {
			margin-bottom: 10px;
			font-size: 14px ;
		}
		a {
			color: #0073aa;
		}
		a:hover,
		a:active {
			color: #006799;
		}
		a:focus {
			color: #124964;
			-webkit-box-shadow:
				0 0 0 1px #5b9dd9,
				0 0 2px 1px rgba(30, 140, 190, 0.8);
			box-shadow:
				0 0 0 1px #5b9dd9,
				0 0 2px 1px rgba(30, 140, 190, 0.8);
			outline: none;
		}
		.button {
			background: #f3f5f6;
			border: 1px solid #016087;
			color: #016087;
			display: inline-block;
			text-decoration: none;
			font-size: 13px;
			line-height: 2;
			height: 28px;
			margin: 0;
			padding: 0 10px 1px;
			cursor: pointer;
			-webkit-border-radius: 3px;
			-webkit-appearance: none;
			border-radius: 3px;
			white-space: nowrap;
			-webkit-box-sizing: border-box;
			-moz-box-sizing:    border-box;
			box-sizing:         border-box;

			vertical-align: top;
		}

		.button.button-large {
			line-height: 2.30769231;
			min-height: 32px;
			padding: 0 12px;
		}

		.button:hover,
		.button:focus {
			background: #f1f1f1;
		}

		.button:focus {
			background: #f3f5f6;
			border-color: #007cba;
			-webkit-box-shadow: 0 0 0 1px #007cba;
			box-shadow: 0 0 0 1px #007cba;
			color: #016087;
			outline: 2px solid transparent;
			outline-offset: 0;
		}

		.button:active {
			background: #f3f5f6;
			border-color: #7e8993;
			-webkit-box-shadow: none;
			box-shadow: none;
		}

		<?php
		if ( 'rtl' === $text_direction ) {
			echo 'body { font-family: Tahoma, Arial; }';
		}
		?>
	</style>
</head>
<body id="error-page">
<?php endif; // ! did_action( 'admin_head' ) ?>
	<?php echo wp_kses_post( $message ); ?>
	<?php
	if ( $is_error_from_csm_snippet 
		&& is_user_logged_in() 
		&& current_user_can( 'manage_options' )
		) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $delayed_js_redirect_script;
	}
	?>
</body>
</html>	
	<?php
	if ( $parsed_args['exit'] ) {
		die();
	}

	} else {
	// =========================================================================================================
	// ========= If the error is not triggered by Code Snippets Manager, and $message is not an object =========
	// ========= Copy from _default_wp_die_handler() in /wp-includes/functions.php =============================
	// =========================================================================================================
	list( $message, $title, $parsed_args ) = _wp_die_process_input( $message, $title, $args );

	if ( is_string( $message ) ) {
		if ( ! empty( $parsed_args['additional_errors'] ) ) {
			$message = array_merge(
				array( $message ),
				wp_list_pluck( $parsed_args['additional_errors'], 'message' )
			);
			$message = "<ul>\n\t\t<li>" . implode( "</li>\n\t\t<li>", $message ) . "</li>\n\t</ul>";
		}

		$message = sprintf(
			'<div class="wp-die-message">%s</div>',
			$message
		);
	}

	$have_gettext = function_exists( '__' );

	if ( ! empty( $parsed_args['link_url'] ) && ! empty( $parsed_args['link_text'] ) ) {
		$link_url = $parsed_args['link_url'];
		if ( function_exists( 'esc_url' ) ) {
			$link_url = esc_url( $link_url );
		}
		$link_text = $parsed_args['link_text'];
		$message  .= "\n<p><a href='{$link_url}'>{$link_text}</a></p>";
	}

	if ( isset( $parsed_args['back_link'] ) && $parsed_args['back_link'] ) {
		$back_text = $have_gettext ? __( '&laquo; Back' ) : '&laquo; Back';
		$message  .= "\n<p><a href='javascript:history.back()'>$back_text</a></p>";
	}

	if ( ! did_action( 'admin_head' ) ) :
		if ( ! headers_sent() ) {
			header( "Content-Type: text/html; charset={$parsed_args['charset']}" );
			status_header( $parsed_args['response'] );
			nocache_headers();
		}

		$text_direction = $parsed_args['text_direction'];
		$dir_attr       = "dir='$text_direction'";

		/*
		 * If `text_direction` was not explicitly passed,
		 * use get_language_attributes() if available.
		 */
		if ( empty( $args['text_direction'] )
			&& function_exists( 'language_attributes' ) && function_exists( 'is_rtl' )
		) {
			$dir_attr = get_language_attributes();
		}
		?>
<!DOCTYPE html>
<html <?php echo $dir_attr; ?>>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $parsed_args['charset']; ?>" />
	<meta name="viewport" content="width=device-width">
		<?php
		if ( function_exists( 'wp_robots' ) && function_exists( 'wp_robots_no_robots' ) && function_exists( 'add_filter' ) ) {
			add_filter( 'wp_robots', 'wp_robots_no_robots' );
			wp_robots();
		}
		?>
	<title><?php echo $title; ?></title>
	<style type="text/css">
		html {
			background: #f1f1f1;
		}
		body {
			background: #fff;
			border: 1px solid #ccd0d4;
			color: #444;
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
			margin: 2em auto;
			padding: 1em 2em;
			max-width: 700px;
			-webkit-box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
			box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
		}
		h1 {
			border-bottom: 1px solid #dadada;
			clear: both;
			color: #666;
			font-size: 24px;
			margin: 30px 0 0 0;
			padding: 0;
			padding-bottom: 7px;
		}
		#error-page {
			margin-top: 50px;
		}
		#error-page p,
		#error-page .wp-die-message {
			font-size: 14px;
			line-height: 1.5;
			margin: 25px 0 20px;
		}
		#error-page code {
			font-family: Consolas, Monaco, monospace;
		}
		ul li {
			margin-bottom: 10px;
			font-size: 14px ;
		}
		a {
			color: #2271b1;
		}
		a:hover,
		a:active {
			color: #135e96;
		}
		a:focus {
			color: #043959;
			box-shadow: 0 0 0 2px #2271b1;
			outline: 2px solid transparent;
		}
		.button {
			background: #f3f5f6;
			border: 1px solid #016087;
			color: #016087;
			display: inline-block;
			text-decoration: none;
			font-size: 13px;
			line-height: 2;
			height: 28px;
			margin: 0;
			padding: 0 10px 1px;
			cursor: pointer;
			-webkit-border-radius: 3px;
			-webkit-appearance: none;
			border-radius: 3px;
			white-space: nowrap;
			-webkit-box-sizing: border-box;
			-moz-box-sizing:    border-box;
			box-sizing:         border-box;

			vertical-align: top;
		}

		.button.button-large {
			line-height: 2.30769231;
			min-height: 32px;
			padding: 0 12px;
		}

		.button:hover,
		.button:focus {
			background: #f1f1f1;
		}

		.button:focus {
			background: #f3f5f6;
			border-color: #007cba;
			-webkit-box-shadow: 0 0 0 1px #007cba;
			box-shadow: 0 0 0 1px #007cba;
			color: #016087;
			outline: 2px solid transparent;
			outline-offset: 0;
		}

		.button:active {
			background: #f3f5f6;
			border-color: #7e8993;
			-webkit-box-shadow: none;
			box-shadow: none;
		}

		<?php
		if ( 'rtl' === $text_direction ) {
			echo 'body { font-family: Tahoma, Arial; }';
		}
		?>
	</style>
</head>
<body id="error-page">
<?php endif; // ! did_action( 'admin_head' ) ?>
	<?php echo $message; ?>
</body>
</html>
	<?php
	if ( $parsed_args['exit'] ) {
		die();
	}
			
	}
}