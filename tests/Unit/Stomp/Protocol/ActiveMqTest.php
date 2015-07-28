<?php
namespace Stomp\Tests\Unit\Protocol;

use Stomp\Protocol;
use Stomp\Protocol\ActiveMq;
use PHPUnit_Framework_TestCase;
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
 * ActiveMqTest protocol test case.
 *
 * @package Stomp
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class ActiveMqTest extends PHPUnit_Framework_TestCase
{
    function testSubscribeFrameHasNoDurableHeaderFieldByDefault()
    {
        $base = new Protocol(1, 'my-client');
        $activeMq = new ActiveMq($base);

        $frame = $activeMq->getSubscribeFrame('/my/target');
        $this->assertArrayNotHasKey('activemq.subscriptionName', $frame->headers);
    }

    function testSubscribeFrameHasDurableHeaderFieldByForDurableSubscription()
    {
        $base = new Protocol(1, 'my-client');
        $activeMq = new ActiveMq($base);

        $frame = $activeMq->getSubscribeFrame('/my/target', array(), true);
        $this->assertArrayHasKey('activemq.subscriptionName', $frame->headers);
        $this->assertEquals('my-client', $frame->headers['activemq.subscriptionName']);
    }

    function testSubscribeFrameHasPrefetchSizeKey()
    {
        $base = new Protocol(10, 'my-client');
        $activeMq = new ActiveMq($base);

        $frame = $activeMq->getSubscribeFrame('/my/target');
        $this->assertArrayHasKey('activemq.prefetchSize', $frame->headers);
        $this->assertEquals(10, $frame->headers['activemq.prefetchSize']);
    }
}
