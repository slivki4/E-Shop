<style>

#ys-modal #main {
  width: 680px;
}

  #ys-modal #fiscal-product {
    display: flex;
    flex-direction: column;
    margin-bottom: 10px;
  }

  #ys-modal #fiscal-product #head {
    display: flex;
    flex-direction: row;
    min-height: 130px;
  }

  #ys-modal #fiscal-product #head img {
    width: auto;
    max-height: 120px;
    border: 1px solid #e0e0e0;
  }


  #ys-modal #fiscal-product #head > div {
    margin-left: 15px;
  }


  #ys-modal #fiscal-product #head span {
    display: block;
    font-size: 18px;
    line-height: 25px;
    color: #055180;  
  }

  #ys-modal #fiscal-product #head strong {
    display: inline-block;
    margin-top: 10px;
    padding: 2px 10px;
    background: #08c;
    font-size: 16px;
    color: #fff;
  }


  #ys-modal #fiscal-product #info-box {
    margin: 0;
    padding: 10px;
    background: #fff5d9;
    color: #362a48;
    border: 1px solid #ecbd81;
  }


  #ys-modal #modal-form  {
    display: flex;
    flex-direction : column;
  }

  #ys-modal #modal-form .ys-modal-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin: 5px 0;
  }

  #ys-modal label {
    margin: 0;
    color: #606060;
  }


  #ys-modal label strong{
    color: #e04c00;
  }

  #ys-modal .error {
    display: none;
    padding: 0 5px;
    background: #ffdfd9;
    font-size: 13px;
    color: #a90000;
    border: 1px solid #e0aca2;
  }

  #ys-modal .error.show {
    display: block;
  }

  #ys-modal input.text, #ys-modal textarea.text{
    display: block;
    width: 100%;
    color: #444;
  }

  #ys-modal textarea.text {
    resize: none;
    height: 55px;
  }

  #ys-modal #submit {
    margin: 20px 0;
  }

@media (max-width: 575px) {
  #ys-modal #fiscal-product #head {
    display: block;
    flex-direction: row;
    min-height: auto;
    margin-bottom: 20px;
  }

  #ys-modal #fiscal-product #head img {
    display: none;
  }
}

</style>
<div id="fiscal-product">
  <div id="head">
    <img src="<?php echo $data['image'][0]; ?>" width="<?php echo $data['image'][1]; ?>" height="<?php echo $data['image'][2]; ?>">
    <div>
      <span><?php echo $data['product']->name; ?></span>
      <strong><?php echo number_format($data['product']->price, 2, '.', '').' лв.'; ?></strong>
    </div>
  </div>
  <p id="info-box">Моля, попълнете необходимите данни за фискализация.</p>
 

<form id="modal-form" name="myform" action="#" method="post">
  <div id="company">
    <div class="ys-modal-row">
      <label><?php echo $data['labels']['company']; ?><strong> *</strong></label>
      <span class="error"></span>
    </div>
    <textarea class="text" name="company"><?php echo $basket['data']['company']; ?></textarea>
  </div>

  <div id="company_address">
    <div class="ys-modal-row">
      <label><?php echo $data['labels']['company_address']; ?><strong> *</strong></label>
      <span class="error"></span>
    </div>
    <input type="text" class="text" name="company_address" value="<?php echo $basket['data']['company_address']; ?>" />
  </div>

  <div id="object_name">
    <div class="ys-modal-row">
      <label><?php echo $data['labels']['object_name']; ?><strong> *</strong></label>
      <span class="error"></span>
    </div>
    <input type="text" class="text" name="object_name" value="<?php echo $basket['data']['object_name']; ?>" />
  </div>

  <div id="object_address">
    <div class="ys-modal-row">
      <label><?php echo $data['labels']['object_address']; ?><strong> *</strong></label>
      <span class="error"></span>
    </div>
    <input type="text" class="text" name="object_address" value="<?php echo $basket['data']['object_address']; ?>" />
  </div>

  <div id="business_type">
    <div class="ys-modal-row">
      <label><?php echo $data['labels']['business_type']; ?><strong> *</strong></label>
      <span class="error"></span>
    </div>
    <input type="text" class="text" name="business_type" value="<?php echo $basket['data']['business_type']; ?>" />
  </div>

  <div id="tax_service">
    <div class="ys-modal-row">
      <label><?php echo $data['labels']['tax_service']; ?><strong> *</strong></label>
      <span class="error"></span>
    </div>
    <input type="text" class="text" name="tax_service" value="<?php echo $basket['data']['tax_service']; ?>" />
  </div>


  <div id="tax_region">
    <div class="ys-modal-row">
      <label><?php echo $data['labels']['tax_region']; ?><strong> *</strong></label>
      <span class="error"></span>
    </div>
    <input type="text" class="text" name="tax_region" value="<?php echo $basket['data']['tax_region']; ?>" />
  </div>  

  <div id="bulstat">
    <div class="ys-modal-row">
      <label><?php echo $data['labels']['bulstat']; ?><strong> *</strong></label>
      <span class="error"></span>
    </div>
    <input type="text" class="text" name="bulstat" value="<?php echo $basket['data']['bulstat']; ?>" />
  </div>    

  <div id="dds">
    <div class="ys-modal-row">
      <label><?php echo $data['labels']['dds']; ?><strong> *</strong></label>
      <span class="error"></span>
    </div>
    <select name="dds">
    <option value=""></option>
      <option value="Регистриран по ЗДДС">Регистриран по ЗДДС</option>
      <option value="Не регистриран по ЗДДС">Не регистриран по ЗДДС</option>
    </select>
  </div>

  <div id="mol">
    <div class="ys-modal-row">
      <label><?php echo $data['labels']['mol']; ?><strong> *</strong></label>
      <span class="error"></span>
    </div>
    <input type="text" class="text" name="mol" value="<?php echo $basket['data']['mol']; ?>" />
  </div>

  <div id="phone">
    <div class="ys-modal-row">
      <label><?php echo $data['labels']['phone']; ?><strong> *</strong></label>
      <span class="error"></span>
    </div>
    <input type="text" class="text" name="phone" value="<?php echo $basket['data']['phone']; ?>" />
  </div>

  <div id="email">
    <div class="ys-modal-row">
      <label><?php echo $data['labels']['email']; ?><strong> *</strong></label>
      <span class="error"></span>
    </div>
    <input type="text" class="text" name="email" value="<?php echo $basket['data']['email']; ?>" />
  </div>

  <div id="submit">
    
    <input type="submit" name="submit" value="Добави в количката" />
  </div>
</form>
</div>