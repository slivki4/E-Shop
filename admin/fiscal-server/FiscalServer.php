<?php 
  class FiscalServer extends YS_Admin{

    public function apiRequest($server, $controller, $inputs, $method = 'GET') {
     $url = $server.$controller;

      $context = stream_context_create([
        'http' => [
          'header'  => "Content-type: application/json\r\n",
          'method'  => $method,
          'content' => json_encode($inputs),
          'timeout' => 30000
        ]
      ]);
  
      $result = file_get_contents($url, false, $context);	
      return json_decode($result);
    }


    public function index(){
      if(!current_user_can('manage_options')) {
        wp_die('Unauthorized user');
      }
   

      if(!empty($_POST) && check_admin_referer('ys-option', 'ys-option-nonce')) {
       self::submit();
      }
      
      $output = [
        'devices' => self::apiRequest('http://94.156.153.21:50980/', '/api/fiscal-devices', ['fiscalDatabase' => true]),
        'ys_fiscal_server_device' => get_option('ys_fiscal_server_device', ''),
        'ys_fiscal_server_ip' => get_option('ys_fiscal_server_ip', ''),
        'ys_fiscal_server_port' => get_option('ys_fiscal_server_port', ''),
      ];

      require_once(__DIR__.'/index.php');
    }


    private static function submit (){
      $validator = new Validator();
      $inputs = [
        'ys_fiscal_server_device' => sanitize($_POST['ys_fiscal_server_device'], 'trim|stripslashes|htmlspecialchars'),
        'ys_fiscal_server_ip' => sanitize($_POST['ys_fiscal_server_ip'], 'trim|stripslashes|htmlspecialchars'),
        'ys_fiscal_server_port' => sanitize($_POST['ys_fiscal_server_port'], 'trim|stripslashes|htmlspecialchars'),
      ];

      $rules = [
        'ys_fiscal_server_device' => ['required'],
        'ys_fiscal_server_ip' => ['required'],
        'ys_fiscal_server_port' => ['required'],
      ]; 
      
      $messages = [
        'ys_fiscal_server_device' => [
          'required' => 'Не сте Избрали фискално устройство!', 
        ],
        'ys_fiscal_server_ip' => [
          'required' => 'Не сте попълнили IP адрес!', 
        ],
        'ys_fiscal_server_port' => [
          'required' => 'Не сте попълнили Порт!', 
        ]
      ];
      
      if(!$validator->validate($inputs, $rules, $messages)) {
        foreach($validator->getErrors() as $error) {
          self::my_notice('error',  $error);
        }
      } else {
        update_option('ys_fiscal_server_device', $inputs['ys_fiscal_server_device']);
        update_option('ys_fiscal_server_ip', $inputs['ys_fiscal_server_ip']);
        update_option('ys_fiscal_server_port', $inputs['ys_fiscal_server_port']);
        self::my_notice('success', 'Записа завърши успешно!');
      }

    }

    public function my_notice($action, $notice, $class = '') {
      echo '<div class="'.'notice-'.$action.' '.$class.' notice">';
      echo '<p>'.$notice.'</p>';;
      echo '</div>';   
    }


  }

?>