<?php

namespace Thruster\Component\SocketClient;

use Thruster\Component\EventLoop\EventLoopInterface;
use Thruster\Component\Promise\PromiseInterface;
use Thruster\Component\Stream\Stream;

/**
 * Class SecureConnector
 *
 * @package Thruster\Component\SocketClient
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class SecureConnector implements ConnectorInterface
{
    /**
     * @var ConnectorInterface
     */
    private $connector;

    /**
     * @var StreamEncryption
     */
    private $streamEncryption;

    /**
     * @var array
     */
    private $context;

    public function __construct(ConnectorInterface $connector, EventLoopInterface $loop)
    {
        $this->context = [];

        $this->connector        = $connector;
        $this->streamEncryption = new StreamEncryption($loop);
    }

    public function withContext(array $contextOptions) : self
    {
        $connector          = clone $this;

        $connector->context = array_filter($contextOptions + $connector->context, function ($value) {
            return ($value !== null);
        });

        return $connector;
    }

    public function create(string $host, int $port) : PromiseInterface
    {

        $sslContext = $this->context['ssl'] ?? [];

        $this->context['ssl'] = $sslContext + [
                'SNI_enabled'     => true,
                'SNI_server_name' => $host,
                'peer_name'       => $host,
            ];

        return $this->connector->create($host, $port)->then(function (Stream $stream) use ($host) {
            // (unencrypted) TCP/IP connection succeeded

            // set required SSL/TLS context options
            $resource = $stream->getStream();

            stream_context_set_option($stream->getStream(), $this->context);

            // try to enable encryption
            return $this->streamEncryption->enable($stream)->then(null, function ($error) use ($stream) {

                // establishing encryption failed => close invalid connection and return error
                $stream->close();

                throw $error;
            });
        });
    }
}
