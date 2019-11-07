<?php

class YS_Customer extends WC_Customer { 

  public function __construct($data = 0, $is_session = false) {
    add_filter('woocommerce_checkout_update_customer_data', array($this, 'checkout_update_customer_data'), 10, 2);

    parent::__construct($data, $is_session);
  
    if($user = YS_User::getUserData()) {
      $this->data['email']                  = $user->email;
      $this->data['first_name']             = $user->full_name;
      $this->data['display_name']           = $user->full_name;
      $this->data['billing']['email']       = $user->email;
      $this->data['billing']['first_name']  = $user->full_name;
      $this->data['billing']['company']     = $user->company_name;
      $this->data['billing']['phone']       = $user->phone;
    }

  }

  public static function get_billing_ys_address(){
    return YS_User::getUserData()->delivery_address[0]->address;
  }

  public function save() {
		return $this->get_id();
	}


  public function checkout_update_customer_data($true, $instance ){
    return false;
  }

	public function get_shipping_country( $context = 'view' ) {
		return 'BG';
	}

}

