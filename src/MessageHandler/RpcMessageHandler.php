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
use MakiseCo\WebSocket\WebSocketServer;

class RpcMessageHandler implements MessageHandlerInterface
{
    private WebSocketServer $server;

    public function __construct(WebSocketServer $server)
    {
        $this->server = $server;
    }

    public function handle(WebSocketMessage $message): void
    {
        // parse payload
        $payload = $message->getPayload();

        try {
            $decoded = \json_decode($payload, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $this->server->send(
                $message->getFd(),
                \json_encode(
                    [
                        'message' => 'payload_parse_error',
                        'reason' => $e->getMessage(),
                    ],
                    \JSON_THROW_ON_ERROR
                ),
                0
            );
        }

        $this->server->send(
            $message->getFd(),
            $message->getPayload()
        );
    }
}
