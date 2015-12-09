<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\States\Meta;

use Stomp\Transport\Frame;

/**
 * Subscription Meta info
 *
 * @package Stomp\States\Meta
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class Subscription
{
    /**
     * @var int
     */
    private $subscriptionId;

    /**
     * @var String
     */
    private $selector;

    /**
     * @var String
     */
    private $destination;

    /**
     * @var String
     */
    private $ack;

    /**
     * Subscription constructor.
     * @param String $destination
     * @param String $selector
     * @param String $ack
     * @param int $subscriptionId
     */
    public function __construct($destination, $selector, $ack, $subscriptionId)
    {
        $this->subscriptionId = $subscriptionId;
        $this->selector = $selector;
        $this->destination = $destination;
        $this->ack = $ack;
    }


    /**
     * @return int
     */
    public function getSubscriptionId()
    {
        return $this->subscriptionId;
    }

    /**
     * @return String
     */
    public function getSelector()
    {
        return $this->selector;
    }

    /**
     * @return String
     */
    public function getDestination()
    {
        return $this->destination;
    }

    /**
     * @return String
     */
    public function getAck()
    {
        return $this->ack;
    }


    /**
     * Checks if the given frame belongs to current Subscription.
     *
     * @param Frame $frame
     * @return bool
     */
    public function belongsTo(Frame $frame)
    {
        return ($frame['subscription'] == $this->subscriptionId);
    }
}
