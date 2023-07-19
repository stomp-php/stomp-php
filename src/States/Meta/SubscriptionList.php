<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\States\Meta;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Stomp\Transport\Frame;

/**
 * SubscriptionList meta info for active subscriptions.
 *
 * @package Stomp\States\Meta
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class SubscriptionList implements IteratorAggregate, ArrayAccess, Countable
{

    /**
     * @var Subscription[]
     */
    private $subscriptions = [];

    /**
     * Returns the last added active Subscription.
     *
     * @return Subscription
     */
    public function getLast(): Subscription
    {
        return end($this->subscriptions);
    }

    /**
     * Returns the subscription the frame belongs to or false if no matching subscription was found.
     *
     * @param Frame $frame
     * @return Subscription|false
     */
    public function getSubscription(Frame $frame)
    {
        foreach ($this->subscriptions as $subscription) {
            if ($subscription->belongsTo($frame)) {
                return $subscription;
            }
        }
        return false;
    }

    /**
     * @inheritdoc
     *
     * @return \ArrayIterator|Subscription[]
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->subscriptions);
    }

    /**
     * @inheritdoc
     */
    public function offsetExists($offset): bool
    {
        return isset($this->subscriptions[$offset]);
    }

    /**
     * @inheritdoc
     *
     * @return Subscription
     */
    public function offsetGet($offset): Subscription
    {
        return $this->subscriptions[$offset];
    }

    /**
     * @inheritdoc
     */
    public function offsetSet($offset, $value): void
    {
        $this->subscriptions[$offset] = $value;
    }

    /**
     * @inheritdoc
     */
    public function offsetUnset($offset): void
    {
        unset($this->subscriptions[$offset]);
    }

    /**
     * @inheritdoc
     */
    public function count(): int
    {
        return count($this->subscriptions);
    }
}
