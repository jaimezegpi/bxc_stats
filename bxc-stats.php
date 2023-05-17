<?php
/*
* Plugin Name: BXC-Stats
* Author: Jaime A. Zegpi B.
* Description: Jaime A. Zegpi B.
* Plugin URI: https://jaime.zegpi.cl
* Licence : GPLv2.
* License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/
/*
BXC-Stats is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.
 
BXC-Stats is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License
along with {Plugin Name}. If not, see {URI to Plugin License}.
*/

date_default_timezone_set('America/Santiago');
if ( !session_id() ){
	session_start();
}
if ( isset($_GET["utm_origin"]) ){ $_SESSION['utm_origin']=sanitize_text_field($_GET["utm_origin"]); }else{ if ( !isset($_SESSION['utm_origin']) ){$_SESSION['utm_origin']="" ;} }
if ( isset($_GET["utm_campaign"]) ){ $_SESSION['utm_campaign']=sanitize_text_field($_GET["utm_campaign"]); }else{ if ( !isset($_SESSION['utm_campaign']) ){$_SESSION['utm_campaign']="" ;} }
if ( isset($_GET["utm_source"]) ){ $_SESSION['utm_source']=sanitize_text_field($_GET["utm_source"]); }else{ if ( !isset($_SESSION['utm_source']) ){$_SESSION['utm_source']="" ;} }
if ( isset($_GET["utm_medium"]) ){ $_SESSION['utm_medium']=sanitize_text_field($_GET["utm_medium"]); }else{ if ( !isset($_SESSION['utm_medium']) ){$_SESSION['utm_medium']="" ;} }
if ( isset($_GET["utm_content"]) ){ $_SESSION['utm_content']=sanitize_text_field($_GET["utm_content"]); }else{ if ( !isset($_SESSION['utm_content']) ){$_SESSION['utm_content']="" ;} }
if ( isset($_GET["utm_term"]) ){ $_SESSION['utm_term']=sanitize_text_field($_GET["utm_term"]); }else{ if ( !isset($_SESSION['utm_term']) ){$_SESSION['utm_term']="" ;} }
if ( isset($_GET["gclid"]) ){ $_SESSION['gclid']=sanitize_text_field($_GET["gclid"]); }else{ if ( !isset($_SESSION['gclid']) ){$_SESSION['gclid']="" ;} }

if ( !function_exists('bxc_stats_export_all') ){
	function bxc_stats_export_all( ) {

			global $post;
			global $wpdb;
			$where = bxc_stats_whereFilterMaker();
			$query="SELECT * FROM {$wpdb->prefix}bxc_stats_data ".$where.";";
			//echo $query;
			$results = $wpdb->get_results( $query, OBJECT );
			$filename = date('Y_m_d__H_i_s')."_";
			$filename .= $_GET["bxc_stats_action"];
			$filename .= ".csv";

			$fp = fopen(plugin_dir_path(__FILE__).'/csv/'.$filename, 'w');

			foreach ($results as $campos_c) {
				$cmp = json_decode(json_encode($campos_c), true);
				fputcsv($fp, $cmp);
			}

			fclose($fp);
			return $filename;
	}
	//add_action( 'manage_pages_custom_column', 'bxc_stats_add_custom_column_content' );
}


if ( !function_exists('bxc_stats_add_new_column_handler') ){
	function bxc_stats_add_new_column_handler( $columns ) {
		$columns['column_counter_unique_users'] = 'Unique Visits';
		return $columns;
	}add_filter( 'manage_pages_columns', 'bxc_stats_add_new_column_handler' );
	//add_filter( 'manage_pages_columns', 'bxc_stats_add_new_column_handler' );
}

if ( !function_exists('bxc_stats_add_custom_column_content') ){
	function bxc_stats_add_custom_column_content( $column ) {
		if ( 'column_counter_unique_users' === $column ) {
			global $post;
			global $wpdb;
			$results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}bxc_stats_data WHERE post_id = '".$post->ID."' AND interaction = '1' ; ", OBJECT );
			 echo sanitize_text_field( count($results) );
		}
	}add_action( 'manage_pages_custom_column', 'bxc_stats_add_custom_column_content' );
	//add_action( 'manage_pages_custom_column', 'bxc_stats_add_custom_column_content' );
}

