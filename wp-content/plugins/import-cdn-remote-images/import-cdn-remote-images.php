<?php
/*
Plugin Name: Import CDN-Remote Images
Plugin URI: https://atakanau.blogspot.com/2020/10/import-cdn-remote-images-wp-plugin.html
Description: Add Cloudinary images and videos to the media library without importing, i.e. uploading them to your WordPress site.
Version: 2.1.2
Author: Atakan Au
Author URI: https://atakanau.blogspot.com
Text Domain: import-cdn-remote-images
Domain Path: /languages/
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0-standalone.html

Import CDN-Remote Images is distributed in the hope that it will be
useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General
Public License for more details.
 
Import CDN-Remote Images published under the GNU General Public License.
https://www.gnu.org/licenses/gpl-3.0-standalone.html.
*/

#region define stuff
define('AAUICRI_VERSION', '2.1.2');
define('AAUICRI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AAUICRI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AAUICRI_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('AAUICRI_PLUGIN_DOMAIN', 'import-cdn-remote-images');
define('AAUICRI_SUPPORT_LINK', 'https://atakanau.blogspot.com/2020/10/import-cdn-remote-images-wp-plugin.html');
#endregion define stuff
#region 
#endregion 

/** Installation
 *
 */
function aauicri_activate(){
	$settings = get_option('aauicri_settings');
	if($settings===false){	// fresh install
		$settings = aauicri_get_defaults('dflt_sttngs');
		update_option('aauicri_settings', json_encode($settings));
	}
	else if( gettype($settings) == 'object' ){	// update from v1.x
		if( $settings && isset($settings->cloudinary) && $settings->cloudinary ){	// import saved values
			$old_settings = $settings;
			$settings = aauicri_get_defaults('dflt_sttngs');
			$settings->cdn->cloudinary->cloud_name = $old_settings->cloudinary->cloud_name;
			$settings->cdn->cloudinary->api_key = $old_settings->cloudinary->api_key;
			$settings->cdn->cloudinary->api_secret = $old_settings->cloudinary->api_secret;
			update_option('aauicri_settings', json_encode($settings));
		}
	}
	else if( gettype($settings) == 'string' ){	// update from v2.x or higher 
	}
}
add_action('activate_' . AAUICRI_PLUGIN_BASENAME, 'aauicri_activate');

/** Default hardcoded settings
 *
 */
function aauicri_get_defaults($type){
	switch ($type) {
		case 'dflt_sttngs':	// default settings
			$settings = (object) array();
			$settings->opt = (object) array();
			$settings->opt->vrs = AAUICRI_VERSION;
			$settings->opt->xxx = 'xxx';
			
			$settings->cdn = (object) array();
			$settings->cdn->cloudinary = (object) array();
			$settings->cdn->cloudinary->cloud_name = '';
			$settings->cdn->cloudinary->api_key = '';
			$settings->cdn->cloudinary->api_secret = '';
			$retval = $settings;
			break;
		default:
			$retval = false;
			break;
	}

	return $retval;
}

/** Load plugin textdomain.
 *
*/
function aauicri_load_textdomain(){
	load_plugin_textdomain(AAUICRI_PLUGIN_DOMAIN, false, dirname(plugin_basename(__FILE__)) . '/languages/');
}
add_action('plugins_loaded', 'aauicri_load_textdomain');

/** Main class
 *
*/
class atakanauICRI{
	/**
	 * Constructor
	 *
	 * @since 1.0.1
	 */
	public function __construct(){
		// setup menu
		add_action('admin_menu', array($this, 'ui'));

		add_action( 'wp_ajax_aauicri_admin_ajax', array($this, 'aauicri_admin_ajax'));

		// extra links
		add_filter('plugin_row_meta', array($this, 'aauicri_plugin_meta_links'), 10, 2);
		add_filter('plugin_action_links_' . AAUICRI_PLUGIN_BASENAME, array($this, 'action_links'));

		add_action('admin_init', array($this, 'init_admin'));
	}

	function init_admin(){
		// load assets
		$name = 'aauicri';
		wp_enqueue_style( $name.'-admin-style', plugins_url( '/assets/css/aauicri.css', __FILE__ ), '', AAUICRI_VERSION );
		wp_enqueue_script( $name.'-admin-script', plugins_url( '/assets/js/aauicri.js', __FILE__ ), array('jquery'), AAUICRI_VERSION, true );
	}

	#region ajax
	public function aauicri_admin_ajax(){
		$data = (object) array();
		$data->msg = "";
		$_user = wp_get_current_user();
		if ( is_user_logged_in() && ( in_array('administrator',$_user->roles) || in_array('shop_manager',$_user->roles) ) ){
			$req_type = isset($_POST["req_type"]) ? sanitize_text_field( $_POST["req_type"] ) : false;
			if( $req_type == 'save_settings'){
				$settings = json_decode(get_option('aauicri_settings'));
				$settings->cdn->cloudinary->cloud_name 	= isset($_POST["cloud_name"	]) ? sanitize_text_field( $_POST["cloud_name"	] ) : '';
				$settings->cdn->cloudinary->api_key 	= isset($_POST["api_key"	]) ? sanitize_text_field( $_POST["api_key"		] ) : '';
				$settings->cdn->cloudinary->api_secret 	= isset($_POST["api_secret"	]) ? sanitize_text_field( $_POST["api_secret"	] ) : '';

				update_option('aauicri_settings', json_encode($settings));
				$data->msg = __( 'Settings saved.' );
			}
			elseif( $req_type == 'get_settings_cloudinary'){
				$settings = json_decode(get_option('aauicri_settings'));
				$data->cloudinary_settings = $settings->cdn->cloudinary;
				$setok=$this->get_setup_cdns($settings);
				if( !in_array('cloudinary',$setok) ){
					$data->msg = __('Missing settings',AAUICRI_PLUGIN_DOMAIN) . ": Cloudinary";
					$data->cloudinary_settings = false;
				}
			}
			elseif( $req_type == 'get_library_cloudinary'){
				// fn_recursive_sanitize_text_field funnction will sanitize the posted array variable
				$data->cloudinary = isset($_POST["cloudinary"]) ? (object) $this->fn_recursive_sanitize_text_field($_POST["cloudinary"]) : false; // sanitize array
				$data->page =  isset($_POST["page" ]) ?  sanitize_text_field( $_POST["page"] ) : false;
				$data->limit = isset($_POST["limit"]) ? (int) sanitize_text_field( $_POST["limit"] ) : false;
				$data->https = isset($_POST["https"]) ? (int) sanitize_text_field( $_POST["https"] ) : false;
				if($data->cloudinary){
					#region online

					$handle = curl_init();
					$url = 'https://'.$data->cloudinary->api_key.':'.$data->cloudinary->api_secret.'@api.cloudinary.com/v1_1/'.$data->cloudinary->cloud_name.'/resources/image'
						.'?max_results='.( $data->limit ? $data->limit : '500' )
						.( $data->page == '' ? '' : '&next_cursor='.$data->page )
						;
					curl_setopt($handle, CURLOPT_URL, $url);
					curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
					$readed = curl_exec($handle);
					curl_close($handle);
					$data_result=json_decode($readed, true);

					$data->results = $data_result;
					$data->library = array();
					if(isset($data_result['resources']))
						foreach($data_result['resources'] as $rsc){
							$data->library[] = [	// [URL],[Width],[Height],[mime]
								$data->https ? $rsc["secure_url"] : $rsc["url"]
								,$rsc["width"]
								,$rsc["height"]
								,'image/' . ( $rsc["format"]=='jpg' ? 'jpeg' : $rsc["format"] )
							];
						}

					if( $data->limit )	// finish
						$data->page = ".";
					else	// read next page
						$data->page = isset($data_result['next_cursor']) ? $data_result['next_cursor'] : ".";

					#endregion online
				}
			}
			elseif( $req_type == 'add_to_media_library'){
				$data->result = $this->fn_prepae_external_image_data();
			}
		}

		header('Content-Type: application/json');
		echo json_encode($data);
		exit();
		die();
	}

	#endregion ajax
	#region ajax sub functions

	public function fn_prepae_external_image_data(){
		global $wpdb;
		$info = $this->fn_sanitize_and_validate_input();
		$urlArr = $info['urls'];
		$urlformat = $info['urlformat'];

		$failed_urls = array();
		$url_paths = array();
		$exist_urls = array();
		
		// prevent from duplicate item import
		foreach( $urlArr as $i => $urlRow )
			array_push( $url_paths, $urlRow[0] );
		$exits_objs = $wpdb->get_results("SELECT guid FROM $wpdb->posts WHERE guid IN( '". implode("','",$url_paths) ."' )");
		foreach ( $exits_objs as $exits_obj )
			array_push( $exist_urls, $exits_obj->guid );

		foreach( $urlArr as $i => $urlRow ){
			if( !is_null($urlRow[4]) ){	//				input error: missing or invalid width height at [URL],[Width],[Height] method
			}
			elseif( in_array($urlRow[0], $exist_urls) ){	//	error [URL] already in media library
				$info['urls'][$i][4]=__('Error:') . __('Already exist.',AAUICRI_PLUGIN_DOMAIN).' ⁄₁';
			}
			else{	//									no input error , not in media library
				if( $info['urlformat']==0 ){	//		input method: [URL] read width height
					$img_info = $this->get_img_info_obj( $urlRow[0] );	//	[URL]
					if( $img_info->http_status==404 ){	// error
						$info['urls'][$i][4]=__('Error:') . __('Link not found.').' ⁄₃';
					}
					elseif( !in_array($img_info->http_status, [200,206]) ){	// error
						$info['urls'][$i][4]=__('Error:') . __('Link not found.').' ⁄₄';
					}
					elseif( (int)$img_info->width==0 || (int)$img_info->height==0 ){	// error
						$info['urls'][$i][4]=__('Error:') . __('Image Processing Error').' ⁄₅';
					}
					else{	// ok
						$info['urls'][$i][1] = $img_info->width;
						$info['urls'][$i][2] = $img_info->height;
						$info['urls'][$i][3] = $img_info->content_type;
						$info['urls'][$i][5] = $this->fn_add_external_image( $info['urls'][$i][0], $info['urls'][$i][1], $info['urls'][$i][2],$img_info->content_type );	//	[URL],[Width],[Height],[mime]
					}
				}
				elseif( $info['urlformat']==1 ){	//	input method: [URL],[Width],[Height]
					$info['urls'][$i][3] = $this->get_img_mime_str( $urlRow[0] );	//	[mime] = f ( [URL] )
					if($info['urls'][$i][3]){	// ok 200
						$info['urls'][$i][5] = $this->fn_add_external_image( $urlRow[0], $urlRow[1], $urlRow[2],$info['urls'][$i][3] );	//	[URL],[Width],[Height],[mime]
					}
					else{	// error 404
						$info['urls'][$i][4]=__('Error:') . __('Link not found.').' ⁄₂';
					}
				}
				elseif( $info['urlformat']==2 ){	//	input method: [URL],[Width],[Height],[mime]
					$info['urls'][$i][3] = $this->get_img_mime_str( $urlRow[0] );	//	[mime] = f ( [URL] )
					if($info['urls'][$i][3]){	// ok 200
						$info['urls'][$i][5] = $this->fn_add_external_image( $urlRow[0], $urlRow[1], $urlRow[2],$info['urls'][$i][3] );	//	[URL],[Width],[Height],[mime]
					}
					else{	// error 404
						$info['urls'][$i][4]=__('Error:') . __('Link not found.').' ⁄₂';
					}
				}
			}
		}

		$info['exist_urls'] = $exist_urls;

		return $info;
	}
	public function fn_add_external_image($url,$wdth,$hght,$mime){	// [URL],[Width],[Height],[mime]
		$filename = wp_basename( $url );
		$attachment = array(
			'guid' => $url
			,'post_mime_type' => $mime
			,'post_title' => preg_replace( '/\.[^.]+$/', '', $filename )
		);
		$attachment_metadata = array(
			'width' => $wdth,
			'height' => $hght,
			'file' => $filename );
		$attachment_metadata['sizes'] = array( 'full' => $attachment_metadata );
		$attachment_id = wp_insert_attachment( $attachment );
		wp_update_attachment_metadata( $attachment_id, $attachment_metadata );
		return $attachment_id;
	}

	public function fn_sanitize_and_validate_input(){
		$urlformat = (int) sanitize_text_field( $_POST['urlformat'] );
		$raw_urls = explode( " ", sanitize_text_field( $_POST['urls'] ) );
		$urls = array();
		if($urlformat==0){	//		posted data: [URL]
			foreach( $raw_urls as $i => $raw_url ){
				// Don't call sanitize_text_field on url because it removes '%20'.
				// Always use esc_url/esc_url_raw when sanitizing URLs. See:
				// https://codex.wordpress.org/Function_Reference/esc_url
				$urls[$i] = [esc_url_raw(trim($raw_url)) ,0 ,0 ,NULL ,NULL];	//	[URL],[Width],[Height],[mime],[error]
			}
		}
		else if($urlformat==1){	//	posted data: [URL],[Width],[Height]
			foreach( $raw_urls as $i => $raw_url ){
				$arr = explode(',',trim( $raw_url ));
				if( $row=count($arr) >= 3 ){	//	ok: [],[],[] (,[])
					$row=[trim(array_pop($arr)),trim(array_pop($arr)),implode(',',$arr)];	//	[Height],[Width],[URL]
					$width_str = $row[1].'';
					$width_int = intval( $width_str );
					$height_str = $row[0].'';
					$height_int = intval( $height_str );
					$error = ( ( ! empty( $height_str ) && $height_int <= 0 ) 
									|| $height_int.'' != $height_str 
									|| ( ! empty( $width_str ) && $width_int <= 0 ) 
									|| $width_int.'' != $width_str 
								) 
								?
								__('Width and height must be integer numbers.') 
								: 
								NULL;
					$urls[$i] = [esc_url_raw(trim($row[2])) ,$error?$width_str:$width_int ,$error?$height_str:$height_int ,NULL ,$error];	//	[URL],[Width],[Height],[mime],[error]
				}
				else
					$urls[$i] = [$raw_url ,0 ,0 ,NULL ,__('Invalid data provided.')];	//	[URL],[Width],[Height],[mime],[error]
			}
		}
		else if($urlformat==2){	//	posted data: [URL],[Width],[Height],[mime]
			// values comes from api. user should not modify it. so, we do not need validate them. may be unnecessary:
			foreach( $raw_urls as $i => $raw_url ){
				$arr = explode(',',trim( $raw_url ));
				if( $row=count($arr) >= 4 ){	//	ok: [],[],[],[] (,[])
					$row=[trim(array_pop($arr)),trim(array_pop($arr)),trim(array_pop($arr)),implode(',',$arr)];	//	[mime],[Height],[Width],[URL]
					$width_str = $row[2].'';
					$width_int = intval( $width_str );
					$height_str = $row[1].'';
					$height_int = intval( $height_str );
					$error = ( ( ! empty( $height_str ) && $height_int <= 0 ) 
									|| $height_int.'' != $height_str 
									|| ( ! empty( $width_str ) && $width_int <= 0 ) 
									|| $width_int.'' != $width_str 
								) 
								?
								__('Width and height must be integer numbers.') 
								: 
								NULL;
					$urls[$i] = [esc_url_raw(trim($row[3])) ,$error?$width_str:$width_int ,$error?$height_str:$height_int , $row[0] ,$error];	//	[URL],[Width],[Height],[mime],[error]
				}
				else
					$urls[$i] = [$raw_url ,0 ,0 ,NULL ,__('Invalid data provided.')];	//	[URL],[Width],[Height],[mime],[error]
			}
		}

		$input = array(
			'urls' =>  $urls
			,'urlformat' => $urlformat
		);

		return $input;
	}
	/**
	 * Recursive sanitation for an array
	 * 
	 * @param $array
	 *
	 * @return mixed
	 */
	public function fn_recursive_sanitize_text_field($array){
		foreach ( $array as $key => &$value ) {
			if ( is_array( $value ) ) {
				$value = $this->fn_recursive_sanitize_text_field($value);
			}
			else {
				$value = sanitize_text_field( $value );
			}
		}

		return $array;
	}

	/** Get image information without dowload whole file
	* 
	* @param (string) $url
	* @param (string) $referer
	*
	* @return (object) stdClass(
	* 	width			=> (int)		; 5184
	* 	height			=> (int)		; 3456
	* 	content_type	=> (string)		; image/jpeg
	* 	http_status		=> (int)		; 206
	* 	error			=> (string) | NULL
	* )
	*/
	public function get_img_info_obj($url, $referer=NULL){
		$headers = array( 'Range: bytes=0-131072' );
		$headers = array( 'Range: bytes=0-32768' );
		if ( !empty( $referer ) ) { array_push( $headers, 'Referer: ' . $referedr ); }

		// Get remote image
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers);
		$data = curl_exec( $ch );
		$http_status = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		$curl_errno = curl_errno( $ch );
		$content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

		curl_close($ch);

		$imgdata = (object) array();
		$imgdata->content_type = $content_type;
		$imgdata->http_status = $http_status;
		if ( !in_array( $http_status, [200, 206] ) ) {
			$imgdata->error = $curl_errno;
		}
		else{
			// Process image
			$image = imagecreatefromstring( $data );
			$imgdata->width = imagesx( $image );
			$imgdata->height = imagesy( $image );
			$imgdata->error = NULL;
		}

		return $imgdata;
	}
	public function get_img_mime_str( $url ){
		$ch = curl_init( $url );

		// Issue a HEAD request and follow any redirects.
		curl_setopt( $ch, CURLOPT_NOBODY, true );
		curl_setopt( $ch, CURLOPT_HEADER, true );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
		// curl_setopt( $ch, CURLOPT_USERAGENT, get_user_agent_string() );

		$data = curl_exec( $ch );
		//Retrieve the size of the remote file in bytes.
		$http_status = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		$content_type = $http_status == 200 ? curl_getinfo($ch, CURLINFO_CONTENT_TYPE) : false;

		curl_close( $ch );

		return $content_type;
	}


	#endregion ajax sub functions
	#region other
	public function get_setup_cdns($settings){
		$setok=[];
		if(	$settings->cdn->cloudinary->cloud_name
			&& $settings->cdn->cloudinary->api_key
			&& $settings->cdn->cloudinary->api_secret
			&& 1<=strlen($settings->cdn->cloudinary->cloud_name)
			&& 15<=strlen($settings->cdn->cloudinary->api_key)
			&& 27<=strlen($settings->cdn->cloudinary->api_secret)
		)
			array_push($setok,'cloudinary');
		return $setok;
	}

	#endregion other

	#region ui and links
	public function aauicri_plugin_meta_links($links, $file){
		if ( $file == AAUICRI_PLUGIN_BASENAME ) {
			$support_link = '<a target="_blank" href="https://atakanau.blogspot.com/2020/10/import-cdn-remote-images-wp-plugin.html#comments">' . __(translate('Support')) . '</a>';
			$rate_link = '<a target="_blank" href="https://wordpress.org/support/plugin/import-cdn-remote-images/reviews/?filter=5#new-post">' . __(translate('Rate',AAUICRI_PLUGIN_DOMAIN)).' ★★★★★' . '</a>';
			$links[] = $support_link;
			$links[] = $rate_link;
		}
		return $links;
	}
	public function ui(){
		$mytxt = __('Import images',AAUICRI_PLUGIN_DOMAIN);
		add_submenu_page(
			'upload.php'
			, __('Import CDN-Remote Images',AAUICRI_PLUGIN_DOMAIN)
			, __('Import images',AAUICRI_PLUGIN_DOMAIN)
			, current_user_can('administrator') ? 'manage_options' : 'manage_woocommerce'		// capability
			, 'aauicri-import', array($this, 'loadPage')
		);
	}
	public function action_links($links){
		$links[] = '<a href="' . get_admin_url(null, 'upload.php?page=aauicri-import&tab=settings') . '">' . __('Settings') . '</a>';
		return $links;
	}
	#endregion ui and links

	#region pages
	public function loadPage(){
		$settings = json_decode(get_option('aauicri_settings'));
		$str = sanitize_text_field( $_SERVER["QUERY_STRING"] );
		parse_str($str, $url_args);
		if( isset($url_args["tab"]) && $url_args["tab"] == "settings")
			$this->settingsPage($settings);
		else
			$this->importPage($settings);
	}

	public function importPage($settings){
		$setok=$this->get_setup_cdns($settings);
		?>
		<div class="wrap">
			<h2 id="aauicri_page-import">
				<?php _e('Import CDN-Remote Images', AAUICRI_PLUGIN_DOMAIN) ?> — <?php _e('Import images', AAUICRI_PLUGIN_DOMAIN); ?>
				<span class="nonessential alignright"><?php _e('Version'); ?>: <?php echo AAUICRI_VERSION; ?></span>
			</h2>
			<table class="aauicri-frame0">
				<tr>
					<td>
						<div class="tabarea-aauicri">

<div class="alignright">
	<a class="button" href="<?php echo admin_url('upload.php?page=aauicri-import&tab=settings') ?>"><?php echo __('Settings'); ?></a>
</div>
<div class="tabarea-aauicri">

	<h2 class="nav-tab-wrapper">
		<a id="aauicri_p0-tab" data-toggle="aauicri_p0" href="#aauicri_p0_pane" class="nav-tab nav-tab-active" > <?php echo __('URL list',AAUICRI_PLUGIN_DOMAIN); ?> </a>
		<a id="aauicri_p1-tab" data-toggle="aauicri_p1" href="#aauicri_p1_pane" class="nav-tab" >  Cloudinary </a>
	</h2>

	<div class="tab-content">
		<div id="aauicri_p0-pane" class="tab-pane tab-pane-aauicri_p0 active" >
			<p><?php _e("Please fill in the image URLs. Multiple URLs are supported with each URL specified in one line.", AAUICRI_PLUGIN_DOMAIN); ?></p>
				<table class="form-table">
					<tr valign="top">
						<th scope="row">
							<?php _e('Input format', AAUICRI_PLUGIN_DOMAIN); ?>:
						</th>
						<td>
							<label for="aauicri-urlformat-0"><input type="radio" name="aauicri-urlformat" id="aauicri-urlformat-0" value="0" checked="checked" />
								[URL]
							</label>
							<label for="aauicri-urlformat-1"><input type="radio" name="aauicri-urlformat" id="aauicri-urlformat-1" value="1" />
								[URL],[<?php _e('Width'); ?>],[<?php _e('Height'); ?>]
							</label>
						</td>
					</tr>
				</table>

				<textarea id="aauicri-urllist" name="aauicri-urllist" class="aauicri-urls" rows="5" required placeholder="" ></textarea>

<table class="description-wide" cellpadding="0" cellspacing="0" >
	<tbody>
		<tr>
			<td>
				<div class="aauicri-importresult aauicri-0 notice notice-success inline hidden"></div>
				<div class="aauicri-importnotice aauicri-0 notice notice-error inline hidden"></div>
			</td>
			<td>
			</td>
			<td class="textright">
				<p>
					<button type="button" class="button hidden aauicri-canceljob aauicri-urllist" data-job="urllist" >
						<?php _e('Cancel') ?>
					</button>
					<span class="spinner" style="float: initial;"></span>
					<button id="aauicri-importanalyse" type="button" class="button button-primary" 
					data-ajax_url="<?php echo admin_url('admin-ajax.php?action=aauicri_admin_ajax') ?>" 
					data-msg_nodata="<?php _e('Invalid data. The item does not exist.') ?>" 
					data-updating="<?php _e("Upgrading database&#8230;") ?>" 
					data-dftext="<?php _e('Import') ?>" 
					data-single="<?php echo _n( '%s media file attached.', '%s media files attached.', 1 ) ?>" 
					data-multi="<?php echo _n( '%s media file attached.', '%s media files attached.', 2 ) ?>" 
					>
						<?php _e('Import') ?>
					</button>
				</p>
			</td>
		</tr>
	</tbody>
</table>

<table class="description-wide aauicri-importprogress aauicri-importprogress-0 hidden" cellpadding="0" cellspacing="0" >
	<tbody>
		<tr>
			<td style="text-align:center;width:10em;min-width:10em">
				<div class="notice notice-success inline">
					<strong>% <span class="aauicri-addstatuspercent-0">?</span></strong><br>
					<span>
						<small class="aauicri-addstatus">[ ( <span class="aauicri-listblock-0"> </span> ) / <span class="aauicri-listcount-0"></span> ]</small>
					</span>
				</div>
			</td>
			<td>
				<div class="notice notice-success inline aauicri-importprogressbar-0" style="padding:1px 0">&nbsp;<br>&nbsp;</div>
			</td>
		</tr>
	</tbody>
</table>

			</div>
			<div id="aauicri_p1-pane" class="tab-pane tab-pane-aauicri_p1" >

<form action="#<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post">
	<div id="">
<?php if( !in_array('cloudinary',$setok) ){ ?>
		<div class="notice notice-warning warning inline">
			<p><strong><?php _e('Warning',AAUICRI_PLUGIN_DOMAIN); ?>:</strong></p>
			<p><?php _e('Missing settings',AAUICRI_PLUGIN_DOMAIN); ?></p>
			<p>
				<a class="button button-primary" href="<?php echo admin_url('upload.php?page=aauicri-import&tab=settings') ?>"><?php echo __('Settings'); ?></a>
			</p>
		</div>
<?php 
} ?>
		<table class="form-table">
			<tr valign="top">
				<th scope="row">
					<label for="aauicri-limit"><?php _e('Number of fetch image',AAUICRI_PLUGIN_DOMAIN); ?>:</label>
				</th>
				<td>
					<label for="aauicri-fetch_cloudinary-0"><input type="radio" name="aauicri-fetch_cloudinary" id="aauicri-fetch_cloudinary-0" value="0" />
						<?php _e('All'); ?>
					</label>
					<label for="aauicri-fetch_cloudinary-1"><input type="radio" name="aauicri-fetch_cloudinary" id="aauicri-fetch_cloudinary-1" value="1" checked="checked" />
						<?php _e('Limit',AAUICRI_PLUGIN_DOMAIN); ?>:
					</label>
					<input id="aauicri-limit_cloudinary" name="aauicri-limit_cloudinary" type="number" min="0" max="500" value="<?php echo ( isset( $_GET['aauicri-limit_cloudinary'] ) ) ? esc_html( $_GET['aauicri-limit_cloudinary'] ) : 500 ; ?>">
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label><?php _e('Protocol',AAUICRI_PLUGIN_DOMAIN); ?>:</label>
				</th>
				<td>
					<fieldset>
					<?php $isSecure=(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443; ?>
					<label for="aauicri-protocol_1"><input type="radio" name="aauicri-protocol" id="aauicri-protocol_1" value="1" <?php checked( $isSecure) ?> />https</label>
					<label for="aauicri-protocol_0"><input type="radio" name="aauicri-protocol" id="aauicri-protocol_0" value="0" <?php checked(!$isSecure) ?> />http</label>
					</fieldset>
				</td>
			</tr>
		</table>

<table class="description-wide" cellpadding="0" cellspacing="0" >
	<tbody>
		<tr>
			<td>
				<div class="aauicri-importresult aauicri-1 notice notice-success inline hidden"></div>
				<div class="aauicri-importnotice aauicri-1 notice notice-error inline hidden"></div>
			</td>
			<td>
			</td>
			<td class="textright">
				<button type="button" class="button hidden aauicri-canceljob aauicri-cldnry" data-job="cldnry" >
					<?php _e('Cancel') ?>
				</button>
				<span class="spinner" style="xxxvisibility:visible;"></span>
				<button id="aauicri-read_cloudinary_start" type="button" class="button button-primary" 
				 data-updating="<?php echo __("Updating…") ?>" 
				 data-empty="<?php echo __("Empty") ?>: <?php echo __("items") ?>" 
				 data-single="<?php echo _n( '%s media file attached.', '%s media files attached.', 1 ) ?>" 
				 data-multi="<?php echo _n( '%s media file attached.', '%s media files attached.', 2 ) ?>" 
				 data-ajax_url="<?php echo admin_url('admin-ajax.php?action=aauicri_admin_ajax') ?>">
					<?php echo __('Import') ?> 
				</button>
			</td>
		</tr>
	</tbody>
</table>

<table class="description-wide aauicri-importprogress aauicri-importprogress-1 hidden" cellpadding="0" cellspacing="0" >
	<tbody>
		<tr>
			<td style="text-align:center;width:10em;min-width:10em">
				<div class="notice notice-success inline">
					<strong>% <span class="aauicri-addstatuspercent-1">?</span></strong><br>
					<span>
						<small class="aauicri-addstatus">[ ( <span class="aauicri-listblock-1"> </span> ) / <span class="aauicri-listcount-1"></span> ]</small>
					</span>
				</div>
			</td>
			<td>
				<div class="notice notice-success inline aauicri-importprogressbar-1" style="padding:1px 0">&nbsp;<br>&nbsp;</div>
			</td>
		</tr>
	</tbody>
</table>

		<textarea id="aauicri-urls" class="hidden" rows="3" name="aauicri-urls" required readonly value=""></textarea>
		<br/>
	</div>

</form>


		</div>
	</div>

</div>
<table id="aauicri-importtable" class="plugins description-wide hidden" cellpadding="0" >
	<thead>
		<tr>
			<th><?php _e('Status'); ?></th>
			<th>URL</th>
			<th><?php _e('Width'); ?></th>
			<th><?php _e('Height'); ?></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td class="notice notice-info inline notice-alt"></td>
			<td></td>
			<td></td>
			<td></td>
		</tr>
		<tr>
			<td class="notice notice-error inline notice-alt"><i class="error-message"></i></td>
			<td></td>
			<td></td>
			<td></td>
		</tr>
	</tbody>
</table>

						</div>

					</td>
					<td align="right"><?php $this->aauInfoBar(); ?></td>
				</tr>
			</table>
			<?php $this->aauFooter(); ?>
		</div>
	<?php	}

	public function settingsPage($settings){
		?>
		<div class="wrap">
			<h2 id="aauicri_page-settings">
				<?php _e('Import CDN-Remote Images', AAUICRI_PLUGIN_DOMAIN) ?> — <?php _e('Settings'); ?>
				<span class="nonessential alignright"><?php _e('Version'); ?>: <?php echo AAUICRI_VERSION; ?></span>
			</h2>

			<table class="aauicri-frame0">
				<tr>
					<td>
						<div class="tabarea-aauicri">

<h2 class="nav-tab-wrapper">
	<a id="aauicri_p0-tab" data-toggle="aauicri_p0" href="#aauicri_p0_pane" class="nav-tab nav-tab-active" > <?php _e('General'); ?> </a>
	<a id="aauicri_p1-tab" data-toggle="aauicri_p1" href="#aauicri_p1_pane" class="nav-tab" > Cloudinary </a>
</h2>

<div class="tab-content">
	<div id="aauicri_p0-pane" class="tab-pane tab-pane-aauicri_p0 active" >

		<table class="form-table">
			<tr valign="top">
				<th scope="row">
					<label for="aauicri-trygetfile"><?php _e('Skip fast methot when image processing fails.', AAUICRI_PLUGIN_DOMAIN); ?></label>
				</th>
				<td>
					<input type="checkbox" id="aauicri-trygetfile" name="aauicri-trygetfile" value="1" disabled="disabled">
				</td>
				<td><p class="description">(N/A)</p></td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label for="aauicri-urlblocksize"><?php _e('URL per ajax request', AAUICRI_PLUGIN_DOMAIN); ?>:</label>
				</th>
				<td>
					<input type="text" id="aauicri-trygetfile" name="aauicri-urlblocksize" value="10" class="regular-text" disabled="disabled">
				</td>
				<td><p class="description">(N/A)</p></td>
			</tr>
		</table>

	</div>
	<div id="aauicri_p1-pane" class="tab-pane tab-pane-aauicri_p1" >

		<table class="form-table">

			<tr valign="top">
				<td scope="row"><label for="aauicri-cloud_name">Cloud name</label></td>
				<td>
					<input id="aauicri-cloud_name" name="aauicri-cloud_name" type="text" value="<?php echo $settings->cdn->cloudinary->cloud_name ?>" class="regular-text code">
				</td>
				<td><p class="description"><?php _e("Your Cloudinary account's cloud name.", AAUICRI_PLUGIN_DOMAIN); ?></p></td>
			</tr>
			
			<tr valign="top">
				<td scope="row"><label for="aauicri-api_key">Api key</label></td>
				<td>
					<input id="aauicri-api_key" name="aauicri-api_key" type="text" value="<?php echo $settings->cdn->cloudinary->api_key ?>" class="regular-text code">
				</td>
				<td><p class="description"><?php _e("Your Cloudinary account's Api key.", AAUICRI_PLUGIN_DOMAIN); ?></p></td>
			</tr>

			<tr valign="top">
				<td scope="row"><label for="aauicri-api_secret">Api secret</label></td>
				<td>
					<input id="aauicri-api_secret" name="aauicri-api_secret" type="password" value="<?php echo $settings->cdn->cloudinary->api_secret ?>" class="regular-text code">
					
				</td>
				<td><p class="description"><?php _e("Your Cloudinary account's Api secret.", AAUICRI_PLUGIN_DOMAIN); ?></p></td>
			</tr>
		</table>

	</div>
</div>
<div class="textright">
	<span class="spinner"></span>
	<button id="aauicri-settings_save" type="button" class="button button-primary" data-ajax_url="<?php echo admin_url('admin-ajax.php?action=aauicri_admin_ajax') ?>">
		<?php _e(translate('Save')) ?>
	</button>
</div>

						</div>

					</td>
					<td align="right"><?php $this->aauInfoBar(); ?></td>
				</tr>
			</table>

			<?php $this->aauFooter(); ?>
		</div>
	<?php 
	}

	public function add_utm_parameters(){
		$url_blog = "https://atakanau.blogspot.com/2020/10/import-cdn-remote-images-wp-plugin.html";
		$utm_parameters = array(
			'utm_source'	=> home_url(),
			'utm_medium'	=> 'link',
			'utm_content'	=> 'textlink',
			'utm_campaign'	=> AAUICRI_PLUGIN_DOMAIN
		);
		$url_parts = parse_url($url_blog);
		$query = [];
		if (isset($url_parts['query'])) {
			parse_str($url_parts['query'], $query);
		}
		$query = array_merge($query, $utm_parameters);
		$url_parts['query'] = http_build_query($query);
	
		// Yeni URL'yi oluştur
		return $url_blog .= "?{$url_parts['query']}";
	}

	public function aauInfoBar(){
		?>
			<div class="aauicri-infobar">
			<span class="">
<i class="dashicons dashicons-admin-home"></i> 
<?php _e('Visit my blog. The ads on the website may help me earn some tip. ;)', AAUICRI_PLUGIN_DOMAIN); ?>
<br/>
<a href="<?php echo $this->add_utm_parameters(); ?>" target="_blank">atakanau.blogspot.com</a>
<br/><br/>
<i class="dashicons dashicons-wordpress-alt dashicons-wordpress"></i> 
<?php _e('Please help me continue development by giving the plugin a 5 star.', AAUICRI_PLUGIN_DOMAIN); ?>
<br/>
<a target="_blank" href="https://wordpress.org/support/plugin/import-cdn-remote-images/reviews/?filter=5#new-post">
<?php _e(translate('Rate','import-cdn-remote-images')); ?> ★★★★★
</a>
<br/><br/>
<i class="dashicons dashicons-share"></i> 
<?php _e('Share plugin', AAUICRI_PLUGIN_DOMAIN); ?>:
<br/><a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo str_replace(':','%3A',AAUICRI_SUPPORT_LINK); ?>" target="_blank"><i class="dashicons dashicons-facebook"></i> Facebook</a>
<br/><a href="https://twitter.com/home?status=<?php echo str_replace(' ','%20',__('Import CDN-Remote Images', AAUICRI_PLUGIN_DOMAIN) ); ?>%20-%20<?php echo str_replace(' ','%20',__('Add Cloudinary images and videos to the media library without importing, i.e. uploading them to your WordPress site.', AAUICRI_PLUGIN_DOMAIN) ); ?>%20%0A<?php echo str_replace(':','%3A',AAUICRI_SUPPORT_LINK); ?>" target="_blank"><i class="dashicons dashicons-twitter"></i> Twitter</a>
<br/><a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo str_replace(':','%3A',AAUICRI_SUPPORT_LINK); ?>&title=Import%20CDN-Remote%20Images%20Plugin&summary=&source=" target="_blank"><i class="dashicons dashicons-linkedin"></i> LinkedIn</a>
			</span>
			</div>
	<?php }
	public function aauFooter(){?>
			<hr/>
			<div class="metabox-holder-disabled textright"><span class="postbox">   <a href="<?php echo $this->add_utm_parameters(); ?>" target="_blank">atakanau.blogspot.com</a>   </span></div>
	<?php }
	#endregion pages
}

// initialise plugin.
$atakanauICRI = new atakanauICRI();
