<?php

namespace Spatie\NovaTranslatable\Tests;

use Dotenv\Dotenv;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    public function setUp()
    {
        parent::setUp();

        Route::middlewareGroup('nova', []);

        $this->setUpDatabase();
    }

    protected function getEnvironmentSetUp($app)
    {
        //If we're not in travis, load our local .env file
        if (empty(getenv('CI'))) {
            $envPath = realpath(__DIR__.'/..');

            if (! file_exists($envPath)) {
                $dotenv = new Dotenv($envPath);
                $dotenv->load();
            }
        }

        $app['config']->set('database.default', 'mysql');
        $app['config']->set('database.connections.mysql', [
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'database' => env('DB_DATABASE', 'nova_translatable'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
            'strict' => false,
        ]);
    }

    protected function setUpDatabase()
    {
        $this->artisan('migrate:fresh');
    }
}
