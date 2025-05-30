<?php
/**
 * nochex_apiwidget.php payment module class for Nochex APC payment method
 *
 * @package paymentMethod
 * @copyright Copyright 2003-2006 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

define('MODULE_PAYMENT_NOCHEXAPI_RM', '2');
 include_once((IS_ADMIN_FLAG === true ? DIR_FS_CATALOG_MODULES : DIR_WS_MODULES) . 'payment/nochex_apiwidget/nochex_functions.php');

/**
 * Nochex APC payment method class
 *
 */
class nochex_apiwidget extends base {
  /**
   * string repesenting the payment method
   *
   * @var string
   */
  var $code;
  /**
   * $title is the displayed name for this payment method
   *
   * @var string
    */
  var $title;
  /**
   * $description is a soft name for this payment method
   *
   * @var string
    */
  var $description;
  /**
   * $enabled determines whether this module shows or not... in catalog.
   *
   * @var boolean
    */
  var $enabled;
   
  /**
    * constructor
    *
    * @param int $nochex_apiwidget_id
    * @return nochex
    */
  function __construct($nochex_apiwidget_id = '') {
    global $order, $messageStack;
    $this->code = 'nochex_apiwidget';
    if (IS_ADMIN_FLAG === true) {
      $this->title = defined('MODULE_PAYMENT_NOCHEXAPI_TEXT_ADMIN_TITLE') ? MODULE_PAYMENT_NOCHEXAPI_TEXT_ADMIN_TITLE : 'Secure Payment by Credit / Debit Card (Nochex)'; // Payment Module title in Admin
    } else {
      $this->title = defined('MODULE_PAYMENT_NOCHEXAPI_TEXT_CATALOG_TITLE') ? MODULE_PAYMENT_NOCHEXAPI_TEXT_CATALOG_TITLE : 'Secure Payment by Credit / Debit Card (Nochex)'; // Payment Module title in Catalog
    }
	
    $this->description = defined('MODULE_PAYMENT_NOCHEXAPI_TEXT_DESCRIPTION') ? MODULE_PAYMENT_NOCHEXAPI_TEXT_DESCRIPTION : null;
    $this->sort_order = defined('MODULE_PAYMENT_NOCHEXAPI_SORT_ORDER') ? MODULE_PAYMENT_NOCHEXAPI_SORT_ORDER : null;//deprecated	
	$this->enabled = (defined('MODULE_PAYMENT_NOCHEXAPI_STATUS') && MODULE_PAYMENT_NOCHEXAPI_STATUS == 'True');
	
	 if (defined('MODULE_PAYMENT_NOCHEXAPI_ORDER_STATUS_ID') && (int)MODULE_PAYMENT_NOCHEXAPI_ORDER_STATUS_ID > 0) {
      $this->order_status = MODULE_PAYMENT_NOCHEXAPI_ORDER_STATUS_ID;//deprecated
    } else {
      $this->order_status = defined('MODULE_PAYMENT_NOCHEXAPI_PENDING_STATUS_ID') ? MODULE_PAYMENT_NOCHEXAPI_PENDING_STATUS_ID : 0;//deprecated
	}
	
    if (is_object($order)) $this->update_status();
    $this->form_action_url = 'https://secure.nochex.com/default.aspx';//deprecated
  }
  /**
   * calculate zone matches and flag settings to determine whether this module should display to customers or not
    *
    */
  function update_status() {
    global $order, $db;

    if ($this->enabled && (int)MODULE_PAYMENT_NOCHEXAPI_ZONE > 0 && isset($order->billing['country']['id'])) {
      $check_flag = false;
      $check_query = $db->Execute("SELECT zone_id FROM " . TABLE_ZONES_TO_GEO_ZONES . " WHERE geo_zone_id = '" . MODULE_PAYMENT_NOCHEXAPI_ZONE . "' AND zone_country_id = '" . (int)$order->billing['country']['id'] . "' ORDER BY zone_id");
      while (!$check_query->EOF) {
        if ($check_query->fields['zone_id'] < 1) {
          $check_flag = true;
          break;
        } elseif ($check_query->fields['zone_id'] == $order->billing['zone_id']) {
          $check_flag = true;
          break;
        }
        $check_query->MoveNext();
      }

      if ($check_flag == false) {
        $this->enabled = false;
      }
    }
	
  }
  
