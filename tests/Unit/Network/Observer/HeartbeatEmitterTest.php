<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Tests\Unit\Network\Observer;

use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use RuntimeException;
use Stomp\Exception\ConnectionException;
use Stomp\Network\Connection;
use Stomp\Network\Observer\Exception\HeartbeatException;
use Stomp\Network\Observer\HeartbeatEmitter;
use Stomp\Transport\Frame;

/**
 * EmitterTest
 *
 * @package Stomp\Tests\Unit\Network\Observer\Heartbeat
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class HeartbeatEmitterTest extends TestCase
{
    /**
     * @var HeartbeatEmitter
     */
    private $instance;
    private $beatsSend = 0;
    private $connectionReadTimeOut = [
        0,
        5000
    ];

    /**
     * @var Connection|PHPUnit_Framework_MockObject_MockObject
     */
    private $connection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['sendAlive', 'getReadTimeout'])
            ->getMock();
        $this->connection->expects($this->any())->method('sendAlive')->willReturnCallback(
            function () {
                $this->beatsSend++;
                return true;
            }
        );
        $this->connection->expects($this->any())->method('getReadTimeout')->willReturnCallback(
            function () {
                return $this->connectionReadTimeOut;
            }
        );
        $this->instance = new HeartbeatEmitter($this->connection, 0.5);
    }

    public function testEmitterActivatedByConnectAndConnectedFrames()
    {
        $this->assertFalse($this->instance->isEnabled());

        $connectFrame = new Frame(HeartbeatEmitter::FRAME_CLIENT_CONNECT);
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

        $connectedFrame = new Frame(HeartbeatEmitter::FRAME_SERVER_CONNECTED);
        $this->instance->receivedFrame($connectedFrame);
        $this->assertFalse($this->instance->isEnabled());

        $connectedFrame['heart-beat'] = '0,0';
        $this->instance->receivedFrame($connectedFrame);
        $this->assertFalse($this->instance->isEnabled());
    }

    public function testEmitterNotActivatedIfClientDontWantToSendBeats()
    {
        $connectFrame = new Frame(HeartbeatEmitter::FRAME_CLIENT_CONNECT);
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
        $connectedFrame = new Frame(HeartbeatEmitter::FRAME_SERVER_CONNECTED);
        $connectedFrame['heart-beat'] = '0,100';
        $this->instance->receivedFrame($connectedFrame);
        $this->assertTrue($this->instance->isEnabled());
    }

    public function testNoDelayIfEmitterIsNotActive()
    {
        $this->assertFalse($this->instance->isDelayed());
    }

    public function testDelayDetection()
    {
        // can happen when the connection was recycled
        $connectedFrame = new Frame(HeartbeatEmitter::FRAME_SERVER_CONNECTED);
        $connectedFrame['heart-beat'] = '0,50';
        $this->instance->receivedFrame($connectedFrame);
        $this->assertFalse($this->instance->isDelayed());
        usleep(60000);
        $this->assertTrue($this->instance->isDelayed());
    }

    public function testBeatTriggeredByEmptyBufferRead()
    {
        // can happen when the connection was recycled
        $connectedFrame = new Frame(HeartbeatEmitter::FRAME_SERVER_CONNECTED);
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

        usleep(60000);
        $this->assertTrue($this->instance->isDelayed());
        $this->beatsSend = 0;
        $this->instance->emptyBuffer();
        $this->assertEquals(1, $this->beatsSend);
    }

    public function testBeatTriggeredByEmptyRead()
    {
        // can happen when the connection was recycled
        $connectedFrame = new Frame(HeartbeatEmitter::FRAME_SERVER_CONNECTED);
        $connectedFrame['heart-beat'] = '0,50';
        $this->instance->receivedFrame($connectedFrame);

        usleep(60000);
        $this->assertTrue($this->instance->isDelayed());
        $this->beatsSend = 0;
        $this->instance->emptyRead();
        $this->assertEquals(1, $this->beatsSend);
    }

    public function testIntervalCalculation()
    {
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

    public function testEmitterThrowsExceptionWhenConnectionReadTimeoutIsTooHigh()
    {
        $this->connectionReadTimeOut = [0,900000]; // 900 ms
        // we offer 300
        $connectFrame = new Frame(HeartbeatEmitter::FRAME_CLIENT_CONNECT);
        $connectFrame['heart-beat'] = '300,0';
        $this->instance->sentFrame($connectFrame);

        $this->expectException(HeartbeatException::class);
        $this->expectExceptionMessage(
            'Client heartbeat is lower than connection read timeout, causing failing heartbeats.'
        );

        // server asks for 500
        $connectedFrame = new Frame(HeartbeatEmitter::FRAME_SERVER_CONNECTED);
        $connectedFrame['heart-beat'] = '0,500';
        $this->instance->receivedFrame($connectedFrame);
    }

    public function testEmitterThrowsExceptionWhenAliveSignalFails()
    {
        // can happen when the connection was recycled
        $connectedFrame = new Frame(HeartbeatEmitter::FRAME_SERVER_CONNECTED);
        $connectedFrame['heart-beat'] = '0,50';
        $this->instance->receivedFrame($connectedFrame);

        usleep(60000);

        $this->connection->expects($this->once())
            ->method('sendAlive')
            ->willThrowException(new ConnectionException('Send failure.'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not send heartbeat to server.');

        $this->instance->emptyBuffer();
    }

    public function testEmitterTriggersAliveCallWhenNotDelayedButPessimisticModeEnabledAndEmptyReadDetected()
    {
        $connectedFrame = new Frame(HeartbeatEmitter::FRAME_SERVER_CONNECTED);
        $connectedFrame['heart-beat'] = '0,500';
        $this->instance->receivedFrame($connectedFrame);

        $this->connection->expects($this->once())
            ->method('sendAlive');
        $this->assertTrue($this->instance->isEnabled());
        $this->assertFalse($this->instance->isDelayed());

        $this->instance->setPessimistic(true);
        $this->instance->emptyRead();
    }
}
