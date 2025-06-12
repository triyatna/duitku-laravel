<?php

namespace Triyatna\DuitkuLaravel;

use Illuminate\Support\Facades\Http;

class Duitku
{
    protected $merchantKey;
    protected $merchantCode;
    protected $isSandbox;
    protected $baseUrl;

    public function __construct($merchantKey, $merchantCode, $isSandbox = false)
    {
        $this->merchantKey = $merchantKey;
        $this->merchantCode = $merchantCode;
        $this->isSandbox = $isSandbox;
        $this->setBaseUrl();
    }

    /**
     * Mengatur Base URL berdasarkan mode sandbox atau production.
     */
    protected function setBaseUrl()
    {
        $this->baseUrl = $this->isSandbox
            ? 'https://sandbox.duitku.com/webapi'
            : 'https://passport.duitku.com/webapi';
    }

    /**
     * Mendapatkan daftar metode pembayaran yang tersedia.
     */
    public function getPaymentMethods($amount)
    {
        $datetime = date('Y-m-d H:i:s');
        $signature = hash('sha256', $this->merchantCode . $amount . $datetime . $this->merchantKey);

        $response = Http::post($this->baseUrl . '/api/merchant/paymentmethod/getpaymentmethod', [
            'merchantcode' => $this->merchantCode,
            'amount' => $amount,
            'datetime' => $datetime,
            'signature' => $signature,
        ]);

        return $response->json();
    }

    /**
     * Membuat permintaan pembayaran (invoice).
     */
    public function createInvoice($paymentAmount, $paymentMethod, $merchantOrderId, $productDetails, $customerVaName, $email, $phoneNumber, $callbackUrl, $returnUrl, $expiryPeriod = 1440)
    {
        $signature = hash('sha256', $this->merchantCode . $merchantOrderId . $paymentAmount . $this->merchantKey);

        $params = [
            'merchantCode'      => $this->merchantCode,
            'paymentAmount'     => $paymentAmount,
            'paymentMethod'     => $paymentMethod,
            'merchantOrderId'   => $merchantOrderId,
            'productDetails'    => $productDetails,
            'customerVaName'    => $customerVaName,
            'email'             => $email,
            'phoneNumber'       => $phoneNumber,
            'itemDetails'       => null, // Sesuaikan jika perlu
            'customerDetail'    => null, // Sesuaikan jika perlu
            'callbackUrl'       => $callbackUrl,
            'returnUrl'         => $returnUrl,
            'signature'         => $signature,
            'expiryPeriod'      => $expiryPeriod,
        ];

        $response = Http::post($this->baseUrl . '/api/merchant/v2/inquiry', $params);

        return $response->json();
    }

    /**
     * Memeriksa status transaksi.
     */
    public function checkTransactionStatus($merchantOrderId)
    {
        $signature = hash('sha256', $this->merchantCode . $merchantOrderId . $this->merchantKey);

        $response = Http::post($this->baseUrl . '/api/merchant/transactionStatus', [
            'merchantCode' => $this->merchantCode,
            'merchantOrderId' => $merchantOrderId,
            'signature' => $signature,
        ]);

        return $response->json();
    }

    /**
     * Menangani callback dari Duitku.
     */
    public function handleCallback()
    {
        $post = file_get_contents('php://input');
        $data = json_decode($post, true);

        if (!isset($data['merchantCode'], $data['amount'], $data['merchantOrderId'], $data['signature'])) {
            return ['status' => 'error', 'message' => 'Invalid callback data'];
        }

        $signature = hash('sha256', $data['merchantCode'] . $data['amount'] . $data['merchantOrderId'] . $this->merchantKey);

        if ($data['signature'] !== $signature) {
            return ['status' => 'error', 'message' => 'Invalid signature'];
        }

        // Signature valid, proses transaksi di sini
        // Misalnya, update status order di database Anda

        return ['status' => 'success', 'data' => $data];
    }
}
