<div id="transactions" class="wrap">
<div id="ys-overlay">
	<div class="ys-spinner">
    <div class="loader"></div>
  </div>		
</div>	
<?php require(YS_PLUGIN_DIR.'/admin/navigation.php'); ?>
  <h2>Транзакции</h2>
	<table id="transactions-filters" class="form-table transactions">
		<thead>
			<tr>
				<th>Месец</th>
				<th>Година</th>
				<th>Общо транзакции</th>
				<th>Обща такса</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td>
					<select id="months">
						<?php 
							foreach($output['months'] as $key => $value) {
								if($key == $output['current_month']) {
									echo '<option value="'.$key.'" selected>'.$value.'</option>';
								} else {
									echo '<option value="'.$key.'">'.$value.'</option>';
								}
							} 
						?>
					</select>
				</td>

				<td>
					<select id="years">
						<?php 
						foreach($output['years'] as $key => $value) {
							echo '<option value="'.$key.'">'.$value.'</option>';
						}	
						?>
					</select>
				</td>
				
				<td id="sum_rows">
					<?php 
						echo $output['transactions']->sum_rows; 
					?>
				</td>

				<td id="sum_rows">
					<?php 
						echo $output['transactions']->taxa; 
					?>
				</td>

			</tr>     
		</tbody> 
	</table>
	<?php require_once('table.php'); ?>
</div> 