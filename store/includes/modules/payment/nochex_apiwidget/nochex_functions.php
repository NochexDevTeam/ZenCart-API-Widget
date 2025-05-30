<?php
/**
 * functions used by payment module class for Nochex APC payment method
 *
 * @package paymentMethod
 * @copyright Copyright 2003-2006 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @copyright Portions Copyright (c) 2004 DevosC.com
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

// counter used for debug purposes:
  $nochex_error_counter = 0;
  $nochex_instance_id = time();

function apc_datetime_to_sql_format($nochexDateTime)
{
	$time = substr($nochexDateTime, -8);
	$year = substr($nochexDateTime, 6, 4);
	$month= substr($nochexDateTime, 3, 2);
	$day = substr($nochexDateTime, 0, 2);
    return ($year . '-' . $month . '-' . $day . ' ' . $time);
}

// Functions for Nochex processing
function apc_debug_email($message, $email_address = MODULE_PAYMENT_NOCHEXAPI_MERCHANT_ID, $always_send = false) {
  if (MODULE_PAYMENT_NOCHEXAPI_APC_DEBUG == 'Log and Email' || $always_send) {
    global $nochex_error_counter, $nochex_instance_id;
    $nochex_error_counter ++;
    mail($email_address,'APC DEBUG message (' . $nochex_instance_id . ') #' . $nochex_error_counter, $message);
  }
  if (MODULE_PAYMENT_NOCHEXAPI_APC_DEBUG == 'Log and Email' || MODULE_PAYMENT_NOCHEXAPI_APC_DEBUG == 'Log File' || MODULE_PAYMENT_NOCHEXAPI_APC_DEBUG == 'Yes') apc_add_error_log($message);
}

function apc_get_stored_session($session_stuff) {
  global $db;
  
  $sql = 'SELECT * 
          FROM ' . TABLE_NOCHEXAPI_SESSION . ' 
          WHERE session_id = "'.$session_stuff.'"';
		  
  $stored_session = $db->Execute($sql);
  if ($stored_session->recordCount() < 1) {
    apc_debug_email('APC FATAL ERROR::Could not find stored session in DB, cannot re-create session'); 
    return false;
  }
  $_SESSION = unserialize(base64_decode($stored_session->fields['saved_session']));
  return true;
}

function apc_create_order_array($new_order_id, $response) {
  $nochex_order = array('order_id' => $new_order_id,
                        'nc_transaction_id' => $_POST['transaction_id'],
                        'nc_transaction_date' => apc_datetime_to_sql_format($_POST['transaction_date']),
                        'nc_status' => $_POST['status'],
                        'nc_to_email' => $_POST['to_email'],
                        'nc_from_email' => $_POST['from_email'],
                        'nc_order_id' => $_POST['order_id'],
                        'nc_custom' => $_POST['custom'],
                        'nc_amount' => $_POST['amount'],
                        'nc_security_key' => $_POST['security_key'],
                        'nochex_response' => $response,
                        'date_added' => 'now()'
                       );
  return $nochex_order;
}

function apc_add_error_log($message) {
  global  $apc_instance_id;
  $fp = @fopen('includes/modules/payment/nochex_apiwidget/logs/apc_' . $nochex_instance_id . '.log', 'a');
  @fwrite($fp, date('D M Y G:i') . ' -- ' . $message . "\n\n");
  @fclose($fp);
}

?>
