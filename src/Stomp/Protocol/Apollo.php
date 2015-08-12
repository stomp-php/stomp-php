<?php

/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Protocol;

use Stomp\Frame;
use Stomp\Protocol;

/* vim: set expandtab tabstop=3 shiftwidth=3: */

/**
 * ActiveMq Apollo Stomp dialect.
 *
 *
 * @package Stomp
 * @author András Rutkai <riskawarrior@live.com>
 */
class Apollo extends Protocol
{
    /**
     * Configure Apollo protocol.
     *
     * @param Protocol $base
     */
    public function __construct(Protocol $base)
    {
        parent::__construct($base->getPrefetchSize(), $base->getClientId());
    }

    /**
     * ActiveMq subscribe frame.
     *
     * @param string $destination
     * @param array $headers
     * @param boolean $durable durable subscription
     * @return Frame
     */
    public function getSubscribeFrame($destination, array $headers = array(), $durable = false)
    {
        $frame = parent::getSubscribeFrame($destination, $headers, $durable);
        if ($this->hasClientId() && $durable) {
            $frame->setHeader('persistent', 'true');
        }
        return $frame;
    }

    public function getUnsubscribeFrame($destination, array $headers = array(), $durable = false)
    {
        $frame = parent::getUnsubscribeFrame($destination, $headers, $durable);
        if ($this->hasClientId()) {
            $this->addClientId($frame);
            if ($durable) {
                $frame->setHeader('persistent', 'true');
            }
        }
        return $frame;
    }
}
