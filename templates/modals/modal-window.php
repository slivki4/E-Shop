<div id="ys-modal">
  <div id="main">
    <div id="title">
      <span><?php echo $data['title'] ?></span>
      <span id="close">×</span>
    </div>
    <div id="content">
      <?php require_once($data['layout']); ?>
    </div>
    <div class="ys-spinner">
      <div class="loader"></div>
    </div>
  </div>
</div>