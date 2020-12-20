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

class OnClose
{
    private WebSocketServer $server;
    private int $fd;

    public function __construct(WebSocketServer $server, int $fd)
    {
        $this->server = $server;
        $this->fd = $fd;
    }

    public function getServer(): WebSocketServer
    {
        return $this->server;
    }

    public function getFd(): int
    {
        return $this->fd;
    }
}
