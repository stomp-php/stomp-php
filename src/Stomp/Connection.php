<?php

/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp;

use Stomp\Exception\ConnectionException;
use Stomp\Exception\ErrorFrameException;

/* vim: set expandtab tabstop=3 shiftwidth=3: */

/**
 * A Stomp Connection
 *
 * @package Stomp
 * @author Hiram Chirino <hiram@hiramchirino.com>
 * @author Dejan Bosanac <dejan@nighttale.net>
 * @author Michael Caplan <mcaplan@labnet.net>
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class Connection
{
    /**
     * Default ActiveMq port
     */
    const DEFAULT_PORT = 61613;

    /**
     * Host schemes.
     *
     * @var array[]
     */
    private $hosts = array();

    /**
     * Connection timeout in seconds.
     *
     * @var integer
     */
    private $connectTimeout;

    /**
     * Connection read wait timeout.
     *
     * 0 => seconds
     * 1 => milliseconds
     *
     * @var array
     */
    private $readTimeout = array(60, 0);

    /**
     * Connection options.
     *
     * @var array
     */
    private $params = array(
        'randomize' => false // connect to one host from list in random order
    );

    /**
     * Active connection resource.
     *
     * @var resource
     */
    private $connection = null;

    /**
     * Connected host info.
     *
     * @var array
     */
    private $activeHost = array();

    /**
     * Stream Context used for client connection
     *
     * @see http://php.net/manual/de/function.stream-context-create.php
     *
     * @var array
     */
    private $context = array();

    /**
     * Frame parser
     *
     * @var Parser
     */
    private $parser;

    /**
     * Initialize connection
     *
     * Example broker uri
     * - Use only one broker uri: tcp://localhost:61614
     * - use failover in given order: failover://(tcp://localhost:61614,ssl://localhost:61612)
     * - use brokers in random order://(tcp://localhost:61614,ssl://localhost:61612)?randomize=true
     *
     * @param string $brokerUri
     * @param integer $connectionTimeout in seconds
     * @param array   $context stream context
     * @throws ConnectionException
     */
    public function __construct($brokerUri, $connectionTimeout = 1, array $context = array())
    {
        $this->parser = new Parser();
        $this->connectTimeout = $connectionTimeout;
        $this->context = $context;
        $pattern = "|^(([a-zA-Z0-9]+)://)+\(*([a-zA-Z0-9\.:/i,-]+)\)*\??([a-zA-Z0-9=&]*)$|i";
        if (preg_match($pattern, $brokerUri, $matches)) {
            $scheme = $matches[2];
            $hosts = $matches[3];
            $options = $matches[4];

            if ($options) {
                parse_str($options, $connectionOptions);
                $this->params = $connectionOptions + $this->params;
            }

            if ($scheme != 'failover') {
                $this->parseUrl($brokerUri);
            } else {
                $urls = explode(',', $hosts);
                foreach ($urls as $url) {
                    $this->parseUrl($url);
                }
            }
        }

        if (empty($this->hosts)) {
            throw new ConnectionException("Bad Broker URL {$brokerUri}. Check used scheme!");
        }
    }


    /**
     * Parse a broker URL
     *
     * @param string $url Broker URL
     * @return void
     */
    private function parseUrl($url)
    {
        $parsed = parse_url($url);
        array_push($this->hosts, $parsed + array('port' => '61613', 'scheme' => 'tcp'));
    }

    /**
     * Set the read timeout
     *
     * @param integer $seconds      seconds
     * @param integer $milliseconds milliseconds
     * @return void
     */
    public function setReadTimeout($seconds, $milliseconds = 0)
    {
        $this->readTimeout[0] = $seconds;
        $this->readTimeout[1] = $milliseconds;
    }

    /**
     * Set socket context
     *
     * @param array $context
     * @return void
     */
    public function setContext(array $context)
    {
        $this->context = $context;
    }

    /**
     * Connect to an broker.
     *
     * @return boolean
     * @throws ConnectionException
     */
    public function connect()
    {
        if (!$this->isConnected()) {
            $this->connection = $this->getConnection();
        }
        return true;
    }



    /**
     * Get a connection.
     *
     * @return resource (stream)
     * @throws ConnectionException
     */
    protected function getConnection()
    {
        $hosts = $this->getHostList();

        while ($host = array_shift($hosts)) {
            try {
                return $this->connectSocket($host);
            } catch (ConnectionException $connectionException) {
                if (empty($hosts)) {
                    throw new ConnectionException("Could not connect to a broker", array(), $connectionException);
                }
            }
        }
    }

    /**
     * Get the host list.
     *
     * @return array
     */
    protected function getHostList()
    {
        $hosts = array_values($this->hosts);
        if ($this->params['randomize']) {
            shuffle($hosts);
        }
        return $hosts;
    }

    /**
     * Try to connect to given host.
     *
     * @param array $host
     * @return resource (stream)
     * @throws ConnectionException if connection setup fails
     */
    protected function connectSocket(array $host)
    {
        $this->activeHost = $host;
        $errNo = null;
        $errStr = null;
        $context = stream_context_create($this->context);
        $socket = @stream_socket_client(
            $host['scheme'] . '://' . $host['host'] . ':' . $host['port'],
            $errNo,
            $errStr,
            $this->connectTimeout,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!is_resource($socket)) {
            throw new ConnectionException(sprintf('Failed to connect. (%s: %s)', $errNo, $errStr), $host);
        }

        return $socket;
    }


    /**
     * Connection established.
     *
     * @return boolean
     */
    public function isConnected()
    {
        return ($this->connection && is_resource($this->connection));
    }

    /**
     * Close connection.
     *
     * @return void
     */
    public function disconnect()
    {
        if ($this->isConnected()) {
            stream_socket_shutdown($this->connection, STREAM_SHUT_RDWR);
        }
        $this->connection = null;
        $this->activeHost = array();
    }


    /**
     * Write frame to server.
     *
     * @param Frame $stompFrame
     * @return boolean
     * @throws ConnectionException
     */
    public function writeFrame(Frame $stompFrame)
    {
        if (!$this->isConnected()) {
            throw new ConnectionException('Not connected to any server.', $this->activeHost);
        }
        $data = $stompFrame->__toString();
        if (!@fwrite($this->connection, $data, strlen($data))) {
            throw new ConnectionException('Was not possible to write frame!', $this->activeHost);
        }
        return true;
    }

    /**
     * Try to read a frame from the server.
     *
     * @return Frame|false when no frame to read
     * @throws ConnectionException
     * @throws ErrorFrameException
     */
    public function readFrame()
    {
        if ($this->parser->hasBufferedFrames()) {
            return $this->parser->getFrame();
        }
        if (!$this->hasDataToRead()) {
            return false;
        }

        do {
            $read = @fgets($this->connection, 1024);
            if ($read === false || $read === '') {
                throw new ConnectionException('Was not possible to read data from stream.', $this->activeHost);
            }
            $this->parser->addData($read);
        } while (!$this->parser->parse());
        $frame = $this->parser->getFrame();
        if ($frame->isErrorFrame()) {
            throw new ErrorFrameException($frame);
        }
        return $frame;
    }

    /**
     * Check if connection has new data which can be read.
     *
     * This might wait until readTimeout is reached.
     *
     * @return boolean
     * @throws ConnectionException
     * @see Connection::setReadTimeout()
     */
    public function hasDataToRead()
    {
        if (!$this->isConnected()) {
            throw new ConnectionException('Not connected to any server.', $this->activeHost);
        }

        $read = array($this->connection);
        $write = null;
        $except = null;
        $hasStreamInfo = @stream_select($read, $write, $except, $this->readTimeout[0], $this->readTimeout[1]);

        if ($hasStreamInfo === false) {
            throw new ConnectionException('Check failed to determine if the socket is readable.', $this->activeHost);
        }
        return !empty($read);
    }
}