  /**
   * JS validation which does error-checking of data-entry if this module is selected for use
   * (Number, Owner, and CVV Lengths)
   *
   * @return string
    */
  function javascript_validation() {
    return false;
  }
  /**
   * Displays Credit Card Information Submission Fields on the Checkout Payment Page
   * In the case of Nochex, this only displays the Nochex title
   *
   * @return array
    */
  function selection() {
    return array('id' => $this->code,
                 'module' => defined('MODULE_PAYMENT_NOCHEXAPI_TEXT_CATALOG_LOGO') ? MODULE_PAYMENT_NOCHEXAPI_TEXT_CATALOG_LOGO : null,
				 'icon' => defined('MODULE_PAYMENT_NOCHEXAPI_TEXT_CATALOG_LOGO') ? MODULE_PAYMENT_NOCHEXAPI_TEXT_CATALOG_LOGO : null);
  }
  /**
   * Normally evaluates the Credit Card Type for acceptance and the validity of the Credit Card Number & Expiration Date
   * Since Nochex module is not collecting info, it simply skips this step.
   *
   * @return boolean
   */
  function pre_confirmation_check() {
    return false;
  }
  /**
   * Display Credit Card Information on the Checkout Confirmation Page
   * Since none is collected for Nochex before forwarding to payments page, this is skipped
   *
   * @return boolean
    */
  function confirmation() {
    return false;
  }
  /**
   * Build the data and actions to process when the "Submit" button is pressed on the order-confirmation screen.
   * This sends the data to the payment gateway for processing.
   * (These are hidden fields on the checkout confirmation page)
   *
   * @return string
    */
  function process_button() {
    global $db, $order, $currencies, $currency;

    $this->totalsum = $order->info['total'];
	  
    $last_order_id = $db->Execute("select * from " . TABLE_ORDERS . " order by orders_id desc limit 1");
	if(!empty($last_order_id->fields['orders_id'])){
		$new_order_id = $last_order_id->fields['orders_id'];
	}else{
		$new_order_id = 0;
	}
    $new_order_id = ($new_order_id + 1);
	  
    $sql = "insert into " . TABLE_NOCHEXAPI_SESSION . " (session_id, saved_session, expiry) values (
            '" . $new_order_id . "',
            '" . base64_encode(serialize($_SESSION)) . "',
            '" . (time() + (1*60*60*24*2)) . "')";

    $db->Execute($sql);

    $my_currency = "GBP";
// Create a string that contains a listing of products ordered for the description field
    $description = '';
	
	for ($i=0; $i<sizeof($order->products); $i++) {
	   $description = $order->products[$i]['name'] . ' (qty: ' . $order->products[$i]['qty'] . ')';
	}
	
    $telephone = preg_replace('/\D/', '', $order->customer['telephone']);
    $payment_fields = array();

  			$billing_address = array();
  			if(strlen($order->customer['street_address'])>0) $billing_address[] = $order->customer['street_address'];
  			if(strlen($order->customer['suburb'])>0) $billing_address[] = $order->customer['suburb'];
			
			$delivery_address = array();
  			if(strlen($order->delivery['street_address'])>0) $delivery_address[] = $order->delivery['street_address'];
  			if(strlen($order->delivery['suburb'])>0) $delivery_address[] = $order->delivery['suburb'];
			
			
			if(defined('MODULE_PAYMENT_NOCHEXAPI_TESTING') == "Test" || MODULE_PAYMENT_NOCHEXAPI_TESTING == "Test"){
					$test_tran = "true";
				}else{
					$test_tran = "false";
				}
	
			if(defined('MODULE_PAYMENT_NOCHEXAPI_MERCHANT_ID') == 0 || MODULE_PAYMENT_NOCHEXAPI_MERCHANT_ID == 0){	
				$ssql = "select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_NOCHEXAPI_MERCHANT_ID'" ;
				$merchannt = $db->Execute($ssql);
				$merchant_id = $merchannt->fields["configuration_value"];
			}else{	
				$merchant_id = defined('MODULE_PAYMENT_NOCHEXAPI_MERCHANT_ID') ? MODULE_PAYMENT_NOCHEXAPI_MERCHANT_ID : null;
			}
				
			if(defined('MODULE_PAYMENT_NOCHEXAPI_API_WIDGETKEY') == 0 || MODULE_PAYMENT_NOCHEXAPI_API_WIDGETKEY == 0){	
				$ssql = "select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_NOCHEXAPI_API_WIDGETKEY'" ;
				$api_key = $db->Execute($ssql);
				$api_key = $api_key->fields["configuration_value"];
			}else{	
				$api_key = defined('MODULE_PAYMENT_NOCHEXAPI_API_WIDGETKEY') ? MODULE_PAYMENT_NOCHEXAPI_API_WIDGETKEY : null;
			}

 
			?>
			</form>
			
