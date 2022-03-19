<?php

declare(strict_types=1);

namespace Authanram\QueryMonitor;

use Illuminate\Console\Command;

class QueryMonitorCommand extends Command
{
    protected $signature = 'query-monitor';

    protected $description = 'Monitor database queries';

    public function handle(): int
    {
        $service = resolve(QueryMonitor::class);

        $service->run($this);

        $this->clearScreen();

        $this->line('<fg=green>Query Monitor</> [Listening on: ' . $service->getUri().']');

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
}
