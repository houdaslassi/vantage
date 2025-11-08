<?php

namespace houdaslassi\Vantage\Tests;

use houdaslassi\Vantage\VantageServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            VantageServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Setup database
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Setup queue to sync for testing
        $app['config']->set('queue.default', 'sync');

        // Enable routes for testing
        $app['config']->set('vantage.routes', true);
    }
}

