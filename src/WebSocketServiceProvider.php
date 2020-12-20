<?php
/**
 * This file is part of the Makise-Co Framework
 *
 * World line: 0.571024a
 * (c) Dmitry K. <coder1994@gmail.com>
 */

declare(strict_types=1);

namespace MakiseCo\WebSocket;

use DI\Container;
use MakiseCo\Bootstrapper;
use MakiseCo\Config\ConfigRepositoryInterface;
use MakiseCo\Providers\ServiceProviderInterface;
use MakiseCo\WebSocket\MessageHandler\MessageHandlerFactoryInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

class WebSocketServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $container->set(
            WebSocketConfig::class,
            function (ConfigRepositoryInterface $config) {
                return WebSocketConfig::fromArray($config->get('websocket', []));
            }
        );

        $container->set(
            WebSocketServer::class,
            static function (Container $container, ConfigRepositoryInterface $config) {
                $wsConfig = $container->get(WebSocketConfig::class);
                $factory = $container->get($wsConfig->getMessageHanlderFactory());
                if ($factory instanceof MessageHandlerFactoryInterface) {
                    throw new \InvalidArgumentException(
                        "Message handler factory must implement MessageHandlerFactoryInterface"
                    );
                }

                return new WebSocketServer(
                    $container->get(EventDispatcherInterface::class),
                    $container->get(MessageHandlerFactoryInterface::class),
                    $factory,
                    $config->get('app.name', 'makise-co')
                );
            }
        );

        // install services bootstrapper
        $container->get(EventDispatcher::class)->addListener(
            Events\BeforeWorkerStarted::class,
            static function (Events\BeforeWorkerStarted $event) use ($container) {
                $services = $event->getServer()->getConfig()->getServices();

                $container->get(Bootstrapper::class)->init($services);
            }
        );
        $container->get(EventDispatcher::class)->addListener(
            Events\BeforeWorkerExit::class,
            static function (Events\BeforeWorkerExit $event) use ($container) {
                $services = $event->getServer()->getConfig()->getServices();

                $container->get(Bootstrapper::class)->stop($services);
            }
        );
    }
}
