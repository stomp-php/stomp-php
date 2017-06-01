<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Tests\Unit\Stomp\Network\Observer;


use PHPUnit\Framework\TestCase;
use Stomp\Network\Connection;
use Stomp\Network\Observer\HeartbeatEmitter;
use Stomp\Transport\Frame;

/**
 * EmitterTest
 *
 * @package Stomp\Tests\Unit\Stomp\Network\Observer\Heartbeat
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class HeartbeatEmitterTest extends TestCase
{
    /**
     * @var HeartbeatEmitter
     */
    private $instance;
    private $beatsSend = 0;

    protected function setUp()
    {
        parent::setUp();
        $connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->setMethods(['sendAlive'])
            ->getMock();
        $connection->expects($this->any())->method('sendAlive')->willReturnCallback(
            function () {
                $this->beatsSend++;
                return true;
            }
        );
        $this->instance = new HeartbeatEmitter($connection, 0.5);
    }

    public function testEmitterActivatedByConnectAndConnectedFrames()
    {
        $this->assertFalse($this->instance->isEnabled());

        $connectFrame = new Frame(\Stomp\Network\Observer\HeartbeatEmitter::FRAME_CLIENT_CONNECT);
        $connectFrame['heart-beat'] = '100,0';
        $this->instance->sentFrame($connectFrame);
        $this->assertFalse($this->instance->isEnabled());

        $connectedFrame = new Frame(HeartbeatEmitter::FRAME_SERVER_CONNECTED);
        $connectedFrame['heart-beat'] = '0,100';
        $this->instance->receivedFrame($connectedFrame);

        $this->assertTrue($this->instance->isEnabled());
    }

    public function testEmitterNotActivatedIfServerDontWantToReceiveBeats()
    {
        $this->assertFalse($this->instance->isEnabled());

        $connectFrame = new Frame(HeartbeatEmitter::FRAME_CLIENT_CONNECT);
        $connectFrame['heart-beat'] = '100,0';
        $this->instance->sentFrame($connectFrame);
        $this->assertFalse($this->instance->isEnabled());

        $connectedFrame = new Frame(\Stomp\Network\Observer\HeartbeatEmitter::FRAME_SERVER_CONNECTED);
        $this->instance->receivedFrame($connectedFrame);
        $this->assertFalse($this->instance->isEnabled());

        $connectedFrame['heart-beat'] = '0,0';
        $this->instance->receivedFrame($connectedFrame);
        $this->assertFalse($this->instance->isEnabled());
    }

    public function testEmitterNotActivatedIfClientDontWantToSendBeats()
    {
        $connectFrame = new Frame(\Stomp\Network\Observer\HeartbeatEmitter::FRAME_CLIENT_CONNECT);
        $this->instance->sentFrame($connectFrame);
        $this->assertFalse($this->instance->isEnabled());

        $connectedFrame = new Frame(HeartbeatEmitter::FRAME_SERVER_CONNECTED);
        $connectedFrame['heart-beat'] = '0,100';
        $this->instance->receivedFrame($connectedFrame);
        $this->assertFalse($this->instance->isEnabled());
    }

    public function testEmitterActivatedIfServerRequestsBeatsAndNoConnectFrameWasSend()
    {
        // can happen when the connection was recycled
        $connectedFrame = new Frame(\Stomp\Network\Observer\HeartbeatEmitter::FRAME_SERVER_CONNECTED);
        $connectedFrame['heart-beat'] = '0,100';
        $this->instance->receivedFrame($connectedFrame);
        $this->assertTrue($this->instance->isEnabled());
    }


    public function testDelayDetection()
    {
        // can happen when the connection was recycled
        $connectedFrame = new Frame(HeartbeatEmitter::FRAME_SERVER_CONNECTED);
        $connectedFrame['heart-beat'] = '0,50';
        $this->instance->receivedFrame($connectedFrame);
        $this->instance->emptyLineReceived();
        $this->assertFalse($this->instance->isDelayed());
        usleep(60000);
        $this->assertTrue($this->instance->isDelayed());
    }

    public function testBeatTriggeredByEmptyBufferRead()
    {
        // can happen when the connection was recycled
        $connectedFrame = new Frame(\Stomp\Network\Observer\HeartbeatEmitter::FRAME_SERVER_CONNECTED);
        $connectedFrame['heart-beat'] = '0,50';
        $this->instance->receivedFrame($connectedFrame);
        $this->instance->emptyBuffer();
        usleep(60000);
        $this->assertTrue($this->instance->isDelayed());
        $this->beatsSend = 0;
        $this->instance->emptyBuffer();
        $this->assertEquals(1, $this->beatsSend);
    }

    public function testBeatTriggeredByFrameRead()
    {
        // can happen when the connection was recycled
        $connectedFrame = new Frame(HeartbeatEmitter::FRAME_SERVER_CONNECTED);
        $connectedFrame['heart-beat'] = '0,50';
        $this->instance->receivedFrame($connectedFrame);
        $this->instance->emptyLineReceived();
        usleep(60000);
        $this->assertTrue($this->instance->isDelayed());
        $this->beatsSend = 0;
        $this->instance->receivedFrame(new Frame('MESSAGE'));
        $this->assertEquals(1, $this->beatsSend);
    }

    public function testBeatTriggeredByEmptyLineRead()
    {
        // can happen when the connection was recycled
        $connectedFrame = new Frame(HeartbeatEmitter::FRAME_SERVER_CONNECTED);
        $connectedFrame['heart-beat'] = '0,50';
        $this->instance->receivedFrame($connectedFrame);
        $this->instance->emptyLineReceived();
        usleep(60000);
        $this->assertTrue($this->instance->isDelayed());
        $this->beatsSend = 0;
        $this->instance->emptyBuffer();
        $this->assertEquals(1, $this->beatsSend);
    }


    public function testLastBeatUpdated()
    {
        // can happen when the connection was recycled
        $connectedFrame = new Frame(HeartbeatEmitter::FRAME_SERVER_CONNECTED);
        $connectedFrame['heart-beat'] = '0,50';
        $this->instance->receivedFrame($connectedFrame);
        $this->instance->sentFrame(new Frame('MESSAGE'));
        $lastBeat = $this->instance->getLastbeat();
        usleep(4000);
        $this->assertEquals($lastBeat, $this->instance->getLastbeat());
        $this->instance->sentFrame(new Frame('MESSAGE'));
        $this->assertGreaterThan($lastBeat, $this->instance->getLastbeat());
    }

    public function testIntervalUsageHasMinimumAndMaximumLimit()
    {
        $minUsageInstance = new HeartbeatEmitter(
            $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->getMock(),
            0
        );
        $maxUsageInstance = new HeartbeatEmitter(
            $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->getMock(),
            1
        );

        $this->assertGreaterThan(0, $minUsageInstance->getIntervalUsage());
        $this->assertLessThan(1, $maxUsageInstance->getIntervalUsage());
    }

    public function testIntervalCalculation()
    {
        // half time of expected beat interval is target send rate
        $this->assertEquals(0.5, $this->instance->getIntervalUsage());

        // we offer 300
        $connectFrame = new Frame(HeartbeatEmitter::FRAME_CLIENT_CONNECT);
        $connectFrame['heart-beat'] = '300,0';
        $this->instance->sentFrame($connectFrame);

        // server asks for 500
        $connectedFrame = new Frame(HeartbeatEmitter::FRAME_SERVER_CONNECTED);
        $connectedFrame['heart-beat'] = '0,500';
        $this->instance->receivedFrame($connectedFrame);

        // usage * max(interval) / ms
        $this->assertEquals((0.5 * 500) / 1000, $this->instance->getInterval());
    }
}