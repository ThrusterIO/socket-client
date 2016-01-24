<?php

namespace Thruster\Component\SocketClient\Tests;

use Thruster\Component\EventLoop\EventLoop;
use Thruster\Component\Socket\Server;
use Thruster\Component\SocketClient\TcpConnector;

/**
 * Class TcpConnectorTest
 *
 * @package Thruster\Component\SocketClient\Tests
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class TcpConnectorTest extends TestCase
{
    public function testConnectionToEmptyPortShouldFail()
    {
        $loop = $this->getLoop();

        $connector = new TcpConnector($loop);
        $connector->create('127.0.0.1', 9999)
            ->then($this->expectCallableNever(), $this->expectCallableOnce());

        $loop->run();
    }

    public function testConnectionToBadHostShouldFail()
    {
        $loop = $this->getLoop();

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method(static::MOCK_FUNCTION)
            ->will(
                $this->returnCallback(
                    function ($exception) {
                        $this->assertInstanceOf('RuntimeException', $exception);
                        $this->assertSame(
                            'Connection to 0.0.0.1:9999 failed: No route to host',
                            $exception->getMessage()
                        );
                    }
                )
            );

        $connector = new TcpConnector($loop);
        $connector->create('0.0.0.1', 9999)
            ->then($this->expectCallableNever(), $this->getCallable($mock));

        $loop->run();
    }

    public function testConnectionToTcpServerShouldSucceed()
    {
        $capturedStream = null;

        $loop = $this->getLoop();

        $server = new Server($loop);
        $server->on('connection', $this->expectCallableOnce());
        $server->on('connection', function () use ($server, $loop) {
            $server->shutdown();
        });
        $server->listen(9999);

        $connector = new TcpConnector($loop);
        $connector->create('127.0.0.1', 9999)
            ->then(function ($stream) use (&$capturedStream) {
                $capturedStream = $stream;
                $stream->end();
            });

        $loop->run();

        $this->assertInstanceOf('Thruster\Component\Stream\Stream', $capturedStream);
    }

    public function testConnectionToEmptyIp6PortShouldFail()
    {
        $loop = $this->getLoop();

        $connector = new TcpConnector($loop);
        $connector
            ->create('::1', 9999)
            ->then($this->expectCallableNever(), $this->expectCallableOnce());

        $loop->tick();
    }

    public function testConnectionToIp6TcpServerShouldSucceed()
    {
        $capturedStream = null;

        $loop = $this->getLoop();

        $server = new Server($loop);
        $server->on('connection', $this->expectCallableOnce());
        $server->on('connection', array($server, 'shutdown'));
        $server->listen(9999, '::1');

        $connector = new TcpConnector($loop);
        $connector
            ->create('::1', 9999)
            ->then(function ($stream) use (&$capturedStream) {
                $capturedStream = $stream;
                $stream->end();
            });

        $loop->run();

        $this->assertInstanceOf('Thruster\Component\Stream\Stream', $capturedStream);
    }

    public function testConnectionToHostnameShouldFailImmediately()
    {
        $loop = $this->getMock('Thruster\Component\EventLoop\EventLoopInterface');

        $connector = new TcpConnector($loop);
        $connector->create('www.google.com', 80)->then(
            $this->expectCallableNever(),
            $this->expectCallableOnce()
        );
    }
}
