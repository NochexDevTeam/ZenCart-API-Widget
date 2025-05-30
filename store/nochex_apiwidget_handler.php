<?php 
/**
 * nochex_apiwidget_handler.php callback handler for Nochex APC payment method
 *
 * @package paymentMethod
 * @copyright Copyright 2003-2006 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */
 
/* Include custom application_top.php */
require('includes/application_top.php'); 
/* Include Nochex functions */ 
require('includes/modules/payment/nochex_apiwidget/nochex_functions.php');
/* Include checkout_process in relation to the session language **/
require('includes/languages/english/modules/payment/nochex_apiwidget.php');

require('includes/languages/english/lang.checkout_process.php');

global $db;
/* APC Code */
// Payment confirmation from http post 
ini_set("SMTP","mail.nochex.com"); 
$header = "From: apc@nochex.com";

$your_email = isset($_POST["to_email"]);  // your merchant account email address
  

function http_post($server, $port, $url, $vars) { 

    // get urlencoded vesion of $vars array 
    $urlencoded = ""; 
    foreach ($vars as $Index => $Value) // loop round variables and encode them to be used in query
    $urlencoded .= urlencode($Index ) . "=" . urlencode($Value) . "&"; 
    $urlencoded = substr($urlencoded,0,-1);   // returns portion of string, everything but last character

    $headers = "POST $url HTTP/1.0\r\n"  // headers to be sent to the server
    . "Content-Type: application/x-www-form-urlencoded\r\n" 
    . "User-Agent: Nochexapc/1.0\r\n" 
    . "Host: secure.nochex.com\r\n" 
    . "Content-Length: ". strlen($urlencoded) . "\r\n\r\n";  // length of the string
  
    $fp = fsockopen($server, $port, $errno, $errstr, 20);  // returns file pointer
    if (!$fp) return "ERROR: fsockopen failed.\r\nError no: $errno - $errstr";  // if cannot open socket then display error message

    fputs($fp, $headers);  //writes to file pointer
    fputs($fp, $urlencoded);  
  
    $ret = ""; 
    while (!feof($fp)) $ret .= fgets($fp, 1024); // while it’s not the end of the file it will loop 
    fclose($fp);  // closes the connection
	
    return $ret; // array 
	
}



