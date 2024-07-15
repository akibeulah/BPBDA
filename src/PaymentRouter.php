<?php

namespace AkiBe\PaymentRouter;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

class PaymentRouter
{
    public function processPayment($amount, $currency, $metadata, $email): array
    {
        $config = Config::get('payment-routes.paymentSolutions');

        foreach ($config as $solution) {
            foreach ($solution['constraints'] as $constraint) {
                if ($constraint['currency'] === $currency &&
                    $amount >= $constraint['minAmount'] &&
                    $amount <= $constraint['maxAmount'] &&
                    $constraint['active']
                ) {
                    $provider = $solution['provider'];

                    return $this->handleProvider($provider, $amount, $currency, $metadata, $email);
                }
            }
        }

        return ['status' => 'error', 'message' => 'No suitable provider found'];
    }

    private function handleProvider($provider, $amount, $currency, $metadata, $email): array
    {
        $provider = strtolower($provider);

        switch ($provider) {
            case 'paystack':
                $paystackSecretKey = env('PAYSTACK_SECRET_KEY');
                return $this->processWithPayStack($paystackSecretKey, $amount, $currency, $metadata, $email);

            case 'flutterwave':
                $flutterwaveSecretKey = env('FLUTTERWAVE_SECRET_KEY');
                return $this->processWithFlutterwave($flutterwaveSecretKey, $amount, $currency, $metadata, $email);

            case 'nomba':
                $nombaApiKey = env('NOMBA_API_KEY');
                $nombaAccountId = env('NOMBA_ACCOUNT_ID');
                $nombaClientId = env('NOMBA_CLIENT_ID');
                return $this->processWithNomba($amount, $currency, $metadata, $email, $nombaApiKey, $nombaAccountId, $nombaClientId);

            default:
                return ['status' => 'error', 'message' => 'Provider not supported'];
        }
    }


    private function processWithPayStack($apiKey, $amount, $currency, $metadata, $email): array
    {
        $url = 'https://api.paystack.co/transaction/initialize';

        $fields = [
            'email' => $email,
            'amount' => $amount * 100,
            'metadata' => $metadata,
            'currency' => $currency
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Cache-Control' => 'no-cache',
                'Content-Type' => 'application/json',
            ])->post($url, $fields);

            $responseData = $response->json();
            if ($response->successful()) {
                return [
                    'status' => 'success',
                    'paymentLink' => isset($responseData['data']['authorization_url']) ? $responseData['data']['authorization_url'] : '',
                ];
            } else {
                return [
                    'status' => 'error',
                    'message' => isset($responseData['message']) ? $responseData['message'] : 'An error occurred while creating the payment link.',
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }


    private function processWithFlutterwave($apiKey, $amount, $currency, $metadata, $email): array
    {
        return ['status' => 'error', 'message' => 'Flutterwave not yet supported'];
    }

    private function processWithNomba($amount, $currency, $metadata, $email, $apiKey, $accountId, $clientId): array
    {
        $authTokenData = $this->getNombaAuthToken($apiKey, $accountId, $clientId);

        if ($authTokenData['status'] !== 'success') {
            return $authTokenData;
        }

        $accessToken = $authTokenData['access_token'];
        $orderData = [
            'orderReference' => time() . "_" . uniqid(),
            'customerId' => 'customer_id',
            'callbackUrl' => 'https://webhook.site/92a035f4-b270-4197-b5d1-029f9d2c8693',
            'customerEmail' => $email,
            'amount' => $amount,
            'currency' => $currency
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
                'accountId' => $accountId
            ])->post('https://api.nomba.com/v1/checkout/order', [
                'order' => $orderData,
                'tokenizeCard' => 'false'
            ]);

            $responseData = $response->json();

            if ($response->successful()) {
                return [
                    'status' => 'success',
                    'paymentLink' => $responseData['payment_link'] ?? ''
                ];
            } else {
                return [
                    'status' => 'error',
                    'message' => $responseData['message'] ?? 'An error occurred while creating the payment link.'
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    private function getNombaAuthToken($apiKey, $accountId, $clientId): array
    {
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'accountId' => $accountId
            ])->post('https://api.nomba.com/v1/auth/token/issue', [
                'grant_type' => 'client_credentials',
                'client_id' => $clientId,
                'client_secret' => $apiKey
            ]);

            $responseData = $response->json();

            if ($response->successful()) {
                return [
                    'status' => 'success',
                    'access_token' => $responseData['access_token']
                ];
            } else {
                return [
                    'status' => 'error',
                    'message' => $responseData['message'] ?? 'An error occurred while obtaining the token.'
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

}
