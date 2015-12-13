<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Tests\Functional\ActiveMq\Mode;

use Stomp\Broker\ActiveMq\Mode\DurableSubscription;
use Stomp\Tests\Functional\ActiveMq\ActiveMqFunctionalTestCase;
use Stomp\Tests\Functional\ActiveMq\ClientProvider;
use Stomp\Transport\Message;

/**
 * DurableSubscriptionTest
 *
 * @package Stomp\Tests\Functional\ActiveMq\Mode
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class DurableSubscriptionTest extends ActiveMqFunctionalTestCase
{
    public function testWithAutoAck()
    {
        $client = $this->getClient();
        $client->setClientId('durable-client-id-1');
        $durableInit = new DurableSubscription($client, '/topic/durable-test');
        $durableInit->activate();
        $this->assertTrue($durableInit->isActive());
        $durableInit->inactive();
        $this->assertFalse($durableInit->isActive());
        $client->disconnect(true);

        $producer = ClientProvider::getClient();
        $producer->setClientId('producer');
        $producer->send('/topic/durable-test', new Message('Hello!'));
        $producer->disconnect(true);

        $durableAwake = new DurableSubscription($client, '/topic/durable-test');
        $durableAwake->activate();
        $this->assertEquals('/topic/durable-test', $durableAwake->getSubscription()->getDestination());
        $hello = $durableAwake->read();
        $this->assertEquals('Hello!', $hello->body);
        $durableAwake->deactivate();
    }

    public function testWithClientAck()
    {
        $this->clearDLQ();
        $client = $this->getClient();
        $client->getConnection()->setReadTimeout(0, 500000);
        $client->setClientId('durable-client-id-2');
        $durableInit = new DurableSubscription($client, '/topic/durable-test-2', null, 'client-individual');
        $durableInit->activate();
        $durableInit->inactive();
        $client->disconnect(true);

        $producer = ClientProvider::getClient();
        $producer->setClientId('producer');
        $producer->send('/topic/durable-test-2', new Message('First', ['persistent' => 'true']));
        $producer->send('/topic/durable-test-2', new Message('Second', ['persistent' => 'true']));
        $producer->disconnect(true);

        $durableAwake = new DurableSubscription($client, '/topic/durable-test-2', null, 'client-individual');
        $durableAwake->activate();
        $durableAwake->nack($durableAwake->read());
        $durableAwake->ack($durableAwake->read());
        $durableAwake->deactivate();


        $dlq = $this->getCurrentDLQ();
        $this->assertCount(1, $dlq);
        $message = $dlq[0];
        $this->assertEquals('First', $message->body);
    }
}
