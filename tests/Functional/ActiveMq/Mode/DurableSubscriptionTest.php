<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Tests\Functional\ActiveMq\Mode;

use PHPUnit_Framework_TestCase;
use Stomp\Broker\ActiveMq\Mode\DurableSubscription;
use Stomp\Tests\Functional\ActiveMq\ClientProvider;
use Stomp\Transport\Message;

/**
 * DurableSubscriptionTest
 *
 * @package Stomp\Tests\Functional\ActiveMq\Mode
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class DurableSubscriptionTest extends PHPUnit_Framework_TestCase
{
    public function testDurableSubscription()
    {
        $client = ClientProvider::getClient();
        $client->setClientId('durable-client-id-1');
        $durableInit = new DurableSubscription($client, '/topic/durable-test');
        $durableInit->activate();
        $durableInit->inactive();
        $client->disconnect(true);

        $producer = ClientProvider::getClient();
        $producer->setClientId('producer');
        $producer->send('/topic/durable-test', new Message('Hello!'));

        $durableAwake = new DurableSubscription($client, '/topic/durable-test');
        $durableAwake->activate();
        $hello = $durableAwake->read();
        $this->assertEquals('Hello!', $hello->body);
        $durableAwake->deactivate();
    }
}
