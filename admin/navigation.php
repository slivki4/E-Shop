<h3>Янак Софт</h3>
<nav class="nav-tab-wrapper">
<?php
  foreach(self::$pages as $key => $val) {
    $active_tab  = '';
    if($_GET['page'] === $key) $active_tab = 'nav-tab-active';
    printf( '<a id="'.$key.'" href="%s" class="nav-tab '.$active_tab.'">%s</a>', admin_url( 'admin.php?page='.$key ), __($val));
  }
?>
</nav>
