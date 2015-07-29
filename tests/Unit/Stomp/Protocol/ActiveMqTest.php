<?php

/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Tests\Unit\Protocol;

use Stomp\Protocol;
use Stomp\Protocol\ActiveMq;
use PHPUnit_Framework_TestCase;

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
