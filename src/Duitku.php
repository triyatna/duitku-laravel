<?php

namespace Triyatna\DuitkuLaravel;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\JsonResponse;
use Exception;

class Duitku
{
    protected string $merchantKey;
    protected string $merchantCode;
    protected bool $isSandbox;
    protected string $baseUrl;

    public function __construct(string $merchantKey, string $merchantCode, bool $isSandbox = false)
    {
        $this->merchantKey = $merchantKey;
        $this->merchantCode = $merchantCode;
        $this->isSandbox = $isSandbox;
        $this->setBaseUrl();
    }

    /**
     * Sets the base URL based on sandbox or production mode.
     */
    protected function setBaseUrl(): void
    {
        $this->baseUrl = $this->isSandbox
            ? 'https://sandbox.duitku.com/webapi'
            : 'https://passport.duitku.com/webapi';
    }

    /**
     * Get the list of available payment methods.
     *
     * @param int $amount The transaction amount.
     * @return array
     */
    public function getPaymentMethods(int $amount): array
    {
        $datetime = date('Y-m-d H:i:s');
        $signature = hash('sha256', $this->merchantCode . $amount . $datetime . $this->merchantKey);

        return $this->sendRequest('/api/merchant/paymentmethod/getpaymentmethod', [
            'merchantcode' => $this->merchantCode,
            'amount' => $amount,
            'datetime' => $datetime,
            'signature' => $signature,
        ]);
    }

    /**
     * Create a new payment request (invoice).
     *
     * @param int $paymentAmount
     * @param string $paymentMethod
     * @param string $merchantOrderId
     * @param string $productDetails
     * @param string $customerVaName
     * @param string $email
     * @param string|null $phoneNumber
     * @param string $callbackUrl
     * @param string $returnUrl
     * @param int $expiryPeriod
     * @param array|null $itemDetails
     * @param array|null $customerDetail
     * @return array
     */
    public function createInvoice(
        int $paymentAmount,
        string $paymentMethod,
        string $merchantOrderId,
        string $productDetails,
        string $customerVaName,
        string $email,
        ?string $phoneNumber,
        string $callbackUrl,
        string $returnUrl,
        int $expiryPeriod = 1440,
        ?array $itemDetails = null,
        ?array $customerDetail = null
    ): array {
        $signature = hash('sha256', $this->merchantCode . $merchantOrderId . $paymentAmount . $this->merchantKey);

        $params = [
            'merchantCode' => $this->merchantCode,
            'paymentAmount' => $paymentAmount,
            'paymentMethod' => $paymentMethod,
            'merchantOrderId' => $merchantOrderId,
            'productDetails' => $productDetails,
            'customerVaName' => $customerVaName,
            'email' => $email,
            'phoneNumber' => $phoneNumber,
            'itemDetails' => $itemDetails,
            'customerDetail' => $customerDetail,
            'callbackUrl' => $callbackUrl,
            'returnUrl' => $returnUrl,
            'signature' => $signature,
            'expiryPeriod' => $expiryPeriod,
        ];

        return $this->sendRequest('/api/merchant/v2/inquiry', $params);
    }

    /**
     * Check the status of a transaction.
     *
     * @param string $merchantOrderId The unique order ID.
     * @return array
     */
    public function checkTransactionStatus(string $merchantOrderId): array
    {
        $signature = hash('sha256', $this->merchantCode . $merchantOrderId . $this->merchantKey);

        return $this->sendRequest('/api/merchant/transactionStatus', [
            'merchantCode' => $this->merchantCode,
            'merchantOrderId' => $merchantOrderId,
            'signature' => $signature,
        ]);
    }

    /**
     * Handle the callback from Duitku.
     * It validates the signature and returns a structured response.
     *
     * @return JsonResponse
     */
    public function handleCallback(): JsonResponse
    {
        try {
            $callbackData = request()->all();

            if (!isset($callbackData['merchantCode'], $callbackData['amount'], $callbackData['merchantOrderId'], $callbackData['signature'])) {
                return response()->json(['status' => 'error', 'message' => 'Invalid callback data.'], 400);
            }

            // Re-calculate the signature
            $signature = hash('sha256', $callbackData['merchantCode'] . $callbackData['amount'] . $callbackData['merchantOrderId'] . $this->merchantKey);

            if ($callbackData['signature'] !== $signature) {
                return response()->json(['status' => 'error', 'message' => 'Invalid signature.'], 401);
            }

            // Signature is valid. You can now process the transaction.
            // Example: Update order status in your database.
            // event(new DuitkuPaymentSuccess($callbackData));

            return response()->json(['status' => 'success', 'data' => $callbackData]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'An unexpected error occurred.', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * A centralized method for sending HTTP requests to the Duitku API.
     *
     * @param string $endpoint The API endpoint.
     * @param array $params The request parameters.
     * @return array
     */
    private function sendRequest(string $endpoint, array $params): array
    {
        try {
            $response = Http::post($this->baseUrl . $endpoint, $params);

            // Throw an exception if the request failed (4xx or 5xx response)
            $response->throw();

            return [
                'status' => 'success',
                'message' => 'Request successful.',
                'data' => $response->json(),
            ];
        } catch (\Illuminate\Http\Client\RequestException $e) {
            // Handle HTTP errors specifically
            return [
                'status' => 'error',
                'message' => 'HTTP Request Failed.',
                'details' => $e->response->body() ?? $e->getMessage(),
            ];
        } catch (Exception $e) {
            // Handle other exceptions (e.g., network issues)
            return [
                'status' => 'error',
                'message' => 'An unexpected error occurred.',
                'details' => $e->getMessage(),
            ];
        }
    }
}
