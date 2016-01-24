<?php

namespace Thruster\Component\SocketClient\Tests;

use Thruster\Component\SocketClient\DnsConnector;
use function Thruster\Component\Promise\resolve;
use function Thruster\Component\Promise\reject;

/**
 * Class DnsConnectorTest
 *
 * @package Thruster\Component\SocketClient\Tests
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class DnsConnectorTest extends TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $tcp;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $resolver;

    /**
     * @var DnsConnector
     */
    private $connector;

    public function setUp()
    {
        $this->tcp = $this->getMock('Thruster\Component\SocketClient\ConnectorInterface');

        $this->resolver = $this->getMockBuilder('Thruster\Component\Dns\ResolverInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->connector = new DnsConnector($this->tcp, $this->resolver);
    }

    public function testPassByResolverIfGivenIp()
    {
        $this->resolver->expects($this->never())->method('resolve');
        $this->tcp->expects($this->once())->method('create')->with($this->equalTo('127.0.0.1'), $this->equalTo(80));

        $this->connector->create('127.0.0.1', 80);
    }

    public function testPassThroughResolverIfGivenHost()
    {
        $this->resolver->expects($this->once())
            ->method('resolve')
            ->with($this->equalTo('google.com'))
            ->will($this->returnValue(resolve('1.2.3.4')));

        $this->tcp->expects($this->once())
            ->method('create')
            ->with($this->equalTo('1.2.3.4'), $this->equalTo(80));

        $this->connector->create('google.com', 80);
    }

    public function testSkipConnectionIfDnsFails()
    {
        $this->resolver->expects($this->once())
            ->method('resolve')
            ->with($this->equalTo('example.invalid'))
            ->will($this->returnValue(reject()));
        $this->tcp->expects($this->never())->method('create');

        $this->connector->create('example.invalid', 80);
    }
}
