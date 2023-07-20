<?php

/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Broker\Apollo;

use Stomp\Protocol\Protocol;
use Stomp\Protocol\Version;
use Stomp\Transport\Frame;

/**
 * Apollo Stomp dialect.
 *
 * @package Stomp
 * @author András Rutkai <riskawarrior@live.com>
 */
class Apollo extends Protocol
{
    /**
     * Apollo subscribe frame.
     *
     * @param string $destination The destination to subscribe to.
     * @param string|null $subscriptionId A subscription id.
     * @param string $ack The ACK selection.
     * @param string|null $selector An Sql 92 selector.
     * @param boolean $durable durable subscription
     * @return \Stomp\Transport\Frame The SUBSCRIBE frame
     */
    public function getSubscribeFrame(
        string $destination,
        ?string $subscriptionId = null,
        string $ack = 'auto',
        ?string $selector = null,
        bool $durable = false
    ): Frame {
        $frame = parent::getSubscribeFrame($destination, $subscriptionId, $ack, $selector);
        if ($this->hasClientId() && $durable) {
            $frame['persistent'] = 'true';
        }
        return $frame;
    }

    /**
     * Apollo unsubscribe frame.
     *
     * @param string $destination The destination to unsubscribe from.
     * @param string|null $subscriptionId The subscription id to unsubscribe from.
     * @param bool $durable Whether this was a durable subscription.
     * @return \Stomp\Transport\Frame The UNSUBSCRIBE frame
     */
    public function getUnsubscribeFrame(
        string $destination,
        ?string $subscriptionId = null,
        bool $durable = false
    ): Frame {
        $frame = parent::getUnsubscribeFrame($destination, $subscriptionId);
        if ($durable) {
            $frame['persistent'] = 'true';
        }
        return $frame;
    }

    /**
     * @inheritdoc
     *
     * @note Apollo seems to allow 'ack' header for ack messages. This is not spec compliant.
     */
    public function getAckFrame(Frame $frame, string $transactionId = null): Frame
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
     *
     * @note Apollo seems to allow 'ack' header for nack messages. This is not spec compliant.
     */
    public function getNackFrame(Frame $frame, ?string $transactionId = null, ?bool $requeue = null): Frame
    {
        if ($requeue !== null) {
            throw new \LogicException('requeue header not supported');
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
}
