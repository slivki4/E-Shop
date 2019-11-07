<?php

class YS_Rest_Server extends WP_REST_Controller {
  //The namespace and version for the REST SERVER
  public $my_namespace = 'my_rest_server/v';
  public $my_version   = '1';
  
  
  public function __construct(){
    add_action( 'rest_api_init', array($this, 'register_routes'));
  }

  public function register_routes() {
    $namespace = $this->my_namespace . $this->my_version;
    register_rest_route( $namespace, '/image', array(
			'methods'	=>  'POST',
			'callback'	=> array( Image, 'getAll'),
    ));
    register_rest_route( $namespace, '/image/set', array(
			'methods'	=>  'POST',
			'callback'	=> array(Image, 'create'),
    ));
		register_rest_route( $namespace, '/image/rename', array(
			'methods'	=>  'POST',
			'callback'	=> array( Image, 'rename'),
    ));
		register_rest_route( $namespace, '/image/delete', array(
			'methods'	=>  'POST',
			'callback'	=> array( Image, 'remove'),
    ));
		register_rest_route( $namespace, '/modal/fiscal', array(
			'methods'	=>  'POST',
			'callback'	=> array( YS_Fiscal, 'modalFormShow'),
    ));
		register_rest_route( $namespace, '/modal/fiscal/submit', array(
			'methods'	=>  'POST',
			'callback' => array( YS_Fiscal, 'modalFormSubmit'),
    ));
    register_rest_route( $namespace, '/admin/transactions', array(
			'methods'	=>  'POST',
			'callback' => array( Transactions, 'ajax'),
    ));    
    register_rest_route( $namespace, '/modal/package', array(
			'methods'	=>  'POST',
			'callback' => array( YS_Package, 'show'),
    ));  
  }
	
}

$ys_rest_server = new YS_Rest_Server();
