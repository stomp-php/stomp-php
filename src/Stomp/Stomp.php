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
use Stomp\Protocol\ActiveMq;
use Stomp\Protocol\Apollo;
use Stomp\Protocol\RabbitMq;

/* vim: set expandtab tabstop=3 shiftwidth=3: */

/**
 * A Stomp Connection
 *
 *
 * @package Stomp
 * @author Hiram Chirino <hiram@hiramchirino.com>
 * @author Dejan Bosanac <dejan@nighttale.net>
 * @author Michael Caplan <mcaplan@labnet.net>
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class Stomp
{
    /**
     * Perform request synchronously
     *
     * @var boolean
     */
    public $sync = true;

    /**
     * Default prefetch size
     *
     * @var int
     */
    public $prefetchSize = 1;

    /**
     * Client id used for durable subscriptions
     *
     * @var string
     */
    public $clientId = null;

    /**
    * Vendor flavouring (AMQ or RMQ at the moment)
    */
    public $brokerVendor = 'AMQ';

    /**
     * active subscriptions
     *
     * @var string[]
     */
    private $subscriptions = array();

    /**
     * Connection session id
     *
     * @var string
     */
    private $sessionId;

    /**
     * Frames that have been read but not processed yet.
     *
     * @var Frame[]
     */
    private $unprocessedFrames = array();

    /**
     *
     * @var Connection
     */
    private $connection;

    /**
     *
     * @var Protocol
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
    private $login = null;

    /**
     *
     * @var string
     */
    private $passcode = null;

    /**
     * Constructor
     *
     * @param string|Connection $broker Broker URL or a connection
     * @param string $login
     * @param string $passcode
     * @throws StompException
     * @see Connection::__construct()
     */
    public function __construct($broker, $login = null, $passcode = null)
    {
        $this->connection = $broker instanceof Connection ? $broker : new Connection($broker);
        $this->login = $login;
        $this->passcode = $passcode;
    }

    /**
     * Connect to server
     *
     * @param string $login
     * @param string $passcode
     * @return boolean
     * @throws StompException
     */
    public function connect($login = null, $passcode = null)
    {
        if ($login !== null) {
            $this->login = $login;
        }
        if ($passcode !== null) {
            $this->passcode = $passcode;
        }
        $this->connection->connect();
        $this->protocol = new Protocol($this->prefetchSize, $this->clientId);
        $this->sendFrame($this->protocol->getConnectFrame($this->login, $this->passcode), false);
        if ($frame = $this->connection->readFrame()) {
            if ($frame->command != 'CONNECTED') {
                throw new UnexpectedResponseException($frame, 'Expected a CONNECTED Frame!');
            }
            $this->sessionId = $frame->headers['session'];
            if (isset($frame->headers['server']) && false !== stristr(trim($frame->headers['server']), 'rabbitmq')) {
                $this->brokerVendor = 'RMQ';
                $this->protocol = new RabbitMq($this->protocol);
            } elseif (isset($frame->headers['server']) && false !== stristr(trim($frame->headers['server']), 'apache-apollo')) {
                $this->protocol = new Apollo($this->protocol);
            } else {
                $this->protocol = new ActiveMq($this->protocol);
            }
            return true;
        }
        throw new ConnectionException('Connection not acknowledged');
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
    public function send($destination, $msg, array $header = array(), $sync = null)
    {
        if (!$msg instanceof Frame) {
            return $this->send($destination, new Frame('SEND', $header, $msg), array(), $sync);
        }

        $msg->addHeaders($header);
        $msg->setHeader('destination', $destination);
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
        $stompFrame->setHeader('receipt', $receipt);
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
                if ($frame->command == 'RECEIPT') {
                    if ($frame->headers['receipt-id'] == $receipt) {
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
     * Returns the timestamp with microtime to stop wait for a receipt.
     *
     * @return float
     */
    protected function calculateReceiptWaitEnd()
    {
        return microtime(true) + $this->receiptWait;
    }

    /**
     * Register to listen to a given destination
     *
     * @param string $destination Destination queue
     * @param array $properties
     * @param boolean $sync Perform request synchronously
     * @param boolean $durable durable subscription
     * @return boolean
     * @throws StompException
     */
    public function subscribe($destination, $properties = null, $sync = null, $durable = false)
    {
        $subscribe = $this->sendFrame(
            $this->protocol->getSubscribeFrame(
                $destination,
                $properties ?: array(),
                $durable
            ),
            $sync
        );

        return $this->subscriptions[$destination] = $subscribe;
    }
    /**
     * Remove an existing subscription
     *
     * @param string $destination
     * @param array $properties
     * @param boolean $sync Perform request synchronously
     * @param boolean $durable durable subscription
     * @return boolean
     * @throws StompException
     */
    public function unsubscribe($destination, $properties = null, $sync = null, $durable = false)
    {
        $unsubscribe = $this->sendFrame(
            $this->protocol->getUnsubscribeFrame(
                $destination,
                $properties ?: array(),
                $durable
            ),
            $sync
        );

        if ($unsubscribe) {
            $this->subscriptions[$destination] = false;
        }

        return $unsubscribe;
    }
    /**
     * Start a transaction
     *
     * @param string $transactionId
     * @param boolean $sync Perform request synchronously
     * @return boolean
     * @throws StompException
     */
    public function begin($transactionId = null, $sync = null)
    {
        return $this->sendFrame($this->protocol->getBeginFrame($transactionId), $sync);
    }
    /**
     * Commit a transaction in progress
     *
     * @param string $transactionId
     * @param boolean $sync Perform request synchronously
     * @return boolean
     * @throws StompException
     */
    public function commit($transactionId = null, $sync = null)
    {
        return $this->sendFrame($this->protocol->getCommitFrame($transactionId), $sync);
    }

    /**
     * Roll back a transaction in progress
     *
     * @param string $transactionId
     * @param boolean $sync Perform request synchronously
     * @return bool
     */
    public function abort($transactionId = null, $sync = null)
    {
        return $this->sendFrame($this->protocol->getAbortFrame($transactionId), $sync);
    }
    /**
     * Acknowledge consumption of a message from a subscription
     * Note: This operation is always asynchronous
     *
     * @param string|Frame $message ID to ack
     * @param string $transactionId
     * @return boolean
     * @throws StompException
     */
    public function ack($message, $transactionId = null)
    {
        if ($message instanceof Frame) {
            return $this->sendFrame($this->protocol->getAckFrame($message->getMessageId(), $transactionId), false);
        } else {
            return $this->sendFrame($this->protocol->getAckFrame($message, $transactionId), false);
        }
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
     *
     */
    public function disconnect()
    {
        try {
            if ($this->connection && $this->connection->isConnected()) {
                if ($this->protocol) {
                    $this->sendFrame($this->protocol->getDisconnectFrame(), false);
                }
                $this->connection->disconnect();
            }
        } catch (StompException $ex) {
            // nothing!
        }
        $this->sessionId = null;
        $this->subscriptions = array();
        $this->unprocessedFrames = array();
        $this->protocol = null;
    }

    /**
     * Current stomp session ID
     *
     * @return string
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
     * Protocol is only set after calling connect().
     *
     * @return null|Protocol
     */
    public function getProtocol()
    {
        return $this->protocol;
    }


    /**
     * Set timeout to wait for content to read
     *
     * @param int $seconds  Seconds to wait for a frame
     * @param int $milliseconds Milliseconds to wait for a frame
     * @return void
     *
     * @deprecated use $client->getConnection()
     */
    public function setReadTimeout($seconds, $milliseconds = 0)
    {
        $this->connection->setReadTimeout(array($seconds, $milliseconds));
    }


    /**
     * Connection has data to read.
     *
     * @return boolean
     * @deprecated use $client->getConnection()
     */
    public function hasFrameToRead()
    {
        return $this->connection->hasDataToRead();
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
}
