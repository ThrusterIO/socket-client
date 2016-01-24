<?php

namespace Thruster\Component\SocketClient;

use RuntimeException;
use Thruster\Component\EventLoop\EventLoopInterface;
use Thruster\Component\Promise\FulfilledPromise;
use Thruster\Component\Promise\PromiseInterface;
use Thruster\Component\Promise\RejectedPromise;
use Thruster\Component\Stream\Stream;

/**
 * Class UnixConnector
 *
 * @package Thruster\Component\SocketClient
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class UnixConnector implements ConnectorInterface
{
    private $loop;

    public function __construct(EventLoopInterface $loop)
    {
        $this->loop = $loop;
    }

    public function create(string $path, int $unusedPort = 0) : PromiseInterface
    {
        $resource = @stream_socket_client('unix://' . $path, $errNo, $errStr, 1.0);

        if (false === $resource) {
            return new RejectedPromise(
                new RuntimeException(
                    sprintf('Unable to connect to unix domain socket "%s": %s', $path, $errStr),
                    $errNo
                )
            );
        }

        return new FulfilledPromise(new Stream($resource, $this->loop));
    }
}
