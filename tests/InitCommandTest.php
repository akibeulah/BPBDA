<?php

namespace AkiBe\PaymentRouter\Tests;

use Orchestra\Testbench\TestCase;
use AkiBe\PaymentRouter\PaymentRouterServiceProvider;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class InitCommandTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [PaymentRouterServiceProvider::class];
    }

    /**
     * @covers \AkiBe\PaymentRouter\Console\InitCommand::handle
     */
    public function testInitCommandGeneratesConfigFile()
    {
        $filePath = base_path('PaymentRoutesConfig.php');

        if (File::exists($filePath)) {
            File::delete($filePath);
        }

        Artisan::call('payment-router:init');

        $this->assertTrue(File::exists($filePath));

        File::delete($filePath);
    }

    /**
     * @covers \AkiBe\PaymentRouter\Console\InitCommand::handle
     */
    public function testInitCommandDoesNotOverwriteExistingFile()
    {
        $filePath = base_path('PaymentRoutesConfig.php');
        $dummyContent = '<?php return ["dummy" => "content"];';

        File::put($filePath, $dummyContent);

        Artisan::call('payment-router:init');

        $this->assertEquals($dummyContent, File::get($filePath));

        File::delete($filePath);
    }
}
