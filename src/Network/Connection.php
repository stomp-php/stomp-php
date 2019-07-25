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
use Stomp\Network\Observer\ConnectionObserverCollection;
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
     * Timeout (seconds) that are applied on write calls.
     *
     * @var integer
     */
    private $writeTimeout = 3;

    /**
     * Using persistent connection for creating socket
     *
     * @var bool
     */
    private $persistentConnection = false;

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
     * @var resource|null
     */
    private $connection;

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
     * @var ConnectionObserverCollection
     */
    private $observers;

    /**
     * Maximum number of bytes to write to a resource.
     *
     * @var int
     */
    private $maxWriteBytes = 8192;

    /**
     * Maximum number of bytes to read from a resource.
     *
     * @var int
     */
    private $maxReadBytes = 8192;

    /**
     * Alive Signal
     */
    const ALIVE = "\n";

    /**
     * @var callable|null
     */
    private $waitCallback;

    /**
     * Initialize connection
     *
     * Example broker uri
     * - Use only one broker uri: tcp://localhost:61614
     * - use failover in given order: failover://(tcp://localhost:61614,ssl://localhost:61612)
     * - use brokers in random order: failover://(tcp://localhost:61614,ssl://localhost:61612)?randomize=true
     *
     * @param string $brokerUri
     * @param integer $connectionTimeout in seconds
     * @param array $context stream context
     * @throws ConnectionException
     */
    public function __construct($brokerUri, $connectionTimeout = 1, array $context = [])
    {
        $this->parser = new Parser();
        $this->observers = new ConnectionObserverCollection();
        $this->parser->setObserver($this->observers);
        $this->connectTimeout = $connectionTimeout;
        $this->context = $context;
        $pattern = "|^(([a-zA-Z0-9]+)://)+\(*([a-zA-Z0-9\.:/i,-_]+)\)*\??([a-zA-Z0-9=&]*)$|i";
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
     * Sets a wait callback that will be invoked when the connection is waiting for new data.
     *
     * This is a good place to call `pcntl_signal_dispatch()` if you need to ensure that your process signals in an
     * interval that is lower as read timeout. You should also return `false` in your callback if you don't want the
     * connection to continue waiting for data.
     *
     * @param callable|null $waitCallback
     */
    public function setWaitCallback($waitCallback)
    {
        if ($waitCallback !== null) {
            if (!is_callable($waitCallback)) {
                throw new \InvalidArgumentException('$waitCallback must be callable.');
            }
        }
        $this->waitCallback = $waitCallback;
    }

    /**
     * Returns the connect timeout in seconds.
     *
     * @return int
     */
    public function getConnectTimeout()
    {
        return $this->connectTimeout;
    }

    /**
     * Returns the collection of observers of this connection.
     *
     * @return ConnectionObserverCollection
     */
    public function getObservers()
    {
        return $this->observers;
    }

    /**
     * Parse a broker URL
     *
     * @param string $url Broker URL
     * @return void
     * @throws ConnectionException
     */
    private function parseUrl($url)
    {
        $parsed = parse_url($url);
        if ($parsed === false) {
            throw new ConnectionException('Unable to parse url '. $url);
        }
        array_push($this->hosts, $parsed + ['port' => '61613', 'scheme' => 'tcp']);
    }

    /**
     * Set the read timeout
     *
     * @param integer $seconds      seconds
     * @param integer $microseconds microseconds (1μs = 0.000001s, ex. 500ms = 500000)
     * @return void
     */
    public function setReadTimeout($seconds, $microseconds = 0)
    {
        $this->readTimeout[0] = $seconds;
        $this->readTimeout[1] = $microseconds;
    }

    /**
     * Returns the read timeout
     *
     * First element contains full seconds, second the microseconds part.
     *
     * @return array
     */
    public function getReadTimeout()
    {
        return $this->readTimeout;
    }

    /**
     * Set the write timeout
     *
     * @param int $writeTimeout seconds
     */
    public function setWriteTimeout($writeTimeout)
    {
        $this->writeTimeout = $writeTimeout;
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
     * Set the maximum number of bytes to write to a resource
     *
     * This will be useful if you are suffering problems with OpenSSL or Amazon MQ
     *
     * @param int $maxWriteBytes bytes
     */
    public function setMaxWriteBytes($maxWriteBytes)
    {
        $this->maxWriteBytes = $maxWriteBytes;
    }

    /**
     * Set the maximum number of bytes to read from a resource
     *
     * This will be useful if you are suffering problems with OpenSSL or Amazon MQ
     *
     * @param int $maxReadBytes bytes
     */
    public function setMaxReadBytes($maxReadBytes)
    {
        $this->maxReadBytes = $maxReadBytes;
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
     * @param boolean $persistentConnection
     */
    public function setPersistentConnection($persistentConnection)
    {
        $this->persistentConnection = $persistentConnection;
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
        if ($this->shouldRandomizeHosts()) {
            shuffle($hosts);
        }
        return $hosts;
    }

    /**
     * Returns whether the broker host list should be shuffled in random order.
     *
     * This applies when specifying multiple hosts using a failover:// protocol in the URI.
     *
     * @return bool
     *   Whether the broker hosts should be shuffled in random order.
     */
    protected function shouldRandomizeHosts()
    {
        return filter_var($this->params['randomize'], FILTER_VALIDATE_BOOLEAN);
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
        $flags = STREAM_CLIENT_CONNECT;
        if ($this->persistentConnection) {
            $flags |= STREAM_CLIENT_PERSISTENT;
        }
        $socket = @stream_socket_client(
            $host['scheme'] . '://' . $host['host'] . ':' . $host['port'],
            $errNo,
            $errStr,
            $this->connectTimeout,
            $flags,
            $context
        );


        if (!is_resource($socket)) {
            throw new ConnectionException(sprintf('Failed to connect. (%s: %s)', $errNo, $errStr), $host);
        }

        if (!@stream_set_blocking($socket, false)) {
            throw new ConnectionException('Failed to set non blocking mode for stream.', $host);
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
            @stream_socket_shutdown($this->connection, STREAM_SHUT_RDWR);
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
        $this->writeData($stompFrame, $this->writeTimeout);
        $this->observers->sentFrame($stompFrame);
        return true;
    }

    /**
     * Write passed data to the stream, respecting passed timeout.
     *
     * @param Frame|string $stompFrame
     * @param float $timeout in seconds, supporting fractions
     * @throws ConnectionException
     */
    private function writeData($stompFrame, $timeout)
    {
        $data = (string) $stompFrame;
        $offset = 0;
        $size = strlen($data);
        $lastByteTime = microtime(true);
        do {
            $written = @fwrite($this->connection, substr($data, $offset), $this->maxWriteBytes);

            if ($written === false) {
                throw new ConnectionException('Was not possible to write frame!', $this->activeHost);
            }

            if ($written > 0) {
                // offset tracking
                $offset += $written;
                $lastByteTime = microtime(true);
            } else {
                // timeout tracking
                if ((microtime(true) - $lastByteTime) > $timeout) {
                    throw new ConnectionException(
                        'Was not possible to write frame! Write operation timed out.',
                        $this->activeHost
                    );
                }
            }
            // keep some time to breath
            if ($written < $size) {
                time_nanosleep(0, 2500000); // 2.5ms / 0.0025s
            }
        } while ($offset < $size);
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
        // first we try to check the parser for any leftover frames
        if ($frame = $this->parser->nextFrame()) {
            return $this->onFrame($frame);
        }

        if (!$this->hasDataToRead()) {
            return false;
        }

        do {
            $read = @fread($this->connection, $this->maxReadBytes);
            if ($read === false) {
                throw new ConnectionException(sprintf('Was not possible to read data from stream.'), $this->activeHost);
            }

            // this can be caused by different events on the stream, ex. new data or any kind of signal
            // it also happens when a ssl socket was closed on the other side... so we need to test
            if ($read === '') {
                $this->observers->emptyRead();
                // again we give some time here
                // as this path is most likely indicating that the socket is not working anymore
                time_nanosleep(0, 5000000); // 5ms / 0.005s
                return false;
            }

            $this->parser->addData($read);

            if ($frame = $this->parser->nextFrame()) {
                return $this->onFrame($frame);
            }
        } while ($this->isDataOnStream());

        return false;
    }

    /**
     * The connection onFrame handler.
     *
     * @param Frame $frame
     * @return Frame
     * @throws ErrorFrameException
     */
    private function onFrame(Frame $frame)
    {
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

        $isDataInBuffer = $this->connectionHasDataToRead($this->readTimeout[0], $this->readTimeout[1]);
        if (!$isDataInBuffer) {
            $this->observers->emptyBuffer();
        }
        return $isDataInBuffer;
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
        $timeout = microtime(true) + $timeoutSec + ($timeoutMicros ? $timeoutMicros / 1000000 : 0);
        while (($hasData = $this->isDataOnStream()) === false) {
            if ($timeout < microtime(true)) {
                return false;
            }
            if ($this->waitCallback) {
                if (call_user_func($this->waitCallback) === false) {
                    return false;
                }
            }

            $slept = time_nanosleep(0, 2500000); // 2.5ms / 0.0025s
            if (\is_array($slept)) {
                return false;
            }
        }

        return $hasData === true;
    }

    /**
     * Checks if there is readable data on the stream.
     *
     * Will return true if data is available, false if no data is detected and null if the operation was interrupted.
     *
     * @return bool|null
     * @throws ConnectionException
     */
    private function isDataOnStream()
    {
        $read = [$this->connection];
        $write = null;
        $except = null;
        $hasStreamInfo = @stream_select($read, $write, $except, 0);

        if ($hasStreamInfo === false) {
            // can return `false` if used in combination with `pcntl_signal` and lead to false errors here
            $error = error_get_last();
            if ($error && isset($error['message']) && stripos($error['message'], 'interrupted system call') === false) {
                throw new ConnectionException(
                    'Check failed to determine if the socket is readable.',
                    $this->activeHost
                );
            }
            return null;
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

    /**
     * Writes an "alive" message on the connection to indicate that the client is alive.
     *
     * @param float $timeout in seconds supporting fractions (microseconds)
     *
     * @return void
     * @throws ConnectionException
     */
    public function sendAlive($timeout = 1.0)
    {
        if ($this->isConnected()) {
            $this->writeData(self::ALIVE, $timeout);
        }
    }
    
    /**
     * Immediately releases all allocated resources when the connection object gets destroyed.
     *
     * This is especially important for long running processes.
     */
    public function __destruct()
    {
        $this->disconnect();
    }
}
