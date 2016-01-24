<?php

namespace Thruster\Component\SocketClient\Tests;

use Thruster\Component\Dns\Executor;
use Thruster\Component\Dns\Resolver;
use Thruster\Component\EventLoop\EventLoop;
use Thruster\Component\SocketClient\SecureConnector;
use Thruster\Component\SocketClient\DnsConnector;
use Thruster\Component\SocketClient\TcpConnector;
use Thruster\Component\Stream\BufferedSink;
use Thruster\Component\Stream\Stream;

/**
 * Class IntegrationTest
 *
 * @package Thruster\Component\SocketClient\Tests
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class IntegrationTest extends TestCase
{

    public function testGettingStuffFromGoogleShouldWork()
    {
        $loop = $this->getLoop();

        $resolver = new Resolver('8.8.8.8:53', new Executor($loop));
        $connector = new DnsConnector(new TcpConnector($loop), $resolver);

        $connected = false;
        $response = null;

        $connector->create('google.com', 80)
            ->then(function (Stream $conn) use (&$connected) {
                $connected = true;
                $conn->write("GET / HTTP/1.0\r\n\r\n");
                return BufferedSink::createPromise($conn);
            })
            ->then(function ($data) use (&$response) {
                $response = $data;
            });

        $loop->run();

        $this->assertTrue($connected);
        $this->assertRegExp('#^HTTP/1\.0#', $response);
    }

    public function testGettingEncryptedStuffFromGoogleShouldWork()
    {
        $loop = $this->getLoop();

        $resolver = new Resolver('8.8.8.8:53', new Executor($loop));
        $connector = new DnsConnector(new TcpConnector($loop), $resolver);

        $connected = false;
        $response = null;

        $secureConnector = new SecureConnector(
            $connector,
            $loop
        );

        $secureConnector->create('google.com', 443)
            ->then(function (Stream $conn) use (&$connected) {
                $connected = true;

                $conn->write("GET / HTTP/1.0\r\n\r\n");
                return BufferedSink::createPromise($conn);
            })
            ->then(function ($data) use (&$response) {
                $response = $data;
            });

        $loop->run();

        $this->assertTrue($connected);
        $this->assertRegExp('#^HTTP/1\.0#', $response);
    }
}
