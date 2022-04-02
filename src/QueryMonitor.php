<?php

declare(strict_types=1);

namespace Authanram\QueryMonitor;

use Exception;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use React\Socket\ConnectionInterface;
use React\Socket\Connector;
use React\Socket\SocketServer;

class QueryMonitor
{
    protected SocketServer|null $server = null;

    protected bool $isEnabled;

    protected string $uri;

    protected array $queries = [];

    public function __construct()
    {
        $this->isEnabled = config('query-monitor.enabled', false);

        $this->uri = config('query-monitor.uri', '127.0.0.1:7777');
    }

    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function listen(): void
    {
        DB::listen(fn (QueryExecuted $query) => $this->queries[] = [
            'sql' => $query->sql,
            'time' => $query->time,
        ]);

        app()->terminating(function () {
            if (count($this->queries) === 0) {
                return;
            }

            $this->send($this->queries);

            $this->queries = [];
        });
    }

    public function run(QueryMonitorCommand $command): void
    {
        $this->server = new SocketServer($this->uri);

        $this->server->on('connection', function (ConnectionInterface $connection) use($command) {
            $connection->on('data', function ($data) use ($connection, $command) {
                $command->line("\033\143\e[3J");

                $command->printQueries(json_decode($data, true, 512, JSON_THROW_ON_ERROR));

                $connection->close();
            });
        });
    }

    public function terminate(): void
    {
        $this->server->close();
    }

    public function send(array $data): void
    {
        (new Connector())->connect($this->uri)
            ->then(function (ConnectionInterface $connection) use ($data) {
                $connection->write(json_encode($data, JSON_THROW_ON_ERROR));
            }, function (Exception $exception) {
                $message = $exception->getMessage();

                if (stripos($message, 'ECONNREFUSED') !== false) {
                    return;
                }

                Log::error($message);
            });
    }
}
