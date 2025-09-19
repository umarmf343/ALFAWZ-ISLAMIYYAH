<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;

trait CreatesApplication
{
    public function createApplication()
    {
        $_SERVER['APP_ENV'] = 'testing';
        $_ENV['APP_ENV'] = 'testing';

        $_SERVER['LARAVEL_ENVIRONMENT_FILE'] = '.env.testing';
        $_ENV['LARAVEL_ENVIRONMENT_FILE'] = '.env.testing';

        $app = require __DIR__ . '/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');

        return $app;
    }
}
