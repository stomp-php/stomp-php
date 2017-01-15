<?php

/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Tests\Functional\RabbitMq;

use PHPUnit_Framework_TestCase;
use Stomp\Client;
use Stomp\SimpleStomp;
use Stomp\Transport\Bytes;
use Stomp\Transport\Frame;
use Stomp\Transport\Map;

/**
 * Client test for RabbitMq Broker
 *
 * @package Stomp
 * @author Michael Caplan <mcaplan@labnet.net>
 * @author Dejan Bosanac <dejan@nighttale.net>
 */
class ClientTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Client
     */
    private $stomp;

    /**
     * @var SimpleStomp
     */
    private $simpleStomp;
    private $queue = '/queue/test';
    private $topic = '/topic/test';

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->stomp = ClientProvider::getClient();
        $this->stomp->setSync(false);
        $this->simpleStomp = new SimpleStomp($this->stomp);
    }
    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
        if ($this->stomp->isConnected()) {
            $this->stomp->disconnect();
        }
        $this->stomp = null;
        parent::tearDown();
    }

    /**
     * Tests Stomp->hasFrameToRead()
     *
     */
    public function testHasFrameToRead()
    {
        $this->stomp->connect();
        $this->stomp->getConnection()->setReadTimeout(0, 750000);

        $this->assertFalse($this->stomp->getConnection()->hasDataToRead(), 'Has frame to read when non expected');

        $this->stomp->send($this->queue, 'testHasFrameToRead');

        $simpleStomp = new SimpleStomp($this->stomp);
        $simpleStomp->subscribe($this->queue, null, 'client');

        $this->assertTrue($this->stomp->getConnection()->hasDataToRead(), 'Did not have frame to read when expected');

        $frame = $this->stomp->readFrame();

        $this->assertTrue($frame instanceof Frame, 'Frame expected');

        $simpleStomp->ack($frame);
    }

    /**
     * Tests Stomp->ack()
     */
    public function testAck()
    {
        $this->stomp->connect();

        $messages = [];

        for ($x = 0; $x < 100; ++$x) {
            $this->stomp->send($this->queue, $x);
            $messages[$x] = 'sent';
        }

        $this->stomp->disconnect();

        for ($y = 0; $y < 100; $y += 10) {
            $this->stomp->connect();


            $this->simpleStomp->subscribe($this->queue, null, 'client');

            for ($x = $y; $x < $y + 10; ++$x) {
                $frame = $this->stomp->readFrame();
                $this->assertTrue($frame instanceof Frame);
                $this->assertArrayHasKey(
                    $frame->body,
                    $messages,
                    $frame->body . ' is not in the list of messages to ack'
                );
                $this->assertEquals(
                    'sent',
                    $messages[$frame->body],
                    $frame->body . ' has been marked acked, but has been received again.'
                );
                $messages[$frame->body] = 'acked';

                $this->simpleStomp->ack($frame);
            }

            $this->stomp->disconnect();
        }

        $un_acked_messages = [];

        foreach ($messages as $key => $value) {
            if ($value == 'sent') {
                $un_acked_messages[] = $key;
            }
        }

        $this->assertEquals(
            0,
            count($un_acked_messages),
            'Remaining messages to ack' . var_export($un_acked_messages, true)
        );
    }

    /**
     * Tests Stomp->abort()
     */
    public function testAbort()
    {
        $this->stomp->getConnection()->setReadTimeout(0, 750000);
        $this->stomp->connect();
        $this->simpleStomp->begin('tx1');
        $this->assertTrue($this->stomp->send('/queue/abort', 'testSend', ['transaction' => 'tx1']));
        $this->simpleStomp->abort('tx1');

        $this->simpleStomp->subscribe('/queue/abort');
        $frame = $this->stomp->readFrame();
        $this->assertFalse($frame);
        $this->simpleStomp->unsubscribe('/queue/abort');
    }

    /**
     * Tests Stomp->connect()
     */
    public function testConnect()
    {
        $this->assertTrue($this->stomp->connect());
        $this->assertTrue($this->stomp->isConnected());
    }

    /**
     * Tests Stomp->disconnect()
     */
    public function testDisconnect()
    {
        $this->stomp->connect();
        $this->assertTrue($this->stomp->isConnected());
        $this->stomp->disconnect();
        $this->assertFalse($this->stomp->isConnected());
    }

    /**
     * Tests Stomp->getSessionId()
     */
    public function testGetSessionId()
    {
        if (! $this->stomp->isConnected()) {
            $this->stomp->connect();
        }
        $this->assertNotNull($this->stomp->getSessionId());
    }

    /**
     * Tests Stomp->isConnected()
     */
    public function testIsConnected()
    {
        $this->stomp->connect();
        $this->assertTrue($this->stomp->isConnected());
        $this->stomp->disconnect();
        $this->assertFalse($this->stomp->isConnected());
    }

    /**
     * Tests Stomp->readFrame()
     */
    public function testReadFrame()
    {
        $this->stomp->connect();
        $this->stomp->send('/queue/readframe', 'testReadFrame');
        $this->simpleStomp->subscribe('/queue/readframe');
        $frame = $this->stomp->readFrame();
        $this->assertTrue($frame instanceof Frame);
        $this->assertEquals('testReadFrame', $frame->body, 'Body of test frame does not match sent message');
        $this->simpleStomp->unsubscribe('/queue/readframe');
    }

    /**
     * Tests Stomp->send()
     */
    public function testSend()
    {
        if (! $this->stomp->isConnected()) {
            $this->stomp->connect();
        }
        $simpleStomp = new SimpleStomp($this->stomp);
        $this->assertTrue($this->stomp->send('/queue/sendframe', 'testSend'));
        $simpleStomp->subscribe('/queue/sendframe');
        $frame = $this->stomp->readFrame();
        $this->assertTrue($frame instanceof Frame);
        $this->assertEquals('testSend', $frame->body, 'Body of test frame does not match sent message');
        $simpleStomp->unsubscribe('/queue/sendframe');
    }

    /**
     * Tests Stomp->subscribe()
     */
    public function testSubscribe()
    {
        if (! $this->stomp->isConnected()) {
            $this->stomp->connect();
        }
        $simpleStomp = new SimpleStomp($this->stomp);
        $this->assertTrue($simpleStomp->subscribe('/queue/sub'));
        $simpleStomp->unsubscribe('/queue/sub');
        $this->stomp->disconnect();
    }

    /**
     * Tests Stomp message transformation - json map
     */
    public function testJsonMapTransformation()
    {
        if (! $this->stomp->isConnected()) {
            $this->stomp->connect();
        }
        $simpleStomp = new SimpleStomp($this->stomp);
        $body = ['city' => 'Belgrade', 'name' => 'Dejan'];
        $header = [];
        $header['transformation'] = 'jms-map-json';
        $mapMessage = new Map($body, $header);
        $this->stomp->send('/queue/transform', $mapMessage);

        $simpleStomp->subscribe('/queue/transform', null, 'auto', null, ['transformation' => 'jms-map-json']);
        $msg = $this->stomp->readFrame();
        $this->assertTrue($msg instanceof Map);

        /** @var \Stomp\Transport\Map $msg */
        $this->assertEquals($msg->map, $body);
        $this->stomp->disconnect();
    }

    /**
     * Tests Stomp byte messages
     */
    public function testByteMessages()
    {
        if (! $this->stomp->isConnected()) {
            $this->stomp->connect();
        }
        $body = 'test';
        $mapMessage = new Bytes($body);
        $this->stomp->send('/queue/bytes', $mapMessage);

        $simpleStomp = new SimpleStomp($this->stomp);
        $simpleStomp->subscribe('/queue/bytes');
        $msg = $this->stomp->readFrame();
        $this->assertEquals($msg->body, $body);
        $this->stomp->disconnect();
    }

    /**
     * Tests Stomp->unsubscribe()
     */
    public function testUnsubscribe()
    {
        if (! $this->stomp->isConnected()) {
            $this->stomp->connect();
        }
        $simpleStomp = new SimpleStomp($this->stomp);
        $simpleStomp->subscribe('/queue/unsub');
        $this->assertTrue($simpleStomp->unsubscribe('/queue/unsub'));
    }

    /**
     * @see https://www.rabbitmq.com/stomp.html#d.dts
     */
    public function testDurable()
    {
        $this->subscribe();
        $this->produce();
        $this->consume();
    }

    protected function subscribe()
    {
        $consumer = ClientProvider::getClient();
        $consumer->setClientId('test');
        $consumer->connect();

        $simpleStomp = new SimpleStomp($consumer);
        $simpleStomp->subscribe(
            $this->topic,
            'myId',
            'client-individual',
            null,
            ['durable' => 'true', 'auto-delete' => 'false']
        );
        $simpleStomp->unsubscribe($this->topic, 'myId');
        $consumer->disconnect();
    }

    protected function produce()
    {
        $producer = ClientProvider::getClient();
        $producer->connect();
        $producer->send($this->topic, 'test message', ['persistent' => 'true']);
        $producer->disconnect();
    }


    protected function consume()
    {
        $consumer = ClientProvider::getClient();
        $consumer->setClientId('test');
        $consumer->connect();
        $consumer->getConnection()->setReadTimeout(5);

        $simpleStomp = new SimpleStomp($consumer);
        $simpleStomp->subscribe(
            $this->topic,
            'myId',
            'client-individual',
            null,
            ['durable' => 'true', 'auto-delete' => 'false']
        );


        $frame = $simpleStomp->read();
        $this->assertEquals($frame->body, 'test message');
        if ($frame != null) {
            $simpleStomp->ack($frame);
        }

        $simpleStomp->unsubscribe($this->topic, 'myId', ['durable' => 'true', 'auto-delete' => 'false']);

        $consumer->disconnect();
    }
}
