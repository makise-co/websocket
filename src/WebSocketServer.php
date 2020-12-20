<?php
/**
 * This file is part of the Makise-Co Framework
 *
 * World line: 0.571024a
 * (c) Dmitry K. <coder1994@gmail.com>
 */

declare(strict_types=1);

namespace MakiseCo\WebSocket;

use Closure;
use MakiseCo\WebSocket\MessageHandler\MessageHandlerFactoryInterface;
use MakiseCo\WebSocket\MessageHandler\MessageHandlerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Swoole\Coroutine;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server as SwooleServer;
use Throwable;

class WebSocketServer
{
    public const MODE_MAIN = 'master';
    public const MODE_MANAGER = 'manager';
    public const MODE_WORKER = 'worker';

    protected string $mode = self::MODE_MAIN;

    protected SwooleServer $server;
    protected EventDispatcherInterface $eventDispatcher;

    protected MessageHandlerFactoryInterface $requestHandlerFactory;
    protected MessageHandlerInterface $requestHandler;

    protected WebSocketConfig $config;

    protected string $appName;

    protected array $connections = [];

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        MessageHandlerFactoryInterface $requestHandlerFactory,
        WebSocketConfig $config,
        string $appName
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->requestHandlerFactory = $requestHandlerFactory;

        $this->config = $config;
        $this->appName = $appName;
    }

    public function getCurrentWorkerPid(): int
    {
        return $this->server->worker_pid;
    }

    public function getCurrentWorkerId(): int
    {
        return $this->server->worker_id;
    }

    public function getConfig(): WebSocketConfig
    {
        return $this->config;
    }

    public function start(string $host, int $port): void
    {
        $this->server = new SwooleServer($host, $port, SWOOLE_PROCESS, SWOOLE_SOCK_TCP);
        $this->server->set(
            array_merge(
                [
                    'daemonize' => false,
                    'worker_num' => 1,
                    'send_yield' => true,
                ],
                $this->config->getOptions()
            )
        );

        $this->server->on(
            'Start',
            function (SwooleServer $server) {
                $this->setProcessName('master process');

                $this->eventDispatcher->dispatch(new Events\ServerStarted());
            }
        );

        $this->server->on(
            'ManagerStart',
            function (SwooleServer $server) {
                $this->mode = self::MODE_MANAGER;

                $this->setProcessName('manager process');

                $this->eventDispatcher->dispatch(new Events\ManagerStarted());
            }
        );

        $this->server->on(
            'WorkerStart',
            function (SwooleServer $server, int $workerId) {
                $this->mode = self::MODE_WORKER;

                $this->setProcessName('worker process');

                try {
                    // dispatch before worker started event for early services initialization (before routes resolved)
                    $this->eventDispatcher->dispatch(new Events\BeforeWorkerStarted($workerId, $this));

                    // request handler and their dependencies should be resolved before worker will start requests processing
                    $this->requestHandler = $this->requestHandlerFactory->create();

                    // dispatch application level WorkerStarted event
                    $this->eventDispatcher->dispatch(new Events\WorkerStarted($workerId));
                } catch (Throwable $e) {
                    // stop server if worker cannot be started (to prevent infinite loop)
                    Coroutine::defer(fn() => $server->shutdown());

                    throw $e;
                }
            }
        );

        $this->server->on(
            'WorkerStop',
            function (SwooleServer $server, int $workerId) {
                $this->mode = self::MODE_WORKER;

                // dispatch before worker exit event to stop services
                $this->eventDispatcher->dispatch(new Events\BeforeWorkerExit($workerId, $this));

                $this->eventDispatcher->dispatch(new Events\WorkerStopped($workerId));
            }
        );

        $this->server->on(
            'WorkerExit',
            function (SwooleServer $server, int $workerId) {
                $this->mode = self::MODE_WORKER;

                $this->eventDispatcher->dispatch(new Events\WorkerExit($workerId));
            }
        );

        $this->server->on(
            'Shutdown',
            function (SwooleServer $server) {
                $this->eventDispatcher->dispatch(new Events\ServerShutdown());
            }
        );

        $this->server->on('Open', Closure::fromCallable([$this, 'onOpen']));
        $this->server->on('Close', Closure::fromCallable([$this, 'onClose']));
        $this->server->on('Message', Closure::fromCallable([$this, 'onMessage']));

        $this->server->start();
    }

    public function stop(): void
    {
        $this->server->shutdown();
    }

    public function send(int $fd, string $data, int $opcode = 0): void
    {
        $this->server->push($fd, $data, $opcode);
    }

    /**
     * Broadcast message to filtered client list
     *
     * @param string $data
     * @param Closure $filter
     */
    public function broadcastTo(string $data, Closure $filter): void
    {
        foreach ($this->server->connections as $fd) {
            if ($this->server->isEstablished($fd) && $filter($fd, $this)) {
                $this->server->push($fd, $data);
            }
        }
    }

    /**
     * Broadcast message to all clients
     *
     * @param string $data
     */
    public function broadcast(string $data): void
    {
        foreach ($this->server->connections as $fd) {
            if ($this->server->isEstablished($fd)) {
                $this->server->push($fd, $data);
            }
        }
    }

    protected function onOpen(SwooleServer $server, \Swoole\Http\Request $request): void
    {
        $this->eventDispatcher->dispatch(new Events\OnOpen($this, $request->fd));
    }

    protected function onClose(SwooleServer $server, int $fd): void
    {
        $this->eventDispatcher->dispatch(new Events\OnClose($this, $fd));
    }

    protected function onMessage(SwooleServer $server, Frame $frame): void
    {
        $message = new WebSocketMessage($frame->fd, $frame->data, $frame->opcode);

        $this->requestHandler->handle($message);
    }

    protected function setProcessName(string $name): void
    {
        if (!empty($this->appName)) {
            swoole_set_process_name("{$this->appName} {$name}");

            return;
        }

        swoole_set_process_name($name);
    }
}
