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
    private $_hosts = array();

    /**
     * Connection timeout in seconds.
     *
     * @var integer
     */
    private $_connect_timeout;

    /**
     * Connection read wait timeout.
     *
     * 0 => seconds
     * 1 => milliseconds
     *
     * @var array
     */
    private $_read_timeout = array(60, 0);

    /**
     * Connection options.
     *
     * @var array
     */
    private $_params = array(
        'randomize' => false, // connect to one host from list in random order
    );

    /**
     * Active connection resource.
     *
     * @var resource
     */
    private $_connection = null;

    /**
     * Connected host info.
     *
     * @var array
     */
    private $_activeHost = array();

    /**
     * Stream Context used for client connection
     *
     * @see http://php.net/manual/de/function.stream-context-create.php
     *
     * @var array
     */
    private $_context = array();

    /**
     * Frame parser
     *
     * @var Parser
     */
    private $_parser;

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
    public function __construct ($brokerUri, $connectionTimeout = 1, array $context = array())
    {
        $this->_parser = new Parser();
        $this->_connect_timeout = $connectionTimeout;
        $this->_context = $context;
        $pattern = "|^(([a-zA-Z0-9]+)://)+\(*([a-zA-Z0-9\.:/i,-]+)\)*\??([a-zA-Z0-9=&]*)$|i";
        if (preg_match($pattern, $brokerUri, $matches)) {
            $scheme = $matches[2];
            $hosts = $matches[3];
            $options = $matches[4];

            if ($options) {
                parse_str($options, $connectionOptions);
                $this->_params = $connectionOptions + $this->_params;
            }

            if ($scheme != "failover") {
                $this->_parseUrl($brokerUri);
            } else {
                $urls = explode(",", $hosts);
                foreach ($urls as $url) {
                    $this->_parseUrl($url);
                }
            }
        }

        if (empty($this->_hosts)) {
            throw new ConnectionException("Bad Broker URL {$brokerUri}. Check used scheme!");
        }
    }


    /**
     * Parce a broker URL
     *
     * @param string $url Broker URL
     * @return void
     */
    private function _parseUrl ($url)
    {
        $parsed = parse_url($url);
        array_push($this->_hosts, $parsed + array('port' => '61613', 'scheme' => 'tcp'));
    }

    /**
     * Set the read timeout
     *
     * @param array $timeout 0 => seconds, 1 => milliseconds
     * @return void
     */
    public function setReadTimeout (array $timeout)
    {
        $this->_read_timeout = $timeout;
    }

    /**
     * Set socket context
     *
     * @param array $context
     * @return void
     */
    public function setContext(array $context)
    {
        $this->_context = $context;
    }

    /**
     * Connect to an broker.
     *
     * @return boolean
     * @throws ConnectionException
     */
    public function connect ()
    {
        if (!$this->isConnected()) {
            $this->_connection = $this->_getConnection();
        }
        return true;
    }



    /**
     * Get a connection.
     *
     * @return resource (stream)
     * @throws ConnectionException
     */
    protected function _getConnection ()
    {
        $hosts = $this->_getHostList();

        while ($host = array_shift($hosts)) {
            try {
                return $this->_connect($host);
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
    protected function _getHostList ()
    {
        $hosts = array_values($this->_hosts);
        if ($this->_params['randomize']) {
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
    protected function _connect (array $host)
    {
        $this->_activeHost = $host;
        $errNo = null;
        $errStr = null;
        $context = stream_context_create($this->_context);
        $socket = @stream_socket_client($host['scheme'] . '://' . $host['host'] . ':' . $host['port'], $errNo, $errStr, $this->_connect_timeout, STREAM_CLIENT_CONNECT, $context);

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
    public function isConnected ()
    {
        return ($this->_connection && is_resource($this->_connection));
    }

    /**
     * Close connection.
     *
     * @return void
     */
    public function diconnect ()
    {
        if ($this->isConnected()) {
            stream_socket_shutdown($this->_connection, STREAM_SHUT_RDWR);
        }
        $this->_connection = null;
        $this->_activeHost = array();
    }


    /**
     * Write frame to server.
     *
     * @param Frame $stompFrame
     * @return boolean
     * @throws ConnectionException
     */
    public function writeFrame (Frame $stompFrame)
    {
        if (!$this->isConnected()) {
            throw new ConnectionException('Not connected to any server.', $this->_activeHost);
        }
        $data = $stompFrame->__toString();
        if (!@fwrite($this->_connection, $data, strlen($data))) {
            throw new ConnectionException('Was not possible to write frame!', $this->_activeHost);
        }
        return true;
    }

    /**
     * Try to read a frame from the server.
     *
     * @return Frame|False when no frame to read
     * @throws ConnectionException
     */
    public function readFrame ()
    {
        if ($this->_parser->hasBufferedFrames()) {
            return $this->_parser->getFrame();
        }
        if (!$this->hasDataToRead()) {
            return false;
        }

        do {
            $read = @fgets($this->_connection, 1024);
            if ($read === false || $read === '') {
                throw new ConnectionException('Was not possible to read data from stream.', $this->_activeHost);
            }
            $this->_parser->addData($read);
        } while (!$this->_parser->parse());
        $frame = $this->_parser->getFrame();
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
    public function hasDataToRead ()
    {
        if (!$this->isConnected()) {
            throw new ConnectionException('Not connected to any server.', $this->_activeHost);
        }

        $read = array($this->_connection);
        $write = null;
        $except = null;
        $hasStreamInfo = @stream_select($read, $write, $except, $this->_read_timeout[0], $this->_read_timeout[1]);

        if ($hasStreamInfo === false) {
            throw new ConnectionException('Check failed to determine if the socket is readable.', $this->_activeHost);
        }
        return !empty($read);
    }
}
