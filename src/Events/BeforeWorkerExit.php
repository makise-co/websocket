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
 * @internal This event is used to stop app services, for your purposes please use WorkerExit event
 */
class BeforeWorkerExit
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
