<?php
/**
 * This file is part of the Makise-Co Framework
 *
 * World line: 0.571024a
 * (c) Dmitry K. <coder1994@gmail.com>
 */

declare(strict_types=1);

namespace MakiseCo\WebSocket;

class WebSocketMessage
{
    private int $fd;
    private string $payload;
    private int $opcode;

    /**
     * Message constructor.
     * @param int $fd
     * @param string $payload
     * @param int $opcode
     */
    public function __construct(int $fd, string $payload, int $opcode = 0)
    {
        $this->fd = $fd;
        $this->payload = $payload;
        $this->opcode = $opcode;
    }

    public function getFd(): int
    {
        return $this->fd;
    }

    public function getPayload(): string
    {
        return $this->payload;
    }

    public function getOpcode(): int
    {
        return $this->opcode;
    }
}
