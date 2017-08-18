<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Tests\Unit\Broker\ActiveMq\Mode;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Stomp\Broker\ActiveMq\Mode\ActiveMqMode;
use Stomp\Broker\RabbitMq\RabbitMq;
use Stomp\Client;

/**
 * ActiveMqModeTest
 *
 * @package Stomp\Tests\Unit\Broker\ActiveMq\Mode
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class ActiveMqModeTest extends TestCase
{
    /**
     * @expectedException \Stomp\Broker\Exception\UnsupportedBrokerException
     */
    public function testGetProtocolWillThrowExceptionIfClientIsNotConnectedToActiveMq()
    {
        $client = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->setMethods(['getProtocol'])
            ->getMock();

        $client->expects($this->any())->method('getProtocol')->willReturn(new RabbitMq('clientid'));

        $activeMqMode = $this->getMockForAbstractClass(ActiveMqMode::class, [$client]);

        $getProtocol = new ReflectionMethod($activeMqMode, 'getProtocol');
        $getProtocol->setAccessible(true);
        $getProtocol->invoke($activeMqMode);
    }
}
