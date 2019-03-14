<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp;

use Stomp\Exception\StompException;
use Stomp\Protocol\Protocol;
use Stomp\Transport\Frame;
use Stomp\Transport\Message;

/**
 * Simple Stomp Client
 *
 * This is a legacy implementation of the old Stomp Client (Version 2-3).
 * It's an almost stateless client, only wrapping some protocol calls for you.
 *
 * @package Stomp
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class SimpleStomp
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * LegacyStomp constructor.
     *
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Read response frame from server
     *
     * @return Frame|false when no frame to read
     */
    public function read()
    {
        return $this->client->readFrame();
    }

    /**
     * Register to listen to a given destination
     *
     * @param string $destination Destination queue
     * @param null $subscriptionId
     * @param string $ack
     * @param string $selector
     * @param array $header
     * @return bool
     */
    public function subscribe($destination, $subscriptionId = null, $ack = 'auto', $selector = null, array $header = [])
    {
        return $this->client->sendFrame(
            $this->getProtocol()->getSubscribeFrame($destination, $subscriptionId, $ack, $selector)->addHeaders($header)
        );
    }

    /**
     * @return Protocol
     */
    protected function getProtocol()
    {
        return $this->client->getProtocol();
    }

    /**
     * Send a message
     *
     * @param string $destination
     * @param Message $message
     * @return bool
     * @throws StompException
     */
    public function send($destination, Message $message)
    {
        return $this->client->send($destination, $message);
    }

    /**
     * Remove an existing subscription
     *
     * @param string $destination
     * @param string $subscriptionId
     * @param array $header
     * @return boolean
     * @throws StompException
     */
    public function unsubscribe($destination, $subscriptionId = null, array $header = [])
    {
        return $this->client->sendFrame(
            $this->getProtocol()->getUnsubscribeFrame($destination, $subscriptionId)->addHeaders($header)
        );
    }

    /**
     * Start a transaction
     *
     * @param string $transactionId
     * @return boolean
     * @throws StompException
     */
    public function begin($transactionId = null)
    {
        return $this->client->sendFrame($this->getProtocol()->getBeginFrame($transactionId));
    }

    /**
     * Commit a transaction in progress
     *
     * @param string $transactionId
     * @return boolean
     * @throws StompException
     */
    public function commit($transactionId = null)
    {
        return $this->client->sendFrame($this->getProtocol()->getCommitFrame($transactionId));
    }

    /**
     * Roll back a transaction in progress
     *
     * @param string $transactionId
     * @return bool
     */
    public function abort($transactionId = null)
    {
        return $this->client->sendFrame($this->getProtocol()->getAbortFrame($transactionId));
    }

    /**
     * Acknowledge consumption of a message from a subscription
     *
     * @param Frame $frame
     * @return void
     */
    public function ack(Frame $frame)
    {
        $this->client->sendFrame($this->getProtocol()->getAckFrame($frame), false);
    }

    /**
     * Not acknowledge consumption of a message from a subscription
     *
     * @param Frame $frame
     * @return void
     */
    public function nack(Frame $frame)
    {
        $this->client->sendFrame($this->getProtocol()->getNackFrame($frame), false);
    }
}