			<style>
				p, address{
					padding:0px!important
				}
				input{
				margin:0px;
				}
				#ncx-show-checkout{
					background: #364fb5;
					color:#fff;
					float:right;
					margin-top:8px;
				}
				.confirm-order{
					display:none
				}
			</style>
				<script src="https://secure.nochex.com/exp/jquery.js"></script>
				<script src="https://secure.nochex.com/exp/nochex_lib.js"></script>
				
		
				<form id="nochexForm" class="ncx-form" name="nochexForm">
					<script id="ncx-config"		
						ncxField-api_key="<?php echo $api_key; ?>"
						ncxField-merchant_id="<?php echo $merchant_id; ?>"
						ncxField-test_transaction="<?php echo $test_tran; ?>"
						ncxField-description="<?php echo $description; ?>"
						ncxField-amount="<?php echo number_format(($order->info['total']) * $currencies->get_value($my_currency), $currencies->get_decimal_places($my_currency)); ?>"
						ncxField-fullname="<?php echo $order->customer['firstname']." ".$order->customer['lastname']; ?>"
						ncxField-email="<?php echo $order->customer['email_address']; ?>"
						ncxField-phone="<?php echo $telephone; ?>"
						ncxField-address="<?php echo implode("\r\n", $billing_address); ?>"
						ncxField-city="<?php echo $order->customer['city']; ?>" 
						ncxField-postcode="<?php echo $order->customer['postcode']; ?>" 
						ncxField-country="<?php echo $order->customer['country']['title']; ?>" 
						ncxField-order_id="<?php echo $new_order_id; ?>"  
						ncxField-optional_1="<?php echo $new_order_id; ?>"  
						ncxField-optional_2="cb" 
						ncxField-success_url ="<?php echo zen_href_link(FILENAME_CHECKOUT_PROCESS, 'referer=nochex_apiwidget', 'NONSSL'); ?>" 
						ncxfield-callback_url="<?php echo zen_href_link('nochex_apiwidget_handler.php', '', 'NONSSL',false,false,true); ?>"
						<?php
							if(isset($order->delivery['street_address'])){
							?>
								ncxField-request_delivery_dtls="True"
								ncxField-delivery_fullname="<?php echo $order->delivery['firstname']." ".$order->delivery['lastname']; ?>"
								ncxField-delivery_address="<?php echo implode("\r\n", $delivery_address); ?>"
								ncxField-delivery_country="<?php echo $order->delivery['country']['title'];; ?>"
								ncxField-delivery_city="<?php echo $order->delivery['city']; ?>"
								ncxField-delivery_postcode="<?php echo $order->delivery['postcode']; ?>"
							<?php
							}
						?>
						>
					</script>
				</form>
				
				<button id="ncx-show-checkout" title="Checkout" class="cssButton submit_button button button_confirm_order"> Pay Now </button>
				

			<?php
			
 }
  /**
   * Store transaction info to the order and process any results that come back from the payment gateway
    *
    */
  function before_process() {
    global $order_total_modules, $order;
 	  
	if ($_GET['referer'] == 'nochex_apiwidget') {
      $this->notify('NOTIFY_PAYMENT_NOCHEX_RETURN_TO_STORE');
      $_SESSION['cart']->reset(true);
      unset($_SESSION['sendto']);
      unset($_SESSION['billto']);
      unset($_SESSION['shipping']);
      unset($_SESSION['payment']);
      unset($_SESSION['comments']);
      unset($_SESSION['cot_gv']);   
      unset($_SESSION);
		
      $order_total_modules->clear_posts();//ICW ADDED FOR CREDIT CLASS SYSTEM
      zen_redirect(zen_href_link(FILENAME_CHECKOUT_SUCCESS, '', 'SSL'));
	   
    } else {
      $this->notify('NOTIFY_PAYMENT_NOCHEX_CANCELLED_DURING_CHECKOUT');
      zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL'));
    }
		
  }
  
  /**
    * Checks referrer 
    * @param string $zf_domain
    * @return boolean
    */
  function check_referrer($zf_domain) {
    return true;
  }
  /**
    * Build admin-page components 
    * @param int $zf_order_id
    * @return string
    */
  function admin_notification($zf_order_id) {
    global $db;
    $output = '';
    $sql = "select * from " . TABLE_NOCHEXAPI . " where order_id = '" . (int)$zf_order_id . "' order by nochex_apiwidget_id DESC LIMIT 1";
    $apc = $db->Execute($sql);
    if ($apc->RecordCount() > 0) require(DIR_FS_CATALOG. DIR_WS_MODULES . 'payment/nochex_apiwidget/nochex_apiwidget_admin_notification.php');
    return $output;
  }
  /**
   * Post-processing activities 
   * @return boolean
    */
  function after_process() {	  
    $_SESSION['order_created'] = '';
    return false;
  }
  /**
   * Used to display error message details 
   * @return boolean
    */
  function output_error() {
    return false;
  }
  /**
   * Check to see whether module is installed
   *
   * @return boolean
    */
  function check() {
    global $db;
    if (!isset($this->_check)) {
      $check_query = $db->Execute("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_NOCHEXAPI_STATUS'");
      $this->_check = $check_query->RecordCount();
    }
    return $this->_check;
  }
  /**
   * Install the payment module and its configuration settings
    *
    */
  function install() {
    global $db;
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Nochex Module', 'MODULE_PAYMENT_NOCHEXAPI_STATUS', 'True', 'Do you want to accept Nochex payments?', '1', '0', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");  
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Nochex Merchant ID', 'MODULE_PAYMENT_NOCHEXAPI_MERCHANT_ID', '', 'For Nochex Merchant account holders, allows you to accept payments using a different merchant ID.', '2', '0', now())");	
	$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Nochex API Widget Key', 'MODULE_PAYMENT_NOCHEXAPI_API_WIDGETKEY', '', 'Please enter your Nochex API Widget Key.', '2', '0', now())");	
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Payment Zone', 'MODULE_PAYMENT_NOCHEXAPI_ZONE', '0', 'If a zone is selected, only enable this payment method for that zone.', '2', '3', 'zen_get_zone_class_title', 'zen_cfg_pull_down_zone_classes(', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Pending Notification Status', 'MODULE_PAYMENT_NOCHEXAPI_PROCESSING_STATUS_ID', '" . DEFAULT_ORDERS_STATUS_ID .  "', 'Set the status of orders made with this payment module that are not yet completed to this value<br />(\'Pending\' recommended)', '2', '0', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Order Status', 'MODULE_PAYMENT_NOCHEXAPI_ORDER_STATUS_ID', '2', 'Set the status of orders made with this payment module that have completed payment to this value<br />(\'Processing\' recommended)', '2', '1', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort order of display.', 'MODULE_PAYMENT_NOCHEXAPI_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '2', '2', now())");   
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Debug Mode', 'MODULE_PAYMENT_NOCHEXAPI_APC_DEBUG', 'Off', 'Enable debug logging? <br />NOTE: This can REALLY clutter your email inbox!<br />Logging goes to the /includes/modules/payment/nochex_apiwidget/logs folder<br />Email goes to the store-owner address.<strong>Leave OFF for normal operation.</strong>', '4', '0', 'zen_cfg_select_option(array(\'Off\',\'Log File\',\'Log and Email\'), ', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Status Live/Testing', 'MODULE_PAYMENT_NOCHEXAPI_TESTING', 'Live', 'Set Nochex module to Live or Test. In Test mode no money is transferred.', '4', '1', 'zen_cfg_select_option(array(\'Live\', \'Test\'), ', now())");
    $this->notify('NOTIFY_PAYMENT_NOCHEX_INSTALLED');
  }
  /**
   * Remove the module and all its settings
    *
    */
  function remove() {
    global $db;
    $db->Execute("delete from " . TABLE_CONFIGURATION . " where configuration_key LIKE 'MODULE_PAYMENT_NOCHEXAPI%'");
    $this->notify('NOTIFY_PAYMENT_NOCHEX_UNINSTALLED');
  }
  /**
   * Internal list of configuration keys used for configuration of the module 
   * @return array
    */
  function keys() {
  
    $keys_list = array('MODULE_PAYMENT_NOCHEXAPI_STATUS',
                       'MODULE_PAYMENT_NOCHEXAPI_MERCHANT_ID',
                       'MODULE_PAYMENT_NOCHEXAPI_API_WIDGETKEY',
                       'MODULE_PAYMENT_NOCHEXAPI_ZONE',
                       'MODULE_PAYMENT_NOCHEXAPI_PROCESSING_STATUS_ID',
                       'MODULE_PAYMENT_NOCHEXAPI_ORDER_STATUS_ID',
                       'MODULE_PAYMENT_NOCHEXAPI_SORT_ORDER',
					   'MODULE_PAYMENT_NOCHEXAPI_APC_DEBUG',
					   'MODULE_PAYMENT_NOCHEXAPI_TESTING');
 
    return $keys_list;
  }
}
?>
