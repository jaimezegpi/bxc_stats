<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
if ( !isset($_GET['api']) ){ exit(); }
$api_keys = array('poke5401851','poke5401852' );
if ( !in_array($_GET['api'] ,$api_keys) ){ echo 'No existe';exit(); }
//SELECT utm_campaign as campana, count(DISTINCT( session_id )) as n_sesiones, max(time) as ultimo_registro FROM `cp_bxc_stats_data` group by utm_campaign order by ultimo_registro DESC

global $wpdb;
switch ($_GET['bxc-stats-action']) {

	case 'getCampaignLast100':

		$results = $wpdb->get_results( "SELECT * FROM `".$wpdb->prefix."bxc_stats_data` where interaction=99 order by time Desc limit 100", OBJECT );

		echo json_encode($results);

		break;
	case 'getCampaignVisitsCounter':
		$charset_collate = $wpdb->get_charset_collate();
		$table_name = $wpdb->prefix . 'bxc_stats_data';
		//$results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}bxc_stats_data WHERE $type ORDER BY time DESC LIMIT $n ;", OBJECT );
		$results = $wpdb->get_results( "SELECT utm_campaign as campana, count(DISTINCT( session_id )) as n_sesiones, max(time) as ultimo_registro, data FROM `".$wpdb->prefix."bxc_stats_data` group by utm_campaign order by ultimo_registro DESC", OBJECT );

		echo json_encode($results);

		break;

	case 'getCampaignVisitsAndRegistersCounterToday':
		$charset_collate = $wpdb->get_charset_collate();
		$table_name = $wpdb->prefix . 'bxc_stats_data';
		//$results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}bxc_stats_data WHERE $type ORDER BY time DESC LIMIT $n ;", OBJECT );
		$time_range = date('Y-m-d');
		$results = $wpdb->get_results( "SELECT bs.utm_campaign as campana, count(DISTINCT( bs.session_id )) as n_sesiones, ( SELECT count(*) FROM ".$wpdb->prefix."bxc_stats_data WHERE interaction = 99 AND utm_campaign = campana AND  time LIKE '".$time_range."%' ) as n_registros, max(bs.time) as ultimo_registro, ( SELECT data FROM ".$wpdb->prefix."bxc_stats_data where interaction = 99 AND utm_campaign = bs.utm_campaign AND  time LIKE '".$time_range."%' order by time DESC limit 1 ) as last_reg FROM `".$wpdb->prefix."bxc_stats_data` as bs WHERE bs.time LIKE '".$time_range."%' group by bs.utm_campaign order by ultimo_registro DESC;", OBJECT );

		echo json_encode($results);

		break;

	case 'getCampaignVisitsAndRegistersCounterLast7Days':
		$charset_collate = $wpdb->get_charset_collate();
		$table_name = $wpdb->prefix . 'bxc_stats_data';
		//$results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}bxc_stats_data WHERE $type ORDER BY time DESC LIMIT $n ;", OBJECT );
		$time_range = date('Y-m-d');
		$results = $wpdb->get_results( "SELECT bs.utm_campaign as campana, count(DISTINCT( bs.session_id )) as n_sesiones, ( SELECT count(*) FROM ".$wpdb->prefix."bxc_stats_data WHERE interaction = 99 AND utm_campaign = campana AND  time >= curdate()-7 ) as n_registros, max(bs.time) as ultimo_registro, ( SELECT data FROM ".$wpdb->prefix."bxc_stats_data where interaction = 99 AND utm_campaign = bs.utm_campaign AND  time >= curdate()-7 order by time DESC limit 1 ) as last_reg FROM `".$wpdb->prefix."bxc_stats_data` as bs WHERE bs.time >= curdate()-7 group by bs.utm_campaign order by ultimo_registro DESC;", OBJECT );

		echo json_encode($results);

		break;



	case 'getCampaignView':
		$charset_collate = $wpdb->get_charset_collate();
		$table_name = $wpdb->prefix . 'bxc_stats_data';
		$results = $wpdb->get_results( "SELECT * FROM `".$wpdb->prefix."bxc_stats_data_view`  order by time DESC;", OBJECT );
		echo json_encode($results);
		break;

	case 'getCF7FormsNames':
		$charset_collate = $wpdb->get_charset_collate();
		$table_name = $wpdb->prefix . 'bxc_stats_data';
		$results = $wpdb->get_results( "SELECT id as form_id, post_date as time, post_title as name  FROM `".$wpdb->prefix."posts` WHERE post_type='wpcf7_contact_form';", OBJECT );
		echo json_encode($results);
		break;


	case 'getAllDBCurrentMonth':

		ini_set('memory_limit', '2048M');
		$query="SELECT * FROM ".$wpdb->prefix ."bxc_stats_data WHERE time LIKE '".date('Y-m-')."%' AND interaction = 99 ORDER BY time DESC LIMIT 100000 ";
		$site =  get_site_url();
		//echo $query;
		$results = $wpdb->get_results( $query);

		$filename = date('Y_m_d__H_i_s')."_";
		$filename .= ".csv";

		$fp = fopen(__DIR__.'/../csv/'.$filename, 'w');
		$mailto = $_GET['mailto'];
		if ( !$mailto ){ $mailto = 'soporte@loftdigital.cl'; }

		foreach ($results as $rindex=>$campos_c) {
			if (!$rindex){
				$thead=array();
				foreach ($campos_c as $cindex => $value) {
					array_push($thead, $cindex);
				}
				fputcsv($fp, $thead );
			}
			$cmp = json_decode(json_encode($campos_c), true);
			
			fputcsv($fp, $cmp);
		}

		$file =  __DIR__;
		require_once(__DIR__.'/../phpmailer/PHPMailer.php');
		require_once(__DIR__.'/../phpmailer/Exception.php');
		include($file.'/../phpexcel/PHPExcel.php');

		$cacheMethod = PHPExcel_CachedObjectStorageFactory:: cache_to_phpTemp;
		$cacheSettings = array( ' memoryCacheSize ' => '128MB');
		PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);

		$objPHPExcel = new PHPExcel();

		$objPHPExcel->setActiveSheetIndex(0);
		$rowCount = 1;
		$inputFileType = 'CSV';
		$inputFileName = __DIR__.'/../csv/'.$filename;
		$objReader = PHPExcel_IOFactory::createReader($inputFileType);
		$objPHPExcel = $objReader->load($inputFileName);
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		$xls_file = __DIR__.'/../csv/'.get_option( 'blogname' ).'_'.date('Y_m_d__H_i_s').'.xlsx';
		$xls_file_name =get_option( 'blogname' ).'_'.date('Y_m_d__H_i_s').'.xlsx';
		$objWriter->save($xls_file);

		$mail = new PHPMailer\PHPMailer\PHPMailer();

		$mail->SetFrom('exportacion@'.$_SERVER['SERVER_NAME'], 'Exportación.'); //Name is optional
		$mail->IsHTML(true);
		$mail->ClearAddresses();
		$mail->Subject   = 'DataBase Export from '.get_option( 'blogname' ).' - '.date('Y:m:d H:i:s');
		$mail->Body     = 'DataBase  ';
		$mail->AddAddress( $mailto );
		//$mail->AddAttachment( __DIR__.'/../csv/'.$filename , 'exportacion2_'.get_site_url().'_'.date('Y_m_d__H_i_s').'.csv' );
		$mail->AddAttachment( $xls_file,$xls_file_name );
		if($mail->Send()){
		    echo 'DataBase sending in excel format to. '.$mailto;
		}else{
		    echo 'Email Sending Failed! '.$mail->ErrorInfo;
		}

		unlink( $xls_file );
		unlink( $inputFileName );

		fclose($fp);

	break;

	case 'getAllDBByCF7ID':

		ini_set('memory_limit', '2048M');
		$query="SELECT * FROM ".$wpdb->prefix ."bxc_stats_data WHERE form_id = '".$_GET['form_id']."' AND interaction = 99 ORDER BY time DESC LIMIT 100000 ";
		$site =  get_site_url();
		//echo $query;
		$results = $wpdb->get_results( $query);

		$filename = date('Y_m_d__H_i_s')."_";
		$filename .= ".csv";

		$fp = fopen(__DIR__.'/../csv/'.$filename, 'w');
		$mailto = $_GET['mailto'];
		if ( !$mailto ){ $mailto = 'soporte@loftdigital.cl'; }

		foreach ($results as $rindex=>$campos_c) {
			if (!$rindex){
				$thead=array();
				foreach ($campos_c as $cindex => $value) {
					array_push($thead, $cindex);
				}
				fputcsv($fp, $thead );
			}
			$cmp = json_decode(json_encode($campos_c), true);
			fputcsv($fp, $cmp);
		}

		$file =  __DIR__;
		require_once(__DIR__.'/../phpmailer/PHPMailer.php');
		require_once(__DIR__.'/../phpmailer/Exception.php');
		include($file.'/../phpexcel/PHPExcel.php');

		$cacheMethod = PHPExcel_CachedObjectStorageFactory:: cache_to_phpTemp;
		$cacheSettings = array( ' memoryCacheSize ' => '128MB');
		PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);

		$objPHPExcel = new PHPExcel();

		$objPHPExcel->setActiveSheetIndex(0);
		$rowCount = 1;
		$inputFileType = 'CSV';
		$inputFileName = __DIR__.'/../csv/'.$filename;
		$objReader = PHPExcel_IOFactory::createReader($inputFileType);
		$objPHPExcel = $objReader->load($inputFileName);
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		$xls_file = __DIR__.'/../csv/'.get_option( 'blogname' ).'_'.date('Y_m_d__H_i_s').'.xlsx';
		$xls_file_name =get_option( 'blogname' ).'_'.date('Y_m_d__H_i_s').'.xlsx';
		$objWriter->save($xls_file);

		$mail = new PHPMailer\PHPMailer\PHPMailer();

		$mail->SetFrom('exportacion@'.$_SERVER['SERVER_NAME'], 'Exportación.'); //Name is optional
		$mail->IsHTML(true);
		$mail->ClearAddresses();
		$mail->Subject   = 'DataBase Export from '.get_option( 'blogname' ).' - '.date('Y:m:d H:i:s');
		$mail->Body     = 'DataBase  ';
		$mail->AddAddress( $mailto );
		//$mail->AddAttachment( __DIR__.'/../csv/'.$filename , 'exportacion2_'.get_site_url().'_'.date('Y_m_d__H_i_s').'.csv' );
		$mail->AddAttachment( $xls_file,$xls_file_name );
		if($mail->Send()){
			//echo $query;
		    echo '<br>CF7 DataBase sending in excel format to. '.$mailto;
		}else{
		    echo 'Email Sending Failed! '.$mail->ErrorInfo;
		}

		unlink( $xls_file );
		unlink( $inputFileName );

		fclose($fp);

	break;

	default:
		$charset_collate = $wpdb->get_charset_collate();
		$table_name = $wpdb->prefix . 'bxc_stats_data';
		//$results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}bxc_stats_data WHERE $type ORDER BY time DESC LIMIT $n ;", OBJECT );
		$results = $wpdb->get_results( "SELECT utm_campaign as campana, count(DISTINCT( session_id )) as n_sesiones, max(time) as ultimo_registro, data FROM `".$wpdb->prefix."bxc_stats_data`  where interaction=99 group by utm_campaign order by ultimo_registro DESC", OBJECT );

		echo json_encode($results);

		break;
}

exit();
?>



