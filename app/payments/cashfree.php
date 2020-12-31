<?php
use Tygh\Registry;

include_once 'cashfree/cf_common.inc';
include_once 'cashfree/cf_notify.php';
require_once 'cashfree/CashfreePayment.php';

if (!defined('AREA')) {die('Access denied');}

// Notify flow s2s
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($mode == 'cf_notify') {
        $cfNotify = new Cf_Notify();
        $cfNotify->process();
        exit;
    }
}

// Return from payment
if (defined('PAYMENT_NOTIFICATION')) {
    if ($mode == 'cf_return') {
        $session_id = base64_decode($_REQUEST['sid']);
        Tygh::$app['session']->resetID($session_id);
        if (isset($view) === false) {
            $view = Registry::get('view');
        }

        $view->assign('order_action', __('placing_order'));
        $view->display('views/orders/components/placing_order.tpl');
        fn_flush();

        $cashfreePayment = new CashfreePayment();

        $cashfreePayment->postProcessing();
        exit;
    }
} else {
    $cashfreePayment = new CashfreePayment();

    $session_id = Tygh::$app['session']->getID();

    $returnUrl = fn_url("payment_notification.cf_return?payment=cashfree&sid=" . base64_encode($session_id), AREA, 'current');

    $notifyUrl = fn_url("payment_notification.cf_notify?payment=cashfree", AREA, 'current');
    
    $apiEndpoint = ($processor_data['processor_params']['enabled_test_mode'] == 1) ? 'https://test.cashfree.com/billpay' : 'https://www.cashfree.com';  	

    $actionUrl = $apiEndpoint.'/checkout/post/submit';
    
    $appId = $processor_data['processor_params']['app_id'];

    $secretKey = $processor_data['processor_params']['secret_key'];

    $cf_request = array();
    $cf_request["appId"] = $appId;
    $cf_request["orderId"] = $order_id;
    $cf_request["orderNote"] = "Order# " . $order_id;
    $cf_request["orderAmount"] = fn_cf_adjust_amount($order_info['total'], $processor_data['processor_params']['currency']);
    $cf_request["orderCurrency"] = $processor_data['processor_params']['currency'];
    $cf_request["customerPhone"] = $order_info['phone'];
    $cf_request["customerName"] = $order_info['b_firstname'] . " " . $order_info['b_lastname'];
    $cf_request["customerEmail"] = $order_info['email'];
    $cf_request["returnUrl"] = $returnUrl;
    $cf_request["notifyUrl"] = $notifyUrl;
    $cf_request["source"] = "cscart";
    $cf_request["signature"] = $cashfreePayment->generateSignature($processor_data['processor_params'], $cf_request);

    if (!$cf_request['customerPhone']) {
        echo __('text_unsupported_phone');
        exit;
    }

    echo $cashfreePayment->generateHtmlForm($actionUrl, $cf_request);

    exit;
}
