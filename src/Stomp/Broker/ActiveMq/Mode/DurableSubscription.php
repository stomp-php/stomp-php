<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Broker\ActiveMq\Mode;

use Stomp\Client;
use Stomp\Exception\StompException;
use Stomp\States\Meta\Subscription;
use Stomp\Transport\Frame;

/**
 * DurableSubscription for ActiveMq.
 *
 * @package Stomp\Broker\ActiveMq\Mode
 * @author Jens Radtke <swefl.oss@fin-sn.de>l
 */
class DurableSubscription extends ActiveMqMode
{
    /**
     * @var Subscription
     */
    private $subscription;

    /**
     * Subscription state
     *
     * @var bool
     */
    private $active = false;

    /**
     * DurableSubscription constructor.
     * @param Client $client
     * @param string $topic
     * @param string $selector
     * @param string $ack
     * @throws StompException
     */
    public function __construct(Client $client, $topic, $selector = null, $ack = 'auto')
    {
        parent::__construct($client);
        if (!$client->getClientId()) {
            throw new StompException('Client must have been configured to use a specific clientId!');
        }
        $this->subscription = new Subscription($topic, $selector, $ack, $client->getClientId());
    }

    /**
     * Init the subscription.
     *
     * @return void
     */
    public function activate()
    {
        if (!$this->active) {
            $this->client->sendFrame(
                $this->getProtocol()->getSubscribeFrame(
                    $this->subscription->getDestination(),
                    $this->subscription->getSubscriptionId(),
                    $this->subscription->getAck(),
                    $this->subscription->getSelector(),
                    true
                )->addHeaders(
                    $this->options->getOptions()
                )
            );
            $this->active = true;
        }
    }

    /**
     * Mark durable subscription as offline.
     *
     * @see deactivate() if you want to indicate that the consumer is permanently removed.
     * @return void
     */
    public function inactive()
    {
        if ($this->active) {
            $this->client->sendFrame(
                $this->getProtocol()
                    ->getUnsubscribeFrame(
                        $this->subscription->getDestination(),
                        $this->subscription->getSubscriptionId()
                    )
            );
            $this->active = false;
        }
    }

    /**
     * Permanently remove durable subscription.
     *
     * @see inactive() if you just want to indicate that the consumer is offline now.
     * @return void
     */
    public function deactivate()
    {
        if ($this->active) {
            $this->inactive();
            $this->client->sendFrame(
                $this->getProtocol()
                    ->getUnsubscribeFrame(
                        $this->subscription->getDestination(),
                        $this->subscription->getSubscriptionId(),
                        true
                    )
            );
            $this->active = false;
        }
    }

    /**
     * Reads a frame.
     *
     * @return false|\Stomp\Transport\Frame
     */
    public function read()
    {
        return $this->client->readFrame();
    }

    /**
     * Ack a frame.
     *
     * @param Frame $frame
     * @return void
     */
    public function ack(Frame $frame)
    {
        $this->client->sendFrame($this->getProtocol()->getAckFrame($frame));
    }

    /**
     * Nack a frame.
     *
     * @param Frame $frame
     * @return void
     */
    public function nack(Frame $frame)
    {
        $this->client->sendFrame($this->getProtocol()->getNackFrame($frame));
    }

    /**
     * Returns the Subscription details.
     *
     * @return Subscription
     */
    public function getSubscription()
    {
        return $this->subscription;
    }

    /**
     * Check if subscription is currently active.
     *
     * @return boolean
     */
    public function isActive()
    {
        return $this->active;
    }
}
