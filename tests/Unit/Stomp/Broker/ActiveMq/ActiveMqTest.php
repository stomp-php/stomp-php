<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Tests\Unit\Stomp\Broker\ActiveMq;

use Stomp\Broker\ActiveMq\ActiveMq;
use Stomp\Protocol\Version;
use Stomp\Tests\Unit\Stomp\Protocol\ProtocolTestCase;
use Stomp\Transport\Frame;

/**
 * ActiveMqTest
 *
 * @package Stomp\Tests\Unit\Stomp\Broker\ActiveMq
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class ActiveMqTestCase extends ProtocolTestCase
{

    public function testSubscribeUsesConfiguredPrefetchSize()
    {
        $instance = $this->getProtocol();
        /**
         * @var $instance ActiveMq
         */
        $instance->setPrefetchSize(506);
        $result = $instance->getSubscribeFrame('target');
        $this->assertEquals(506, $result['activemq.prefetchSize']);
    }

    public function testSubscribeNonDurable()
    {
        $instance = $this->getProtocol();
        /**
         * @var $instance ActiveMq
         */
        $result = $instance->getSubscribeFrame('target');
        $this->assertEquals($instance->getPrefetchSize(), $result['activemq.prefetchSize']);
        $this->assertNull($result['activemq.subscriptionName']);
    }

    public function testSubscribeDurable()
    {
        $instance = $this->getProtocol();
        /**
         * @var $instance ActiveMq
         */
        $result = $instance->getSubscribeFrame('target', null, 'auto', null, true);
        $this->assertEquals($instance->getPrefetchSize(), $result['activemq.prefetchSize']);
        $this->assertEquals('test-client-id', $result['activemq.subscriptionName']);
    }

    public function testUnsubscribeNonDurable()
    {
        $instance = $this->getProtocol();
        $result = $instance->getUnsubscribeFrame('target');
        $this->assertNull($result['activemq.subscriptionName']);
    }

    public function testUnsubscribeDurable()
    {
        $instance = $this->getProtocol();
        /**
         * @var $instance ActiveMq
         */
        $result = $instance->getUnsubscribeFrame('target', null, true);
        $this->assertEquals('test-client-id', $result['activemq.subscriptionName']);
    }

    public function testAckVersionZero()
    {
        $instance = $this->getProtocol(Version::VERSION_1_0);

        $resultAckBased = $instance->getAckFrame(new Frame(null, ['ack' => 'ack-value']), 'my-transaction');
        $this->assertIsAckFrame($resultAckBased);
        $this->assertEquals('ack-value', $resultAckBased['message-id']);
        $this->assertEquals('my-transaction', $resultAckBased['transaction']);

        $resultIdBased = $instance->getAckFrame(new Frame(null, ['message-id' => 'id-value']), 'my-transaction');
        $this->assertIsAckFrame($resultIdBased);
        $this->assertEquals('id-value', $resultIdBased['message-id']);
        $this->assertEquals('my-transaction', $resultIdBased['transaction']);
    }

    public function testAckVersionOne()
    {
        $instance = $this->getProtocol(Version::VERSION_1_1);

        $resultAckBased = $instance->getAckFrame(new Frame(null, ['ack' => 'ack-value']), 'my-transaction');
        $this->assertIsAckFrame($resultAckBased);
        $this->assertEquals('ack-value', $resultAckBased['message-id']);
        $this->assertEquals('my-transaction', $resultAckBased['transaction']);

        $resultIdBased = $instance->getAckFrame(new Frame(null, [
            'message-id' => 'id-value',
            'subscription' => 'my-subscription'
        ]), 'my-transaction');

        $this->assertIsAckFrame($resultIdBased);
        $this->assertEquals('id-value', $resultIdBased['message-id']);
        $this->assertEquals('my-subscription', $resultIdBased['subscription']);
        $this->assertEquals('my-transaction', $resultAckBased['transaction']);
    }

    public function testAckVersionTwo()
    {
        $instance = $this->getProtocol(Version::VERSION_1_2);

        $resultAckBased = $instance->getAckFrame(new Frame(null, ['ack' => 'ack-value']), 'my-transaction');
        $this->assertIsAckFrame($resultAckBased);
        $this->assertEquals('ack-value', $resultAckBased['id']);
        $this->assertEquals('my-transaction', $resultAckBased['transaction']);

        $resultIdBased = $instance->getAckFrame(new Frame(null, ['message-id' => 'id-value']), 'my-transaction');
        $this->assertIsAckFrame($resultIdBased);
        $this->assertEquals('id-value', $resultIdBased['id']);
        $this->assertEquals('my-transaction', $resultAckBased['transaction']);
    }

    public function testNackVersionZero()
    {
        $instance = $this->getProtocol(Version::VERSION_1_0);

        $resultAckBased = $instance->getNackFrame(new Frame(null, ['ack' => 'ack-value']), 'my-transaction');
        $this->assertEquals('ack-value', $resultAckBased['message-id']);
        $this->assertEquals('my-transaction', $resultAckBased['transaction']);
        $this->assertIsNackFrame($resultAckBased);

        $resultIdBased = $instance->getNackFrame(new Frame(null, ['message-id' => 'id-value']), 'my-transaction');
        $this->assertEquals('id-value', $resultIdBased['message-id']);
        $this->assertEquals('my-transaction', $resultIdBased['transaction']);
        $this->assertIsNackFrame($resultIdBased);
    }


    public function testNackVersionOne()
    {
        $instance = $this->getProtocol(Version::VERSION_1_1);

        $resultAckBased = $instance->getNackFrame(new Frame(null, ['ack' => 'ack-value']), 'my-transaction');
        $this->assertIsNackFrame($resultAckBased);
        $this->assertEquals('ack-value', $resultAckBased['message-id']);
        $this->assertEquals('my-transaction', $resultAckBased['transaction']);

        $resultIdBased = $instance->getNackFrame(new Frame(null, [
            'message-id' => 'id-value',
            'subscription' => 'my-subscription'
        ]), 'my-transaction');

        $this->assertIsNackFrame($resultIdBased);
        $this->assertEquals('id-value', $resultIdBased['message-id']);
        $this->assertEquals('my-subscription', $resultIdBased['subscription']);
        $this->assertEquals('my-transaction', $resultAckBased['transaction']);
    }

    public function testNackVersionTwo()
    {
        $instance = $this->getProtocol(Version::VERSION_1_2);

        $resultAckBased = $instance->getNackFrame(new Frame(null, ['ack' => 'ack-value']), 'my-transaction');
        $this->assertIsNackFrame($resultAckBased);
        $this->assertEquals('ack-value', $resultAckBased['id']);
        $this->assertEquals('my-transaction', $resultAckBased['transaction']);

        $resultIdBased = $instance->getNackFrame(new Frame(null, ['message-id' => 'id-value']), 'my-transaction');
        $this->assertIsNackFrame($resultIdBased);
        $this->assertEquals('id-value', $resultIdBased['id']);
        $this->assertEquals('my-transaction', $resultIdBased['transaction']);
    }

    protected function getProtocolClassFqn()
    {
        return ActiveMq::class;
    }
}
