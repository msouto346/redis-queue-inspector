<?php

declare(strict_types=1);

namespace Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Pdmfc\RedisQueueInspector\Providers\RedisQueueInspectorServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            RedisQueueInspectorServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('queue.default', 'redis');
        $app['config']->set('queue.connections.redis', [
            'driver' => 'redis',
            'connection' => 'default',
        ]);
    }
}
