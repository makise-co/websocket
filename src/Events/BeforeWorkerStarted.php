<?php
/**
 * This file is part of the Makise-Co Framework
 *
 * World line: 0.571024a
 * (c) Dmitry K. <coder1994@gmail.com>
 */

declare(strict_types=1);

namespace MakiseCo\WebSocket\Events;

use MakiseCo\WebSocket\WebSocketServer;

/**
 * @internal This event is used to start app services, for your purposes please use WorkerStarted event
 */
class BeforeWorkerStarted
{
    private int $workerId;
    private WebSocketServer $server;

    public function __construct(int $workerId, WebSocketServer $server)
    {
        $this->workerId = $workerId;
        $this->server = $server;
    }

    public function getWorkerId(): int
    {
        return $this->workerId;
    }

    public function getServer(): WebSocketServer
    {
        return $this->server;
    }
}
