<?php

namespace App\Providers;

use App\Services\LLM\PrismAdapter;
use Illuminate\Support\ServiceProvider;

class PrismServiceProvider extends Service\Provider
{
    public function register(): void
    {
        $this->app->singleton(PrismAdapter::class, function ($app) {
            $adapter = new PrismAdapter();
            
            // If this is a web request and user is authenticated, set the user
            if ($app->has('request') && $app['request']->user()) {
                $adapter->setUser($app['request']->user());
            }
            
            return $adapter;
        });
    }

    public function boot(): void
    {
        //
    }
}
