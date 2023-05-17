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

	case 'getAllDB':
ini_set('memory_limit', '2048M');
	  $query="SELECT * FROM ".$wpdb->prefix."bxc_stats_data ORDER BY time DESC LIMIT 5000; ";
	  $site =  get_site_url();
	  //echo $query;
	  $results = $wpdb->get_results( $query);

	  $filename = date('Y_m_d__H_i_s')."_";
	  $filename .= ".csv";

	  $fp = fopen(__DIR__.'/../csv/'.$filename, 'w');
	  $mailto = $_GET['mailto'];
	  if ( !$mailto ){ $mailto = 'soporte@loftdigital.cl'; }

	  foreach ($results as $campos_c) {
	    $cmp = json_decode(json_encode($campos_c), true);
	    fputcsv($fp, $cmp);
	  }



	  $file =  __DIR__;
	  require_once(__DIR__.'/../phpmailer/PHPMailer.php');
	  require_once(__DIR__.'/../phpmailer/Exception.php');
		//include($file.'/../phpexcel/PHPExcel.php');

//$cacheMethod = PHPExcel_CachedObjectStorageFactory:: cache_to_phpTemp;
//$cacheSettings = array( ' memoryCacheSize ' => '128MB');
//PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);

      //$objPHPExcel = new PHPExcel();

      //$objPHPExcel->setActiveSheetIndex(0);
      $rowCount = 1;
      $inputFileType = 'CSV';
      $inputFileName = __DIR__.'/../csv/'.$filename;
      //$objReader = PHPExcel_IOFactory::createReader($inputFileType);
      //$objPHPExcel = $objReader->load($inputFileName);
      //$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
      $xls_file = __DIR__.'/csv/'.date('YmdHis').'.xlsx';
      $xls_file_name = date('YmdHis').'.xlsx';
      //$objWriter->save($xls_file);

	  $mail = new PHPMailer\PHPMailer\PHPMailer();

	  $mail->SetFrom('exportacion@'.$_SERVER['SERVER_NAME'], 'Exportación.'); //Name is optional
	          $mail->IsHTML(true);
	          $mail->ClearAddresses();
	  $mail->Subject   = 'Exportación Base de datos '.date('Y:m:d H:i:s');
	  $mail->Body      = 'Export CSV en link. '.get_site_url().'/csv/'.$filename;
	  $mail->AddAddress( $mailto );

	  $mail->AddAttachment( __DIR__.'/../csv/'.$filename , 'exportacion2_'.get_site_url().'_'.date('Y_m_d__H_i_s').'.csv' );
	   //$mail->AddAttachment( $inputFileName , 'holi.csv' );
	  $result = $mail->Send();

	  //unlink( __DIR__.'/../csv/'.$filename );
	  //var_dump( 'Exportación segura realizada a '.$mailto.'  '.__DIR__.'/../csv/'.$filename );

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