if (isset($_POST["order_id"])){


if(isset($_POST["optional_2"]) == "cb"){

// stores the response from the Nochex server   
	$response = http_post("ssl://secure.nochex.com", 443, "/callback/callback.aspx", $_POST); 

	// Callback Debug
	$debug = "IP -> " . $_SERVER['REMOTE_ADDR'] ."\r\n\r\nPOST DATA:\r\n"; 
	foreach($_POST as $Index => $Value) 
	$debug .= "$Index -> $Value\r\n"; 
	$debug .= "\r\nRESPONSE:\r\n$response"; 

	if ($_POST['transaction_status'] == "100"){
		$status = " TEST";
	}else{
		$status = " LIVE";
	}

		$order_ID = $_POST['order_id']; 
		$trans_date = $_POST['transaction_date'];
		$trans_Id = $_POST['transaction_id']; 
		$trans_amount = $_POST["amount"]; 
 
	if (!strstr($response, "AUTHORISED")) {   
		$msg = "Callback was not AUTHORISED.";   
		$new_status = defined('MODULE_PAYMENT_NOCHEXAPI_PENDING_STATUS_ID') ? MODULE_PAYMENT_NOCHEXAPI_PENDING_STATUS_ID : 1;
	} else { 
		$msg = "Callback was Authorised";	
		$new_status = defined('MODULE_PAYMENT_NOCHEXAPI_PROCESSING_STATUS_ID') ? MODULE_PAYMENT_NOCHEXAPI_PROCESSING_STATUS_ID : 2; 
	} 
	
	$comments = 'Nochex payment of '.sprintf("%01.2f", $trans_amount).' received at '.$trans_date.' with transaction ID:'.$trans_Id. ' this was a '. $status .' transaction, ' .$msg;     
     
$nochex_order = array('order_id' => $order_ID,
                        'nc_transaction_id' => $_POST['transaction_id'],
                        'nc_transaction_date' => $_POST['transaction_date'],
                        'nc_status' => $status,
                        'nc_to_email' => $_POST['merchant_id'],
                        'nc_from_email' => $_POST['email_address'],
                        'nc_order_id' => $order_ID,
                        'nc_custom' => $_POST['optional_1'],
                        'nc_amount' => $trans_amount,
                        'nc_security_key' => $_POST['security_key'],
                        'nochex_response' => $msg,
                        'date_added' => 'now()'
                       );
					
zen_db_perform(TABLE_NOCHEXAPI, $nochex_order);

$checkSession = apc_get_stored_session($_POST["optional_1"]);
   

}else{


$response = http_post("ssl://secure.nochex.com", 443, "/apc/apc.aspx", $_POST); 
// stores the response from the Nochex server 
$debug = "IP -> " . $_SERVER['REMOTE_ADDR'] ."\r\n\r\nPOST DATA:\r\n"; 
foreach($_POST as $Index => $Value) 
$debug .= "$Index -> $Value\r\n"; 
$debug .= "\r\nRESPONSE:\r\n$response";
// Retrieves the order_id and save it as a variable which can be used in the update query to find a particular record in a database or datatable.	

$order_ID = $_POST['order_id']; 
$trans_date = $_POST['transaction_date'];
$trans_Id = $_POST['transaction_id'];
$status = $_POST['status'];
$trans_amount = $_POST["amount"];

if (!strstr($response, "AUTHORISED")) {  // searches response to see if AUTHORISED is present if it isn’t a failure message is displayed

    $msg = "APC was not AUTHORISED.";  // displays debug message	
    $new_status = defined('MODULE_PAYMENT_NOCHEXAPI_PENDING_STATUS_ID')  ? MODULE_PAYMENT_NOCHEXAPI_PENDING_STATUS_ID : 1;

} else { 

   $msg = "APC was Authorised";	
   $new_status = defined('MODULE_PAYMENT_NOCHEXAPI_PROCESSING_STATUS_ID') ? MODULE_PAYMENT_NOCHEXAPI_PROCESSING_STATUS_ID : 2;
   
} 

$comments = 'Nochex payment of '.sprintf("%01.2f", $trans_amount).' received at '.$trans_date.' with transaction ID:'.$trans_Id. ' this was a '. $status .' transaction, ' .$msg;
  
$nochex_order = array('order_id' => $order_ID,
                        'nc_transaction_id' => $_POST['transaction_id'],
                        'nc_transaction_date' => $_POST['transaction_date'],
                        'nc_status' => $_POST['status'],
                        'nc_to_email' => $_POST['to_email'],
                        'nc_from_email' => $_POST['from_email'],
                        'nc_order_id' => $_POST['order_id'],
                        'nc_custom' => $_POST['custom'],
                        'nc_amount' => $_POST['amount'],
                        'nc_security_key' => $_POST['security_key'],
                        'nochex_response' => $msg,
                        'date_added' => 'now()'
                       );
					
zen_db_perform(TABLE_NOCHEXAPI, $nochex_order);

$checkSession = apc_get_stored_session($_POST["custom"]);

}

if ($checkSession == true) {
/**
       * require shipping class
       */
      require(DIR_WS_CLASSES . 'shipping.php');
      /**
       * require payment class
       */
      require(DIR_WS_CLASSES . 'payment.php');
      $payment_modules = new payment($_SESSION['payment']);
      $shipping_modules = new shipping($_SESSION['shipping']);
      /** 
       * require order class
       */
      require(DIR_WS_CLASSES . 'order.php');
      $order = new order;
      /**
       * require order_total class
       */
      require(DIR_WS_CLASSES . 'order_total.php');
      $order_total_modules = new order_total();
      $zco_notifier->notify('NOTIFY_CHECKOUT_PROCESS_BEFORE_ORDER_TOTALS_PROCESS');
      $order_totals = $order_total_modules->process();
      $zco_notifier->notify('NOTIFY_CHECKOUT_PROCESS_AFTER_ORDER_TOTALS_PROCESS');
	  
	  $insert_id = $order->create($order_totals);
	  $_SESSION['order_number_created'] = $insert_id;
	  
	  $order->create_add_products($insert_id, 2);
	  /*deprecated function and variables that are causing warning php error logs when creating and sending email in includes/classes/order.php - lines 1060 to 1205 but still works for sending email confirmation */
	  //$order->send_order_email($insert_id, 2);
	
if ($order->info['total'] <> $_POST["amount"]) {
		/**/
		$statusPen = defined('MODULE_PAYMENT_NOCHEXAPI_PENDING_STATUS_ID') ? MODULE_PAYMENT_NOCHEXAPI_PENDING_STATUS_ID : 1;
		$sql_data_array = array('orders_id' => $insert_id,
							  'orders_status_id' => $statusPen,
							  'date_added' => 'now()',
							  'comments' => "Paid amount ". $_POST["amount"] . " does not match the order total! " . number_format($order->info['total'],2),
							  'customer_notified' => '0',
							  'updated_by' => 'Nochex'
	  );
	  	  
	  $sqlPen = 'UPDATE ' . TABLE_ORDERS . ' set orders_status = "'.$statusPen.'" where orders_id = "' .(int)$insert_id. '"';	  
	  $db->Execute($sqlPen);
	  
	} else {
	  $sql_data_array = array('orders_id' => $insert_id,
							  'orders_status_id' => $new_status,
							  'date_added' => 'now()',
							  'comments' => $comments,
							  'customer_notified' => '0',
							  'updated_by' => 'Nochex'
	  );
}
	
	  zen_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
	
} else {

apc_debug_email('Could not find stored session in DB, so unable to create an order, check nochex_apiwidget_transactions table for order'); 

}
 }


?>  