if ( !function_exists('bxc_stats_add_unique_visit') ){
	function bxc_stats_add_unique_visit(){
		if ( !is_admin() ){
			if ( !session_id() ){
				session_start();
			}
			$user_id = get_current_user_id();
			global $post;
			global $wpdb;
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			$results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}bxc_stats_data WHERE session_id = '".session_id()."' AND post_id = ".$post->ID." AND interaction = 1 AND query_string='".sanitize_text_field($_SERVER['QUERY_STRING'])."' ;", OBJECT );
			$ip = bxc_stats_getRealIpAddr();
			$request_uri = sanitize_text_field($_SERVER['REQUEST_URI']);
			$query_string = sanitize_text_field($_SERVER['QUERY_STRING']);
			$utm_campaign= sanitize_text_field($_SESSION['utm_origin']);
			$utm_campaign= sanitize_text_field($_SESSION['utm_campaign']);
			$utm_source = sanitize_text_field($_SESSION['utm_source']);
			$utm_medium = sanitize_text_field($_SESSION['utm_medium']);
			$utm_content = sanitize_text_field($_SESSION['utm_content']);
			$utm_term = sanitize_text_field($_SESSION['utm_term']);
			$utm_term = sanitize_text_field($_SESSION['gclid']);

			if ( isset($_SERVER['HTTP_REFERER']) ){
				$referer = $_SERVER['HTTP_REFERER'];
			}else{
				$referer = "";
			}
			if (!count($results)){
				$sql = ' INSERT INTO '.$wpdb->prefix.'bxc_stats_data (post_id,session_id,interaction,url,redirect_url,query_string,time,ip,user_id,utm_campaign,utm_source,utm_medium,utm_content,utm_term,gclid,http_referer)VALUES('.$post->ID.',"'.session_id().'",1,"'.$request_uri.'","'.$_SERVER['REDIRECT_URL'].'","'.$query_string.'","'.Date('Y-m-d H:i:s').'","'.$ip.'",'.$user_id.',"'.$_SESSION['utm_campaign'].'","'.$_SESSION['utm_source'].'","'.$_SESSION['utm_medium'].'","'.$_SESSION['utm_content'].'","'.$_SESSION['utm_term'].'","'.$_SESSION['gclid'].'","'.$referer.'")';
				dbDelta( $sql );
			}

		}
	}
	add_action( 'template_redirect', 'bxc_stats_add_unique_visit' );
}

if ( !function_exists('bxc_stats_add_cf7_form') ){
	function bxc_stats_add_cf7_form($name,$email,$phone,$form_id,$data){
		if ( !is_admin() ){
			if ( !session_id() ){
				session_start();
			}
			$user_id = get_current_user_id();
			if (!$name){ $name=""; }
			if (!$email){ $email=""; }
			if (!$phone){ $phone=""; }
			if (!$data){ $data=""; }
			if (!$form_id){ $form_id=0; }
			global $post;
			global $wpdb;
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

			$ip = bxc_stats_getRealIpAddr();
			$request_uri = sanitize_text_field($_SERVER['REQUEST_URI']);
			$query_string = sanitize_text_field($_SERVER['QUERY_STRING']);
			$utm_campaign= sanitize_text_field($_SESSION['utm_campaign']);
			$utm_origin = sanitize_text_field($_SESSION['utm_origin']);
			$utm_source = sanitize_text_field($_SESSION['utm_source']);
			$utm_medium = sanitize_text_field($_SESSION['utm_medium']);
			$utm_content = sanitize_text_field($_SESSION['utm_content']);
			$utm_term = sanitize_text_field($_SESSION['utm_term']);
			$gclid = sanitize_text_field($_SESSION['gclid']);

			if ( isset($_SERVER['HTTP_REFERER']) ){
				$referer = $_SERVER['HTTP_REFERER'];
			}else{
				$referer = "";
			}
			$post_id = $post->ID;
			if (!$post_id){ $post_id = 0; }
			/*99=cf7*/
			$sql = "INSERT INTO ".$wpdb->prefix."bxc_stats_data (post_id,session_id,interaction,url,redirect_url,query_string,time,ip,user_id,utm_campaign,utm_source,utm_medium,utm_content,utm_term,gclid,http_referer,data,name,email,phone,form_id)VALUES(".$post_id.",'".session_id()."',99,'".$request_uri."','".$_SERVER['REDIRECT_URL']."','".$query_string."','".Date('Y-m-d H:i:s')."','".$ip."',".$user_id.",'".$_SESSION['utm_campaign']."','".$_SESSION['utm_source']."','".$_SESSION['utm_medium']."','".$_SESSION['utm_content']."','".$_SESSION['utm_term']."','".$_SESSION['gclid']."','".$referer."','".$data."','".$name."','".$email."','".phone."',".$form_id.")";
			//file_put_contents("roma.txt", $sql);
			dbDelta( $sql );


		}
	}
	//add_action( 'template_redirect', 'bxc_stats_add_cf7_form' );
}


