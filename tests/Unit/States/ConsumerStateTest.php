<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Tests\Unit\States;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Stomp\Client;
use Stomp\Protocol\Protocol;
use Stomp\StatefulStomp;
use Stomp\States\ConsumerState;
use Stomp\States\DrainingConsumerState;
use Stomp\States\Meta\Subscription;
use Stomp\States\Meta\SubscriptionList;

/**
 * ConsumerStateTest
 *
 * @package Stomp\Tests\Unit\States
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class ConsumerStateTest extends TestCase
{
    public function testUnsubscribeWillThrowExceptionIfGivenIdIsNotActive()
    {
        $client = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->setMethods(['getProtocol', 'sendFrame', 'readFrame'])
            ->getMock();

        /**
         * @var $client Client
         */
        $stateful = new StatefulStomp($client);
        $consumerState = new ConsumerState($client, $stateful);

        $this->expectException(\InvalidArgumentException::class);

        $consumerState->unsubscribe('not-existing');
    }

    public function testUnsubscribeWillOpenDrainingConsumerStateWhenClientBuffersNotEmpty()
    {
        $client = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->setMethods(['getProtocol', 'sendFrame', 'readFrame', 'isBufferEmpty'])
            ->getMock();

        $client->expects($this->once())
            ->method('isBufferEmpty')
            ->willReturn(false);

        $protocol = new Protocol('-');
        $client->expects($this->any())
            ->method('getProtocol')
            ->willReturn($protocol);
        /**
         * @var $client Client
         */
        $stateful = new StatefulStomp($client);
        $consumerState = new ConsumerState($client, $stateful);

        $subscriptions = new SubscriptionList();
        $subscriptions['id-a'] = new Subscription('somewhere', null, 'AUTO', 'id-a');

        $accessor = new ReflectionMethod($consumerState, 'init');
        $accessor->setAccessible(true);
        $accessor->invoke($consumerState, ['subscriptions' => $subscriptions]);

        $consumerState->unsubscribe();

        self::assertInstanceOf(DrainingConsumerState::class, $stateful->getState());
    }
}
