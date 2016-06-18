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
     * @param string $subscriptionId
     * @param string $ack
     * @param string $selector
     * @param boolean|false $durable durable subscription
     * @return Frame
     */
    public function getSubscribeFrame(
        $destination,
        $subscriptionId = null,
        $ack = 'auto',
        $selector = null,
        $durable = false
    ) {
        $frame = parent::getSubscribeFrame($destination, $subscriptionId, $ack, $selector);
        $frame['activemq.prefetchSize'] = $this->prefetchSize;
        if ($durable) {
            $frame['activemq.subscriptionName'] = $this->getClientId();
        }
        return $frame;
    }

    /**
     * ActiveMq unsubscribe frame.
     *
     * @param string $destination
     * @param string $subscriptionId
     * @param bool|false $durable
     * @return Frame
     */
    public function getUnsubscribeFrame($destination, $subscriptionId = null, $durable = false)
    {
        $frame = parent::getUnsubscribeFrame($destination, $subscriptionId);
        if ($durable) {
            $frame['activemq.subscriptionName'] = $this->getClientId();
        }
        return $frame;
    }

    /**
     * @inheritdoc
     */
    public function getAckFrame(Frame $frame, $transactionId = null)
    {
        $ack = $this->createFrame('ACK');
        $ack['transaction'] = $transactionId;

        switch ($this->getVersion()) {
            case Version::VERSION_1_0:
                $ack['message-id'] = $frame['ack'] ?: $frame->getMessageId();
                break;
            case Version::VERSION_1_1:
                $ack['message-id'] = $frame['ack'] ?: $frame->getMessageId();
                if (false === is_null($frame->offsetGet('subscription'))) {
                    $ack['subscription'] = $frame->offsetGet('subscription');
                }
                break;
            case Version::VERSION_1_2:
                $ack['id'] = $frame['ack'] ?: $frame->getMessageId();
                break;
        }

        return $ack;
    }

    /**
     * @inheritdoc
     */
    public function getNackFrame(Frame $frame, $transactionId = null)
    {
        $nack = $this->createFrame('NACK');
        $nack['transaction'] = $transactionId;

        switch ($this->getVersion()) {
            case Version::VERSION_1_0:
                $nack['message-id'] = $frame['ack'] ?: $frame->getMessageId();
                break;
            case Version::VERSION_1_1:
                $nack['message-id'] = $frame['ack'] ?: $frame->getMessageId();
                if (false === is_null($frame->offsetGet('subscription'))) {
                    $nack['subscription'] = $frame->offsetGet('subscription');
                }
                break;
            case Version::VERSION_1_2:
                $nack['id'] = $frame['ack'] ?: $frame->getMessageId();
                break;
        }

        return $nack;
    }


    /**
     * Prefetch Size for subscriptions
     *
     * @return int
     */
    public function getPrefetchSize()
    {
        return $this->prefetchSize;
    }

    /**
     * Prefetch Size for subscriptions
     *
     * @param int $prefetchSize
     * @return ActiveMq
     */
    public function setPrefetchSize($prefetchSize)
    {
        $this->prefetchSize = $prefetchSize;
        return $this;
    }
}
