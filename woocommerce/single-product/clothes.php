
<div id="ys-clothes-wrapper">
  <div id="ys-sizes-list" class="row ys-clothes">
    <input type="hidden" name="ys_clothes_product">
    <div class="col-lg-12">
      <strong>Размер</strong>
      <ul class="sizes">
        <?php 
        foreach($sizes as $key => $size) : ?>
          <li id="<?php echo $key; ?>">
            <div class="checkbox">
              <label>
                <input type="radio" name="size" class="checkbox-option" value="<?php echo $key; ?>" />
                <?php echo $size->razmer; ?>
              </label>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>


  <div id="ys-color-list" class="row ys-clothes">
    <div class="col-lg-12">
    <strong>Цвят</strong>
        <?php foreach($sizes as $key => $size) : ?>
          <ul id="<?php echo $key; ?>" class="colors">   
          <?php foreach($size->items as $key2 => $item): ?>
              <?php if($item->color == '') continue; ?>
              <li>
                <div class="checkbox">
                  <label>
                    <input type="radio" name="color" class="checkbox-option" value="<?php echo $key2; ?>" />
                    <?php echo $item->color; ?>
                  </label>
                </div>                   
              </li>
            <?php endforeach; ?>
          </ul>  
        <?php endforeach; ?>
      </div>		
    </div>
</div>

    
