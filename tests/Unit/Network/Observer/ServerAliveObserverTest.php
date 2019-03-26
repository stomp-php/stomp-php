<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Tests\Unit\Network\Observer;

use PHPUnit\Framework\TestCase;
use Stomp\Network\Observer\AbstractBeats;
use Stomp\Network\Observer\ServerAliveObserver;
use Stomp\Transport\Frame;

/**
 * ServerAliveObserverTest
 *
 * @package Stomp\Tests\Unit\Network\Observer
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class ServerAliveObserverTest extends TestCase
{
    public function testHeartBeatFrameMapping()
    {
        $instance = new ServerAliveObserver();
        $method = new \ReflectionMethod($instance, 'onHeartbeatFrame');
        $method->setAccessible(true);

        $method->invoke($instance, new Frame(AbstractBeats::FRAME_CLIENT_CONNECT), [10, 20]);
        $clientProperty = new \ReflectionProperty($instance, 'intervalClient');
        $clientProperty->setAccessible(true);
        self::assertEquals(20, $clientProperty->getValue($instance));

        $method->invoke($instance, new Frame(AbstractBeats::FRAME_SERVER_CONNECTED), [30, 40]);
        $serverProperty = new \ReflectionProperty($instance, 'intervalServer');
        $serverProperty->setAccessible(true);
        self::assertEquals(30, $serverProperty->getValue($instance));
    }

    public function testCalculateInterval()
    {
        $instance = new ServerAliveObserver(5);
        $method = new \ReflectionMethod($instance, 'calculateInterval');
        $method->setAccessible(true);
        self::assertEquals(20, $method->invoke($instance, 4));
    }

    /**
     * @expectedExceptionMessage The server failed to send expected heartbeats.
     * @expectedException \Stomp\Network\Observer\Exception\HeartbeatException
     */
    public function testOnDelayThrowsException()
    {
        $instance = new ServerAliveObserver();
        $method = new \ReflectionMethod($instance, 'onDelay');
        $method->setAccessible(true);
        $method->invoke($instance);
    }

    /**
     * Check that the given activity indicator raises a certain follow up task or not.
     *
     * @param string $activityMethod
     * @param string|null $observerMethod
     * @throws \ReflectionException
     *
     * @dataProvider observerMappingProvider
     */
    public function testObserverMapping($activityMethod, $observerMethod = null)
    {
        $methods = ['rememberActivity', 'checkDelayed'];
        $instance = $this->getMockBuilder(ServerAliveObserver::class)
            ->setMethods($methods)
            ->getMock();
        foreach ($methods as $method) {
            $instance->expects(($observerMethod === $method) ? $this->once() : $this->never())
                ->method($method);
        }
        $method = new \ReflectionMethod($instance, $activityMethod);
        $method->setAccessible(true);
        $method->invoke($instance);
    }

    public function observerMappingProvider()
    {
        return [
            'Connection State Changes' => [
                'onPotentialConnectionStateActivity',
                'checkDelayed'
            ],
            'Server Activity' => [
                'onServerActivity',
                'rememberActivity'
            ],
            'Client Activity' => [
                'onClientActivity'
            ]
        ];
    }
}
