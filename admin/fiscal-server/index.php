<div id="ys-wrap">
<div id="ys-overlay">
	<div class="ys-spinner">
    <div class="loader"></div>
  </div>		
</div>	
<?php require(YS_PLUGIN_DIR.'/admin/navigation.php'); ?>
  <form action="" method="post">
    <div class="section">
    <h2>Връзка с фискален сървър</h2>
    <ul>
      <li>
        <label>Фискално устройство:</label>
         <span>
           <select name="ys_fiscal_server_device" style="width:280px;">
             <?php 
             foreach($output['devices'] as $key => $val) {
               foreach($val->model as $key2 => $val2) {
                 if($output['ys_fiscal_server_device'] == $val2->code) {
                   echo '<option value="'.$val2->code.'" selected>'.$val2->name.'</option>';
                 }
                 else {
                  echo '<option value="'.$val2->code.'">'.$val2->name.'</option>';
                 }
               } 
             } 
             ?>
           </select>
          </span>
        </li>
        <li>
          <label>IP адрес на фискалния сървър:</label>
          <span><input id="ys_ip_address" class="ys-text"  type="text" name="ys_fiscal_server_ip" value="<?php echo $output['ys_fiscal_server_ip']; ?>" /></span>
        </li>
        <li>
          <label>Порт на фискалния сървър:</label>
          <span><input id="ys_port_number" class="ys-text"  type="text" name="ys_fiscal_server_port" value="<?php echo $output['ys_fiscal_server_port']; ?>" style="width:80px" /></span>
        </li>
        <li>
          <p>
          <?php wp_nonce_field('ys-option', 'ys-option-nonce'); ?>
          <input class="button button-primary button-large" type="submit" value="Запази" />
        </p>
        </li>
      </ul>
    </div>
  </form>
</div> 