if ( !function_exists('bxc_stats_getRealIpAddr') ){
	function bxc_stats_getRealIpAddr(){
		if (!empty($_SERVER['HTTP_CLIENT_IP'])){
		$ip=$_SERVER['HTTP_CLIENT_IP'];
		}elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
		$ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
		}else{
		$ip=$_SERVER['REMOTE_ADDR'];
		}
		return $ip;
	}
}

function bxc_stats_plugin_activate() {

  add_option( 'Activated_Plugin', 'BXC-Stats' );
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$table_name = $wpdb->prefix . 'bxc_stats_data';
		/*
		* Interaction;
		* 1: Unique view
		* 2: Click in #id or .class
		* 3: Event
		* 99: form
		*/
		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			ip varchar(20) NOT NULL,
			session_id text NOT NULL,
			post_id int(10) NOT NULL,
			form_id int(10) NOT NULL,
			user_id int(10) NOT NULL,
			interaction int(5),
			http_referer varchar(100),
			redirect_url text,
			query_string text,
			utm_campaign varchar(100),
			utm_source varchar(100),
			utm_medium varchar(100),
			utm_content varchar(100),
			utm_term varchar(100),
			utm_origin varchar(100),
			gclid varchar(100),
			name varchar(100),
			email varchar(100),
			phone varchar(100),
			url text,
			data text,
			time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			UNIQUE KEY id (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );

}
register_activation_hook( __FILE__, 'bxc_stats_plugin_activate' );

function bxc_stats_load_plugin() {

    if ( is_admin() && get_option( 'Activated_Plugin' ) == 'BXC-Stats' ) {

        delete_option( 'Activated_Plugin' );

    }
}
add_action( 'admin_init', 'bxc_stats_load_plugin' );


/*Admin Page*/
if ( !function_exists('bxc_stats_setup_menu') ){
	add_action('admin_menu', 'bxc_stats_setup_menu');
	function bxc_stats_setup_menu(){
	    add_menu_page( 'BXC-Stats Page', 'BXC-Stats', 'manage_options', 'bxc-stats', 'bxc_stats_setup_admin_view' );
	}
}

if ( !function_exists('bxc_stats_setup_admin_view') ){
	function bxc_stats_setup_admin_view(){
		/*ACTIONS*/
		if ( isset($_GET["bxc_stats_action"]) ){
			$csv_file = "";
			if ( $_GET["bxc_stats_action"] == 1 ){
				$csv_file = bxc_stats_export_all(  );
			}
			if ( $_GET["bxc_stats_action"] == 99 ){
				$csv_file = bxc_stats_export_all( 99 );
			}
		}
		wp_enqueue_style( 'bxc-stats-style-css', plugins_url('css/bxc-stats-style.css', __FILE__),false,1,'all');
		wp_enqueue_style( 'bxc-stats-desktop-css', plugins_url('css/bxc-stats-desktop.css', __FILE__),false,1,'all');

	    include( plugin_dir_path( __FILE__ ) . 'view/admin-stats.php');
	}
}

/*Get Rows by date*/
if ( !function_exists('bxc_stats_getRowsCountByDate') ){
	function bxc_stats_getRowsCountByDate($date_start,$date_end){
	    if ( !$date_start || !$date_end ){
	    	return false;
	    }else{
			global $wpdb;
			$charset_collate = $wpdb->get_charset_collate();
			$table_name = $wpdb->prefix . 'bxc_stats_data';
			$results = $wpdb->get_results( "SELECT count(*) AS total FROM {$wpdb->prefix}bxc_stats_data WHERE interaction = 1 AND (time >='$date_start' AND time <='$date_end' ) ;", OBJECT );
			return $results;
	    }

	}
}


