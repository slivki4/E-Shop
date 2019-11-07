<?php
/**
 * Class WC_Email_Customer_Reset_Password file.
 *
 * @package WooCommerce\Emails
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}


include_once (WC_ABSPATH.'includes'.'/emails/class-wc-email-customer-reset-password.php');
if ( ! class_exists( 'YS_Email_Customer_Reset_Password' ) ) :

	class YS_Email_Customer_Reset_Password extends WC_Email_Customer_Reset_Password {
		
		public function trigger( $user_login = '', $reset_key = '' ) {
			$this->setup_locale();

			if ( $user_login && $reset_key ) {
				$this->object     = $user_login;
				$this->user_id    = $this->object->ID;
				$this->user_login = $this->object->data->user_email;
				$this->reset_key  = $reset_key;
				$this->user_email = stripslashes( $this->object->user_email );
				$this->recipient  = $this->user_email;
			}

			if ( $this->is_enabled() && $this->get_recipient() ) {
				$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
			}

			$this->restore_locale();
		}	

	}

endif;

return new YS_Email_Customer_Reset_Password();