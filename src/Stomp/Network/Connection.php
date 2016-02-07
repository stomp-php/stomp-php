<?php

/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Network;

use Stomp\Exception\ConnectionException;
use Stomp\Exception\ErrorFrameException;
use Stomp\Transport\Frame;
use Stomp\Transport\Parser;

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
    private $hosts = [];

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
    private $readTimeout = [60, 0];

    /**
     * Connection options.
     *
     * @var array
     */
    private $params = [
        'randomize' => false // connect to one host from list in random order
    ];

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
    private $activeHost = [];

    /**
     * Stream Context used for client connection
     *
     * @see http://php.net/manual/de/function.stream-context-create.php
     *
     * @var array
     */
    private $context = [];

    /**
     * Frame parser
     *
     * @var Parser
     */
    private $parser;

    /**
     * Host connected to.
     *
     * @var String
     */
    private $host;

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
    public function __construct($brokerUri, $connectionTimeout = 1, array $context = [])
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
        array_push($this->hosts, $parsed + ['port' => '61613', 'scheme' => 'tcp']);
    }

    /**
     * Set the read timeout
     *
     * @param integer $seconds      seconds
     * @param integer $microseconds microseconds (1μs = 0.000001s)
     * @return void
     */
    public function setReadTimeout($seconds, $microseconds = 0)
    {
        $this->readTimeout[0] = $seconds;
        $this->readTimeout[1] = $microseconds;
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
                    throw new ConnectionException("Could not connect to a broker", [], $connectionException);
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

        $this->host = $host['host'];
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
        $this->activeHost = [];
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
        if (!$this->hasDataToRead()) {
            return false;
        }

        // See if there are newlines waiting to be processed (some brokers send empty lines as heartbeat)
        $this->gobbleNewLines();

        do {
            $read = @stream_get_line($this->connection, 8192, Parser::FRAME_END);
            if ($read === false) {
                throw new ConnectionException('Was not possible to read data from stream.', $this->activeHost);
            }

            $this->parser->addData($read);

            // Include zero-byte back at the end
            if (strlen($read) != 8192) {
                $this->parser->addData(Parser::FRAME_END);
            }

        } while (!$this->parser->parse());

        // See if there are newlines after the \0
        $this->gobbleNewLines();

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

        return $this->connectionHasDataToRead($this->readTimeout[0], $this->readTimeout[1]);
    }

    /**
     * Read any newline left in the data to read.
     *
     * Newlines will not be added to the parser, if this method encounters a different character or result,
     * it'll add that to the parser's data buffer and abort.
     */
    private function gobbleNewLines()
    {
        // Only test the stream, return immediately if nothing is left
        while ($this->connectionHasDataToRead(0, 0) && ($data = @fread($this->connection, 1)) !== false) {
            // If its not a newline, it indicates a new messages has been added,
            // so add that to the data-buffer of the parser.
            if ($data !== "\n" && $data !== "\r") {
                $this->parser->addData($data);
                break;
            }
        }
    }

    /**
     * See if the connection has data left.
     *
     * If both timeout-parameters are set to 0, it will return immediately.
     *
     * @param int $timeoutSec Second-timeout part
     * @param int $timeoutMicros Microsecond-timeout part
     * @return bool
     * @throws ConnectionException
     */
    private function connectionHasDataToRead($timeoutSec, $timeoutMicros)
    {
        $read = [$this->connection];
        $write = null;
        $except = null;
        $hasStreamInfo = @stream_select($read, $write, $except, $timeoutSec, $timeoutMicros);

        if ($hasStreamInfo === false) {
            throw new ConnectionException('Check failed to determine if the socket is readable.', $this->activeHost);
        }

        return !empty($read);
    }

    /**
     * Returns the parser which is used by the connection.
     *
     * @return Parser
     */
    public function getParser()
    {
        return $this->parser;
    }

    /**
     * Returns the host the connection was established to.
     *
     * @return String
     */
    public function getHost()
    {
        return $this->host;
    }
}
