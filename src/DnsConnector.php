<?php

namespace Thruster\Component\SocketClient;

use Thruster\Component\Dns\ResolverInterface;
use Thruster\Component\Promise\PromiseInterface;
use function Thruster\Component\Promise\resolve;

/**
 * Class DnsConnector
 *
 * @package Thruster\Component\SocketClient
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class DnsConnector implements ConnectorInterface
{
    /**
     * @var ConnectorInterface
     */
    private $connector;

    /**
     * @var ResolverInterface
     */
    private $resolver;

    public function __construct(ConnectorInterface $connector, ResolverInterface $resolver)
    {
        $this->connector = $connector;
        $this->resolver = $resolver;
    }

    public function create(string $host, int $port) : PromiseInterface
    {
        return $this
            ->resolveHostname($host)
            ->then(function ($address) use ($port) {
                return $this->connector->create($address, $port);
            });
    }

    private function resolveHostname(string $host)
    {
        if (false !== filter_var($host, FILTER_VALIDATE_IP)) {
            return resolve($host);
        }

        return $this->resolver->resolve($host);
    }
}
