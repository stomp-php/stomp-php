<?php

/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Tests\Functional\RabbitMq;

use Stomp\Frame;
use Stomp\Message\Bytes;
use Stomp\Message\Map;
use Stomp\Stomp;

/* vim: set expandtab tabstop=3 shiftwidth=3: */

/**
 * Client test for RabbitMq Broker
 *
 * @package Stomp
 * @author Michael Caplan <mcaplan@labnet.net>
 * @author Dejan Bosanac <dejan@nighttale.net>
 */
class ClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Stomp
     */
    private $Stomp;
    private $broker = 'tcp://127.0.0.1:61030';
    private $queue = '/queue/test';
    private $topic = '/topic/test';
    private $login = 'guest';
    private $password = 'guest';

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->Stomp = new Stomp($this->broker);
        $this->Stomp->brokerVendor = 'RMQ';
        $this->Stomp->sync = false;
    }
    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
        $this->Stomp = null;
        parent::tearDown();
    }

    /**
     * Tests Stomp->hasFrameToRead()
     *
     */
    public function testHasFrameToRead()
    {
        if (! $this->Stomp->isConnected()) {
            $this->Stomp->connect($this->login, $this->password);
        }

        $this->Stomp->getConnection()->setReadTimeout(5);

        $this->assertFalse($this->Stomp->getConnection()->hasDataToRead(), 'Has frame to read when non expected');

        $this->Stomp->send($this->queue, 'testHasFrameToRead');

        $this->Stomp->subscribe($this->queue, array('ack' => 'client','prefetch-count' => 1 ));

        $this->assertTrue($this->Stomp->getConnection()->hasDataToRead(), 'Did not have frame to read when expected');

        $frame = $this->Stomp->readFrame();

        $this->assertTrue($frame instanceof Frame, 'Frame expected');

        $this->Stomp->ack($frame);

        $this->Stomp->disconnect();

        $this->Stomp->getConnection()->setReadTimeout(60);
    }

    /**
     * Tests Stomp->ack()
     */
    public function testAck()
    {
        if (! $this->Stomp->isConnected()) {
            $this->Stomp->connect($this->login, $this->password);
        }

        $messages = array();

        for ($x = 0; $x < 100; ++$x) {
            $this->Stomp->send($this->queue, $x);
            $messages[$x] = 'sent';
        }

        $this->Stomp->disconnect();

        for ($y = 0; $y < 100; $y += 10) {
            $this->Stomp->connect($this->login, $this->password);

            $this->Stomp->subscribe($this->queue, array('ack' => 'client', 'activemq.prefetchSize' => 1));

            for ($x = $y; $x < $y + 10; ++$x) {
                $frame = $this->Stomp->readFrame();
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

                $this->assertTrue($this->Stomp->ack($frame), "Unable to ack {$frame->headers['message-id']}");

            }

            $this->Stomp->disconnect();
        }

        $un_acked_messages = array();

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
        $this->Stomp->getConnection()->setReadTimeout(1);
        if (! $this->Stomp->isConnected()) {
            $this->Stomp->connect($this->login, $this->password);
        }
        $this->Stomp->begin('tx1');
        $this->assertTrue($this->Stomp->send($this->queue, 'testSend', array('transaction' => 'tx1')));
        $this->Stomp->abort('tx1');

        $this->Stomp->subscribe($this->queue);
        $frame = $this->Stomp->readFrame();
        $this->assertFalse($frame);
        $this->Stomp->unsubscribe($this->queue);
        $this->Stomp->disconnect();
    }

    /**
     * Tests Stomp->connect()
     */
    public function testConnect()
    {
        $this->assertTrue($this->Stomp->connect($this->login, $this->password));
        $this->assertTrue($this->Stomp->isConnected());
    }

    /**
     * Tests Stomp->disconnect()
     */
    public function testDisconnect()
    {
        if (! $this->Stomp->isConnected()) {
            $this->Stomp->connect($this->login, $this->password);
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
            $this->Stomp->connect($this->login, $this->password);
        }
        $this->assertNotNull($this->Stomp->getSessionId());
    }

    /**
     * Tests Stomp->isConnected()
     */
    public function testIsConnected()
    {
        $this->Stomp->connect($this->login, $this->password);
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
            $this->Stomp->connect($this->login, $this->password);
        }
        $this->Stomp->send($this->queue, 'testReadFrame');
        $this->Stomp->subscribe($this->queue);
        $frame = $this->Stomp->readFrame();
        $this->assertTrue($frame instanceof Frame);
        $this->assertEquals('testReadFrame', $frame->body, 'Body of test frame does not match sent message');
        $this->Stomp->ack($frame);
        $this->Stomp->unsubscribe($this->queue);
    }

    /**
     * Tests Stomp->send()
     */
    public function testSend()
    {
        if (! $this->Stomp->isConnected()) {
            $this->Stomp->connect($this->login, $this->password);
        }
        $this->assertTrue($this->Stomp->send($this->queue, 'testSend'));
        $this->Stomp->subscribe($this->queue);
        $frame = $this->Stomp->readFrame();
        $this->assertTrue($frame instanceof Frame);
        $this->assertEquals('testSend', $frame->body, 'Body of test frame does not match sent message');
        $this->Stomp->ack($frame);
        $this->Stomp->unsubscribe($this->queue);
    }

    /**
     * Tests Stomp->subscribe()
     */
    public function testSubscribe()
    {
        if (! $this->Stomp->isConnected()) {
            $this->Stomp->connect($this->login, $this->password);
        }
        $this->assertTrue($this->Stomp->subscribe($this->queue));
        $this->Stomp->unsubscribe($this->queue);
    }

    /**
     * Tests Stomp message transformation - json map
     */
    public function testJsonMapTransformation()
    {
        if (! $this->Stomp->isConnected()) {
            $this->Stomp->connect($this->login, $this->password);
        }
        $body = array('city' => 'Belgrade', 'name' => 'Dejan');
        $header = array();
        $header['transformation'] = 'jms-map-json';
        $mapMessage = new Map($body, $header);
        $this->Stomp->send($this->queue, $mapMessage);

        $this->Stomp->subscribe($this->queue, array('transformation' => 'jms-map-json'));
        $msg = $this->Stomp->readFrame();
        $this->assertTrue($msg instanceof Map);

        /** @var Map $msg */
        $this->assertEquals($msg->map, $body);
        $this->Stomp->ack($msg);
        $this->Stomp->disconnect();
    }

    /**
     * Tests Stomp byte messages
     */
    public function testByteMessages()
    {
        if (! $this->Stomp->isConnected()) {
            $this->Stomp->connect($this->login, $this->password);
        }
        $body = 'test';
        $mapMessage = new Bytes($body);
        $this->Stomp->send($this->queue, $mapMessage);

        $this->Stomp->subscribe($this->queue);
        $msg = $this->Stomp->readFrame();
        $this->assertEquals($msg->body, $body);
        $this->Stomp->ack($msg);
        $this->Stomp->disconnect();
    }

    /**
     * Tests Stomp->unsubscribe()
     */
    public function testUnsubscribe()
    {
        if (! $this->Stomp->isConnected()) {
            $this->Stomp->connect($this->login, $this->password);
        }
        $this->Stomp->subscribe($this->queue);
        $this->assertTrue($this->Stomp->unsubscribe($this->queue));
    }

    public function testDurable()
    {
        $this->subscribe();
        sleep(2);
        $this->produce();
        sleep(2);
        $this->consume();
    }

    protected function subscribe()
    {
        $consumer = new Stomp($this->broker);
        $consumer->sync = true;
        $consumer->clientId = 'test';
        $consumer->connect($this->login, $this->password);
        $consumer->subscribe($this->topic, array('persistent' => 'true'));
        $consumer->unsubscribe($this->topic);
        $consumer->disconnect();
    }

    protected function produce()
    {
        $producer = new Stomp($this->broker);
        $producer->sync = true;
        $producer->connect($this->login, $this->password);
        $producer->send($this->topic, 'test message', array('persistent' => 'true'));
        $producer->disconnect();
    }


    protected function consume()
    {
        $consumer2 = new Stomp($this->broker);
        $consumer2->sync = true;
        $consumer2->clientId = 'test';
        $consumer2->getConnection()->setReadTimeout(1);
        $consumer2->connect($this->login, $this->password);
        $consumer2->subscribe($this->topic, array('persistent' => 'true'));

        $frame = $consumer2->readFrame();
        $this->assertEquals($frame->body, 'test message');
        if ($frame != null) {
            $consumer2->ack($frame);
        }

        $consumer2->disconnect();
    }
}
