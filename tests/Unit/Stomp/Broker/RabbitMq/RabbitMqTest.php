<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Tests\Unit\Stomp\Broker\RabbitMq;

use Stomp\Broker\RabbitMq\RabbitMq;
use Stomp\Tests\Unit\Stomp\Protocol\ProtocolTestCase;

/**
 * RabbitMqTest
 *
 * @package Stomp\Tests\Unit\Stomp\Broker\RabbitMq
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class RabbitMqTest extends ProtocolTestCase
{

    public function testSubscribeUsesConfiguredPrefetchSize()
    {
        $instance = $this->getProtocol();
        /**
         * @var $instance RabbitMq
         */
        $instance->setPrefetchCount(506);
        $result = $instance->getSubscribeFrame('target');
        $this->assertEquals(506, $result['prefetch-count']);
    }

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
         * @var $instance RabbitMq
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
         * @var $instance RabbitMq
         */
        $result = $instance->getUnsubscribeFrame('target', null, true);
        $this->assertEquals('true', $result['persistent']);
        $this->assertIsUnsubscribeFrame($result);
    }

    /**
     * Must return the fqn for tested protocol.
     *
     * @return string
     */
    protected function getProtocolClassFqn()
    {
        return RabbitMq::class;
    }
}
