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
use Stomp\Broker\Exception\UnsupportedBrokerException;
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
    public function testGetProtocolWillThrowExceptionIfClientIsNotConnectedToActiveMq()
    {
        $client = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getProtocol'])
            ->getMock();

        $client->expects($this->any())->method('getProtocol')->willReturn(new RabbitMq('clientid'));

        $activeMqMode = $this->getMockForAbstractClass(ActiveMqMode::class, [$client]);

        $getProtocol = new ReflectionMethod($activeMqMode, 'getProtocol');
        if (PHP_VERSION_ID < 80100) {
            $getProtocol->setAccessible(true);
        }
        $this->expectException(UnsupportedBrokerException::class);

        $getProtocol->invoke($activeMqMode);
    }
}
