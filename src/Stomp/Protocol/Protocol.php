<?php

/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Protocol;

use Stomp\Exception\StompException;
use Stomp\Transport\Frame;

/**
 * Stomp base protocol
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
     * Client id used for durable subscriptions
     *
     * @var string
     */
    private $clientId;

    /**
     * @var string
     */
    private $version;

    /**
     * Server Version
     *
     * @var string
     */
    private $server;

    /**
     * Setup stomp protocol with configuration.
     *
     * @param string $clientId
     * @param string $version
     * @param string $server
     */
    public function __construct($clientId, $version = Version::VERSION_1_0, $server = null)
    {
        $this->clientId = $clientId;
        $this->server = $server;
        $this->version = $version;

    }

    /**
     * Get the connect frame
     *
     * @param string $login
     * @param string $passcode
     * @param array $versions
     * @param string $host
     * @return \Stomp\Transport\Frame
     */
    final public function getConnectFrame($login = '', $passcode = '', array $versions = [], $host = null)
    {
        $frame = new Frame('CONNECT');
        $frame->legacyMode(true);

        if ($login || $passcode) {
            $frame->addHeaders(compact('login', 'passcode'));
        }

        if ($this->hasClientId()) {
            $frame['client-id'] = $this->getClientId();
        }

        if (!empty($versions)) {
            $frame['accept-version'] = implode(',', $versions);
        }

        $frame['host'] = $host;

        return $frame;
    }

    /**
     * Get subscribe frame.
     *
     * @param string $destination
     * @param string $subscriptionId
     * @param string $ack
     * @param string $selector
     * @return \Stomp\Transport\Frame
     */
    public function getSubscribeFrame($destination, $subscriptionId = null, $ack = 'auto', $selector = null)
    {
        $frame = new Frame('SUBSCRIBE');

        $frame['destination'] = $destination;
        $frame['ack'] = $ack;
        $frame['id'] = $subscriptionId;
        $frame['selector'] = $selector;
        return $frame;
    }

    /**
     * Get unsubscribe frame.
     *
     * @param string $destination
     * @param string $subscriptionId
     * @return \Stomp\Transport\Frame
     */
    public function getUnsubscribeFrame($destination, $subscriptionId = null)
    {
        $frame = new Frame('UNSUBSCRIBE');
        $frame['destination'] = $destination;
        $frame['id'] = $subscriptionId;
        return $frame;
    }

    /**
     * Get transaction begin frame.
     *
     * @param string $transactionId
     * @return \Stomp\Transport\Frame
     */
    public function getBeginFrame($transactionId = null)
    {
        $frame = new Frame('BEGIN');
        $frame['transaction'] = $transactionId;
        return $frame;
    }

    /**
     * Get transaction commit frame.
     *
     * @param string $transactionId
     * @return \Stomp\Transport\Frame
     */
    public function getCommitFrame($transactionId = null)
    {
        $frame = new Frame('COMMIT');
        $frame['transaction'] = $transactionId;
        return $frame;
    }

    /**
     * Get transaction abort frame.
     *
     * @param string $transactionId
     * @return \Stomp\Transport\Frame
     */
    public function getAbortFrame($transactionId = null)
    {
        $frame = new Frame('ABORT');
        $frame['transaction'] = $transactionId;
        return $frame;
    }

    /**
     * Get message acknowledge frame.
     *
     * @param Frame $frame
     * @param string $transactionId
     * @return Frame
     */
    public function getAckFrame(Frame $frame, $transactionId = null)
    {
        $ack = new Frame('ACK');
        $ack['transaction'] = $transactionId;
        if ($this->hasVersion(Version::VERSION_1_2)) {
            $ack['id'] = $frame->getMessageId();
        } else {
            $ack['message-id'] = $frame->getMessageId();
        }
        return $ack;
    }

    /**
     * Get message not acknowledge frame.
     *
     * @param \Stomp\Transport\Frame $frame
     * @param string $transactionId
     * @return \Stomp\Transport\Frame
     * @throws StompException
     */
    public function getNackFrame(Frame $frame, $transactionId = null)
    {
        if ($this->version === Version::VERSION_1_0) {
            throw new StompException('Stomp Version 1.0 has no support for NACK Frames.');
        }
        $nack = new Frame('NACK');
        $nack['transaction'] = $transactionId;
        if ($this->hasVersion(Version::VERSION_1_2)) {
            $ack['id'] = $frame->getMessageId();
        } else {
            $ack['message-id'] = $frame->getMessageId();
        }

        $ack['message-id'] = $frame->getMessageId();
        return $nack;
    }

    /**
     * Get the disconnect frame.
     *
     * @return \Stomp\Transport\Frame
     */
    public function getDisconnectFrame()
    {
        $frame = new Frame('DISCONNECT');
        if ($this->hasClientId()) {
            $frame['client-id'] = $this->getClientId();
        }
        return $frame;
    }

    /**
     * Client Id is set
     *
     * @return boolean
     */
    public function hasClientId()
    {
        return (boolean) $this->clientId;
    }

    /**
     * Client Id is set
     *
     * @return string
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * Stomp Version
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Server Version Info
     *
     * @return string
     */
    public function getServer()
    {
        return $this->server;
    }


    public function hasVersion($version)
    {
        return version_compare($this->version, $version, '>=');
    }
}