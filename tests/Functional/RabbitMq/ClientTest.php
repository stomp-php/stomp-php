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
use Stomp\LegacyStomp;
use Stomp\Transport\Bytes;
use Stomp\Transport\Frame;
use Stomp\Transport\Map;

/* vim: set expandtab tabstop=3 shiftwidth=3: */

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
     * @var LegacyStomp
     */
    private $legacyStomp;
    private $broker = 'tcp://localhost:61030';
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

        $this->stomp = new Client($this->broker);
        $this->stomp->setSync(false);
        $this->stomp->setVhostname('/');
        $this->stomp->setLogin($this->login, $this->password);
        $this->legacyStomp = new LegacyStomp($this->stomp);
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
        $this->stomp->getConnection()->setReadTimeout(5);

        $this->assertFalse($this->stomp->getConnection()->hasDataToRead(), 'Has frame to read when non expected');

        $this->stomp->send($this->queue, 'testHasFrameToRead');

        $legacyStomp = new LegacyStomp($this->stomp);
        $legacyStomp->subscribe($this->queue, null, 'client');

        $this->assertTrue($this->stomp->getConnection()->hasDataToRead(), 'Did not have frame to read when expected');

        $frame = $this->stomp->readFrame();

        $this->assertTrue($frame instanceof Frame, 'Frame expected');

        $legacyStomp->ack($frame);
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


            $this->legacyStomp->subscribe($this->queue, null, 'client');

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

                $this->legacyStomp->ack($frame);

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
        $this->stomp->getConnection()->setReadTimeout(1);
        $this->stomp->connect();
        $this->legacyStomp->begin('tx1');
        $this->assertTrue($this->stomp->send('/queue/abort', 'testSend', ['transaction' => 'tx1']));
        $this->legacyStomp->abort('tx1');

        $this->legacyStomp->subscribe('/queue/abort');
        $frame = $this->stomp->readFrame();
        $this->assertFalse($frame);
        $this->legacyStomp->unsubscribe('/queue/abort');
        $this->stomp->disconnect();
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
        $this->legacyStomp->subscribe('/queue/readframe');
        $frame = $this->stomp->readFrame();
        $this->assertTrue($frame instanceof Frame);
        $this->assertEquals('testReadFrame', $frame->body, 'Body of test frame does not match sent message');
        $this->legacyStomp->unsubscribe('/queue/readframe');
    }

    /**
     * Tests Stomp->send()
     */
    public function testSend()
    {
        if (! $this->stomp->isConnected()) {
            $this->stomp->connect();
        }
        $legacyStomp = new LegacyStomp($this->stomp);
        $this->assertTrue($this->stomp->send('/queue/sendframe', 'testSend'));
        $legacyStomp->subscribe('/queue/sendframe');
        $frame = $this->stomp->readFrame();
        $this->assertTrue($frame instanceof Frame);
        $this->assertEquals('testSend', $frame->body, 'Body of test frame does not match sent message');
        $legacyStomp->unsubscribe('/queue/sendframe');
    }

    /**
     * Tests Stomp->subscribe()
     */
    public function testSubscribe()
    {
        if (! $this->stomp->isConnected()) {
            $this->stomp->connect();
        }
        $legacyStomp = new LegacyStomp($this->stomp);
        $this->assertTrue($legacyStomp->subscribe('/queue/sub'));
        $legacyStomp->unsubscribe('/queue/sub');
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
        $legacyStomp = new LegacyStomp($this->stomp);
        $body = ['city' => 'Belgrade', 'name' => 'Dejan'];
        $header = [];
        $header['transformation'] = 'jms-map-json';
        $mapMessage = new Map($body, $header);
        $this->stomp->send('/queue/transform', $mapMessage);

        $legacyStomp->subscribe('/queue/transform', null, 'auto', null, ['transformation' => 'jms-map-json']);
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

        $legacyStomp = new LegacyStomp($this->stomp);
        $legacyStomp->subscribe('/queue/bytes');
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
        $legacyStomp = new LegacyStomp($this->stomp);
        $legacyStomp->subscribe('/queue/unsub');
        $this->assertTrue($legacyStomp->unsubscribe('/queue/unsub'));
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
        $consumer = new Client($this->broker);
        $consumer->setSync(true);
        $consumer->setVhostname('/');
        $consumer->setClientId('test');
        $consumer->setLogin($this->login, $this->password);
        $consumer->connect();

        $legacyStomp = new LegacyStomp($consumer);
        $legacyStomp->subscribe($this->topic, 'myId', 'client-individual', null, ['persistent' => 'true']);
        $legacyStomp->unsubscribe($this->topic, 'myId');
        $consumer->disconnect();
    }

    protected function produce()
    {
        $producer = new Client($this->broker);
        $producer->setSync(true);
        $producer->setVhostname('/');
        $producer->setLogin($this->login, $this->password);
        $producer->connect();
        $producer->send($this->topic, 'test message', ['persistent' => 'true']);
        $producer->disconnect();
    }


    protected function consume()
    {
        $consumer2 = new Client($this->broker);
        $consumer2->setSync(true);
        $consumer2->setVhostname('/');
        $consumer2->setClientId('test');
        $consumer2->getConnection()->setReadTimeout(1);
        $consumer2->setLogin($this->login, $this->password);
        $consumer2->connect();
        $consumer2->getConnection()->setReadTimeout(5);

        $legacyStomp = new LegacyStomp($consumer2);
        $legacyStomp->subscribe($this->topic, 'myId', 'client-individual', null, ['persistent' => 'true']);


        $frame = $legacyStomp->read();
        $this->assertEquals($frame->body, 'test message');
        if ($frame != null) {
            $legacyStomp->ack($frame);
        }

        $legacyStomp->unsubscribe($this->topic, 'myId');

        $consumer2->disconnect();
    }
}
