<?php
/**
 * Class WC_Email_Customer_Processing_Order file.
 *
 * @package WooCommerce\Emails
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

include_once (WC_ABSPATH.'includes'.'/emails/class-wc-email-customer-on-hold-order.php');
if ( ! class_exists( 'YS_Email_Customer_On_Hold_Order', false ) ) :

	class YS_Email_Customer_On_Hold_Order extends WC_Email_Customer_On_Hold_Order {

		public function trigger( $order_id, $order = false ) {
			$this->setup_locale();

			if ( $order_id && ! is_a( $order, 'WC_Order' ) ) {
				$order = wc_get_order( $order_id );
			}

			if ( is_a( $order, 'WC_Order' ) ) {
				$this->object                         = $order;
				$this->recipient                      = $this->object->get_billing_email();
				$this->placeholders['{order_date}']   = wc_format_datetime( $this->object->get_date_created() );
				$this->placeholders['{order_number}'] = $this->object->get_order_number();
			}
			$this->enabled = 'yes';
			if ( $this->is_enabled() && $this->get_recipient() ) {
				 $message = apply_filters( 'woocommerce_mail_content', $this->style_inline( $this->get_content()));
				 $order->collectEmails($this->get_recipient(), $this->get_subject(), $message);
				 $order->sendMail();
			}

			$this->restore_locale();
		}
	}
	
endif;

return new YS_Email_Customer_On_Hold_Order();