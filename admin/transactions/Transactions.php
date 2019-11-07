<?php 
  class Transactions extends YS_Admin{

    private static $rows = 20;
    private static $active_page = 1;
    private static $sum_rows = 0;

    public static function index(){
      if(!current_user_can('manage_options')) {
        wp_die('Unauthorized user');
      }

  
      $output = [
        'current_month' => (int)date('n'),
        'current_year' => (int)date('Y'),
        'active_page' => self::$active_page,
        'months' => ['1' => 'Януари', '2' => 'Февруари', '3' => 'Март', '4' => 'Април', '5' => 'Май', '6' => 'Юни', 
          '7' => 'Юли', '8' => 'Август', '9' => 'Септември', '10' => 'Октомври', '11' => 'Ноември', '12' => 'Декември',          
        ]
      ];
  
      for($i = $output['current_year']; $i > $output['current_year'] -10; $i--){
        $output['years'][$i] = $i;
      }
      $output = array_merge($output, self::get($output));
      require_once(__DIR__.'/index.php');
    }


    public static function get($output) {
      $output['transactions'] = YanakAPI::instance()->apiRequest('transactions', 'GET', [
        'rows' => self::$rows,
        'page' => self::$active_page,
        'sum_rows' => self::$sum_rows,
        'month' => $output['current_month'],
        'year' => $output['current_year'],
      ]);

      $output['pagiantion'] = ceil($output['transactions']->sum_rows / self::$rows);
      return $output;
    }


    public static function ajax(WP_REST_Request $request) {
      $input = $request->get_params();
      self::$active_page = $input['active_page'];
      self::$sum_rows = $input['sum_rows'];

      $output = self::get($input);

      ob_start();
      include_once(__DIR__.'/table.php');
      $output['html'] = ob_get_contents();
      ob_end_clean();
      wp_send_json_success($output);
    }

  }

?>