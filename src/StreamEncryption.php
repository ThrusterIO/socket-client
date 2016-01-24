<?php

namespace Thruster\Component\SocketClient;

use Thruster\Component\EventLoop\EventLoopInterface;
use Thruster\Component\Promise\Deferred;
use Thruster\Component\Stream\Stream;
use UnexpectedValueException;

/**
 * Class StreamEncryption
 *
 * @package Thruster\Component\SocketClient
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class StreamEncryption
{
    /**
     * @var EventLoopInterface
     */
    private $loop;

    /**
     * @var int
     */
    private $method;

    /**
     * @var string
     */
    private $errStr;

    /**
     * @var int
     */
    private $errno;

    /**
     * @var bool
     */
    private $wrapSecure;

    public function __construct(EventLoopInterface $loop)
    {
        $this->method     = STREAM_CRYPTO_METHOD_TLS_CLIENT;
        $this->wrapSecure = false;

        $this->loop = $loop;

        if (defined('STREAM_CRYPTO_METHOD_TLSv1_0_CLIENT')) {
            $this->method |= STREAM_CRYPTO_METHOD_TLSv1_0_CLIENT;
        }
        if (defined('STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT')) {
            $this->method |= STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT;
        }
        if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
            $this->method |= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
        }
    }

    public function enable(Stream $stream)
    {
        return $this->toggle($stream, true);
    }

    public function disable(Stream $stream)
    {
        return $this->toggle($stream, false);
    }

    public function toggle(Stream $stream, $toggle)
    {
        if ($stream instanceof SecureStream) {
            $stream = $stream->getDecorating();
        }

        // pause actual stream instance to continue operation on raw stream socket
        $stream->pause();

        // TODO: add write() event to make sure we're not sending any excessive data

        $deferred = new Deferred();

        // get actual stream socket from stream instance
        $socket = $stream->getStream();

        $toggleCrypto = function () use ($socket, $deferred, $toggle) {
            $this->toggleCrypto($socket, $deferred, $toggle);
        };

        $this->loop->addReadStream($socket, $toggleCrypto);
        $toggleCrypto();

        return $deferred->promise()->then(function () use ($stream, $toggle) {
            if ($toggle && $this->wrapSecure) {
                return new SecureStream($stream, $this->loop);
            }

            $stream->resume();

            return $stream;
        }, function ($error) use ($stream) {
            $stream->resume();
            throw $error;
        });
    }

    public function toggleCrypto($socket, Deferred $deferred, $toggle)
    {
        //set_error_handler([$this, 'handleError']);
        $result = stream_socket_enable_crypto($socket, $toggle, $this->method);
        //restore_error_handler();

        if (true === $result) {
            $this->loop->removeReadStream($socket);

            $deferred->resolve();
        } elseif (false === $result) {
            $this->loop->removeReadStream($socket);

            $deferred->reject(new UnexpectedValueException(
                sprintf('Unable to complete SSL/TLS handshake: %s', $this->errStr),
                $this->errno
            ));
        } else {
            // need more data, will retry
        }
    }

    public function handleError($errNo, $errStr)
    {
        $this->errstr = str_replace(["\r", "\n"], ' ', $errStr);
        $this->errno  = $errNo;
    }
}
