<?php

class Cf_Notify
{
    /**
     * Process a Cashfree Notify. We exit in the following cases:
     * - Successful processed
     */
    public function process()
    {
        $data = $_POST;

        $cfSignature = null;
        if (isset($data['signature']) === true) {
            $cfSignature = $data['signature'];
        }

        $merchantOrderId = $data['orderId'];
        $referenceId = $data['referenceId'];
        if ((empty($cfSignature) === false) and $data['txStatus'] == 'SUCCESS') {
            if (fn_check_payment_script('cashfree.php', $merchantOrderId, $processorData)) {
                $orderInfo = fn_get_order_info($merchantOrderId);
                $orderAmount = $data["orderAmount"];
                $paymentMode = $data["paymentMode"];
                $referenceId = $data["referenceId"];
                $txStatus = $data["txStatus"];
                $txTime = $data["txTime"];
                $txMsg = $data["txMsg"];
                $signature = $data["signature"];

                $secretKey = $processorData['processor_params']['secret_key'];

                $data = $merchantOrderId . $orderAmount . $referenceId . $txStatus . $paymentMode . $txMsg . $txTime;
                $hash_hmac = hash_hmac('sha256', $data, $secretKey, true);
                $computedSignature = base64_encode($hash_hmac);

                if ($computedSignature != $signature) {
                    exit;
                }
                $pp_response['order_status'] = 'P';
                $pp_response['reason_text'] = $data["txMsg"];
                $pp_response['transaction_id'] = $referenceId;
                $pp_response['client_id'] = $merchantOrderId;

                fn_define('ORDER_MANAGEMENT', true);
                fn_finish_payment($merchantOrderId, $pp_response);

                exit;

            }
            exit;

        }
    }

}