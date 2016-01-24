<?php

namespace Thruster\Component\SocketClient;

use Thruster\Component\EventLoop\EventLoopInterface;
use Thruster\Component\Stream\DuplexStreamInterface;
use Thruster\Component\Stream\Stream;
use Thruster\Component\Stream\WritableStreamInterface;

/**
 * Class SecureStream
 *
 * @package Thruster\Component\SocketClient
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class SecureStream extends Stream implements DuplexStreamInterface
{
    /**
     * @var Stream
     */
    private $decorating;

    public function __construct(Stream $stream, EventLoopInterface $loop)
    {
        $this->stream     = $stream->stream;
        $this->decorating = $stream;
        $this->loop       = $loop;

        $stream->on('error', function ($error) {
            $this->emit('error', [$error, $this]);
        });

        $stream->on('end', function () {
            $this->emit('end', [$this]);
        });

        $stream->on('close', function () {
            $this->emit('close', [$this]);
        });

        $stream->on('drain', function () {
            $this->emit('drain', [$this]);
        });

        $stream->pause();

        $this->resume();
    }

    public function handleData($stream)
    {
        $data = stream_get_contents($stream);

        $this->emit('data', [$data, $this]);

        if (false === is_resource($stream) || feof($stream)) {
            $this->end();
        }
    }

    /**
     * @return Stream
     */
    public function getDecorating()
    {
        return $this->decorating;
    }

    public function pause()
    {
        $this->loop->removeReadStream($this->decorating->stream);
    }

    public function resume()
    {
        if ($this->isReadable()) {
            $this->loop->addReadStream($this->decorating->stream, [$this, 'handleData']);
        }
    }

    public function isReadable() : bool
    {
        return $this->decorating->isReadable();
    }

    public function isWritable() : bool
    {
        return $this->decorating->isWritable();
    }

    public function write($data)
    {
        return $this->decorating->write($data);
    }

    public function close()
    {
        return $this->decorating->close();
    }

    public function end($data = null)
    {
        return $this->decorating->end($data);
    }

    public function pipe(WritableStreamInterface $destination, array $options = [])
    {
        $this->pipeAll($this, $destination, $options);

        return $destination;
    }
}
