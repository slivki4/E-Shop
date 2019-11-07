<div id="ys-package-product">
 
	<div class="row">
    <div class="col-sm-12 col-lg-12">
			<ul id="products-list">
			<?php 
				foreach($packages as $key => $value) {
					$checked = '';
					if($key === 0) $checked = 'checked';
					echo '<li>';
					echo '<label for="'.$value->id.'">';
						echo '<input '.$checked.' id="'.$value->id.'" type="radio" name="products-list" value="'.$value->id.'" data-price="'.number_format($value->price, 2, '.', '').'">';
					echo $value->packet_choise_name.'</label>';
					echo '</li>';	
				}
				?>
				</ul>
    </div>
  </div>

	<div class="row">
		<div class="col-sm-12 col-lg-12">
			<div id="modifier">
			<?php 
				if(!empty($additions)) : ?>
				<ul>
					<?php
						foreach($additions as $key => $modifier) {
							echo '<li data-product-id="'.$modifier->id.'" data-price="'.number_format($modifier->price, 2, '.', '').'">
								<label for="'.$modifier->id.'">
									<input id="'.$modifier->id.'" type="checkbox" name="'.$modifier->id.'"  value="'.number_format($modifier->price, 2, '.', '').'">
									<span>'.$modifier->name.'</span>
									<small>'.number_format($modifier->price, 2, '.', '').' лв.</small>
								</label>
							</li>';
						}
					?>
				</ul>
			<?php endif; ?>
			</div>
		</div>
	</div>

</div>