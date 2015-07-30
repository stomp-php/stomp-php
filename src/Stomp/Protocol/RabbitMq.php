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
     * Configure a RabbitMq protocol.
     *
     * @param Protocol $base
     */
    public function __construct(Protocol $base)
    {
        parent::__construct($base->getPrefetchSize(), $base->getClientId());
    }


    /**
     * RabbitMq subscribe frame.
     *
     * @param string $destination
     * @param array $headers
     * @param boolean $durable durable subscription
     * @return Frame
     */
    public function getSubscribeFrame($destination, array $headers = array(), $durable = false)
    {
        $frame = parent::getSubscribeFrame($destination, $headers);
        $frame->setHeader('prefetch-count', $this->getPrefetchSize());
        $this->addClientId($frame);
        if ($durable) {
            $frame->setHeader('persistent', 'true');
        }
        return $frame;
    }

    /**
     * RabbitMq unsubscribe frame.
     *
     * @param string $destination
     * @param array $headers
     * @param boolean $durable durable subscription
     * @return Frame
     */
    public function getUnsubscribeFrame($destination, array $headers = array(), $durable = false)
    {
        $frame = parent::getUnsubscribeFrame($destination, $headers);
        $this->addClientId($frame);
        if ($durable) {
            $frame->setHeader('persistent', 'true');
        }
        return $frame;
    }
}
