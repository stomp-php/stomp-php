<?php

/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Tests\Unit\Protocol;

use PHPUnit_Framework_TestCase;
use Stomp\Broker\ActiveMq\ActiveMq;
use Stomp\Protocol\Protocol;

/* vim: set expandtab tabstop=3 shiftwidth=3: */

/**
 * ActiveMqTest protocol test case.
 *
 * @package Stomp
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class ActiveMqTest extends PHPUnit_Framework_TestCase
{
    public function testSubscribeFrameHasNoDurableHeaderFieldByDefault()
    {
        $activeMq = new ActiveMq('my-client');

        $frame = $activeMq->getSubscribeFrame('/my/target');
        $this->assertArrayNotHasKey('activemq.subscriptionName', $frame);
    }

    public function testSubscribeFrameHasDurableHeaderFieldByForDurableSubscription()
    {
        $activeMq = new ActiveMq('my-client');

        $frame = $activeMq->getSubscribeFrame('/my/target', null, 'auto', null, true);
        $this->assertArrayHasKey('activemq.subscriptionName', $frame);
        $this->assertEquals('my-client', $frame['activemq.subscriptionName']);
    }

    public function testSubscribeFrameHasPrefetchSizeKey()
    {
        $activeMq = new ActiveMq('my-client');
        $activeMq->setPrefetchSize(10);

        $frame = $activeMq->getSubscribeFrame('/my/target');
        $this->assertArrayHasKey('activemq.prefetchSize', $frame);
        $this->assertEquals(10, $frame['activemq.prefetchSize']);
    }
}
