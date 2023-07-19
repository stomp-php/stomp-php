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
     * @var string
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
     * @param String|null $selector
     * @param String $ack
     * @param string|null $subscriptionId
     * @param array $header additionally passed to create this subscription
     */
    public function __construct(
        string $destination,
        ?string $selector,
        string $ack,
        ?string $subscriptionId,
        array $header = []
    ) {
        $this->subscriptionId = $subscriptionId;
        $this->selector = $selector;
        $this->destination = $destination;
        $this->ack = $ack;
        $this->header = $header;
    }


    /**
     * @return string
     */
    public function getSubscriptionId(): string
    {
        return $this->subscriptionId;
    }

    /**
     * @return String
     */
    public function getSelector(): ?string
    {
        return $this->selector;
    }

    /**
     * @return String
     */
    public function getDestination(): ?string
    {
        return $this->destination;
    }

    /**
     * @return String
     */
    public function getAck(): string
    {
        return $this->ack;
    }

    /**
     * @return array
     */
    public function getHeader(): array
    {
        return $this->header;
    }

    /**
     * Checks if the given frame belongs to current Subscription.
     *
     * @param Frame $frame
     * @return bool
     */
    public function belongsTo(Frame $frame): bool
    {
        return ($frame['subscription'] == $this->subscriptionId);
    }
}
