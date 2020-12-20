<?php
/**
 * This file is part of the Makise-Co Framework
 *
 * World line: 0.571024a
 * (c) Dmitry K. <coder1994@gmail.com>
 */

declare(strict_types=1);

namespace MakiseCo\WebSocket\MessageHandler;

use MakiseCo\WebSocket\WebSocketMessage;

interface MessageHandlerInterface
{
    /**
     * @param WebSocketMessage $message incoming message
     */
    public function handle(WebSocketMessage $message): void;
}
