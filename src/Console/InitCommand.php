<?php

namespace AkiBe\PaymentRouter\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InitCommand extends Command
{
    protected $signature = 'payment-router:init';
    protected $description = 'Initialize the payment router and generate PaymentRoutesConfig.php';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $configContent = <<<'EOD'
<?php
return [
    'paymentSolutions' => [
        [
            'name' => '',
            'provider' => '',
            'constraints' => [
                [
                    'currency' => '',
                    'minAmount' => 0,
                    'maxAmount' => 100,
                    'active' => true,
                ],
            ],
        ],
    ],
];
EOD;

        $filePath = base_path('PaymentRoutesConfig.php');

        if (!File::exists($filePath)) {
            File::put($filePath, $configContent);
            $this->info('PaymentRoutesConfig.php generated successfully.');
        } else {
            $this->warn('PaymentRoutesConfig.php already exists.');
        }
    }
}
