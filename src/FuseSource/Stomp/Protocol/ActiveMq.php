<?php

namespace FuseSource\Stomp\Protocol;

use FuseSource\Stomp\Frame;
use FuseSource\Stomp\Protocol;

/**
 *
 * Copyright 2005-2006 The Apache Software Foundation
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/* vim: set expandtab tabstop=3 shiftwidth=3: */

/**
 * ActiveMq Stomp dialect.
 *
 *
 * @package Stomp
 * @author Hiram Chirino <hiram@hiramchirino.com>
 * @author Dejan Bosanac <dejan@nighttale.net>
 * @author Michael Caplan <mcaplan@labnet.net>
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class ActiveMq extends Protocol
{
    /**
     * Configure a ActiveMq protocol.
     *
     * @param Protocol $base
     */
    function __construct(Protocol $base)
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
        $frame = parent::getSubscribeFrame($destination, $headers);
        $frame->setHeader('activemq.prefetchSize', $this->getPrefetchSize());
        if ($durable) {
            $frame->setHeader('activemq.subscriptionName', $this->getClientId());
        }
        return $frame;
    }

    public function getUnsubscribeFrame($destination, array $headers = array(), $durable = false)
    {
        $frame = parent::getUnsubscribeFrame($destination, $headers, $durable);
        if ($this->hasClientId() && $durable) {
            $frame->setHeader('activemq.subscriptionName', $this->getClientId());
        }
        return $frame;
    }

}
