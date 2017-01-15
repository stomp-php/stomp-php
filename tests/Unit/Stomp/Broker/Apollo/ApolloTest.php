<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Tests\Unit\Stomp\Broker\Apollo;

use Stomp\Broker\Apollo\Apollo;
use Stomp\Protocol\Version;
use Stomp\Tests\Unit\Stomp\Protocol\ProtocolTestCase;
use Stomp\Transport\Frame;

/**
 * ApolloTest
 *
 * @package Stomp\Tests\Unit\Stomp\Broker\Apollo
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class ApolloTest extends ProtocolTestCase
{

    public function testSubscribeNonDurable()
    {
        $instance = $this->getProtocol();
        $result = $instance->getSubscribeFrame('target');
        $this->assertNull($result['persistent']);
        $this->assertIsSubscribeFrame($result);
    }

    public function testSubscribeDurable()
    {
        $instance = $this->getProtocol();
        /**
         * @var $instance Apollo
         */
        $result = $instance->getSubscribeFrame('target', null, 'auto', null, true);
        $this->assertEquals('true', $result['persistent']);
        $this->assertIsSubscribeFrame($result);
    }

    public function testUnsubscribeNonDurable()
    {
        $instance = $this->getProtocol();
        $result = $instance->getUnsubscribeFrame('target');
        $this->assertNull($result['persistent']);
        $this->assertIsUnsubscribeFrame($result);
    }

    public function testUnsubscribeDurable()
    {
        $instance = $this->getProtocol();
        /**
         * @var $instance Apollo
         */
        $result = $instance->getUnsubscribeFrame('target', null, true);
        $this->assertEquals('true', $result['persistent']);
        $this->assertIsUnsubscribeFrame($result);
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

    protected function getProtocolClassFqn()
    {
        return Apollo::class;
    }
}
