<?php

namespace FuseSource\Stomp;

use Exception;
use FuseSource\Stomp\Exception\StompException;
use FuseSource\Stomp\Message\Map;
/**
 *
 * Copyright 2005-2006 The Apache Software Foundation
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

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
    private $_connect_timeout = 1;

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
     * Initialize connection
     *
     * @param string $brokerUri
     * @throws StompException
     */
    public function __construct ($brokerUri)
    {
        $pattern = "|^(([a-zA-Z0-9]+)://)+\(*([a-zA-Z0-9\.:/i,-]+)\)*\??([a-zA-Z0-9=&]*)$|i";
        if (preg_match($pattern, $brokerUri, $matches)) {
            $scheme = $matches[2];
            $hosts = $matches[3];
            $options = $matches[4];

            if ($options) {
                parse_str($options, $connectionOptions);
                $this->_params += $connectionOptions;
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
            throw new StompException("Bad Broker URL {$brokerUri}");
        }
    }


    /**
     * Parce a broker URL
     *
     * @param string $url Broker URL
     * @throws StompException
     * @return void
     */
    private function _parseUrl ($url)
    {
        if ($parsed = parse_url($url)) {
            array_push($this->_hosts, $parsed);
        } else {
            throw new StompException("Bad Broker URL $url");
        }
    }


    /**
     * Set the connection timeout.
     *
     * @param integer $connectTimeout in seconds
     * @return void
     */
    public function setConnectTimeout ($connectTimeout)
    {
        $this->_connect_timeout = $connectTimeout;
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
     * Connect to an broker.
     *
     * @return boolean
     * @throws
     */
    public function connect ()
    {
        if (!$this->_connection) {
            $this->_connection = $this->_getConnection();
        }
        return true;
    }



    /**
     * Get a connection.
     *
     * @return type
     * @throws Exception
     */
    protected function _getConnection ()
    {
        $hosts = $this->_getHostList();

        while ($host = array_shift($hosts)) {
            try {
                return $this->_connect($host);
            } catch (Exception $connectionException) {
                if (empty($hosts)) {
                    throw new Exception("Could not connect to a broker", 500, $connectionException);
                }
            }
        }
    }

    /**
     * Get the host list.
     *
     * @return array
     */
    private function _getHostList ()
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
     * Will return null if connect can't be established.
     *
     * @param array $host
     * @return resource (stream)|null
     */
    protected function _connect (array $host)
    {
        $port = self::DEFAULT_PORT;
        extract($host, EXTR_OVERWRITE);

        $errNo = null;
        $errStr = null;
        $socket = @fsockopen($scheme . '://' . $host, $port, $errNo, $errStr, $this->_connect_timeout);
        if (!is_resource($socket)) {
            throw new Exception(
                sprintf(
                    'Failed to connect to "%s://%s:%s". %s (%s)',
                    $scheme, $host, $port,
                    $errStr, $errNo
                )
            );
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
            fclose($this->_connection);
        }
        $this->_connection = null;
    }


    /**
     * Write frame to server.
     *
     * @param Frame $stompFrame
     * @return boolean
     * @throws StompException
     */
    public function writeFrame (Frame $stompFrame)
    {
        if (!$this->isConnected()) {
            throw new StompException('Not connected to any server.');
        }
        $data = $stompFrame->__toString();
        if (!@fwrite($this->_connection, $data, strlen($data))) {
            throw new StompException('Was not possible to write frame!');
        }
        return true;
    }

    /**
     * Try to read a frame from the server.
     *
     * @return Frame|False when no frame to read
     * @throws StompException
     */
    public function readFrame ()
    {
        if (!$this->hasDataToRead()) {
            return false;
        }

        $readBuffer = 1024;
        $data = '';
        $end = false;

        do {
            $read = fgets($this->_connection, $readBuffer);
            if ($read === false || $read === "") {
                throw new StompException('Was not possible to read frame.');
            }
            $data .= $read;
            if (strpos($data, "\x00") !== false) {
                $end = true;
                $data = trim($data, "\n");
            }
            $len = strlen($data);
        } while ($len < 2 || $end == false); // need at least 2 bytes or stop if frame end is detected
        $frame = $this->_parseFrame($data);
        if ($frame->isErrorFrame()) {
            throw new StompException($frame->headers['message'], 0, $frame->body);
        }
        return $frame;
    }


    /**
     * Parse a frame from source.
     *
     * @param string $data
     * @return Map|Frame
     */
    protected function _parseFrame ($data)
    {
        list ($header, $body) = explode("\n\n", $data, 2);
        $header = explode("\n", $header);
        $headers = array();
        $command = null;
        foreach ($header as $v) {
            if (isset($command)) {
                list ($name, $value) = explode(':', $v, 2);
                $headers[$name] = $value;
            } else {
                $command = $v;
            }
        }
        $frame = new Frame($command, $headers, trim($body));
        if (isset($frame->headers['transformation']) && $frame->headers['transformation'] == 'jms-map-json') {
            return new Map($frame);
        } else {
            return $frame;
        }
        return $frame;
    }

    /**
     * Check if connection has new data which can be read.
     *
     * This might wait until readTimeout is reached.
     *
     * @return boolean
     * @throws StompException
     * @see setReadTimeout()
     */
    public function hasDataToRead ()
    {
        if (!$this->isConnected()) {
            throw new StompException('Not connected to any server.');
        }

        $read = array($this->_connection);
        $write = null;
        $except = null;
        $hasStreamInfo = @stream_select($read, $write, $except, $this->_read_timeout[0], $this->_read_timeout[1]);

        if ($hasStreamInfo === false) {
            throw new StompException('Check failed to determine if the socket is readable');
        }
        return !empty($read);
    }
}
