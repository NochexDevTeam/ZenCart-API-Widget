<?php
/**
 * @package languageDefines
 * @copyright Copyright 2003-2006 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: NOCHEX.php 4834 2006-10-25 03:24:31Z drbyte $
 */

  define('MODULE_PAYMENT_NOCHEXAPI_TEXT_ADMIN_TITLE', 'Nochex API Widget');
  define('MODULE_PAYMENT_NOCHEXAPI_TEXT_CATALOG_TITLE', 'Credit or Debit Card (Nochex)');
  if (IS_ADMIN_FLAG === true) {
    define('MODULE_PAYMENT_NOCHEXAPI_TEXT_DESCRIPTION', '<strong>Nochex API Widget</strong><br /><br />This module is designed for Nochex Merchants, and uses the latest API Widget Page provided by Nochex to give your buyers a smooth and seamless checkout. Very little configuration is required, but please read the documentation before use.' );
  } else {
    define('MODULE_PAYMENT_NOCHEXAPI_TEXT_DESCRIPTION', '<strong>Nochex</strong>');
  }
  
  define('MODULE_PAYMENT_NOCHEXAPI_MARK_BUTTON_IMG', 'https://ssl.nochex.com/images/zencart/nochex_logo_v2.png');
  define('MODULE_PAYMENT_NOCHEXAPI_MARK_BUTTON_ALT', 'Checkout with Nochex');
  define('MODULE_PAYMENT_NOCHEXAPI_ACCEPTANCE_MARK_TEXT', 'Secure Online Payments.');

  define('MODULE_PAYMENT_NOCHEXAPI_TEXT_CATALOG_LOGO', '<img src="' . MODULE_PAYMENT_NOCHEXAPI_MARK_BUTTON_IMG . '" alt="' . MODULE_PAYMENT_NOCHEXAPI_MARK_BUTTON_ALT . '" title="' . MODULE_PAYMENT_NOCHEXAPI_MARK_BUTTON_ALT . '" /> <br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' .
                                                    '<span class="smallText">' . MODULE_PAYMENT_NOCHEXAPI_ACCEPTANCE_MARK_TEXT . '</span>');
  
  
  define('MODULE_PAYMENT_NOCHEXAPI_ENTRY_TRANSACTION_ID', 'Transaction ID');
  define('MODULE_PAYMENT_NOCHEXAPI_ENTRY_TRANSACTION_DATE', 'Transaction Date');
  define('MODULE_PAYMENT_NOCHEXAPI_ENTRY_TO_EMAIL', 'Payment To');
  define('MODULE_PAYMENT_NOCHEXAPI_ENTRY_FROM_EMAIL', 'Payment From');
  define('MODULE_PAYMENT_NOCHEXAPI_ENTRY_ORDER_ID', 'Order ID');
  define('MODULE_PAYMENT_NOCHEXAPI_ENTRY_CUSTOM', 'Custom Field(s)');
  define('MODULE_PAYMENT_NOCHEXAPI_ENTRY_AMOUNT', 'Amount Paid');
  define('MODULE_PAYMENT_NOCHEXAPI_ENTRY_SECURITY_KEY', 'Security Key');
  define('MODULE_PAYMENT_NOCHEXAPI_ENTRY_STATUS', 'Payment Status');
  define('MODULE_PAYMENT_NOCHEXAPI_ENTRY_NOCHEX_RESPONSE', 'Nochex Response');
  define('MODULE_PAYMENT_NOCHEXAPI_PURCHASE_DECRIPTION_TITLE', STORE_NAME . ' Purchase');

?>