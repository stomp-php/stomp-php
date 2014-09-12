<?php

namespace FuseSource\Stomp;

use FuseSource\Stomp\Exception\StompException;
use FuseSource\Stomp\Protocol\ActiveMq;
use FuseSource\Stomp\Protocol\RabbitMq;

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
    public $sync = false;

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
    private $_subscriptions = array();

    /**
     * Connection session id
     *
     * @var string
     */
    private $_sessionId;

    /**
     * Frames that have been readed but not processed yet.
     *
     * @var Frame[]
     */
    private $_unprocessedFrames = array();

    /**
     *
     * @var Connection
     */
    private $_connection;

    /**
     *
     * @var Protocol
     */
    private $_protocol;

    /**
     * Constructor
     *
     * @param string $brokerUri Broker URL
     * @throws StompException
     */
    public function __construct ($brokerUri)
    {
        $this->_connection = new Connection($brokerUri);
    }

    /**
     * Connect to server
     *
     * @param string $login
     * @param string $passcode
     * @return boolean
     * @throws StompException
     */
    public function connect ($login = '', $passcode = '')
    {
        $this->_connection->connect();
        $this->_protocol = new Protocol($this->prefetchSize, $this->clientId);
        $this->sendFrame($this->_protocol->getConnectFrame($login, $passcode), false);
        if ($frame = $this->_connection->readFrame()) {
            if ($frame->command != 'CONNECTED') {
                throw new StompException("Unexpected command: {$frame->command}", 0, $frame->body);
            }
            $this->_sessionId = $frame->headers["session"];
            if (isset($frame->headers['server']) && false !== stristr(trim($frame->headers['server']), 'rabbitmq')) {
                $this->brokerVendor = 'RMQ';
                $this->_protocol = new RabbitMq($this->_protocol);
            } else {
                $this->_protocol = new ActiveMq($this->_protocol);
            }
            return true;
        }
        throw new StompException("Connection not acknowledged");
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
    public function send ($destination, $msg, array $header = array(), $sync = null)
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
    public function sendFrame(Frame $frame, $sync = null) {
        // determine if client was configured to write sync or not
        $writeSync = $sync !== null ? $sync : $this->sync;
        if ($writeSync) {
            return $this->_sendFrameExpectingReceipt($frame);
        } else {
            return $this->_connection->writeFrame($frame);
        }
    }


    /**
     * Write frame to server and expect an matching receipt frame
     *
     * @param Frame $stompFrame
     */
    protected function _sendFrameExpectingReceipt (Frame $stompFrame)
    {
        $stompFrame->setHeader('receipt', md5(microtime()));
        $this->_connection->writeFrame($stompFrame);
        return $this->_waitForReceipt($stompFrame);
    }


    /**
     * Wait for an receipt
     *
     * @param Frame $frame
     * @return boolean
     * @throws StompException
     */
    protected function _waitForReceipt (Frame $frame)
    {
        $id = (isset($frame->headers['receipt'])) ? $frame->headers['receipt'] : null;
        if ($id === null) {
            return true;
        }

        while(true) {
            if ($frame = $this->_connection->readFrame()) {
                if ($frame->command == 'RECEIPT') {
                    if ($frame->headers['receipt-id'] == $id) {
                        return true;
                    } else {
                        throw new StompException("Unexpected receipt id {$frame->headers['receipt-id']}", 0, $frame->body);
                    }
                } else {
                    $this->_unprocessedFrames[] = $frame;
                }
            } else {
                return false;
            }
        }
    }
    /**
     * Register to listen to a given destination
     *
     * @param string $destination Destination queue
     * @param array $properties
     * @param boolean $sync Perform request synchronously
     * @return boolean
     * @throws StompException
     */
    public function subscribe ($destination, $properties = null, $sync = null)
    {
        if ($this->sendFrame($this->_protocol->getSubscribeFrame($destination, $properties ?: array()), $sync)) {
            $this->_subscriptions[$destination] = true;
            return true;
        } else {
            return false;
        }
    }
    /**
     * Remove an existing subscription
     *
     * @param string $destination
     * @param array $properties
     * @param boolean $sync Perform request synchronously
     * @return boolean
     * @throws StompException
     */
    public function unsubscribe ($destination, $properties = null, $sync = null)
    {
        if ($this->sendFrame($this->_protocol->getUnsubscribeFrame($destination, $properties ?: array()), $sync)) {
            unset($this->_subscriptions[$destination]);
            return true;
        } else {
            return false;
        }
    }
    /**
     * Start a transaction
     *
     * @param string $transactionId
     * @param boolean $sync Perform request synchronously
     * @return boolean
     * @throws StompException
     */
    public function begin ($transactionId = null, $sync = null)
    {
        return $this->sendFrame($this->_protocol->getBeginFrame($transactionId), $sync);
    }
    /**
     * Commit a transaction in progress
     *
     * @param string $transactionId
     * @param boolean $sync Perform request synchronously
     * @return boolean
     * @throws StompException
     */
    public function commit ($transactionId = null, $sync = null)
    {
        return $this->sendFrame($this->_protocol->getCommitFrame($transactionId), $sync);
    }
    /**
     * Roll back a transaction in progress
     *
     * @param string $transactionId
     * @param boolean $sync Perform request synchronously
     */
    public function abort ($transactionId = null, $sync = null)
    {
        return $this->sendFrame($this->_protocol->getAbortFrame($transactionId), $sync);
    }
    /**
     * Acknowledge consumption of a message from a subscription
	 * Note: This operation is always asynchronous
     *
     * @param string|Frame $messageMessage ID
     * @param string $transactionId
     * @return boolean
     * @throws StompException
     */
    public function ack ($message, $transactionId = null)
    {
        if ($message instanceof Frame) {
            return $this->sendFrame($this->_protocol->getAckFrame($message->getMessageId(), $transactionId), false);
        } else {
            return $this->sendFrame($this->_protocol->getAckFrame($message, $transactionId), false);
        }
    }

    /**
     * Read response frame from server
     *
     * @return Frame False when no frame to read
     */
    public function readFrame ()
    {
        return $this->_readBufferedFrame() ?: $this->_connection->readFrame();
    }

    /**
     * Read next buffered frame.
     *
     * @return Frame null when no frame to read
     */
    protected function _readBufferedFrame ()
    {
        return array_shift($this->_unprocessedFrames);
    }

    /**
     * Graceful disconnect from the server
     *
     */
    public function disconnect ()
    {
        try {
            if ($this->_connection->isConnected()) {
                $frame = new Frame('DISCONNECT');
                if ($this->clientId != null) {
                    $frame->setHeader("client-id", $this->clientId);
                }
                $this->sendFrame($frame, false);
                $this->_connection->diconnect();
            }
        } catch (StompException $ex) {
            // nothing!
        }
        $this->_sessionId = null;
        $this->_subscriptions = array();
        $this->_unprocessedFrames = array();
    }

    /**
     * Current stomp session ID
     *
     * @return string
     */
    public function getSessionId()
    {
        return $this->_sessionId;
    }

    /**
     * Graceful object desruction
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
    public function isConnected ()
    {
        return !empty($this->_sessionId) && $this->_connection->isConnected();
    }

    /**
     * Get the used connection.
     *
     * @return Connection
     */
    public function getConnection()
    {
        return $this->_connection;
    }


    /**
     * Set timeout to wait for content to read
     *
     * @param int $seconds_to_wait  Seconds to wait for a frame
     * @param int $milliseconds Milliseconds to wait for a frame
     * @return void
     *
     * @deprecated use $client->getConnection()
     */
    public function setReadTimeout($seconds, $milliseconds = 0)
    {
        $this->_connection->setReadTimeout(array($seconds, $milliseconds));
    }


    /**
     * Connection has data to read.
     *
     * @return boolean
     * @deprecated use $client->getConnection()
     */
    public function hasFrameToRead()
    {
        return $this->_connection->hasDataToRead();
    }
}
