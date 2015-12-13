<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Broker\Apollo\Mode;

use Stomp\Broker\Apollo\Apollo;
use Stomp\Broker\Exception\UnsupportedBrokerException;
use Stomp\Client;
use Stomp\States\Meta\Subscription;
use Stomp\Util\IdGenerator;

/**
 * QueueBrowser ApolloMq util to browse a queue without removing messages from it.
 *
 * @see http://activemq.apache.org/apollo/documentation/stomp-manual.html#Browsing_Subscriptions
 *
 * @package Stomp\Broker\Apollo\Mode
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class QueueBrowser
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var bool
     */
    private $stopOnEnd;

    /**
     * @var Subscription
     */
    private $subscription;

    /**
     * @var bool
     */
    private $active = false;

    /**
     * @var bool
     */
    private $reachedEnd = false;

    /**
     * QueueBrowser constructor.
     *
     * @param Client $client
     * @param string $destination
     * @param bool $stopOnEnd
     */
    public function __construct(Client $client, $destination, $stopOnEnd = true)
    {
        $this->stopOnEnd = $stopOnEnd;
        $this->client = $client;
        $this->stopOnEnd = $stopOnEnd;
        $this->subscription = new Subscription($destination, null, 'auto', IdGenerator::generateId());
    }

    /**
     * Protocol
     * @return Apollo
     * @throws UnsupportedBrokerException
     */
    final protected function getProtocol()
    {
        $protocol = $this->client->getProtocol();
        if (!$protocol instanceof Apollo) {
            throw new UnsupportedBrokerException($protocol, Apollo::class);
        }
        return $protocol;
    }

    /**
     * Headers used in subscribe.
     *
     * @return array
     */
    protected function getHeader()
    {
        return [
            'browser' => 'true',
            'browser-end' => $this->stopOnEnd ? 'true' : 'false',
        ];
    }

    /**
     * Initialize subscription.
     *
     * @return void
     */
    public function subscribe()
    {
        if (!$this->active) {
            $this->reachedEnd = false;
            $this->client->sendFrame(
                $this->getProtocol()->getSubscribeFrame(
                    $this->subscription->getDestination(),
                    $this->subscription->getSubscriptionId(),
                    $this->subscription->getAck(),
                    $this->subscription->getSelector(),
                    false
                )->addHeaders($this->getHeader())
            );
            $this->active = true;
        }
    }

    /**
     * End subscription.
     *
     * @return void
     */
    public function unsubscribe()
    {
        if ($this->active) {
            $this->client->sendFrame(
                $this->getProtocol()->getUnsubscribeFrame(
                    $this->subscription->getDestination(),
                    $this->subscription->getSubscriptionId(),
                    false
                )
            );
            $this->active = false;
        }
    }

    /**
     * Read next message.
     *
     * @return bool|false|\Stomp\Transport\Frame
     */
    public function read()
    {
        if (!$this->active || $this->reachedEnd) {
            return false;
        }
        if ($frame = $this->client->readFrame()) {
            if ($this->stopOnEnd && $frame['browser'] == 'end') {
                $this->reachedEnd = true;
                return false;
            }
        }
        return $frame;
    }

    /**
     * Last message was received (can only be true if 'StopAtEnd' is enabled!)
     *
     * @return boolean
     */
    public function hasReachedEnd()
    {
        return $this->reachedEnd;
    }

    /**
     * Subscription has been initialized.
     *
     * @return boolean
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * Subscription details.
     *
     * @return Subscription
     */
    public function getSubscription()
    {
        return $this->subscription;
    }

    /**
     * @inheritdoc
     */
    public function __destruct()
    {
        IdGenerator::releaseId($this->subscription->getSubscriptionId());
    }
}