/*Get last 10 Visit Rows by type*/
if ( !function_exists('bxc_stats_getLastRows') ){
	function bxc_stats_getLastRows($type=0,$n=100){
		$type = esc_sql($type);
		$n = esc_sql($n);
		if ($type == 0 ){
			$type=" ";
		}else{
			$type=" interaction LIKE '%$type%' ";
		}
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$table_name = $wpdb->prefix . 'bxc_stats_data';
		$results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}bxc_stats_data WHERE $type ORDER BY time DESC LIMIT $n ;", OBJECT );
		echo ">> SELECT * FROM {$wpdb->prefix}bxc_stats_data WHERE interaction = '$type' ORDER BY time DESC LIMIT $n ;";
		return $results;

	}
}

/*Get Columns opstions Rows by name*/
if ( !function_exists('bxc_stats_getColumnOptionsByName') ){
	/**
	 * @param  string
	 * @return object array
	 */
	function bxc_stats_getColumnOptionsByName($type=1,$name){
		$type = esc_sql($type);
		if (!$name){ return false; }
		if ($type == 0 ){ $type="%"; }
		$name = esc_sql($name);
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$table_name = $wpdb->prefix . 'bxc_stats_data';
		$query = "SELECT $name as option FROM {$wpdb->prefix}bxc_stats_data WHERE interaction = '{$type}' GROUP BY $name ORDER BY $name ;";
		$results = $wpdb->get_results( $query, OBJECT );
		return $results;

	}
}


/*Get Columns opstions Rows by name*/
if ( !function_exists('bxc_stats_getRowsByGet') ){
	/**
	 * @param  string
	 * @return object array
	 */
	function bxc_stats_getRowsByGetLimit($limit){

		if ( isset($_GET["bxc_stats_action"]) ){
			if ( $_GET["bxc_stats_action"]=="preview_rows" ){
				$limit = 100;
			}
		}

		if ( $limit == 0 ){
			$limit = " ";
		}else{
			$limit = " LIMIT ".$limit;
		}

		$where = bxc_stats_whereFilterMaker();
		  
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$table_name = $wpdb->prefix . 'bxc_stats_data';
		$query = "SELECT * FROM {$wpdb->prefix}bxc_stats_data $where $limit;";
		$results = $wpdb->get_results( $query, OBJECT );
		return $results;

	}
}





if ( !function_exists('bxc_stats_catch_beforme_send') ){
	add_action('wpcf7_before_send_mail','bxc_stats_catch_cf7_before_send');
	/**
	 * @param  [cftform]
	 * @return [boolean]
	 */
	function bxc_stats_catch_cf7_before_send($WPCF7_ContactForm){
		//$form_id='00000'; /* ID de formulario */
		//$WPCF7_ContactForm->id()
		$form_id = $WPCF7_ContactForm->id();
		
		$currentformInstance  = WPCF7_ContactForm::get_current();
		$contactformsubmition = WPCF7_Submission::get_instance();
		if ($contactformsubmition) {
			$cc_email = array();
			$data = $contactformsubmition->get_posted_data();
			$data_json = json_encode($data);
			/* -------------- */
			/*
			$post_id = $data['ID']; <-- name of input field
			*/
			$name = "";
			$email = "";
			$phone = "";

			try {
				if ( isset( $data['name'] ) ){ $name.=$data['name']." "; }
				if ( isset( $data['NombreyApellidos'] ) ){ $name.=$data['NombreyApellidos']." "; }
				
				if ( isset( $data['nombre'] ) ){ $name.=$data['nombre']." "; }
				if ( isset( $data['nombres'] ) ){ $name.=$data['nombres']." "; }
				if ( isset( $data['lastname'] ) ){ $name.=$data['lastname']." "; }
				if ( isset( $data['apellido'] ) ){ $name.=$data['apellido']." "; }
				if ( isset( $data['apellidos'] ) ){ $name.=$data['apellidos']." "; }

				if ( isset( $data['email'] ) ){ $email.=$data['email']." "; }
				if ( isset( $data['emails'] ) ){ $email.=$data['emails']." "; }
				if ( isset( $data['e-mail'] ) ){ $email.=$data['e-mail']." "; }
				if ( isset( $data['mail'] ) ){ $email.=$data['mail']." "; }

				if ( isset( $data['phone'] ) ){ $phone.=$data['phone']." "; }
				if ( isset( $data['mobil'] ) ){ $phone.=$data['mobil']." "; }
				if ( isset( $data['mobile'] ) ){ $phone.=$data['mobile']." "; }
				if ( isset( $data['telefono'] ) ){ $phone.=$data['telefono']." "; }
				if ( isset( $data['fono'] ) ){ $phone.=$data['fono']." "; }
				if ( isset( $data['fonos'] ) ){ $phone.=$data['fonos']." "; }
				bxc_stats_add_cf7_form($name, $email, $phone, $form_id, $data_json);
			} catch (Exception $e) {

			
			}
			return $currentformInstance;
		}

		return true;
	}
}

