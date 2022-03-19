<?php

declare(strict_types=1);

namespace Authanram\QueryMonitor;

use Illuminate\Support\ServiceProvider;

class QueryMonitorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(QueryMonitor::class, QueryMonitor::class);
    }

    public function boot(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/query-monitor.php', 'query-monitor');

        $this->bootQueryMonitor();

        $this->bootRunningInConsole();
    }

    protected function bootQueryMonitor(): void
    {
        /** @var QueryMonitor $service */
        $service = $this->app[QueryMonitor::class];

        if ($service->isEnabled() === false) {
            return;
        }

        $service->listen();
    }

    protected function bootRunningInConsole(): void
    {
        if ($this->app->runningInConsole() === false) {
            return;
        }

        $this->commands([
            QueryMonitorCommand::class,
        ]);
    }
}
