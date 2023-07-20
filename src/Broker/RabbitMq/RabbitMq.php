<?php

/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Broker\RabbitMq;

use Stomp\Exception\StompException;
use Stomp\Protocol\Protocol;
use Stomp\Transport\Frame;
use Stomp\Protocol\Version;

/**
 * RabbitMq Stomp dialect.
 *
 *
 * @package Stomp
 * @author Hiram Chirino <hiram@hiramchirino.com>
 * @author Dejan Bosanac <dejan@nighttale.net>
 * @author Michael Caplan <mcaplan@labnet.net>
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class RabbitMq extends Protocol
{

    /**
     * Prefetch Size for subscriptions.
     *
     * @var int
     */

    private $prefetchCount = 1;

    /**
     * RabbitMq subscribe frame.
     *
     * @param string $destination The destination to subscribe to.
     * @param string|null $subscriptionId A subscription id.
     * @param string $ack The ACK selection.
     * @param string|null $selector An Sql 92 selector.
     * @param boolean|false $durable durable subscription
     * @return Frame The SUBSCRIBE frame
     */
    public function getSubscribeFrame(
        string $destination,
        ?string $subscriptionId = null,
        string $ack = 'auto',
        ?string $selector = null,
        bool $durable = false
    ): Frame {
        $frame = parent::getSubscribeFrame($destination, $subscriptionId, $ack, $selector);
        $frame['prefetch-count'] = $this->prefetchCount;
        if ($durable) {
            $frame['persistent'] = 'true';
        }
        return $frame;
    }

    /**
     * RabbitMq unsubscribe frame.
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
     * Prefetch Count for subscriptions
     *
     * @return int
     */
    public function getPrefetchCount(): int
    {
        return $this->prefetchCount;
    }

    /**
     * Prefetch Count for subscriptions
     *
     * @param int $prefetchCount
     */
    public function setPrefetchCount(int $prefetchCount)
    {
        $this->prefetchCount = $prefetchCount;
    }


    /**
     * Get message not acknowledge frame.
     *
     * @param \Stomp\Transport\Frame $frame The frame to generate a NACK frame for.
     * @param string|null $transactionId A transaction ID (if applicable)
     * @param bool|null $requeue Requeue header supported on RabbitMQ >= 3.4, ignored in prior versions
     * @return \Stomp\Transport\Frame The NACK frame
     * @throws StompException
     */
    public function getNackFrame(Frame $frame, ?string $transactionId = null, ?bool $requeue = null): Frame
    {
        $nack = parent::getNackFrame($frame, $transactionId);
        if ($requeue !== null) {
            $nack->addHeaders(['requeue' => $requeue ? 'true' : 'false']);
        }
        return $nack;
    }
}
