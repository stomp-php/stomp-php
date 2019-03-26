<?php

/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp;

use Stomp\Exception\ConnectionException;
use Stomp\Exception\MissingReceiptException;
use Stomp\Exception\StompException;
use Stomp\Exception\UnexpectedResponseException;
use Stomp\Network\Connection;
use Stomp\Protocol\Protocol;
use Stomp\Protocol\Version;
use Stomp\Transport\Frame;

/**
 * Stomp Client
 *
 * This is the minimal implementation of a Stomp Client, it allows to send and receive Frames using the Stomp Protocol.
 *
 * @package Stomp
 * @author Hiram Chirino <hiram@hiramchirino.com>
 * @author Dejan Bosanac <dejan@nighttale.net>
 * @author Michael Caplan <mcaplan@labnet.net>
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class Client
{
    /**
     * Perform request synchronously
     *
     * @var boolean
     */
    private $sync = true;


    /**
     * Client id used for durable subscriptions
     *
     * @var string
     */
    private $clientId;

    /**
     * Connection session id
     *
     * @var string|null
     */
    private $sessionId;

    /**
     * Frames that have been read but not processed yet.
     *
     * @var Frame[]
     */
    private $unprocessedFrames = [];

    /**
     * @var Connection|null
     */
    private $connection;

    /**
     *
     * @var Protocol|null
     */
    private $protocol;

    /**
     * Seconds to wait for a receipt.
     *
     * @var float
     */
    private $receiptWait = 2;

    /**
     *
     * @var string
     */
    private $login;

    /**
     *
     * @var string
     */
    private $passcode;


    /**
     *
     * @var array
     */
    private $versions = [Version::VERSION_1_0, Version::VERSION_1_1, Version::VERSION_1_2];

    /**
     *
     * @var string
     */
    private $host;

    /**
     *
     * @var int[]
     */
    private $heartbeat = [0, 0];

    /**
     * @var bool
     */
    private $isConnecting = false;

    /**
     * Constructor
     *
     * @param string|Connection $broker Broker URL or a connection
     * @see Connection::__construct()
     */
    public function __construct($broker)
    {
        $this->connection = $broker instanceof Connection ? $broker : new Connection($broker);
    }

    /**
     * Configure versions to support.
     *
     * @param array $versions defaults to all client supported versions
     */
    public function setVersions(array $versions)
    {
        $this->versions = $versions;
    }

    /**
     * Configure the login to use.
     *
     * @param string $login
     * @param string $passcode
     */
    public function setLogin($login, $passcode)
    {
        $this->login = $login;
        $this->passcode = $passcode;
    }

    /**
     * Sets an fixed vhostname, which will be passed on connect as header['host'].
     *
     * (null = Default value is the hostname determined by connection.)
     *
     * @param string $host
     */
    public function setVhostname($host = null)
    {
        $this->host = $host;
    }

    /**
     * Set the desired heartbeat for the connection.
     *
     * A heartbeat is a specific message that will be send / received when no other data is send / received
     * within an interval - to indicate that the connection is still stable. If client and server agree on a beat and
     * the interval passes without any data activity / beats the connection will be considered as broken and closed.
     *
     * If you want to make sure that the server is still available, you should use the ServerAliveObserver
     * in combination with an requested server heartbeat interval.
     *
     * If you define a heartbeat for client side, you must assure that
     * your application will send data within the interval.
     * You can add \Stomp\Network\Observer\HeartbeatEmitter to your connection in order to send beats automatically.
     *
     * If you don't use HeartbeatEmitter you must either send messages within the interval
     * or make calls to Connection::sendAlive()
     *
     * @param int $send
     *   Number of milliseconds between expected sending of heartbeats. 0 means
     *   no heartbeats sent.
     * @param int $receive
     *   Number of milliseconds between expected receipt of heartbeats. 0 means
     *   no heartbeats expected. (not yet supported by this client)
     * @see \Stomp\Network\Observer\ServerAliveObserver
     * @see \Stomp\Network\Observer\HeartbeatEmitter
     * @see \Stomp\Network\Connection::sendAlive()
     */
    public function setHeartbeat($send = 0, $receive = 0)
    {
        $this->heartbeat = [$send, $receive];
    }

    /**
     * Connect to server
     *
     * @return boolean
     * @throws StompException
     * @see setVhostname
     */
    public function connect()
    {
        if ($this->isConnected()) {
            return true;
        }
        $this->isConnecting = true;
        $this->connection->connect();
        $this->connection->getParser()->legacyMode(true);
        $this->protocol = new Protocol($this->clientId);

        $this->host = $this->host ?: $this->connection->getHost();

        $connectFrame = $this->protocol->getConnectFrame(
            $this->login,
            $this->passcode,
            $this->versions,
            $this->host,
            $this->heartbeat
        );
        $this->sendFrame($connectFrame, false);

        if ($frame = $this->getConnectedFrame()) {
            $version = new Version($frame);

            if ($version->hasVersion(Version::VERSION_1_1)) {
                $this->connection->getParser()->legacyMode(false);
            }

            $this->sessionId = $frame['session'];
            $this->protocol = $version->getProtocol($this->clientId);
            $this->isConnecting = false;
            return true;
        }
        throw new ConnectionException('Connection not acknowledged');
    }

    /**
     * Returns the next available frame from the connection, respecting the connect timeout.
     *
     * @return null|Frame
     * @throws ConnectionException
     * @throws Exception\ErrorFrameException
     */
    private function getConnectedFrame()
    {
        $deadline = microtime(true) + $this->getConnection()->getConnectTimeout();
        do {
            if ($frame = $this->connection->readFrame()) {
                return $frame;
            }
        } while (microtime(true) <= $deadline);

        return null;
    }

    /**
     * Send a message to a destination in the messaging system
     *
     * @param string $destination Destination queue
     * @param string|Frame $msg Message
     * @param array $header
     * @param boolean $sync Perform request synchronously
     * @return boolean
     */
    public function send($destination, $msg, array $header = [], $sync = null)
    {
        if (!$msg instanceof Frame) {
            return $this->send($destination, new Frame('SEND', $header, $msg), [], $sync);
        }

        $msg->addHeaders($header);
        $msg['destination'] = $destination;
        return $this->sendFrame($msg, $sync);
    }

    /**
     * Send a frame.
     *
     * @param Frame $frame
     * @param boolean $sync
     * @return boolean
     */
    public function sendFrame(Frame $frame, $sync = null)
    {
        if (!$this->isConnecting && !$this->isConnected()) {
            $this->connect();
        }
        // determine if client was configured to write sync or not
        $writeSync = $sync !== null ? $sync : $this->sync;
        if ($writeSync) {
            return $this->sendFrameExpectingReceipt($frame);
        } else {
            return $this->connection->writeFrame($frame);
        }
    }


    /**
     * Write frame to server and expect an matching receipt frame
     *
     * @param Frame $stompFrame
     * @return bool
     */
    protected function sendFrameExpectingReceipt(Frame $stompFrame)
    {
        $receipt = md5(microtime());
        $stompFrame['receipt'] = $receipt;
        $this->connection->writeFrame($stompFrame);
        return $this->waitForReceipt($receipt);
    }


    /**
     * Wait for an receipt
     *
     * @param string $receipt
     * @return boolean
     * @throws UnexpectedResponseException If response has an invalid receipt.
     * @throws MissingReceiptException     If no receipt is received.
     */
    protected function waitForReceipt($receipt)
    {
        $stopAfter = $this->calculateReceiptWaitEnd();
        while (true) {
            if ($frame = $this->connection->readFrame()) {
                if ($frame->getCommand() == 'RECEIPT') {
                    if ($frame['receipt-id'] == $receipt) {
                        return true;
                    } else {
                        throw new UnexpectedResponseException($frame, sprintf('Expected receipt id %s', $receipt));
                    }
                } else {
                    $this->unprocessedFrames[] = $frame;
                }
            }
            if (microtime(true) >= $stopAfter) {
                break;
            }
        }
        throw new MissingReceiptException($receipt);
    }

    /**
     * Returns the timestamp with micro time to stop wait for a receipt.
     *
     * @return float
     */
    protected function calculateReceiptWaitEnd()
    {
        return microtime(true) + $this->receiptWait;
    }


    /**
     * Read response frame from server
     *
     * @return Frame|false when no frame to read
     */
    public function readFrame()
    {
        return array_shift($this->unprocessedFrames) ?: $this->connection->readFrame();
    }

    /**
     * Graceful disconnect from the server
     * @param bool $sync
     * @return void
     */
    public function disconnect($sync = false)
    {
        try {
            if ($this->connection && $this->connection->isConnected()) {
                if ($this->protocol) {
                    $this->sendFrame($this->protocol->getDisconnectFrame(), $sync);
                }
            }
        } catch (StompException $ex) {
            // nothing!
        }
        if ($this->connection) {
            $this->connection->disconnect();
        }

        $this->sessionId = null;
        $this->unprocessedFrames = [];
        $this->protocol = null;
        $this->isConnecting = false;
    }

    /**
     * Current stomp session ID
     *
     * @return string|null
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * Graceful object destruction
     *
     */
    public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * Check if client session has ben established
     *
     * @return boolean
     */
    public function isConnected()
    {
        return !empty($this->sessionId) && $this->connection->isConnected();
    }

    /**
     * Get the used connection.
     *
     * @return Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Get the currently used protocol.
     *
     * @return null|\Stomp\Protocol\Protocol
     */
    public function getProtocol()
    {
        if (!$this->isConnecting && !$this->isConnected()) {
            $this->connect();
        }
        return $this->protocol;
    }

    /**
     * @return string
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * @param string $clientId
     * @return Client
     */
    public function setClientId($clientId)
    {
        $this->clientId = $clientId;
        return $this;
    }


    /**
     * Set seconds to wait for a receipt.
     *
     * @param float $seconds
     */
    public function setReceiptWait($seconds)
    {
        $this->receiptWait = $seconds;
    }

    /**
     * Check if client runs in synchronized mode, which is the default operation mode.
     *
     * @return boolean
     */
    public function isSync()
    {
        return $this->sync;
    }

    /**
     * Toggle synchronized mode.
     *
     * @param boolean $sync
     */
    public function setSync($sync)
    {
        $this->sync = $sync;
    }
}