function bxc_stats_whereFilterMaker(){
		$where = "";

		if ( isset($_GET["session_id"]) ){ 
			if ($_GET["session_id"]){
				if ( $where ){ $where.=" AND "; }
				$where.=" session_id LIKE '%".esc_sql($_GET["session_id"])."%'";
			}			
		}

		if ( isset($_GET["post_id"]) ){ 
			if ($_GET["post_id"]){
				if ( $where ){ $where.=" AND "; }
				$where.=" post_id = ".esc_sql($_GET["post_id"]);
			}			
		}

		if ( isset($_GET["form_id"]) ){
			if ( $_GET["form_id"] ){
				if ( $where ){ $where.=" AND "; }
				$where.=" form_id = ".esc_sql($_GET["form_id"]);
			}
		}

		if ( isset($_GET["user_id"]) ){
			if ( $_GET["user_id"] ){
				if ( $where ){ $where.=" AND "; }
				$where.=" user_id = ".esc_sql($_GET["user_id"]);
			}
			
		}

		if ( isset($_GET["interaction"]) ){
			if ( $_GET["interaction"] ){
				if ( $where ){ $where.=" AND "; }
				$where.=" interaction = ".esc_sql($_GET["interaction"]);
			}
		}

		if ( isset($_GET["http_referer"]) ){
			if ( $_GET["http_referer"] ){
				if ( $where ){ $where.=" AND "; }
				$where.=" http_referer = '".esc_sql($_GET["http_referer"])."'";
			}
		}

		if ( isset($_GET["utm_campaig"]) ){
			if( $_GET["utm_campaig"] ){
				if ( $where ){ $where.=" AND "; }
				$where.=" utm_campaig = '".esc_sql($_GET["utm_campaig"])."'";
			}
		}

		if ( isset($_GET["utm_source"]) ){
			if ( $_GET["utm_source"] ){
				if ( $where ){ $where.=" AND "; }
				$where.=" utm_source = '".esc_sql($_GET["utm_source"])."'";
			}			
		}
		if ( isset($_GET["utm_origin"]) ){
			if ( $_GET["utm_origin"] ){
				if ( $where ){ $where.=" AND "; }
				$where.=" utm_origin = '".esc_sql($_GET["utm_origin"])."'";
			}			
		}
		if ( isset($_GET["utm_medium"]) ){
			if ( $_GET["utm_medium"] ){
				if ( $where ){ $where.=" AND "; }
				$where.=" utm_medium = '".esc_sql($_GET["utm_medium"])."'";
			}
		}

		if ( isset($_GET["utm_content"]) ){
			if( $_GET["utm_content"] ){
				if ( $where ){ $where.=" AND "; }
				$where.=" utm_content = '".esc_sql($_GET["utm_content"])."'";
			}
		}

		if ( isset($_GET["utm_temp"]) ){
			if ( $_GET["utm_temp"] ){
				if ( $where ){ $where.=" AND "; }
				$where.=" utm_temp = '".esc_sql($_GET["utm_temp"])."'";
			}			
		}

		if ( isset($_GET["gclid"]) ){
			if ( $_GET["gclid"] ){
				if ( $where ){ $where.=" AND "; }
				$where.=" gclid = '".esc_sql($_GET["gclid"])."'";
			}
		}

		if ( isset($_GET["name"]) ){
			if ( $_GET["name"] ){
				if ( $where ){ $where.=" AND "; }
				$where.=" name LIKE '%".esc_sql($_GET["name"])."%'";
			}
		}

		if ( isset($_GET["email"]) ){
			if ( $_GET["email"] ){
				if ( $where ){ $where.=" AND "; }
				$where.=" email = '".esc_sql($_GET["email"])."'";
			}
		}

		if ( isset($_GET["phone"]) ){
			if( $_GET["phone"] ){
				if ( $where ){ $where.=" AND "; }
				$where.=" phone LIKE '%".esc_sql($_GET["phone"])."%'";
			}
		}

		if ( isset($_GET["url"]) ){
			if( $_GET["url"] ){
				if ( $where ){ $where.=" AND "; }
				$where.=" url = '".esc_sql($_GET["url"])."'";
			}
		}

		if ( isset($_GET["data"]) ){
			if( $_GET["data"] ){
				if ( $where ){ $where.=" AND "; }
				$where.=" data LIKE '%".esc_sql($_GET["data"])."%'";
			}
		}
		  
		if ( $where ){ $where=" WHERE ".$where; }

		if ( $where ){
			return $where;
		}else{
			return false;
		}
}

