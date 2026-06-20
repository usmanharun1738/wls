<?php

namespace App\Providers;

use AfricasTalking\SDK\AfricasTalking;
use Illuminate\Support\ServiceProvider;

class AfricasTalkingServiceProvider extends ServiceProvider
{
    /**
     * Register the Africa's Talking SDK as a singleton.
     */
    public function register(): void
    {
        $this->app->singleton(AfricasTalking::class, function () {
            $username = config('services.africastalking.username');
            $apiKey = config('services.africastalking.api_key');

            if (blank($username) || blank($apiKey)) {
                throw new \RuntimeException(
                    'Africa\'s Talking credentials are not configured. Set AFRICASTALKING_USERNAME and AFRICASTALKING_API_KEY in .env.'
                );
            }

            return new AfricasTalking($username, $apiKey);
        });
    }
}
