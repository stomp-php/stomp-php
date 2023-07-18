<?php

/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Broker\ActiveMq;

use Stomp\Protocol\Protocol;
use Stomp\Protocol\Version;
use Stomp\Transport\Frame;

/**
 * ActiveMq Stomp dialect.
 *
 *
 * @package Stomp
 * @author Hiram Chirino <hiram@hiramchirino.com>
 * @author Dejan Bosanac <dejan@nighttale.net>
 * @author Michael Caplan <mcaplan@labnet.net>
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class ActiveMq extends Protocol
{
    /**
     * Prefetch Size for subscriptions.
     *
     * @var int
     */
    private $prefetchSize = 1;

    /**
     * ActiveMq subscribe frame.
     *
     * @param string $destination
     * @param string|null $subscriptionId
     * @param string $ack
     * @param string|null $selector
     * @param boolean $durable durable subscription
     * @return Frame
     */
    public function getSubscribeFrame(
        string $destination,
        ?string $subscriptionId = null,
        string $ack = 'auto',
        ?string $selector = null,
        bool $durable = false
    ): Frame {
        $frame = parent::getSubscribeFrame($destination, $subscriptionId, $ack, $selector);
        $frame['activemq.prefetchSize'] = $this->prefetchSize;
        if ($durable) {
            $frame['activemq.subscriptionName'] = $this->getClientId();
            $frame['durable-subscriber-name'] = $subscriptionId;
        }
        return $frame;
    }

    /**
     * ActiveMq unsubscribe frame.
     *
     * @param string $destination The destination to unsubscribe from.
     * @param string|null $subscriptionId The subscription id to unsubscribe from.
     * @param bool $durable Whether this was a durable subscription.
     * @return Frame
     */
    public function getUnsubscribeFrame(
        string $destination,
        ?string $subscriptionId = null,
        bool $durable = false
    ): Frame {
        $frame = parent::getUnsubscribeFrame($destination, $subscriptionId);
        if ($durable) {
            $frame['activemq.subscriptionName'] = $this->getClientId();
            $frame['durable-subscriber-name'] = $subscriptionId;
        }
        return $frame;
    }

    /**
     * @inheritdoc
     */
    public function getAckFrame(Frame $frame, ?string $transactionId = null): Frame
    {
        $ack = $this->createFrame('ACK');
        $ack['transaction'] = $transactionId;
        if ($this->hasVersion(Version::VERSION_1_2)) {
            $ack['id'] = $frame['ack'] ?: $frame->getMessageId();
        } else {
            $ack['message-id'] = $frame['ack'] ?: $frame->getMessageId();
            if ($this->hasVersion(Version::VERSION_1_1)) {
                $ack['subscription'] = $frame['subscription'];
            }
        }
        return $ack;
    }

    /**
     * @inheritdoc
     */
    public function getNackFrame(Frame $frame, ?string $transactionId = null, ?bool $requeue = null): Frame
    {
        if ($requeue !== null) {
            throw new \LogicException(
                'requeue header not supported by ActiveMQ. Please read ActiveMQ DLQ documentation.'
            );
        }
        $nack = $this->createFrame('NACK');
        $nack['transaction'] = $transactionId;
        if ($this->hasVersion(Version::VERSION_1_2)) {
            $nack['id'] = $frame['ack'] ?: $frame->getMessageId();
        } else {
            $nack['message-id'] = $frame['ack'] ?: $frame->getMessageId();
            if ($this->hasVersion(Version::VERSION_1_1)) {
                $nack['subscription'] = $frame['subscription'];
            }
        }
        return $nack;
    }


    /**
     * Prefetch Size for subscriptions
     *
     * @return int
     */
    public function getPrefetchSize(): int
    {
        return $this->prefetchSize;
    }

    /**
     * Prefetch Size for subscriptions
     *
     * @param int $prefetchSize
     * @return ActiveMq
     */
    public function setPrefetchSize(int $prefetchSize): self
    {
        $this->prefetchSize = $prefetchSize;
        return $this;
    }
}
