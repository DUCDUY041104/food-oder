<?php
/**
 * MoMo Payment Helper Class
 * Xử lý thanh toán MoMo sử dụng test environment
 */

class MomoPayment {
    // MoMo Test Environment Credentials
    private $partnerCode = "MOMO";
    private $accessKey = "F8BBA842ECF85";
    private $secretKey = "K951B6PE1waDMi640xX08PD3vg6EkVlz";
    private $apiUrl = "https://test-payment.momo.vn/v2/gateway/api/create";
    private $queryUrl = "https://test-payment.momo.vn/v2/gateway/api/query";
    
    /**
     * Tạo chữ ký (signature) cho request
     */
    private function createSignature($rawSignature) {
        return hash_hmac('sha256', $rawSignature, $this->secretKey);
    }
    
    /**
     * Tạo payment URL từ MoMo
     * @param array $params ['amount', 'orderId', 'orderInfo', 'redirectUrl', 'ipnUrl', 'requestId', 'extraData']
     * @return array ['success' => bool, 'payUrl' => string, 'message' => string]
     */
    public function createPayment($params) {
        try {
            $amount = $params['amount'];
            $orderId = $params['orderId'];
            $orderInfo = $params['orderInfo'] ?? "Thanh toán đơn hàng";
            $redirectUrl = $params['redirectUrl'];
            $ipnUrl = $params['ipnUrl'] ?? $redirectUrl;
            $requestId = $params['requestId'] ?? $orderId;
            $requestType = $params['requestType'] ?? "captureWallet";
            $extraData = $params['extraData'] ?? "";
            
            // Tạo raw signature
            $rawSignature = "accessKey=" . $this->accessKey .
                          "&amount=" . $amount .
                          "&extraData=" . $extraData .
                          "&ipnUrl=" . $ipnUrl .
                          "&orderId=" . $orderId .
                          "&orderInfo=" . $orderInfo .
                          "&partnerCode=" . $this->partnerCode .
                          "&redirectUrl=" . $redirectUrl .
                          "&requestId=" . $requestId .
                          "&requestType=" . $requestType;
            
            $signature = $this->createSignature($rawSignature);
            
            // Tạo request body
            $requestBody = [
                'partnerCode' => $this->partnerCode,
                'accessKey' => $this->accessKey,
                'requestId' => $requestId,
                'amount' => $amount,
                'orderId' => $orderId,
                'orderInfo' => $orderInfo,
                'redirectUrl' => $redirectUrl,
                'ipnUrl' => $ipnUrl,
                'extraData' => $extraData,
                'requestType' => $requestType,
                'signature' => $signature,
                'lang' => 'vi'
            ];
            
            // Gọi API MoMo
            $ch = curl_init($this->apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestBody));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json'
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if ($curlError) {
                return [
                    'success' => false,
                    'message' => 'Lỗi kết nối: ' . $curlError
                ];
            }
            
            if ($httpCode !== 200) {
                return [
                    'success' => false,
                    'message' => 'Lỗi HTTP: ' . $httpCode
                ];
            }
            
            $result = json_decode($response, true);
            
            if (isset($result['payUrl']) && !empty($result['payUrl'])) {
                return [
                    'success' => true,
                    'payUrl' => $result['payUrl'],
                    'message' => 'Tạo link thanh toán thành công'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $result['message'] ?? 'Không thể tạo link thanh toán'
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Verify payment với MoMo
     * @param string $orderId
     * @param string $requestId
     * @return array ['success' => bool, 'resultCode' => int, 'message' => string]
     */
    public function verifyPayment($orderId, $requestId = null) {
        try {
            $requestId = $requestId ?? $orderId;
            
            // Tạo raw signature
            $rawSignature = "accessKey=" . $this->accessKey .
                          "&orderId=" . $orderId .
                          "&partnerCode=" . $this->partnerCode .
                          "&requestId=" . $requestId;
            
            $signature = $this->createSignature($rawSignature);
            
            // Tạo request body
            $requestBody = [
                'partnerCode' => $this->partnerCode,
                'requestId' => $requestId,
                'orderId' => $orderId,
                'signature' => $signature,
                'lang' => 'vi'
            ];
            
            // Gọi API MoMo
            $ch = curl_init($this->queryUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestBody));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json'
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if ($curlError) {
                return [
                    'success' => false,
                    'resultCode' => -1,
                    'message' => 'Lỗi kết nối: ' . $curlError
                ];
            }
            
            if ($httpCode !== 200) {
                return [
                    'success' => false,
                    'resultCode' => -1,
                    'message' => 'Lỗi HTTP: ' . $httpCode
                ];
            }
            
            $result = json_decode($response, true);
            
            return [
                'success' => true,
                'resultCode' => intval($result['resultCode'] ?? -1),
                'message' => $result['message'] ?? '',
                'amount' => $result['amount'] ?? 0,
                'transId' => $result['transId'] ?? '',
                'data' => $result
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'resultCode' => -1,
                'message' => 'Lỗi: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Verify signature từ callback
     */
    public function verifyCallbackSignature($params) {
        $accessKey = $params['accessKey'] ?? '';
        $amount = $params['amount'] ?? '';
        $extraData = $params['extraData'] ?? '';
        $message = $params['message'] ?? '';
        $orderId = $params['orderId'] ?? '';
        $orderInfo = $params['orderInfo'] ?? '';
        $orderType = $params['orderType'] ?? '';
        $partnerCode = $params['partnerCode'] ?? '';
        $payType = $params['payType'] ?? '';
        $requestId = $params['requestId'] ?? '';
        $responseTime = $params['responseTime'] ?? '';
        $resultCode = $params['resultCode'] ?? '';
        $transId = $params['transId'] ?? '';
        $signature = $params['signature'] ?? '';
        
        // Tạo raw signature
        $rawSignature = "accessKey=" . $accessKey .
                      "&amount=" . $amount .
                      "&extraData=" . $extraData .
                      "&message=" . $message .
                      "&orderId=" . $orderId .
                      "&orderInfo=" . $orderInfo .
                      "&orderType=" . $orderType .
                      "&partnerCode=" . $partnerCode .
                      "&payType=" . $payType .
                      "&requestId=" . $requestId .
                      "&responseTime=" . $responseTime .
                      "&resultCode=" . $resultCode .
                      "&transId=" . $transId;
        
        $expectedSignature = $this->createSignature($rawSignature);
        
        return hash_equals($expectedSignature, $signature);
    }
}

