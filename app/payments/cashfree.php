<?php
use Tygh\Registry;

include_once 'cashfree/cf_common.inc';
include_once 'cashfree/cf_notify.php';
require_once 'cashfree/CashfreePayment.php';

if (!defined('AREA')) {die('Access denied');}

// Notify flow s2s
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($mode == 'cf_notify') {
        $cf_notify = new Cf_Notify();
        $cf_notify->process();
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
        $cashfree_payment = new CashfreePayment();

        $cashfree_payment->postProcessing();
        exit;
    }
} else {
    $cashfree_payment = new CashfreePayment();

    $app = Tygh::$app;

    $session_id = $app['session']->getID();

    $processor_params = $processor_data['processor_params'];

    $domain = $app['storefront']->url;

    if($processor_params['order_id_prefix_text'] == 1){
        $encoded_string = md5($domain);
        $order_id_prefix_text = substr($encoded_string, 0, 4);
        $order_id = $order_id_prefix_text.'_cf_'.$order_id;
    }

    $return_url = fn_url("payment_notification.cf_return?payment=cashfree&sid=" . base64_encode($session_id), AREA, 'current');
    $callback_url = $return_url."&order_id={order_id}";
    $popup_callback_url = $return_url."&order_id=".$order_id;
    
    $notify_url = fn_url("payment_notification.cf_notify?payment=cashfree", AREA, 'current');
    
    $cf_request["order_id"] = $order_id;
    $cf_request["customer_id"] = (!empty($order_info['user_id']) && $order_info['user_id'] !== 0) ? $order_info['user_id'] : 'cscart_guest_user';
    $cf_request["order_note"] = "Order# " . $order_id;
    $cf_request["order_amount"] = fn_cf_adjust_amount($order_info['total'], $processor_params['currency']);
    $cf_request["order_currency"] = $processor_params['currency'];
    $cf_request["customer_phone"] = preg_replace('/[^\dxX]/', '', $order_info['phone']);
    $cf_request["customer_name"] = $order_info['b_firstname'] . " " . $order_info['b_lastname'];
    $cf_request["customer_email"] = $order_info['email'];
    $cf_request["return_url"] = $callback_url;
    $cf_request["notify_url"] = $notify_url;
    $cf_request["source"] = "cscart";
    $payment_data = $cashfree_payment->getPaymentSessionData($processor_params, $cf_request, $order_info);

    if (!$cf_request['customer_phone']) {
        echo __('text_unsupported_phone');
        exit;
    }

    if($payment_data["STATUS"] == "SUCCESS") {
        $payment_data['in_context'] = false;
        if($processor_params['order_in_context'] == 1){
            $payment_data['in_context'] = true;
        }
        $payment_data['url'] = $popup_callback_url;
        echo $cashfree_payment->generateHtmlForm($payment_data);
        exit;
    } else {
        echo __('text_cf_failed_order');
        exit;
    }
}