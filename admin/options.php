<div id="ys-wrap">
  <?php include('navigation.php'); ?>
  <form  action="" method="post">
    <div class="section">
      <h2>Връзка с база данни</h2>
      <ul>
        <li>
          <label>Email:</label>
          <span><input id="ys_email" class="ys-text"  type="email" name="ys_email" value="<?php echo $output['ys_email']; ?>" /></span>
        </li>
        <li>
          <label>Потребителско име:</label>
          <span><input id="ys_username" class="ys-text" type="text" name="ys_username" value="<?php echo $output['ys_username']; ?>" /></span>
        </li>
      </ul>
    </div>


    <div class="section">
      <h2>Кеширане на данни</h2>
      <ul>
        <li><strong>Кеширането на данните подобрява скороста на зареждане на сайта</strong></li>
        <li>
          <input id="memcached" type="checkbox" name="memcached" />
          <label for="memcached">Изчистване на кеша на сървъра</label>
        </li>
      </ul>
    </div>
    

    <p>
    <?php wp_nonce_field('ys-option', 'ys-option-nonce'); ?>
    <input class="button button-primary button-large" type="submit" value="Запази" />
    </p>
  </form>
</div>



