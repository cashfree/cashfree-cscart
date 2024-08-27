<?php

class CashfreePayment
{
    //Define version of plugin
    const VERSION = '2.0.0';
    const API_VERSION = '2022-09-01';
    const CASHFREE_V3_JS_URL = 'https://sdk.cashfree.com/js/v3/cashfree.js';
    /**
     * Generate Signature for payment
     *
     * @param  mixed $processor_data
     * @param  mixed $cf_request
     * @return void
     */
    public function getPaymentSessionData($processor_data, $cf_request, $order_info)
    {
        $config_data = $this->getCurlValue($processor_data);
        $url = $config_data['curlUrl']."/".$cf_request['order_id'];
        $get_order_response = $this->curlGetRequest($config_data, $processor_data, $url);
        if($get_order_response['STATUS'] == 'SUCCESS') {
            $cf_order = $get_order_response['cfOrder'];
            if ($cf_order->order_status == 'PAID') {
                return array(
                    'STATUS' => 'ERROR'
                );
            } else {
                if (
                    strtotime( $cf_order->order_expiry_time ) > time()
                    && round( $cf_order->order_amount ) === round( $cf_request['order_amount'] )
                    && $cf_order->order_currency === $cf_request['order_currency']
                ) {
                    return array(
                        'STATUS' => 'SUCCESS',
                        'payment_session_id' => $cf_order->payment_session_id,
                        'environment' => $config_data["environment"]
                    );
                } else {
                    return array(
                        'STATUS' => 'ERROR',
                        'payment_session_id' => ""
                    );
                }
            }
        }
        return $this->curlPostRequest($config_data, $processor_data, $cf_request, $order_info);
    }

    // Get config values for gateway environment
	public function getCurlValue($processor_data) {
		$is_sandbox = $processor_data['enabled_test_mode'] === '1';
		$base_url = $is_sandbox ? 'https://sandbox.cashfree.com' : 'https://api.cashfree.com';
	
		return [
			'curlUrl' => "{$base_url}/pg/orders",
			'environment' => $is_sandbox ? 'sandbox' : 'production'
		];
	}

    // Post request for gateway
	private function curlPostRequest($config_data, $processor_data, $data, $order_info) {

        $cart_items = [];
        // Iterate over each product in the order
        foreach ($order_info['products'] as $product) {
            $cart_items[] = [
                'item_id' => (string) $product['product_id'],
                'item_name' => (string) $product['product'],
                'item_details_url' => $product['product_url'],
                'item_original_unit_price' => $product['base_price'],
                'item_quantity' => (int) $product['amount']
            ];
        }

        $shipping_cost_float = fn_cf_adjust_amount($order_info['shipping_cost'], $processor_params['currency']);
        $request_data = array(
            'cart_details' => array(
                'shipping_charge' => $shipping_cost_formatted,
                'cart_items' => $cart_items
            ),
            'customer_details' => array(
                'customer_id' =>  $data['customer_id'],
                'customer_email' =>  $data['customer_email'],
                'customer_phone' =>  $data['customer_phone'],
                'customer_name' =>  $data['customer_name']
            ),
            'order_meta' => array(
                'return_url' => $data['return_url'],
                'notify_url' => $data['notify_url']
            ),
            'order_id' => strval($data['order_id']),
            'order_amount' => $data['order_amount'],
            'order_currency' => $data['order_currency'],
            'order_note' => $data['order_note'],
        );

        // Headers
		$headers = [
			'Accept: application/json',
			'Content-Type: application/json',
			'x-api-version: '. self::API_VERSION,
			'x-client-id:'. $processor_data['app_id'],
			'x-client-secret:'. $processor_data['secret_key']
		];

		// cURL options
        $curl_options = array(
            CURLOPT_URL            => $config_data['curlUrl'],
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($request_data),
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 45,
            CURLOPT_RETURNTRANSFER => true,
        );

        // Initialize cURL session
        $curl = curl_init();

        // Set cURL options
        curl_setopt_array($curl, $curl_options);

        // Execute cURL session and get the response
        $response = curl_exec($curl);

        // Get HTTP status code
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $body = json_decode($response);
		if($http_code === 200) {
			return array(
                'STATUS' => 'SUCCESS',
                'payment_session_id' => $body->payment_session_id,
                'environment' => $config_data['environment']
            );
		} else {
			return array(
                'STATUS' => 'ERROR',
                'message' => $body->message,
            );
		}
	}

    // Get request for gateway
	private function curlGetRequest($config_data, $processor_data, $url) {
        // Headers
		$headers = [
			'Accept: application/json',
			'Content-Type: application/json',
			'x-api-version: '. self::API_VERSION,
			'x-client-id:'. $processor_data['app_id'],
			'x-client-secret:'. $processor_data['secret_key']
		];
        // cURL options
        $curl_options = array(
            CURLOPT_URL            => $url,
            CURLOPT_HTTPGET        => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 45,
            CURLOPT_RETURNTRANSFER => true,
        );


		// Initialize cURL session
        $curl = curl_init();

        // Set cURL options
        curl_setopt_array($curl, $curl_options);

        // Execute cURL session and get the response
        $response = curl_exec($curl);

        // Get HTTP status code
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        
		if($http_code === 200) {
            $body = json_decode($response);
            return array(
                'STATUS' => 'SUCCESS',
                'cfOrder' => $body
            );
		} else {
			return array(
                'STATUS' => 'ERROR',
            );
		}
		
	}
    