if ( !function_exists('bxc_stats_addJavascript') ){
	add_action('wp_footer', 'bxc_stats_addJavascript');
	function bxc_stats_addJavascript(){
		$render_js = "";

	    if ( $_SESSION['utm_campaign'] ){
			$utm_campaign = sanitize_text_field($_SESSION['utm_campaign']);
	    	$render_js = ' jQuery(".utm_campaign").val("'.$utm_campaign.'"); jQuery("#utm_campaign").val("'.$utm_campaign.'");';
	    }

	    if ( $_SESSION['utm_source'] ){
			$utm_source = sanitize_text_field($_SESSION['utm_source']);
	    	$render_js .= ' jQuery(".utm_source").val("'.$utm_source.'"); jQuery("#utm_source").val("'.$utm_source.'");';
	    }
	    if ( $_SESSION['utm_origin'] ){
			$utm_origin = sanitize_text_field($_SESSION['utm_origin']);
	    	$render_js .= ' jQuery(".utm_origin").val("'.$utm_origin.'"); jQuery("#utm_origin").val("'.$utm_origin.'");';
	    }
	    if ( $_SESSION['utm_medium'] ){
			$utm_medium = sanitize_text_field($_SESSION['utm_medium']);
	    	$render_js .= ' jQuery(".utm_medium").val("'.$utm_medium.'"); jQuery("#utm_medium").val("'.$utm_medium.'");';
	    }

	    if ( $_SESSION['utm_content'] ){
			$utm_content = sanitize_text_field($_SESSION['utm_content']);
	    	$render_js .= ' jQuery(".utm_content").val("'.$utm_content.'"); jQuery("#utm_content").val("'.$utm_content.'");';
	    }

	    if ( $_SESSION['utm_term'] ){
			$utm_term = sanitize_text_field($_SESSION['utm_term']);
	    	$render_js = $render_js . ' jQuery(".utm_term").val("'.$utm_term.'"); jQuery("#utm_term").val("'.$utm_term.'");';
	    }

	    if ( $_SESSION['gclid'] ){
			$gclid = sanitize_text_field($_SESSION['gclid']);
	    	$render_js .= ' jQuery(".gclid").val("'.$gclid.'"); jQuery("#gclid").val("'.$gclid.'");';
	    }

	    echo "<script>/* BXC-STATS INI */";
	    echo $render_js;
	    echo "</script>";

	}
}


if ( !function_exists('bxc_stats_renderStats') ){

	function bxc_stats_renderStats(){
		wp_enqueue_style( 'bxc-stats-style-css', plugins_url('css/bxc-stats-style.css', __FILE__),false,1,'all');
		wp_enqueue_style( 'bxc-stats-desktop-css', plugins_url('css/bxc-stats-desktop.css', __FILE__),false,1,'all');

	    include( plugin_dir_path( __FILE__ ) . 'view/render-stats.php');
	}
}



if ( isset($_GET['bxc-stats-action']) ){
	bxc_stats_renderStats();
}

?>
