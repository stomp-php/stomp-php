<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Tests\Unit\Broker\Apollo\Mode;

use PHPUnit\Framework\TestCase;
use Stomp\Broker\Apollo\Mode\QueueBrowser;
use Stomp\Broker\Exception\UnsupportedBrokerException;
use Stomp\Broker\RabbitMq\RabbitMq;
use Stomp\Client;

/**
 * QueueBrowserTest
 *
 * @package Stomp\Tests\Unit\Broker\Apollo\Mode
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class QueueBrowserTest extends TestCase
{
    public function testBrowserWontWorkWithNonApolloBroker()
    {
        $client = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->setMethods(['getProtocol'])
            ->getMock();
        $client->method('getProtocol')->willReturn(new RabbitMq('client-id'));

        /**
         * @var $client Client
         */
        $browser = new QueueBrowser($client, 'target');

        $this->expectException(UnsupportedBrokerException::class);
        $browser->subscribe();
    }
}
