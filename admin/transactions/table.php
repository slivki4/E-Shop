<div id="transactions-wrapper">
	<table id="transactions-content" class="form-table transactions">
		<thead>
			<tr>
				<th>Дата</th>
				<th>№ на заявката</th>
				<th>Държава</th>
				<th>Област</th>
				<th>Град</th>
				<th>Време за приключване</th>
				<th>Такса</th>
				<th>Обща сума</th>
				<th>Ip Address</th>
			</tr>
		</thead>
		<tbody>
			<?php
				foreach($output['transactions']->table as $value) {
					echo '<tr>';
					echo '<td>'.$value->p_date.'</td>';
					echo '<td>'.$value->p_docnumb.'</td>';
					echo '<td>'.$value->country.'</td>';						
					echo '<td>'.$value->region.'</td>';
					echo '<td>'.$value->city.'</td>';			
					echo '<td>'.$value->time_finish_document.'</td>';		
					echo '<td>'.$value->taxa.'</td>';					
					echo '<td>'.$value->total.'</td>';
					echo '<td>'.$value->ip.'</td>';							
					echo '</tr>';
				}
			?>
		</tbody> 
	</table>

<?php
	if($output['pagiantion'] > 1) {
		echo '<ul id="ys_pagination">';
		for($i = 1; $i < $output['pagiantion']+1; $i++) {
			if($i === $output['active_page']) {
				echo '<li class="active">'.$i.'</li>';
			} else {
				echo '<li>'.$i.'</li>';
			}
		}
		echo '</ul>';
	}
?>
</div>