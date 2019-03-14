<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\States;

use Stomp\States\Meta\SubscriptionList;
use Stomp\Transport\Frame;
use Stomp\Transport\Message;

/**
 * Interface IStateful methods that must be treated in every stomp state.
 *
 * @package Stomp\States
 */
interface IStateful
{
    /**
     * Acknowledge consumption of a message from a subscription
     *
     * @param Frame $frame
     * @return void
     */
    public function ack(Frame $frame);

    /**
     * Not acknowledge consumption of a message from a subscription
     *
     * @param Frame $frame
     * @param bool $requeue Requeue header not supported on all brokers
     * @return void
     */
    public function nack(Frame $frame, $requeue = null);

    /**
     * Send a message.
     *
     * @param string $destination
     * @param \Stomp\Transport\Message $message
     * @return bool
     */
    public function send($destination, Message $message);

    /**
     * Begins an transaction.
     *
     * @return void
     */
    public function begin();

    /**
     * Commit current transaction.
     *
     * @return void
     */
    public function commit();

    /**
     * Abort current transaction.
     *
     * @return void
     */
    public function abort();

    /**
     * Subscribe to given destination.
     *
     * Returns the subscriptionId used for this.
     *
     * @param string $destination
     * @param string $selector
     * @param string $ack
     * @param array  $header
     * @return int
     */
    public function subscribe($destination, $selector, $ack, array $header = []);

    /**
     * Unsubscribe from current or given destination.
     *
     * @param int $subscriptionId
     * @return void
     */
    public function unsubscribe($subscriptionId = null);


    /**
     * Read a frame
     *
     * @return \Stomp\Transport\Frame|false
     */
    public function read();

    /**
     * Returns as list of all active subscriptions.
     *
     * @return SubscriptionList
     */
    public function getSubscriptions();
}
