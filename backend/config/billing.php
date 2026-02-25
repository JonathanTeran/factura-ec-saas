<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Payment Gateway
    |--------------------------------------------------------------------------
    |
    | The default payment gateway to use for processing payments.
    | Supported: "stripe", "paypal", "paymentez"
    |
    */

    'default_gateway' => env('BILLING_GATEWAY', 'stripe'),

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    |
    | The default currency for all billing operations.
    |
    */

    'currency' => env('BILLING_CURRENCY', 'USD'),

    /*
    |--------------------------------------------------------------------------
    | Stripe Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Stripe payment gateway.
    |
    */

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'webhook_tolerance' => env('STRIPE_WEBHOOK_TOLERANCE', 300),
    ],

    /*
    |--------------------------------------------------------------------------
    | PayPal Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for PayPal payment gateway.
    |
    */

    'paypal' => [
        'mode' => env('PAYPAL_MODE', 'sandbox'), // sandbox or live
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'client_secret' => env('PAYPAL_CLIENT_SECRET'),
        'webhook_id' => env('PAYPAL_WEBHOOK_ID'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Paymentez Configuration (Ecuador)
    |--------------------------------------------------------------------------
    |
    | Configuration for Paymentez payment gateway, commonly used in Ecuador.
    |
    */

    'paymentez' => [
        'environment' => env('PAYMENTEZ_ENVIRONMENT', 'stg'), // stg or prod
        'app_code' => env('PAYMENTEZ_APP_CODE'),
        'app_key' => env('PAYMENTEZ_APP_KEY'),
        'server_app_code' => env('PAYMENTEZ_SERVER_APP_CODE'),
        'server_app_key' => env('PAYMENTEZ_SERVER_APP_KEY'),
        'endpoints' => [
            'stg' => 'https://ccapi-stg.paymentez.com',
            'prod' => 'https://ccapi.paymentez.com',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Trial Period
    |--------------------------------------------------------------------------
    |
    | Default trial period settings for new subscriptions.
    |
    */

    'trial' => [
        'enabled' => env('BILLING_TRIAL_ENABLED', false),
        'days' => env('BILLING_TRIAL_DAYS', 0),
        'require_payment_method' => env('BILLING_TRIAL_REQUIRE_PAYMENT', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Grace Period
    |--------------------------------------------------------------------------
    |
    | Grace period settings for past due subscriptions.
    |
    */

    'grace_period' => [
        'enabled' => true,
        'days' => env('BILLING_GRACE_PERIOD_DAYS', 7),
    ],

    /*
    |--------------------------------------------------------------------------
    | Invoice Settings
    |--------------------------------------------------------------------------
    |
    | Settings for invoice generation.
    |
    */

    'invoice' => [
        'prefix' => 'INV-',
        'logo' => null,
        'company_name' => env('BILLING_COMPANY_NAME', config('app.name')),
        'company_address' => env('BILLING_COMPANY_ADDRESS'),
        'company_phone' => env('BILLING_COMPANY_PHONE'),
        'company_email' => env('BILLING_COMPANY_EMAIL'),
        'company_ruc' => env('BILLING_COMPANY_RUC'),
        'tax_rate' => env('BILLING_TAX_RATE', 12),
        'footer_text' => 'Gracias por su preferencia.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Subscription Settings
    |--------------------------------------------------------------------------
    |
    | General subscription configuration.
    |
    */

    'subscription' => [
        'prorate' => true,
        'prorate_on_upgrade' => true,
        'prorate_on_downgrade' => false,
        'allow_multiple' => false,
        'cancel_at_period_end' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Retry Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for failed payment retries.
    |
    */

    'retry' => [
        'max_attempts' => 3,
        'days_between_attempts' => [1, 3, 7],
        'notify_on_failure' => true,
        'suspend_after_failures' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Referral Program
    |--------------------------------------------------------------------------
    |
    | Settings for the referral commission program.
    |
    */

    'referral' => [
        'enabled' => env('BILLING_REFERRAL_ENABLED', true),
        'commission_percentage' => env('BILLING_REFERRAL_COMMISSION', 10),
        'commission_duration_months' => env('BILLING_REFERRAL_DURATION', 12),
        'min_payout_amount' => env('BILLING_REFERRAL_MIN_PAYOUT', 50),
        'cookie_duration_days' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Coupon Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for discount coupons.
    |
    */

    'coupon' => [
        'code_length' => 8,
        'code_characters' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789',
        'allow_stacking' => false,
        'max_per_subscription' => 1,
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    |
    | Email notification settings for billing events.
    |
    */

    'notifications' => [
        'payment_success' => true,
        'payment_failed' => true,
        'subscription_created' => true,
        'subscription_canceled' => true,
        'subscription_renewed' => true,
        'trial_ending' => true,
        'trial_ending_days_before' => 3,
        'invoice_available' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for payment gateway webhooks.
    |
    */

    'webhooks' => [
        'path' => 'webhooks/billing',
        'middleware' => ['api'],
        'log_events' => env('BILLING_LOG_WEBHOOKS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Plan Limits
    |--------------------------------------------------------------------------
    |
    | Default limits for free/basic plans.
    |
    */

    'limits' => [
        'free' => [
            'documents_per_month' => 10,
            'users' => 1,
            'companies' => 1,
            'branches_per_company' => 1,
            'storage_mb' => 100,
        ],
    ],

];
