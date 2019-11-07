<?php 

class YS_Admin {

  protected static $pages = [
    'ys' => 'Настойки',
    'ys-transactions' => 'Транзакции',
    'ys-fiscal-server' => 'Фискален сървър',
  ];

  public function __construct() {
    add_action('admin_menu', array($this, 'admin_menu'));
    add_action('admin_notices', 'my_notice');
  }


  public function getLabels($singular_name, $name, $title = FALSE) {
    if( !$title )
        $title = $name;

    return array(
        "name" => $title,
        "singular_name" => $singular_name,
        "add_new" => __("Add New", 'ys-content-types'),
        "add_new_item" => sprintf( __("Add New %s", 'ys-content-types'), $singular_name),
        "edit_item" => sprintf( __("Edit %s", 'ys-content-types'), $singular_name),
        "new_item" => sprintf( __("New %s", 'ys-content-types'), $singular_name),
        "view_item" => sprintf( __("View %s", 'ys-content-types'), $singular_name),
        "search_items" => sprintf( __("Search %s", 'ys-content-types'), $name),
        "not_found" => sprintf( __("No %s found", 'ys-content-types'), $name),
        "not_found_in_trash" => sprintf( __("No %s found in Trash", 'ys-content-types'), $name),
        "parent_item_colon" => ""
    );
  }

  public function admin_menu(){
    add_menu_page('Янак Софт', 'Янак Софт', 'edit_posts', 'ys', array($this, 'options'), 'dashicons-ys-logo', 100);
    add_submenu_page('ys', 'Настройки', 'Настройки', 'edit_posts', 'ys', array($this, 'options'));
    add_submenu_page('ys', 'Транзакции', 'Транзакции', 'edit_posts', 'ys-transactions', array('Transactions', 'index'));
    add_submenu_page('ys', 'Фискален Сървър', 'Фискален Сървър', 'edit_posts', 'ys-fiscal-server', array('FiscalServer', 'index'));
  }


  public function options() {
    if(!current_user_can('manage_options')) {
      wp_die('Unauthorized user');
    }
  
    if(!empty($_POST) && check_admin_referer('ys-option', 'ys-option-nonce')) {
      $this->memcached();
      $this->dbConnection();
    }

    $output = [
      'ys_email' => get_option('ys_email', ''),
      'ys_username' => get_option('ys_username', ''),
    ];
    
    require_once(__DIR__.'/options.php');
  }


  private function memcached(){
    if(isset($_POST['memcached'])) {
      YS_Memcached::instance()->deleteCacheForControllers(YS_Memcached::instance()->getCachableControllers());
    }
  }

  private function dbConnection(){
    $validator = new Validator();

    $inputs = [
      'ys_email' =>  sanitize($_POST['ys_email'], 'trim|stripslashes|htmlspecialchars'),
      'ys_username' => sanitize($_POST['ys_username'], 'trim|stripslashes|htmlspecialchars'),
    ];
    
    $rules = [
      'ys_email' => ['required', 'email'],
      'ys_username' =>  ['required', 'minlength' => 3, 'maxlength' => 50],
    ]; 
    
    $messages = [
      'ys_email' => [
        'required' => 'Не сте попълнили Email', 
        'email' => 'Невалиден Email'
      ],

      'ys_username' => [
        'required' => 	'Не сте попълнили Потребителско име',
        'minlength' => 'Паролата трябва да е минимум 3 символа', 
        'maxlength' => 	'Прекалено дълго Име'
      ] 
    ];
     
    if(!$validator->validate($inputs, $rules, $messages)) {
      foreach($validator->getErrors() as $error) {
        $this->my_notice('error',  $error);
      }
    } else {
      YS_Memcached::instance()->deleteToken();
      YS_Memcached::instance()->deleteCacheForControllers(YS_Memcached::instance()->getCachableControllers());
      update_option('ys_email', $inputs['ys_email']);
      update_option('ys_username', $inputs['ys_username']);
      $this->my_notice('success', 'Редакцията завърши успешно!');
    }
  }

  public function my_notice($action, $notice, $class = '') {
    echo '<div class="'.'notice-'.$action.' '.$class.' notice">';
    echo '<p>'.$notice.'</p>';;
    echo '</div>';   
  }

}

new YS_Admin();