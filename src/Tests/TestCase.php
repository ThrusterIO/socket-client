<?php

namespace Thruster\Component\SocketClient\Tests;

use Thruster\Component\EventLoop\EventLoop;

/**
 * Class TestCase
 *
 * @package Thruster\Component\SocketClient\Tests
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class TestCase extends \PHPUnit_Framework_TestCase
{
    const MOCK_FUNCTION = 'mockFunction';

    /**
     * @var EventLoop
     */
    private $loop;

    public function getLoop()
    {
        if ($this->loop) {
            return $this->loop;
        }

        $this->loop = new EventLoop();

        return $this->loop;
    }

    public function expectCallableExactly($amount)
    {
        $mock = $this->createCallableMock();
        $mock
            ->expects($this->exactly($amount))
            ->method(static::MOCK_FUNCTION);

        return $this->getCallable($mock);
    }

    public function expectCallableOnce()
    {
        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method(static::MOCK_FUNCTION);

        return $this->getCallable($mock);
    }

    public function expectCallableNever()
    {
        $mock = $this->createCallableMock();
        $mock
            ->expects($this->never())
            ->method(static::MOCK_FUNCTION);

        return $this->getCallable($mock);
    }

    public function createCallableMock()
    {
        return $this->getMock(__CLASS__);
    }

    public function getCallable($mock)
    {
        return [$mock, static::MOCK_FUNCTION];
    }

    public function mockFunction()
    {
    }
}
