<?php
if ( !isset($_GET['api']) ){ exit(); }
$api_keys = array('poke5401851','poske5401852' );
if ( !in_array($_GET['api'] ,$api_keys) ){ echo 'No existe';exit(); }
//SELECT utm_campaign as campana, count(DISTINCT( session_id )) as n_sesiones, max(time) as ultimo_registro FROM `cp_bxc_stats_data` group by utm_campaign order by ultimo_registro DESC

global $wpdb;
$charset_collate = $wpdb->get_charset_collate();
$table_name = $wpdb->prefix . 'bxc_stats_data';
//$results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}bxc_stats_data WHERE $type ORDER BY time DESC LIMIT $n ;", OBJECT );
$results = $wpdb->get_results( "SELECT utm_campaign as campana, count(DISTINCT( session_id )) as n_sesiones, max(time) as ultimo_registro, data FROM `".$wpdb->prefix."bxc_stats_data`  where interaction=99 group by utm_campaign order by ultimo_registro DESC", OBJECT );

echo json_encode($results);

exit();
?>



