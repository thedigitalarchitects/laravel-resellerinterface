<?php

namespace Tda\LaravelResellerinterface;

use Illuminate\Support\ServiceProvider;

class ResellerinterfaceServiceProvider extends ServiceProvider
{
    public function registeringPackage()
    {
        $this->app->alias(Resellerinterface::class, 'laravel-resellerinterface');

    }
}
