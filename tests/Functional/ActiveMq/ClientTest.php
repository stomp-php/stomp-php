<?php

/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Tests\Functional\ActiveMq;

use PHPUnit_Framework_TestCase;
use Stomp\Broker\ActiveMq\ActiveMq;
use Stomp\Client;
use Stomp\Network\Observer\HeartbeatEmitter;
use Stomp\SimpleStomp;
use Stomp\Transport\Bytes;
use Stomp\Transport\Frame;
use Stomp\Transport\Map;

/**
 * Client test for ActiveMq Broker
 *
 * @package Stomp
 * @author Michael Caplan <mcaplan@labnet.net>
 * @author Dejan Bosanac <dejan@nighttale.net>
 */
class ClientTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var SimpleStomp
     */
    private $simpleStomp;
    /**
     * @var Client
     */
    private $Stomp;

    private $queue = '/queue/test';
    private $topic = '/topic/test';

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->Stomp = ClientProvider::getClient();
        $this->simpleStomp = new SimpleStomp($this->Stomp);
    }
    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
        $this->Stomp = null;
        parent::tearDown();
    }


    public function testClientDetectedActiveMq()
    {
        if (! $this->Stomp->isConnected()) {
            $this->Stomp->connect();
        }
        $this->assertInstanceOf(ActiveMq::class, $this->Stomp->getProtocol(), 'Expected an ActiveMq Broker.');

        $this->Stomp->disconnect();
    }

    /**
     * Tests Stomp->hasFrameToRead()
     *
     */
    public function testHasFrameToRead()
    {
        if (! $this->Stomp->isConnected()) {
            $this->Stomp->connect();
        }

        $this->Stomp->getConnection()->setReadTimeout(0, 750000);

        $this->assertFalse($this->Stomp->getConnection()->hasDataToRead(), 'Has frame to read when non expected');

        $this->Stomp->send($this->queue, 'testHasFrameToRead');

        $this->simpleStomp->subscribe($this->queue, 'mysubid', 'client');

        $this->assertTrue($this->Stomp->getConnection()->hasDataToRead(), 'Did not have frame to read when expected');

        $frame = $this->Stomp->readFrame();

        $this->assertTrue($frame instanceof Frame, 'Frame expected');

        $this->simpleStomp->ack($frame);

        $this->Stomp->disconnect();

        $this->Stomp->getConnection()->setReadTimeout(60);
    }

    /**
     * Tests Stomp->ack()
     */
    public function testAck()
    {
        if (! $this->Stomp->isConnected()) {
            $this->Stomp->connect();
        }

        $messages = [];

        for ($x = 0; $x < 100; ++$x) {
            $this->Stomp->send($this->queue, $x);
            $messages[$x] = 'sent';
        }

        $this->Stomp->disconnect();

        for ($y = 0; $y < 100; $y += 10) {
            $this->Stomp->connect();

            $this->simpleStomp->subscribe($this->queue, 'mysubid', 'client');

            for ($x = $y; $x < $y + 10; ++$x) {
                $frame = $this->Stomp->readFrame();
                $this->assertInstanceOf(Frame::class, $frame);
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

            $this->Stomp->disconnect();
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
        $this->Stomp->getConnection()->setReadTimeout(3);
        if (! $this->Stomp->isConnected()) {
            $this->Stomp->connect();
        }
        $this->simpleStomp->begin("tx1");
        $this->assertTrue($this->Stomp->send($this->queue, 'testSend', ["transaction" => "tx1"]));
        $this->simpleStomp->abort("tx1");

        $this->simpleStomp->subscribe($this->queue, 'mysubid');
        $frame = $this->Stomp->readFrame();
        $this->assertFalse($frame);
        $this->simpleStomp->unsubscribe($this->queue, 'mysubid');
        $this->Stomp->disconnect();
    }

    /**
     * Tests Stomp->connect()
     */
    public function testConnect()
    {
        $this->assertTrue($this->Stomp->connect());
        $this->assertTrue($this->Stomp->isConnected());
    }
    /**
     * Tests Stomp->disconnect()
     */
    public function testDisconnect()
    {
        if (! $this->Stomp->isConnected()) {
            $this->Stomp->connect();
        }
        $this->assertTrue($this->Stomp->isConnected());
        $this->Stomp->disconnect();
        $this->assertFalse($this->Stomp->isConnected());
    }

    /**
     * Tests Stomp->getSessionId()
     */
    public function testGetSessionId()
    {
        if (! $this->Stomp->isConnected()) {
            $this->Stomp->connect();
        }
        $this->assertNotNull($this->Stomp->getSessionId());
    }

    /**
     * Tests Stomp->isConnected()
     */
    public function testIsConnected()
    {
        $this->Stomp->connect();
        $this->assertTrue($this->Stomp->isConnected());
        $this->Stomp->disconnect();
        $this->assertFalse($this->Stomp->isConnected());
    }

    /**
     * Tests Stomp->readFrame()
     */
    public function testReadFrame()
    {
        if (! $this->Stomp->isConnected()) {
            $this->Stomp->connect();
        }
        $this->Stomp->send($this->queue, 'testReadFrame');
        $this->simpleStomp->subscribe($this->queue, 'mysubid', 'client');
        $frame = $this->Stomp->readFrame();
        $this->assertTrue($frame instanceof Frame);
        $this->assertEquals('testReadFrame', $frame->body, 'Body of test frame does not match sent message');
        $this->simpleStomp->ack($frame);
        $this->simpleStomp->unsubscribe($this->queue, 'mysubid');
    }

    /**
     * Tests Stomp->send()
     */
    public function testSend()
    {
        if (! $this->Stomp->isConnected()) {
            $this->Stomp->connect();
        }
        $this->assertTrue($this->Stomp->send($this->queue, 'testSend'));
        $this->simpleStomp->subscribe($this->queue, 'mysubid', 'client');
        $frame = $this->Stomp->readFrame();
        $this->assertTrue($frame instanceof Frame);
        $this->assertEquals('testSend', $frame->body, 'Body of test frame does not match sent message');
        $this->simpleStomp->ack($frame);
        $this->simpleStomp->unsubscribe($this->queue, 'mysubid');
    }

    /**
     * Tests Stomp->subscribe()
     */
    public function testSubscribe()
    {
        if (! $this->Stomp->isConnected()) {
            $this->Stomp->connect();
        }
        $this->assertTrue($this->simpleStomp->subscribe($this->queue, 'mysubid'));
        $this->simpleStomp->unsubscribe($this->queue, 'mysubid');
    }

    /**
     * Tests Stomp message transformation - json map
     */
    public function testJsonMapTransformation()
    {
        if (! $this->Stomp->isConnected()) {
            $this->Stomp->connect();
        }
        $body = ["city"=>"Belgrade", "name"=>"Dejan"];
        $header = [];
        $header['transformation'] = 'jms-map-json';
        $mapMessage = new Map($body, $header);
        $this->Stomp->send($this->queue, $mapMessage);

        $this->simpleStomp->subscribe($this->queue, 'mysubid', 'auto', null, ['transformation' => 'jms-map-json']);
        $msg = $this->Stomp->readFrame();
        $this->assertTrue($msg instanceof Map);

        /** @var \Stomp\Transport\Map $msg */
        $this->assertEquals($msg->map, $body);
        $this->simpleStomp->ack($msg);
        $this->Stomp->disconnect();
    }

    /**
     * Tests Stomp byte messages
     */
    public function testByteMessages()
    {
        if (! $this->Stomp->isConnected()) {
            $this->Stomp->connect();
        }
        $body = 'test';
        $mapMessage = new Bytes($body);
        $this->simpleStomp->subscribe($this->queue, 'mysubid', 'client-individual');

        $this->Stomp->send($this->queue, $mapMessage);

        $msg = $this->Stomp->readFrame();
        $this->assertInstanceOf(Frame::class, $msg);
        $this->assertEquals($msg->body, $body);
        $this->simpleStomp->ack($msg);
        $this->Stomp->disconnect();
    }

    /**
     * Tests Stomp->unsubscribe()
     */
    public function testUnsubscribe()
    {
        if (! $this->Stomp->isConnected()) {
            $this->Stomp->connect();
        }
        $this->simpleStomp->subscribe($this->queue, 'mysubid');
        $this->assertTrue($this->simpleStomp->unsubscribe($this->queue, 'mysubid'));
    }

    public function testDurable()
    {
        $this->subscribe();
        $this->produce();
        $this->consume();
    }

    protected function produce()
    {
        $producer = ClientProvider::getClient();
        $producer->setSync(false);
        $producer->connect();
        $producer->send($this->topic, 'test message', ['persistent' => 'true']);
        $producer->disconnect();
    }

    protected function subscribe()
    {
        $consumer = ClientProvider::getClient();
        $consumer->setSync(false);
        $consumer->setClientId('test');
        $consumer->connect();

        $amq = $consumer->getProtocol();
        $this->assertInstanceOf(ActiveMq::class, $amq);
        /**
         * @var $amq ActiveMq
         */
        $consumer->sendFrame($amq->getSubscribeFrame($this->topic, 'test', 'auto', null, true));

        $consumer->disconnect(true);
    }

    protected function consume()
    {
        $consumer2 = ClientProvider::getClient();
        $consumer2->setSync(false);
        $consumer2->setClientId('test');
        $consumer2->getConnection()->setReadTimeout(1);
        $consumer2->connect();

        $amq = $consumer2->getProtocol();
        $this->assertInstanceOf(ActiveMq::class, $amq);
        /**
         * @var $amq ActiveMq
         */
        $consumer2->sendFrame($amq->getSubscribeFrame($this->topic, 'test', 'auto', null, true));


        $frame = $consumer2->readFrame();
        $this->assertEquals($frame->body, 'test message');
        if ($frame != null) {
            $consumer2->sendFrame($amq->getAckFrame($frame));
        }

        // yes, that's active mq! you must unsub two times...
        // http://mail-archives.apache.org/mod_mbox/activemq-dev/201205.mbox/raw/
        //        %3C634996273.21688.1336051731428.JavaMail.tomcat@hel.zones.apache.org%3E/
        $consumer2->sendFrame($amq->getUnsubscribeFrame($this->topic, 'test'));
        // that took me some time...
        $consumer2->sendFrame($amq->getUnsubscribeFrame($this->topic, 'test', true));

        $consumer2->disconnect();
    }

    /**
     * Test that heartbeats are supported.
     */
    public function testHeartbeat()
    {
        if ($this->Stomp->isConnected()) {
            $this->Stomp->disconnect();
        }
        $this->Stomp->getConnection()->setPersistentConnection(false);

        // It's important that the read timeout is lower than the offered beat interval.
        // In detail the minimum suggested beat is: ((readTimeout μs / 1000 ms) * IntervalUsage %) + time you need to add logic between any call to the lib
        $this->Stomp->setHeartbeat(0,500); // at least after 0.5 seconds we will let the server know that we're alive
        $this->Stomp->getConnection()->setReadTimeout(0, 250000); // after 0.25 seconds a read operation must timeout

        // we add a beat emitter to the observers of our connection
        $this->Stomp->getConnection()->getObservers()->addObserver(new HeartbeatEmitter($this->Stomp->getConnection()));

        $this->Stomp->connect();
        $this->assertTrue($this->simpleStomp->subscribe($this->queue, 'mysubid', 'client'));

        $this->Stomp->readFrame(); // ~ 0.25 seconds
        usleep(250000); // 0.25 seconds
        // Sleep long enough for a heartbeat to be sent.
        $this->Stomp->readFrame(); // ~ 0.25 seconds

        // Send a frame.
        $this->assertTrue($this->Stomp->send($this->queue, 'testReadFrame'));

        $tries = 0;
        // Check we now have a frame to read.
        while (true) {
            $tries++;
            $frame = $this->Stomp->readFrame(); // ~ 0.25 seconds
            if ($frame) {
                $this->assertTrue($frame instanceof Frame);
                $this->assertEquals('testReadFrame', $frame->body, 'Body of test frame does not match sent message');
                $this->simpleStomp->ack($frame);
                $this->simpleStomp->unsubscribe($this->queue, 'mysubid');
                break;
            }
            $this->assertLessThan(10, $tries, 'Was not able to read the frame inside the expected time.');
        }
    }

    public function testSendAlive()
    {
        $this->Stomp->connect();
        $this->assertTrue($this->Stomp->getConnection()->sendAlive());
        $this->Stomp->disconnect();
    }
}
