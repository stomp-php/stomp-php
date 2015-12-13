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
     * @var array
     */
    private $header;

    /**
     * Subscription constructor.
     * @param String $destination
     * @param String $selector
     * @param String $ack
     * @param int $subscriptionId
     * @param array $header additionally passed to create this subscription
     */
    public function __construct($destination, $selector, $ack, $subscriptionId, array $header = [])
    {
        $this->subscriptionId = $subscriptionId;
        $this->selector = $selector;
        $this->destination = $destination;
        $this->ack = $ack;
        $this->header = $header;
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
     * @return array
     */
    public function getHeader()
    {
        return $this->header;
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
