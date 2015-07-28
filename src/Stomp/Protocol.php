<?php

namespace Stomp;

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
 * Stomp base protocol (1.0)
 *
 *
 * @package Stomp
 * @author Hiram Chirino <hiram@hiramchirino.com>
 * @author Dejan Bosanac <dejan@nighttale.net>
 * @author Michael Caplan <mcaplan@labnet.net>
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class Protocol
{
    /**
     * Default prefetch size
     *
     * @var int
     */
    private $_prefetchSize = 1;

	/**
     * Client id used for durable subscriptions
     *
     * @var string
     */
	private $_clientId = null;

    /**
     * Setup stomp protocol with configuration.
     *
     * @param integer $prefetchSize
     * @param integer $clientId
     */
    public function __construct ($prefetchSize, $clientId)
    {
        $this->_prefetchSize = $prefetchSize;
        $this->_clientId = $clientId;
    }

    /**
     * Get the connect frame
     *
     * @param string $login
     * @param string $passcode
     * @return Frame
     */
    public final function getConnectFrame ($login = '', $passcode = '')
    {
        $frame = new Frame("CONNECT");

        if ($login || $passcode) {
            $frame->addHeaders(compact('login', 'passcode'));
        }

		if ($this->hasClientId()) {
            $frame->setHeader('client-id', $this->getClientId());
		}
        return $frame;
    }

    /**
     * Get subscribe frame.
     *
     * @param string $destination
     * @param array $headers
     * @param boolean $durable durable subscription
     * @return Frame
     */
    public function getSubscribeFrame ($destination, array $headers = array(), $durable = false)
    {
        $frame = new Frame('SUBSCRIBE');
        $frame->setHeader('ack', 'client');
        $frame->addHeaders($headers);
        $frame->setHeader('destination', $destination);
        $this->addClientId($frame);
        return $frame;
    }

    /**
     * Get unsubscribe frame.
     *
     * @param string $destination
     * @param array $headers
     * @param boolean $durable durable subscription
     * @return Frame
     */
    public function getUnsubscribeFrame ($destination, array $headers = array(), $durable = false)
    {
        $frame = new Frame('UNSUBSCRIBE');
        $frame->addHeaders($headers);
        $frame->setHeader('destination', $destination);
        return $frame;
    }

    /**
     * Get transaction begin frame.
     *
     * @param string $transactionId
     * @return Frame
     */
    public function getBeginFrame ($transactionId = null)
    {
        $frame = new Frame('BEGIN');
        if ($transactionId) {
            $frame->setHeader('transaction', $transactionId);
        }
        return $frame;
    }

    /**
     * Get transaction commit frame.
     *
     * @param string $transactionId
     * @return Frame
     */
    public function getCommitFrame ($transactionId = null)
    {
        $frame = new Frame('COMMIT');
        if ($transactionId) {
            $frame->setHeader('transaction', $transactionId);
        }
        return $frame;
    }

    /**
     * Get transaction abort frame.
     *
     * @param string $transactionId
     * @return Frame
     */
    public function getAbortFrame ($transactionId = null)
    {
        $frame = new Frame('ABORT');
        if ($transactionId) {
            $frame->setHeader('transaction', $transactionId);
        }
        return $frame;
    }

    /**
     * Get message acknowledge frame.
     *
     * @param string $messageId
     * @param string $transactionId
     * @return Frame
     */
    public function getAckFrame ($messageId, $transactionId = null)
    {
        $frame = new Frame('ACK');
        if ($transactionId) {
            $frame->setHeader('transaction', $transactionId);
        }
        $frame->setHeader('message-id', $messageId);
        return $frame;
    }

    /**
     * Get the disconnect frame.
     *
     * @return Frame
     */
    public function getDisconnectFrame()
    {
        $frame = new Frame('DISCONNECT');
        if ($this->hasClientId()) {
            $frame->setHeader("client-id", $this->getClientId());
        }
        return $frame;
    }

    /**
     * Configured prefetch size.
     *
     * @return integer
     */
    public function getPrefetchSize ()
    {
        return $this->_prefetchSize;
    }

    /**
     * Client Id is set
     *
     * @return boolean
     */
    public function hasClientId ()
    {
        return (boolean) $this->_clientId;
    }

    /**
     * Client Id is set
     *
     * @return string
     */
    public function getClientId ()
    {
        return $this->_clientId;
    }

    /**
     * Add client id to frame.
     *
     * @param Frame $frame
     * @return void
     */
    protected function addClientId (Frame $frame)
    {
        if ($this->hasClientId()) {
            $frame->setHeader('id', $this->getClientId());
        }
    }
}
