# Payment Router Library
## Built for Laravel Backend Applications

This library is a payment router built using PHP, designed to showcase proficiency in PHP. The library simplifies setting up payment gateways by generating a config file where gateways and constraints for the use of each gateway are provided.

## Installation

Add the package and repository to your `composer.json`:

```json
{
    ...
    "require": {
        ...
        "akibe/payment-router": "dev-main"
    },
    "repositories": [
        ...
        {
            "type": "vcs",
            "url": "git@github.com:akibeulah/BPBDA.git"
        }
    ],
    ...
}
```

Then run:

```bash
composer update
```

## Usage

First, run the initialization command to generate the config file:

```bash
php artisan payment-router:init
```

The config file will look like this and can be updated to use any of the currently supported currencies with min and max amounts:

```php
<?php
return [
    'paymentSolutions' => [
        [
            'name' => 'PayWay',
            'provider' => 'PayWay',
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
            'name' => 'WavePay',
            'provider' => 'WavePay',
            'constraints' => [
                [
                    'currency' => 'USD',
                    'minAmount' => 10,
                    'maxAmount' => 10000,
                    'active' => true,
                ],
            ],
        ],
    ],
];
```

After setting up the config file, run the second command to generate or update the .env file with the required information to use the library:

```bash
php artisan payment-router:generate-env
```

Once this is done successfully, the `processPayment` function can be used to generate a payment link based on the passed-in constraints. It also collects metadata for adding extra information to the payload.

```php
use Akibe\PaymentRouter;

// Example usage:
$paymentRouter = new PaymentRouter();
$paymentLink = $paymentRouter->processPayment($amount, $currency, $metadata);
```