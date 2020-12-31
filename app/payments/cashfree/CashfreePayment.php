<?php

class CashfreePayment
{
    //Define version of plugin
    const VERSION = '1.0.0';
    
    /**
     * Generate Signature for payment
     *
     * @param  mixed $processor_data
     * @param  mixed $cf_request
     * @return void
     */
    public function generateSignature($processor_data, $cf_request)
    {
        // get secret key from your config
        $secretKey = $processor_data['secret_key'];

        ksort($cf_request);
        $signatureData = "";
        foreach ($cf_request as $key => $value) {
            $signatureData .= $key . $value;
        }

        $signature = hash_hmac('sha256', $signatureData, $secretKey, true);
        $signature = base64_encode($signature);

        return $signature;
    }
    
    /**
     * Generate Cashfree payment checkout
     *
     * @param  mixed $actionUrl
     * @param  mixed $cf_request
     * @return void
     */
    public function generateHtmlForm($actionUrl, $cf_request)
    {
        $html = '
            <!DOCTYPE html>
            <body>
                <form action="' . $actionUrl . '" method="post" name="embedded_checkout_form" id="embedded_checkout_form">
                    <input type="hidden" name="appId" value="' . $cf_request['appId'] . '">
                    <input type="hidden" name="orderId" value="' . $cf_request['orderId'] . '">
                    <input type="hidden" name="orderAmount" value="' . $cf_request['orderAmount'] . '">
                    <input type="hidden" name="orderCurrency" value="' . $cf_request['orderCurrency'] . '">

                    <input type="hidden" name="orderNote" value="' . $cf_request['orderNote'] . '">
                    <input type="hidden" name="customerName" value="' . $cf_request['customerName'] . '">
                    <input type="hidden" name="customerEmail" value="' . $cf_request['customerEmail'] . '">

                    <input type="hidden" name="customerPhone" value="' . $cf_request['customerPhone'] . '">

                    <input type="hidden" name="returnUrl" value="' . $cf_request['returnUrl'] . '">
                    <input type="hidden" name="notifyUrl" value="' . $cf_request['notifyUrl'] . '">
                    <input type="hidden" name="signature" value="' . $cf_request['signature'] . '">

                    <input type="hidden" name="source" value="' . $cf_request['source'] . '">
                </form>
                <script type="text/javascript">
                    document.getElementById("embedded_checkout_form").submit();
                </script>
                </body>
            </html>
            ';

        return $html;
    }
    
    /**
     * Processing order after payment
     *
     * @return void
     */
    public function postProcessing()
    {
        $cfSignature = null;
        if (isset($_POST['signature']) === true) {
            $cfSignature = $_POST['signature'];
        }

        $merchantOrderId = $_POST['orderId'];
        $referenceId = $_POST['referenceId'];
        if ((empty($cfSignature) === false) and $_POST['txStatus'] == 'SUCCESS') {
            if (fn_check_payment_script('cashfree.php', $merchantOrderId, $processorData)) {
                $orderInfo = fn_get_order_info($merchantOrderId);

                $success = $this->validateSignature($processorData, $_POST);
            } else {
                $error = 'Cashfree_Error: Invalid Response';

                $success = false;
            }

            if ($success === true) {
                $pp_response['order_status'] = 'P';
                $pp_response['reason_text'] = $_POST['txMsg'];
                $pp_response['transaction_id'] = $merchantOrderId;
                $pp_response['client_id'] = $referenceId;

                fn_finish_payment($merchantOrderId, $pp_response);

                fn_order_placement_routines('route', $merchantOrderId);
            } else {
                $error = $_POST['txMsg'];
                $this->handleFailedPayment($error, $referenceId, $merchantOrderId);
            }
        } else if ($_POST['txStatus'] == 'FAILED') {
            $error = $_POST['txMsg'];
            $message = 'An error occured. Description : ' . $error;

            $this->handleFailedPayment($message, $referenceId, $merchantOrderId);
        } else {
            $error = $_POST['txMsg'];
            $message = 'An error occured. Description : ' . $error;

            $this->handleIncompletePayment($message, $referenceId, $merchantOrderId);
        }
    }
    
    /**
     * Handle Failed Payment
     *
     * @param  mixed $errorMessage
     * @param  mixed $referenceId
     * @param  mixed $merchantOrderId
     * @return void
     */
    protected function handleFailedPayment($errorMessage, $referenceId, $merchantOrderId)
    {
        $pp_response['order_status'] = 'F';
        $pp_response['reason_text'] = $errorMessage;
        $pp_response['transaction_id'] = $referenceId;
        $pp_response['client_id'] = $merchantOrderId;

        fn_finish_payment($merchantOrderId, $pp_response);
        fn_set_notification('E', __('error'), __('text_cf_failed_order') . $merchantOrderId);
        fn_order_placement_routines('checkout_redirect');
    }
    
    /**
     * Handle Incomplete Payment
     *
     * @param  mixed $errorMessage
     * @param  mixed $referenceId
     * @param  mixed $merchantOrderId
     * @return void
     */
    protected function handleIncompletePayment($errorMessage, $referenceId, $merchantOrderId)
    {
        $pp_response['order_status'] = 'N';
        $pp_response['reason_text'] = $errorMessage;
        $pp_response['transaction_id'] = $referenceId;
        $pp_response['client_id'] = $merchantOrderId;

        fn_finish_payment($merchantOrderId, $pp_response);
        fn_set_notification('E', __('error'), __('text_cf_incomplete_order') . $merchantOrderId);
        fn_order_placement_routines('checkout_redirect');
    }
    
    /**
     * Validate Signature after payment
     *
     * @param  mixed $processorData
     * @param  mixed $returnParams
     * @return void
     */
    public function validateSignature($processorData, $returnParams)
    {
        $orderId = $returnParams["orderId"];
        $orderAmount = $returnParams["orderAmount"];
        $paymentMode = $returnParams["paymentMode"];
        $referenceId = $returnParams["referenceId"];
        $txStatus = $returnParams["txStatus"];
        $txTime = $returnParams["txTime"];
        $txMsg = $returnParams["txMsg"];
        $signature = $returnParams["signature"];

        $secretKey = $processorData['processor_params']['secret_key'];

        $data = $orderId . $orderAmount . $referenceId . $txStatus . $paymentMode . $txMsg . $txTime;
        $hash_hmac = hash_hmac('sha256', $data, $secretKey, true);
        $computedSignature = base64_encode($hash_hmac);

        if ($computedSignature != $signature) {
            $success = false;
        }

        $success = true;

        return $success;

    }
}