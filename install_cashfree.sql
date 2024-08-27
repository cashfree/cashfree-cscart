REPLACE INTO cscart_payment_processors (`processor`,`processor_script`,`processor_template`,`admin_template`,`callback`,`type`) VALUES ('Cashfree','cashfree.php', 'views/orders/components/payments/cc_outside.tpl','cashfree/cashfree.tpl', 'Y', 'P');
REPLACE INTO cscart_language_values (`lang_code`,`name`,`value`) VALUES ('EN','cf_app_id','App Id');
REPLACE INTO cscart_language_values (`lang_code`,`name`,`value`) VALUES ('EN','cf_secret_key','Secret Key');
REPLACE INTO cscart_language_values (`lang_code`,`name`,`value`) VALUES ('EN','cf_enabled_test_mode','Enable Test Mode');
REPLACE INTO cscart_language_values (`lang_code`,`name`,`value`) VALUES ('EN','cf_order_id_prefix_text','Enable Order Id Prefix');
REPLACE INTO cscart_language_values (`lang_code`,`name`,`value`) VALUES ('EN','cf_order_in_context','Eanble Cashfree Popup Checkout');
REPLACE INTO cscart_language_values (`lang_code`,`name`,`value`) VALUES ('EN','text_unsupported_phone','Please provide a valid phone number for transaction. Please contact the store administrator regarding this issue.');
REPLACE INTO cscart_language_values (`lang_code`,`name`,`value`) VALUES ('EN','text_cf_failed_order','Order has been failed. Please contact the store staff and tell them the order ID:');
REPLACE INTO cscart_language_values (`lang_code`,`name`,`value`) VALUES ('EN','text_cf_incomplete_order','Order has been incomplete. Please contact the store staff and tell them the order ID:');
