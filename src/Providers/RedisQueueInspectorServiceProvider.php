<?php

declare(strict_types=1);

namespace Pdmfc\RedisQueueInspector\Providers;

use Illuminate\Support\ServiceProvider;
use Pdmfc\RedisQueueInspector\Commands\ShowJobsCommand;

final class RedisQueueInspectorServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ShowJobsCommand::class,
            ]);
        }
    }
}
