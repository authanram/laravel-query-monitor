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

        /** @var QueryMonitor $service */
        $service = $this->app[QueryMonitor::class];

        $this->bootQueryMonitor($service);

        $this->bootRunningInConsole($service);
    }

    protected function bootQueryMonitor(QueryMonitor $service): void
    {
        if ($service->isEnabled() === false) {
            return;
        }

        $service->listen();
    }

    protected function bootRunningInConsole(QueryMonitor $service): void
    {
        if (($service->isEnabled() && $this->app->runningInConsole()) === false) {
            return;
        }

        $this->commands([
            QueryMonitorCommand::class,
        ]);
    }
}
