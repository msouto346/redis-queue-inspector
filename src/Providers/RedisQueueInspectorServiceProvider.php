<?php

declare(strict_types=1);

namespace Msouto\RedisQueueInspector\Providers;

use Illuminate\Support\ServiceProvider;
use Msouto\RedisQueueInspector\Commands\ShowJobsCommand;

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
