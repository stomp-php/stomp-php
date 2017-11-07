<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\States;

use InvalidArgumentException;
use Stomp\States\Meta\Subscription;
use Stomp\States\Meta\SubscriptionList;
use Stomp\Transport\Frame;
use Stomp\Transport\Message;
use Stomp\Util\IdGenerator;

/**
 * ConsumerState client acts as a consumer.
 *
 * @package Stomp\States
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class ConsumerState extends StateTemplate
{
    /**
     * Subscription ack mode
     *
     * @var string
     */
    protected $ack;
    /**
     * Subscription selector
     *
     * @var string
     */
    protected $selector;
    /**
     * Subscription target
     *
     * @var string
     */
    protected $destination;

    /**
     * SubscriptionId
     * @var int
     */
    protected $subId;

    /**
     * @var SubscriptionList
     */
    protected $subscriptions;

    /**
     * @inheritdoc
     */
    protected function init(array $options = [])
    {
        $this->subscriptions = new SubscriptionList();
        if (isset($options['subscriptions'])) {
            $this->subscriptions = $options['subscriptions'];
        } else {
            $this->subscribe($options['destination'], $options['selector'], $options['ack'], $options['header']);
        }
        return $this->subscriptions->getLast()->getSubscriptionId();
    }

    /**
     * @inheritdoc
     */
    public function ack(Frame $frame)
    {
        $this->getClient()->sendFrame($this->getProtocol()->getAckFrame($frame), false);
    }

    /**
     * @inheritdoc
     */
    public function nack(Frame $frame, $requeue = null)
    {
        $this->getClient()->sendFrame($this->getProtocol()->getNackFrame($frame, null, $requeue), false);
    }

    /**
     * @inheritdoc
     */
    public function send($destination, Message $message)
    {
        return $this->getClient()->send($destination, $message);
    }

    /**
     * @inheritdoc
     */
    public function begin()
    {
        $this->setState(new ConsumerTransactionState($this->getClient(), $this->getBase()), $this->getOptions());
    }

    /**
     * @inheritdoc
     */
    public function subscribe($destination, $selector, $ack, array $header = [])
    {
        $subscription = new Subscription($destination, $selector, $ack, IdGenerator::generateId(), $header);
        $this->getClient()->sendFrame(
            $this->getProtocol()->getSubscribeFrame(
                $subscription->getDestination(),
                $subscription->getSubscriptionId(),
                $subscription->getAck(),
                $subscription->getSelector()
            )->addHeaders($header)
        );
        $this->subscriptions[$subscription->getSubscriptionId()] = $subscription;
        return $subscription->getSubscriptionId();
    }


    /**
     * @inheritdoc
     */
    public function unsubscribe($subscriptionId = null)
    {
        if ($this->endSubscription($subscriptionId)) {
            $this->setState(
                new ProducerState($this->getClient(), $this->getBase())
            );
        }
    }


    /**
     * Closes given subscription or last opened.
     *
     * @param string $subscriptionId
     * @return bool true if last one was closed
     */
    protected function endSubscription($subscriptionId = null)
    {
        if (!$subscriptionId) {
            $subscriptionId = $this->subscriptions->getLast()->getSubscriptionId();
        }
        if (!isset($this->subscriptions[$subscriptionId])) {
            throw new InvalidArgumentException(sprintf('%s is no active subscription!', $subscriptionId));
        }
        $subscription = $this->subscriptions[$subscriptionId];
        $this->getClient()->sendFrame(
            $this->getProtocol()->getUnsubscribeFrame(
                $subscription->getDestination(),
                $subscription->getSubscriptionId()
            )
        );
        IdGenerator::releaseId($subscription->getSubscriptionId());

        unset($this->subscriptions[$subscription->getSubscriptionId()]);

        if ($this->subscriptions->count() == 0) {
            return true;
        }
        return false;
    }

    /**
     * @inheritdoc
     */
    public function read()
    {
        return $this->getClient()->readFrame();
    }

    /**
     * @inheritdoc
     */
    public function getSubscriptions()
    {
        return $this->subscriptions;
    }


    /**
     * @inheritdoc
     */
    protected function getOptions()
    {
        return [
            'subscriptions' => $this->subscriptions
        ];
    }
}
