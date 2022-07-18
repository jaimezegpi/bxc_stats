<?php
$get_hora =  bxc_stats_getRowsCountByDate(date("Y-m-d H:01:01"),date("Y-m-d H:59:59"));
$hora = $get_hora[0]->total;

$get_hoy =  bxc_stats_getRowsCountByDate(date("Y-m-d 00:00:00"),date("Y-m-d 23:59:00"));
$hoy = $get_hoy[0]->total;

$get_mes =  bxc_stats_getRowsCountByDate(date("Y-m-01 00:00:00"),date("Y-m-31 23:59:00"));
$mes = $get_mes[0]->total;

$get_ano =  bxc_stats_getRowsCountByDate(date("Y-01-01 00:00:00"),date("Y-12-31 23:59:00"));
$ano = $get_ano[0]->total;

$first_row = bxc_stats_getLastRows(1,1);

$get_columns_array = array("form_id","post_id","user_id","interaction","http_referer","utm_campaign","utm_source","utm_medium");

if ( isset( $csv_file ) ){
	?>
<script type="text/javascript">
	//window.location.href='<?php echo esc_url( plugins_url( 'csv/'.$csv_file, dirname(__FILE__) ) ) ?>';
</script>
	<?php
}

?>
<h1>BCX-Stats</h1>

<section id="bxc-stats-forms" class="block-info">
	<form action="">
	<h2><span>Universal Exporter</span></h2>
	<div class="row">
		<div class="col-sm-12 bxc-stats-last-rows">
			<div class="bxc-stats-container">

				<div class="bxc-stats-last-rows-title">
					<h2>View Last 100 Rows</h2>
				</div>

				<div class="bxc-stats-last-rows-items">
				
					<table>
					<?php


					if ( $first_row ){
						if ( is_array($first_row) ){
							foreach( $first_row AS $row_index=> $row ){
								?>
								<tr>
								
								<?php
								if ( $row ){
									foreach( $row as $inner_row=>$sub_row ){
										$label = esc_html($inner_row);
										?>
										<td>
										<?php
										if ( in_array($inner_row, $get_columns_array) ){
											$options = bxc_stats_getColumnOptionsByName(1,$inner_row);

											if ( count($options) ){
												?>
												<select name="<?php echo $label; ?>">
													<option value="0" <?php if ( !isset($_GET[$label]) ){ echo "selected"; } ?> ><?php echo $label; ?> All </option>
												<?php
												foreach($options AS $option){
													$option_value = esc_html($option->option);
													if ( $option_value==0 ){ continue; }
													$option_text = $option_value;
													if ($label=="post_id" || $label=="form_id" ){
														$option_post = get_post($option_value);
														if ( $option_post ){
															$option_text = $option_value." - ".$option_post->post_title;
														}else{
															$option_text = "";
														}
													}
													if ( $label=="interaction" ){
														/*
														* Interaction;
														* 1: Unique view
														* 2: Click in #id or .class
														* 3: Event
														* 99: form
														*/
														if ( $option_value == 1 ){
															$option_text = "1: Unique Visit";
														}elseif( $option_value == 2 ){
															$option_text = "2: Click in #id or .class";
														}elseif( $option_value == 3 ){
															$option_text = "3: Event";
														}elseif( $option_value == 99 ){
															$option_text = "99: Form";
														}
													}
													?>
													<option value="<?php echo esc_html($option_value); ?>" <?php if ( isset($_GET[$label]) ){ if ($_GET[$label]==$option_value){ echo " selected"; } } ?> > <?php echo sanitize_text_field($option_text); ?> </option>
													<?php
												}
												?>
												</select>
												<?php
											}
											?>

											<?php
										}else{
											?>
											<input type="text" name="<?php echo esc_html($inner_row); ?>" placeHolder="<?php echo esc_html($inner_row); ?> ALL" value="<?php if ( isset($_GET[$label]) ){ if ($_GET[$label]){ echo sanitize_text_field($_GET[$label]); } } ?>">
											<?php
										}
										?>
										</td>
										<?php
									}
								}
								?>
								</tr>
								<?php
							}							
						}

					}

					$last_rows = bxc_stats_getRowsByGetLimit(100);
					if ( $last_rows ){
						foreach ( $last_rows AS $row_key=>$row ){
							$cebra = $row_key/2;
							$cebra_class="";
							if ( is_int($cebra) ){
								$cebra_class="color-grey";
							}

							if ( $row ){
								?><tr><?php
								foreach( $row as $inner_row_b=>$sub_row ){

								?>
								<td class="<?php echo $cebra_class; ?>"><?php echo sanitize_text_field($sub_row); ?></td>
								<?php

								}
								?></tr><?php
							}

						}
					?>

					<?php
					}else{
						echo sanitize_text_field("<h3>No hay Registos aún.</h3>");
					}


					?>					
					</table>
				</div>
			</div>
		</div>
		<div class="col-sm-12">
			<input type="hidden" name="page" value="<?php echo sanitize_text_field($_REQUEST['page']); ?>">
			<button name="bxc_stats_action" value="preview_rows">Preview query</button>
			<button name="bxc_stats_action" value="export_rows">Export all query rows to a file</button>
			
			<?php
			if ( isset($_GET["bxc_stats_action"]) ){
				if ($_GET["bxc_stats_action"]=="export_rows"){
					bxc_stats_export_all();
					echo sanitize_text_field("Export Query to CSV");
				}

				if ($_GET["bxc_stats_action"]=="clean_folder"){
					$fileList = scandir(plugin_dir_path(__FILE__).'../csv/',1);
					if ( $fileList ){
						foreach ($fileList as $key_file => $file) {
							if(is_file(plugin_dir_path(__FILE__).'../csv/'.$file)){
								
								try {
									unlink(plugin_dir_path(__FILE__).'../csv/'.$file);	
									echo " <br>".$file." Deleted.";
								} catch (Exception $e) {
									echo " <br>".$file." Can't be Deleted.";
								}
							}
						}
					}

				}
				
			}
			?>			
		</div>
	</div>

	<?php
	$fileList = scandir(plugin_dir_path(__FILE__).'../csv/',1);

	if ( count($fileList)>2 ){
		?>
	<div class="bxc-stats-file-admin">
		<br>
			<?php
			foreach ($fileList as $key_file => $file) {
				if ( $file=="." || $file==".." ){continue;}
				$file_size = filesize( plugin_dir_path(__FILE__).'../csv/'.$file )/1024;
				?>
				<div class="bxc-stats-file-row flex">
					<div class="bxc-stats-filename">
						<a href="<?php echo plugin_dir_url(__FILE__).'../csv/'.$file; ?>"><?php echo sanitize_text_field($file); ?> - <?php echo $file_size; ?>Kb</a>
					</div>
				</div>
				<?php
			}
			?>
			<small>* Click in FileName to download.</small><br>
		<div class="bxc_stats_delete_container">
			<button name="bxc_stats_action" value="clean_folder">Delete Exports</button></br>
			<small> * This action delete all CVS files in folder.</small>
		</div>
	</div>
		<?php
	}
	?>
	</form>
	</div>
</section>



