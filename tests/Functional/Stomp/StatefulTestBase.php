<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Tests\Functional\Stomp;

use PHPUnit_Framework_TestCase;
use Stomp\Client;
use Stomp\StatefulStomp;
use Stomp\Transport\Frame;
use Stomp\Transport\Message;

/**
 * StatefulTestBase
 * Generic tests for stateful stomp client.
 *
 * @package Stomp\Tests\Functional\Stomp
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
abstract class StatefulTestBase extends PHPUnit_Framework_TestCase
{
    /**
     * @var Client[]
     */
    private $clients = [];

    protected function tearDown()
    {
        foreach ($this->clients as $client) {
            if ($client->isConnected()) {
                $client->disconnect(true);
            }
        }
        parent::tearDown();
    }

    /**
     * @return Client
     */
    abstract protected function getClient();

    /**
     * @return StatefulStomp
     * @throws \Stomp\Exception\ConnectionException
     */
    final protected function getStatefulStomp()
    {
        $client = $this->getClient();
        $client->getConnection()->setReadTimeout(0, 750000);
        $this->clients[] = $client;
        return new StatefulStomp($client);
    }

    public function testSubscribeAndSend()
    {
        $stomp = $this->getStatefulStomp();
        $queue = '/queue/tests-sub';
        $stomp->subscribe($queue);

        $message = new Message(sprintf('send-message-%d', rand(0, PHP_INT_MAX)));
        $this->assertTrue($stomp->send($queue, $message));

        $received = $stomp->read();
        $this->assertInstanceOf(Frame::class, $received, 'No Message received!');
        $this->assertEquals($message->body, $received->body, 'Wrong Message received!');
    }

    public function testMultipleSubscribe()
    {
        $stomp = $this->getStatefulStomp();
        $queues = ['/queue/tests-multisub-a', '/queue/tests-multisub-b', '/queue/tests-multisub-c'];

        $messages = [];
        foreach ($queues as $queue) {
            $stomp->subscribe($queue);
            $messages[$queue] = new Message(sprintf('send-message-%d', rand(0, PHP_INT_MAX)));
        }

        foreach ($messages as $queue => $message) {
            $this->assertTrue($stomp->send($queue, $message));
        }

        while ((!empty($messages)) && ($message = $stomp->read())) {
            foreach ($stomp->getSubscriptions() as $subscription) {
                if ($subscription->belongsTo($message)) {
                    $this->assertEquals(
                        $messages[$subscription->getDestination()]->body,
                        $message->body,
                        'Message is not matching original message send to queue.'
                    );
                    unset($messages[$subscription->getDestination()]);
                }
            }
        }

        $this->assertEmpty($messages, 'Not all messages have been received!');


        foreach ($stomp->getSubscriptions() as $subscription) {
            $stomp->unsubscribe($subscription->getSubscriptionId());
        }

        $this->assertEmpty($stomp->getSubscriptions());
    }

    public function testTransactions()
    {
        $queue = '/queue/tests-transactions';
        $stomp = $this->getStatefulStomp();

        $stomp->subscribe($queue);

        $stomp->begin();
        $stomp->send($queue, new Message('message-a')); // should never be delivered
        $stomp->abort();

        $stomp->begin();
        $stomp->send($queue, new Message('message-b')); // expected to be delivered
        $stomp->commit();

        $message = $stomp->read();
        $this->assertInstanceOf(Frame::class, $message);
        $this->assertEquals($message->body, 'message-b');
    }

    public function testAckAndNack()
    {
        $queue = '/queue/tests-ack-nack';
        $receiver = $this->getStatefulStomp();
        $producer = $this->getStatefulStomp();

        $receiver->subscribe($queue, null, 'client');

        $producer->send($queue, new Message('message-a', ['persistent' => 'true']));
        $producer->send($queue, new Message('message-b', ['persistent' => 'true']));
        $producer->getClient()->disconnect(true);

        for ($i = 0; $i < 2; $i++) {
            $frame = $receiver->read();
            $this->assertInstanceOf(Frame::class, $frame);
            $receiver->nack($frame);
        }

        $frameA = $receiver->read();
        $this->assertInstanceOf(Frame::class, $frameA);
        $this->assertEquals('message-a', $frameA->body);
        $receiver->ack($frameA);

        $frameB = $receiver->read();
        $this->assertInstanceOf(Frame::class, $frameB);
        $this->assertEquals('message-b', $frameB->body);
        $receiver->ack($frameB);
    }
}
