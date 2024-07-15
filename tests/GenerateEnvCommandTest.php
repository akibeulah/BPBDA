<?php

namespace AkiBe\PaymentRouter\Tests;

use Orchestra\Testbench\TestCase;
use AkiBe\PaymentRouter\PaymentRouterServiceProvider;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class GenerateEnvCommandTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [PaymentRouterServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $configContent = <<<'EOD'
<?php
return [
    'paymentSolutions' => [
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
    ]
];
EOD;

        File::put(base_path('PaymentRoutesConfig.php'), $configContent);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if (File::exists(base_path('.env'))) {
            File::delete(base_path('.env'));
        }

        if (File::exists(base_path('PaymentRoutesConfig.php'))) {
            File::delete(base_path('PaymentRoutesConfig.php'));
        }
    }

    /**
     * @covers \AkiBe\PaymentRouter\Console\GenerateEnvCommand::handle
     */
    public function testGenerateEnvCreatesEnvFileIfNotExists()
    {
        if (File::exists(base_path('.env'))) {
            File::delete(base_path('.env'));
        }

        Artisan::call('payment-router:generate-env');

        $this->assertTrue(File::exists(base_path('.env')));

        $envContent = File::get(base_path('.env'));
        $this->assertStringContainsString('PAYSTACK_API_KEY=', $envContent);
        $this->assertStringContainsString('NOMBA_API_KEY=', $envContent);
        $this->assertStringContainsString('NOMBA_ACCOUNT_ID=', $envContent);
        $this->assertStringContainsString('NOMBA_CLIENT_ID=', $envContent);
    }

    /**
     * @covers \AkiBe\PaymentRouter\Console\GenerateEnvCommand::handle
     */
    public function testGenerateEnvDoesNotOverwriteExistingEnvVariables()
    {
        $existingEnvContent = "EXISTING_VAR=value\n";
        File::put(base_path('.env'), $existingEnvContent);

        Artisan::call('payment-router:generate-env');

        $envContent = File::get(base_path('.env'));
        $this->assertStringContainsString('PAYSTACK_API_KEY=', $envContent);
        $this->assertStringContainsString('NOMBA_API_KEY=', $envContent);
        $this->assertStringContainsString('NOMBA_ACCOUNT_ID=', $envContent);
        $this->assertStringContainsString('NOMBA_CLIENT_ID=', $envContent);
    }
}
