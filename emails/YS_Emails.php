<?php

defined( 'ABSPATH' ) || exit;

/**
 * Emails class.
 */
class YS_Emails extends WC_Emails {

  public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
  }
  


	public function init() {
		$default_dir = WC_ABSPATH.'includes/emails/';
		// Include email classes.
		include_once ($default_dir.'class-wc-email.php');

		$this->emails['YS_Email_New_Order']                 = include 'YS_Email_New_Order.php';
		$this->emails['WC_Email_Cancelled_Order']           = include $default_dir.'class-wc-email-cancelled-order.php';
		$this->emails['WC_Email_Failed_Order']              = include $default_dir.'class-wc-email-failed-order.php';
		$this->emails['YS_Email_Customer_On_Hold_Order']    = include 'YS_Email_Customer_On_Hold_Order.php';
		$this->emails['YS_Email_Customer_Processing_Order'] = include 'YS_Email_Customer_Processing_Order.php';
		$this->emails['WC_Email_Customer_Completed_Order']  = include $default_dir.'class-wc-email-customer-completed-order.php';
		$this->emails['WC_Email_Customer_Refunded_Order']   = include $default_dir.'class-wc-email-customer-refunded-order.php';
		$this->emails['WC_Email_Customer_Invoice']          = include $default_dir.'class-wc-email-customer-invoice.php';
		$this->emails['WC_Email_Customer_Note']             = include $default_dir.'class-wc-email-customer-note.php';
		$this->emails['YS_Email_Customer_Reset_Password']   = include 'YS_Email_Customer_Reset_Password.php';
		$this->emails['WC_Email_Customer_New_Account']      = include $default_dir.'class-wc-email-customer-new-account.php';

		$this->emails = apply_filters( 'woocommerce_email_classes', $this->emails );

		// include css inliner.
		if ( ! class_exists( 'Emogrifier' ) && class_exists( 'DOMDocument' ) ) {
			include_once WC_ABSPATH.'includes/libraries/class-emogrifier.php';
		}
	}



}
