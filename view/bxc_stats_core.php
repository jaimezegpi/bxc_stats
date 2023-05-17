<?php
if ( !isset($_GET['api']) ){ exit(); }
$api_keys = array('poke5401851','poke5401852' );
if ( !in_array($_GET['api'] ,$api_keys) ){ echo 'No existe';exit(); }
define('DB_HOST', 'localhost');
define('DB_NAME', 'miradorbaron_pro');
define('DB_USER', 'miradorbaron_loftdigital');
define('DB_PASS', '3a-Q#E');

class db{
  private $dbhost;
  private $dbuser;
  private $dbpass;
  private $dbname;
  private $conn;

/*Constructor*/
  function __construct($dbuser = DB_USER, $dbpass = DB_PASS, $dbname = DB_NAME, $dbhost = DB_HOST){
    $this->dbhost = $dbhost;
    $this->dbuser = $dbuser;
    $this->dbpass = $dbpass;
    $this->dbname = $dbname;
  }

/*Make connection to database*/
  public function sql_open(){

      $this->conn = @mysqli_connect($this->dbhost, $this->dbuser, $this->dbpass,$this->dbname); 
      
      if (mysqli_connect_errno())
      {
          echo ' Error connect '.mysqli_connect_error();
        //array_push($GLOBALS["error"],mysqli_connect_errno().' - '.mysqli_connect_error().' Check Database Connections in setup.php ;) </p>');
      }else{
        mysqli_set_charset($this->conn,"utf8"); //Quita el problema de los acentos.
      }
  }

/*WARNING!! Insecure Query, be carefull,.
Return: array*/
  public function sql_query_read($query){
    $valores = array();
    $result = @mysqli_query($this->conn,$query);
    if (!$result) {
      echo "Error : ".$query.mysqli_connect_error();
    //   array_push($GLOBALS["error"],'<p>Error sql_query_read '.$query.'</p>');
    }else{
      $num_rows= mysqli_num_rows($result);
      for($i=0;$i<$num_rows;$i++){
        $row = mysqli_fetch_assoc($result);
        array_push($valores, $row);
      }
    }
    return $valores;
  }

/*WARNING!! Insecure Query, be carefull..
Return: boolean
*/
  public function sql_query_execute($sql){
    if ($sql)
    {
      $result=@mysqli_query($this->conn,$sql);
    }
     if (!$result) {
      echo "Error: error al ejecutar la query ".$sql;
    //   array_push($GLOBALS["error"], '<p>Error sql_query_execute '.$sql.'</p>');
      return false;
    }else{    
      return true;
    }
  }

//Get last reg id (from primary key)
//Return: id from current insert reg.
  public function sql_id(){
    return mysqli_insert_id($this->conn);
  }

//Close current connection
  public function sql_close(){
    @mysqli_close($this->conn);   
  }

//Clean input value..Use this.
  public function sql_escape($value){
    if ($value){return mysqli_real_escape_string($this->conn,$value);}else{ return false;}
  }

  
/*END OF CLASS*/
}

//SELECT utm_campaign as campana, count(DISTINCT( session_id )) as n_sesiones, max(time) as ultimo_registro FROM `cp_bxc_stats_data` group by utm_campaign order by ultimo_registro DESC


switch ($_GET['bxc-stats-action']) {

	case 'getCampaignLast100':
		$conn = new db();
$conn->sql_open();
		$results = $conn->sql_query_read( "SELECT * FROM cotizaciones where order by fecha_reg Desc limit 100", OBJECT );

		echo json_encode($results);
		$conn->sql_close();
		break;
	case 'getCampaignVisitsCounter':
		$charset_collate = $wpdb->get_charset_collate();
		//$table_name = $wpdb->prefix . 'bxc_stats_data';
		//$results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}bxc_stats_data WHERE $type ORDER BY time DESC LIMIT $n ;", OBJECT );
		//$results = $wpdb->get_results( "SELECT utm_campaign as campana, count(DISTINCT( session_id )) as n_sesiones, max(time) as ultimo_registro, data FROM `".$wpdb->prefix."bxc_stats_data` group by utm_campaign order by ultimo_registro DESC", OBJECT );
		//echo json_encode($results);
		break;
  case 'getAllDB':
      $conn = new db();
      $conn->sql_open();
      $query="SELECT * FROM cotizaciones;";
      //echo $query;
      $results = $conn->sql_query_read( $query);

      $filename = date('Y_m_d__H_i_s')."_";
      $filename .= ".csv";

      $fp = fopen(__DIR__.'/csv/'.$filename, 'w');
      $mailto = $_GET['mailto'];
      if ( !$mailto ){ $mailto = 'soporte@loftdigital.cl'; }

      foreach ($results as $campos_c) {
      $cmp = json_decode(json_encode($campos_c), true);
      fputcsv($fp, $cmp);
      }

      ini_set('display_errors', 1);
      ini_set('display_startup_errors', 1);
      error_reporting(E_ALL);

      $file =  __DIR__;
      include($file.'/phpmailer/PHPMailer.php');

      include($file.'/phpexcel/PHPExcel.php');
      $objPHPExcel = new PHPExcel();

      $objPHPExcel->setActiveSheetIndex(0);
      $rowCount = 1;
      $inputFileType = 'CSV';
      $inputFileName = __DIR__.'/csv/'.$filename;
      $objReader = PHPExcel_IOFactory::createReader($inputFileType);
      $objPHPExcel = $objReader->load($inputFileName);
      $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
      $xls_file = __DIR__.'/csv/'.date('YmdHis').'.xlsx';
      $xls_file_name = date('YmdHis').'.xlsx';
      $objWriter->save($xls_file);

      $mail = new PHPMailer\PHPMailer\PHPMailer();

      $mail->SetFrom('exportacion@edificiosaires.cl', 'Exportación.'); //Name is optional
      $mail->IsHTML(true);
      $mail->ClearAddresses();
      $mail->Subject   = 'Exportación Base de datos '.date('Y:m:d H:i:s');
      $mail->Body      = 'Export CSV adjunta.';
      $mail->AddAddress( $mailto );

      $mail->AddAttachment( $xls_file , $xls_file_name );

      $result = $mail->Send();

      unlink( __DIR__.'/csv/'.$filename );
      var_dump( 'Exportación segura realizada a '.$mailto );

      fclose($fp);

  break;

	default:
		$conn = new db();
		$conn->sql_open();
		$results = $conn->sql_query_read( "SELECT campaign as campana, count(DISTINCT( rut )) as n_sesiones, max(fecha_reg) as ultimo_registro, concat( '{\"nombres\":\"',nombre, apellido, email,'\",\"rut\":\"', rut, '\",\"telefono\":\"', telefono,'\",\"vivienda\":\"', vivienda, '\",\"comuna\":\"', comuna, '\",\"source\":\"',source, '\",\"medium\":\"',medium, campaign, llamado, gclid, utm_term, utm_content, utm_origin,campaign, fecha_reg ) as data FROM cotizaciones group by campaign order by ultimo_registro DESC", OBJECT );
		echo json_encode($results);
		$conn->sql_close();
		break;
}





exit();
?>

