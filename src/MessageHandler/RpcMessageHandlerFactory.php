<?php
/**
 * This file is part of the Makise-Co Framework
 *
 * World line: 0.571024a
 * (c) Dmitry K. <coder1994@gmail.com>
 */

declare(strict_types=1);

namespace MakiseCo\WebSocket\MessageHandler;

use MakiseCo\WebSocket\WebSocketServer;
use Psr\Container\ContainerInterface;

class RpcMessageHandlerFactory implements MessageHandlerFactoryInterface
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function create(): MessageHandlerInterface
    {
        return new RpcMessageHandler(
            $this->container->get(WebSocketServer::class)
        );
    }
}
