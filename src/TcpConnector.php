<?php

namespace Thruster\Component\SocketClient;

use Thruster\Component\EventLoop\EventLoopInterface;
use Thruster\Component\Promise\Deferred;
use Thruster\Component\Promise\FulfilledPromise;
use Thruster\Component\Promise\PromiseInterface;
use Thruster\Component\Promise\RejectedPromise;
use Thruster\Component\SocketClient\Exception\ConnectionException;
use Thruster\Component\Stream\Stream;

/**
 * Class TcpConnector
 *
 * @package Thruster\Component\SocketClient
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class TcpConnector implements ConnectorInterface
{
    /**
     * @var EventLoopInterface
     */
    private $loop;

    public function __construct(EventLoopInterface $loop)
    {
        $this->loop = $loop;
    }

    public function create(string $ip, int $port) : PromiseInterface
    {
        if (false === filter_var($ip, FILTER_VALIDATE_IP)) {
            return new RejectedPromise(
                new \InvalidArgumentException(
                    sprintf(
                        'Given parameter "%s" is not a valid IP',
                        $ip
                    )
                )
            );
        }

        $url = $this->getSocketUrl($ip, $port);

        $socket = @stream_socket_client($url, $errNo, $errStr, 0, STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT);

        if (false === $socket) {
            return new RejectedPromise(
                new \RuntimeException(
                    sprintf("Connection to %s:%d failed: %s", $ip, $port, $errStr),
                    $errNo
                )
            );
        }

        stream_set_blocking($socket, 0);

        // wait for connection

        return $this
            ->waitForStreamOnce($socket)
            ->then([$this, 'checkConnectedSocket'])
            ->then([$this, 'handleConnectedSocket']);
    }

    private function waitForStreamOnce($stream) : PromiseInterface
    {
        $deferred = new Deferred();

        $this->loop->addWriteStream($stream, function ($stream) use ($deferred) {
            $this->loop->removeWriteStream($stream);

            $deferred->resolve($stream);
        });

        return $deferred->promise();
    }

    public function checkConnectedSocket($socket) : PromiseInterface
    {
        // The following hack looks like the only way to
        // detect connection refused errors with PHP's stream sockets.
        if (false === stream_socket_get_name($socket, true)) {
            return new RejectedPromise(
                new ConnectionException('Connection refused')
            );
        }

        return new FulfilledPromise($socket);
    }

    public function handleConnectedSocket($socket) : Stream
    {
        return new Stream($socket, $this->loop);
    }

    private function getSocketUrl(string $ip, int $port) : string
    {
        if (false !== strpos($ip, ':')) {
            // enclose IPv6 addresses in square brackets before appending port
            $ip = '[' . $ip . ']';
        }

        return 'tcp://' . $ip . ':' . $port;
    }
}