    /**
     * Generate Cashfree payment checkout
     *
     * @param  mixed $actionUrl
     * @param  mixed $cf_request
     * @return void
     */
    public function generateHtmlForm($payment_data)
    {
        $cashfreeJsUrl = self::CASHFREE_V3_JS_URL; 
        if ($payment_data['in_context']) {
            $html = <<<EOT
                <!DOCTYPE html>
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>Cashfree Checkout Integration</title>
                    <script src="{$cashfreeJsUrl}"></script>
                </head>
                <body>
                    <form name="cashfree-form" id="cashfree-form" action="{$payment_data['url']}" target="_parent" method="POST">
                    </form>
                    <script type="text/javascript">
                        const cashfree = Cashfree({
                            mode: "{$payment_data['environment']}"
                        })
                        document.addEventListener("DOMContentLoaded", function() {
                            let checkoutOptions = {
                                paymentSessionId: "{$payment_data['payment_session_id']}",
                                platformName: "cs",
                                redirectTarget: "_modal",
                            };
                            cashfree.checkout(checkoutOptions).then((result) => {
                                document.getElementById("cashfree-form").submit();
                            });
                        });
                    </script>
                    </body>
                </html>
                EOT;

            return $html;
        } else {
            $html = <<<EOT
                <!DOCTYPE html>
                <html lang="en">
                    <head>
                        <script src="{$cashfreeJsUrl}"></script>
                    </head>
                    <body>
                    </body>
                    <script>
                        const cashfree = Cashfree({
                            mode: "{$payment_data["environment"]}",
                        });
                        document.addEventListener("DOMContentLoaded", () => {
                            cashfree.checkout({
                            paymentSessionId: "{$payment_data['payment_session_id']}",
                            platformName: "cs"
                            });
                        });
                    </script>
                </html>
                EOT;;

            return $html;
        }
    }
    
    /**
     * Processing order after payment
     *
     * @return void
     */
    public function postProcessing()
    {
        $cashfree_order_id = $_REQUEST['order_id'];
        if (preg_match('/cf_(\d+)/', $cashfree_order_id, $matches)) {
            $order_id = $matches[1];
        } else {
            $order_id = $cashfree_order_id;
        }
        $order_info = fn_get_order_info($order_id);
        $processor_data = $order_info['payment_method']['processor_params'];
        $config_data = $this->getCurlValue($processor_data);
        $url = $config_data['curlUrl']."/".$cashfree_order_id. '/payments';
        $get_order_response = $this->curlGetRequest($config_data, $processor_data, $url);
        if($get_order_response['STATUS'] == 'SUCCESS') {
            $cf_payments = $get_order_response['cfOrder'][0];
            if (($cf_payments->payment_status == 'SUCCESS') && (fn_check_payment_script('cashfree.php', $order_id, $processor_data))){
                if (round($cf_payments->order_amount, 2) == round(fn_cf_adjust_amount($order_info['total'], $processor_data['processor_params']['currency']), 2)) {
                    $pp_response['order_status'] = 'P';
                    $pp_response['reason_text'] = $cf_payments->payment_message;
                    $pp_response['transaction_id'] = $cf_payments->cf_payment_id;
                    $pp_response['client_id'] = $order_id;

                    fn_finish_payment($order_id, $pp_response);

                    fn_order_placement_routines('route', $order_id);
                } else {
                    $message = 'An error occured. Description : Amount Mismatch';
                    $this->handleFailedPayment($message, $cf_payments->cf_payment_id, $order_id);
                }
            }
            elseif ($cf_payments->payment_status == "FAILED") {
                $message = 'An error occured. Description : Transaction has failed';
                $this->handleFailedPayment($message, $cf_payments->cf_payment_id, $order_id);
            } else {
                $message = 'An error occured. Description : Transaction status is '. $cf_payments->payment_status;
                $this->handleIncompletePayment($message, "", $order_id);
            }
        } else {
            $message = 'An error occured. Description : order is invalid';
            $this->handleIncompletePayment($message, "", $order_id);
        }
    }
    
    /**
     * Handle Failed Payment
     *
     * @param  mixed $error_message
     * @param  mixed $reference_id
     * @param  mixed $merchant_order_id
     * @return void
     */
    protected function handleFailedPayment($error_message, $reference_id, $merchant_order_id)
    {
        $pp_response['order_status'] = 'F';
        $pp_response['reason_text'] = $error_message;
        $pp_response['transaction_id'] = $reference_id;
        $pp_response['client_id'] = $merchant_order_id;

        fn_finish_payment($merchant_order_id, $pp_response);
        fn_set_notification('E', __('error'), __('text_cf_failed_order') . $merchant_order_id);
        fn_order_placement_routines('checkout_redirect');
    }
    
    /**
     * Handle Incomplete Payment
     *
     * @param  mixed $error_message
     * @param  mixed $reference_id
     * @param  mixed $merchant_order_id
     * @return void
     */
    protected function handleIncompletePayment($error_message, $reference_id, $merchant_order_id)
    {
        $pp_response['order_status'] = 'N';
        $pp_response['reason_text'] = $error_message;
        $pp_response['transaction_id'] = $reference_id;
        $pp_response['client_id'] = $merchant_order_id;

        fn_finish_payment($merchant_order_id, $pp_response);
        fn_set_notification('E', __('error'), __('text_cf_incomplete_order') . $merchant_order_id);
        fn_order_placement_routines('checkout_redirect');
    }

}