<?php

namespace PatOui\Scout;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Laravel\Scout\EngineManager;

class TestingScoutServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app[EngineManager::class]->extend('testing', function () {
            return new Engines\TestingEngine(
                new Filesystem,
                config('scout')
            );
        });
    }
}
