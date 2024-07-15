<?php

namespace AkiBe\PaymentRouter\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateEnvCommand extends Command
{
    protected $signature = 'payment-router:generate-env';
    protected $description = 'Generate environment variables for payment router';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $envFilePath = base_path('.env');
        $configFilePath = base_path('PaymentRoutesConfig.php');

        if (!File::exists($envFilePath)) {
            File::put($envFilePath, '');
            $this->info('.env file created.');
        }

        if (!File::exists($configFilePath)) {
            $this->error('PaymentRoutesConfig.php not found.');
            return;
        }

        $config = require $configFilePath;
        $envContent = File::get($envFilePath);

        foreach ($config['paymentSolutions'] as $solution) {
            $provider = strtolower($solution['provider']);
            switch ($provider) {
                case 'paystack':
                    $envContent .= "\nPAYSTACK_API_KEY=\n";
                    break;
                case 'flutterwave':
                    $envContent .= "\nFLUTTERWAVE_API_KEY=\n";
                    break;
                case 'nomba':
                    $envContent .= "\nNOMBA_API_KEY=\nNOMBA_ACCOUNT_ID=\nNOMBA_CLIENT_ID=\n";
                    break;
                default:
                    $this->warn("No environment variables defined for provider: {$solution['provider']}");
                    break;
            }
        }

        File::put($envFilePath, $envContent);
        $this->info('Environment variables generated.');
    }
}
