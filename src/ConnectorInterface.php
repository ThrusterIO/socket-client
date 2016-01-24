<?php

namespace Thruster\Component\SocketClient;

use Thruster\Component\Promise\PromiseInterface;

/**
 * Interface ConnectorInterface
 *
 * @package Thruster\Component\SocketClient
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
interface ConnectorInterface
{
    public function create(string $host, int $port) : PromiseInterface;
}
