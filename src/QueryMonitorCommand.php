<?php

declare(strict_types=1);

namespace Authanram\QueryMonitor;

use Illuminate\Console\Command;
use Symfony\Component\Console\Command\SignalableCommandInterface;

class QueryMonitorCommand extends Command implements SignalableCommandInterface
{
    protected $signature = 'query-monitor';

    protected $description = 'Monitor database queries';

    public function __construct(protected QueryMonitor $service)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->service->run($this);

        $this->clearScreen();

        $this->line('<fg=green>Query Monitor</> [Listening on: ' . $this->service->getUri().']');

        return self::SUCCESS;
    }

    public function printQueries(array $queries): void
    {
        $this->clearScreen();

        $duplicates = 0;

        $time = 0;

        $sql = [];

        foreach ($queries as $query) {
            $querySql = $query['sql'];

            $queryTime = $query['time'];

            if (in_array($querySql, $sql, true)) {
                $line = "<fg=red>SQL: $querySql [DUPLICATE]</>";

                $duplicates++;
            } else {
                $line = "SQL: $querySql";
            }

            $this->line($line);

            $this->warn("Time: $queryTime ms\n");

            $sql[] = $querySql;

            $time += $queryTime;
        }

        $this->line("---\n");

        $this->line('Queries: '.count($queries));

        $this->warn("Time: $time ms");

        $this->line("<fg=".($duplicates ? 'red' : 'default').">Duplicates: $duplicates</>");
    }

    protected function clearScreen(): void
    {
        $this->line("\033\143\e[3J");
    }

    public function getSubscribedSignals(): array
    {
        return [SIGINT];
    }

    public function handleSignal(int $signal): void
    {
        if ($signal !== SIGINT) {
            return;
        }

        $this->service->terminate();

        $this->newLine();

        $this->line('<fg=yellow>Query Monitor</> [Terminated]');
    }
}
