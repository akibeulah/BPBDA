<?php

namespace AkiBe\PaymentRouter\Tests;

use Orchestra\Testbench\TestCase;
use AkiBe\PaymentRouter\PaymentRouter;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

class PaymentRouterTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [];
    }

    /**
     * @covers \AkiBe\PaymentRouter\Console\PaymentRouter::processPayment
     */
    public function testProcessPaymentWithValidConstraints()
    {
        Config::shouldReceive('get')
            ->once()
            ->with('payment-routes.paymentSolutions')
            ->andReturn([
                [
                    'name' => 'PayStack',
                    'provider' => 'PayStack',
                    'constraints' => [
                        [
                            'currency' => 'NGN',
                            'minAmount' => 100,
                            'maxAmount' => 10000000,
                            'active' => true,
                        ],
                    ],
                ],
                [
                    'name' => 'Nomba',
                    'provider' => 'nomba',
                    'constraints' => [
                        [
                            'currency' => 'NGN',
                            'minAmount' => 10000000,
                            'maxAmount' => 1000000000,
                            'active' => true,
                        ],
                    ],
                ]
            ]);


        Http::fake([
            'https://api.paystack.co/*' => Http::response([
                'status' => 'success',
                'paymentLink' => 'https://paystack.com/link',
            ], 200),
        ]);

        $paymentRouter = new PaymentRouter();

        $result = $paymentRouter->processPayment(500, 'NGN', ['order_id' => '123'], 'test@example.com');
        $this->assertEquals('success', $result['status']);
        $this->assertEquals('https://paystack.com/link', $result['paymentLink']);

        Http::fake([
            'https://api.nomba.com/*' => Http::response([
                'status' => 'success',
                'payment_link' => 'https://nomba.com/link',
            ], 200),
        ]);
        $result = $paymentRouter->processPayment(15000000, 'NGN', ['order_id' => '456'], 'test@example.com');
        $this->assertEquals('success', $result['status']);
        $this->assertEquals('https://nomba.com/link', $result['paymentLink']);
    }

    /**
     * @covers \AkiBe\PaymentRouter\Console\PaymentRouter::processPayment
     */
    public function testProcessPaymentNoSuitableProviderFound()
    {
        Config::shouldReceive('get')
            ->once()
            ->with('payment-routes.paymentSolutions')
            ->andReturn([
                [
                    'name' => 'PayStack',
                    'provider' => 'PayStack',
                    'constraints' => [
                        [
                            'currency' => 'USD',
                            'minAmount' => 100,
                            'maxAmount' => 10000000,
                            'active' => true,
                        ],
                    ],
                ]
            ]);

        $paymentRouter = new PaymentRouter();

        // Test with unsupported currency
        $result = $paymentRouter->processPayment(500, 'NGN', ['order_id' => '123'], 'test@example.com');
        $this->assertEquals('error', $result['status']);
        $this->assertEquals('No suitable provider found', $result['message']);
    }

    /**
     * @covers \AkiBe\PaymentRouter\Console\PaymentRouter::processPayment
     */
    public function testProcessPaymentProviderNotSupported()
    {
        Config::shouldReceive('get')
            ->once()
            ->with('payment-routes.paymentSolutions')
            ->andReturn([
                [
                    'name' => 'UnsupportedProvider',
                    'provider' => 'UnsupportedProvider',
                    'constraints' => [
                        [
                            'currency' => 'NGN',
                            'minAmount' => 100,
                            'maxAmount' => 10000000,
                            'active' => true,
                        ],
                    ],
                ]
            ]);

        $paymentRouter = new PaymentRouter();

        $result = $paymentRouter->processPayment(500, 'NGN', ['order_id' => '123'], 'test@example.com');
        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Provider not supported', $result['message']);
    }
}
