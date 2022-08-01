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

	case 'getAllDB':

	  $query="SELECT * FROM ".$wpdb->prefix."bxc_stats_data;";
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
	  include($file.'/../phpmailer/PHPMailer.php');
		include($file.'/../phpexcel/PHPExcel.php');
      $objPHPExcel = new PHPExcel();

      $objPHPExcel->setActiveSheetIndex(0);
      $rowCount = 1;
      $inputFileType = 'CSV';
      $inputFileName = __DIR__.'/../csv/'.$filename;
      $objReader = PHPExcel_IOFactory::createReader($inputFileType);
      $objPHPExcel = $objReader->load($inputFileName);
      $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
      $xls_file = __DIR__.'/csv/'.date('YmdHis').'.xlsx';
      $xls_file_name = date('YmdHis').'.xlsx';
      $objWriter->save($xls_file);

	  $mail = new PHPMailer\PHPMailer\PHPMailer();

	  $mail->SetFrom('exportacion@'.$_SERVER['SERVER_NAME'], 'Exportación.'); //Name is optional
	          $mail->IsHTML(true);
	          $mail->ClearAddresses();
	  $mail->Subject   = 'Exportación Base de datos '.date('Y:m:d H:i:s');
	  $mail->Body      = 'Export CSV adjunta.';
	  $mail->AddAddress( $mailto );

	  //$mail->AddAttachment( __DIR__.'/../csv/'.$filename , 'exportacion.csv' );
	   $mail->AddAttachment( $xls_file , $xls_file_name );
	  $result = $mail->Send();

	  //unlink( __DIR__.'/../csv/'.$filename );
	  var_dump( 'Exportación segura realizada a '.$mailto.'  '.__DIR__.'/../csv/'.$filename );

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



