<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Tests\Unit\Network\Observer;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Stomp\Network\Observer\AbstractBeats;
use Stomp\Transport\Frame;

/**
 * AbstractBeatsTest
 *
 * @package Stomp\Tests\Unit\Network\Observer
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class AbstractBeatsTest extends TestCase
{
    public function testNotDelayedWhenNotActivated()
    {
        $instance = $this->getInstance();
        $this->assertFalse($instance->isEnabled());
        $this->assertFalse($instance->isDelayed());
    }

    public function testCheckDelayTriggersOnDelayIfDelayed()
    {
        $instance = $this->getInstance();

        $checkDelayed = new \ReflectionMethod($instance, 'checkDelayed');
        if (PHP_VERSION_ID < 80100) {
            $checkDelayed->setAccessible(true);
        }

        $instance->expects($this->once())->method('onDelay');
        $this->injectActivatedWithServerActivityBeatUpdate($instance, 1);

        time_nanosleep(0, 1000000);

        $checkDelayed->invoke($instance);
        $this->assertTrue($instance->isDelayed());
    }

    public function testCheckDelayWillNotTriggerOnDelayIfNotDelayed()
    {
        $instance = $this->getInstance();
        $instance->expects($this->never())->method('onDelay');
        $this->injectActivatedWithServerActivityBeatUpdate($instance, 500);

        $checkDelayed = new \ReflectionMethod($instance, 'checkDelayed');
        if (PHP_VERSION_ID < 80100) {
            $checkDelayed->setAccessible(true);
        }
        $checkDelayed->invoke($instance);
        $this->assertFalse($instance->isDelayed());
    }

    public function testSendFrameInEnabledStateWillTriggerClientActivity()
    {
        $instance = $this->getInstance();
        $frame = $this->getClientConnectFrame($server = 10, $client = 20);

        $this->assertEnabledOnce($instance, $frame, $server = 10, $client = 20);
        $instance->expects($this->once())->method('onClientActivity');

        // first call must activate the observer
        $this->assertFalse($instance->isEnabled());
        $instance->sentFrame($frame);

        // second call wont start enable process again, but trigger server activity
        $instance->sentFrame($frame);
        $this->assertTrue($instance->isEnabled());
    }


    public function testSendFrameInDisabledStateWillProcessConnectFrame()
    {
        $instance = $this->getInstance();
        $instance->expects($this->never())->method('onServerActivity');
        $instance->expects($this->never())->method('calculateInterval');

        $frame = $this->getClientConnectFrame($server = 5, $client = 10);
        // connect frame must be forwarded
        $instance->expects($this->once())->method('onHeartbeatFrame')->with($frame, [10, 5]);
        $instance->sentFrame($frame);

        $this->assertFalse($instance->isEnabled());
    }


    public function testReceiveFrameInEnabledStateWillTriggerServerActivity()
    {
        $instance = $this->getInstance();
        $frame = $this->getServerConnectedFrame($server = 10, $client = 20);

        $this->assertEnabledOnce($instance, $frame, $server = 10, $client = 20);
        $instance->expects($this->once())->method('onServerActivity');

        // first call must activate the observer
        $this->assertFalse($instance->isEnabled());
        $instance->receivedFrame($frame);

        // second call wont start enable process again, but trigger server activity
        $instance->receivedFrame($frame);
        $this->assertTrue($instance->isEnabled());
    }

    public function testReceiveFrameInDisabledStateWillProcessConnectFrame()
    {
        $instance = $this->getInstance();
        $instance->expects($this->never())->method('onServerActivity');
        $instance->expects($this->never())->method('calculateInterval');

        // connected frame must be forwarded
        $frame = $this->getServerConnectedFrame($server = 10, $client = 20);
        $instance->expects($this->once())->method('onHeartbeatFrame')->with($frame, [10, 20]);
        $instance->receivedFrame($frame);

        $this->assertFalse($instance->isEnabled());
    }

    public function testHeartbeatConfigReceivesZeroValuesForMissingHeartbeatHeaders()
    {
        $instance = $this->getInstance();

        // connected frame must be forwarded
        $frame = new Frame(AbstractBeats::FRAME_SERVER_CONNECTED);
        $instance->expects($this->once())->method('onHeartbeatFrame')->with($frame, [0, 0]);
        $instance->receivedFrame($frame);
    }

    public function testGetIntervalReturnsResultFromCalculateIntervalInMicroSeconds()
    {
        $instance = $this->getInstance();
        $this->injectActivatedWithServerActivityBeatUpdate($instance, 1);
        $this->assertEquals(0.001, $instance->getInterval());
    }

    public function testEmptyLineReceivedWillTriggerServerActivity()
    {
        $instance = $this->getInstance();
        $instance->expects($this->once())->method('onServerActivity');
        $instance->emptyLineReceived();
    }

    public function testEmptyReadWillTriggerPotentialConnectionStateActivity()
    {
        $instance = $this->getInstance();
        $instance->expects($this->once())->method('onPotentialConnectionStateActivity');
        $instance->emptyRead();
    }

    public function testEmptyBufferWillTriggerPotentialConnectionStateActivity()
    {
        $instance = $this->getInstance();
        $instance->expects($this->once())->method('onPotentialConnectionStateActivity');
        $instance->emptyBuffer();
    }

    /**
     * Injects a function that will update the last detected beat whenever a server signal is received.
     *
     * @noinspection PhpDocMissingThrowsInspection
     * @param MockObject|AbstractBeats $instance
     * @param $interval
     */
    private function injectActivatedWithServerActivityBeatUpdate(
        MockObject $instance,
        $interval
    ) {
        $frame = $this->getServerConnectedFrame($interval, $interval);
        $this->assertEnabledOnce($instance, $frame, $interval, $interval);
        $instance->receivedFrame($frame);
        $remember = new \ReflectionMethod($instance, 'rememberActivity');
        if (PHP_VERSION_ID < 80100) {
            $remember->setAccessible(true);
        }
        $instance->expects($this->any())
            ->method('onServerActivity')
            ->willReturnCallback(
                function () use ($instance, $remember) {
                    $remember->invoke($instance);
                }
            );
    }

    /**
     * Assert that the instance will be enabled when a frame with heartbeat details is detected.
     *
     * @param MockObject|AbstractBeats $instance
     * @param Frame $frame
     * @param integer $intervalServer
     * @param integer $intervalClient
     */
    private function assertEnabledOnce(
        MockObject $instance,
        Frame $frame,
        $intervalServer,
        $intervalClient
    ) {
        $instance->expects($this->once())
            ->method('onHeartbeatFrame')
            ->with($frame)
            ->willReturnCallback(
                function () use ($instance, $intervalServer, $intervalClient) {
                    $this->injectClientInterval($instance, $intervalServer);
                    $this->injectServerInterval($instance, $intervalClient);
                }
            );
        $instance->expects($this->once())
            ->method('calculateInterval')
            ->willReturn(max($intervalClient, $intervalClient));
    }

    /**
     * Sets the interval property for client side.
     *
     * @noinspection PhpDocMissingThrowsInspection
     * @param AbstractBeats $instance
     * @param $interval
     */
    private function injectClientInterval(AbstractBeats $instance, $interval)
    {
        $property = new \ReflectionProperty($instance, 'intervalClient');
        if (PHP_VERSION_ID < 80100) {
            $property->setAccessible(true);
        }
        $property->setValue($instance, $interval);
    }

    /**
     * Sets the interval property for server side.
     *
     * @noinspection PhpDocMissingThrowsInspection
     * @param AbstractBeats $instance
     * @param $interval
     */
    private function injectServerInterval(AbstractBeats $instance, $interval)
    {
        $property = new \ReflectionProperty($instance, 'intervalServer');
        if (PHP_VERSION_ID < 80100) {
            $property->setAccessible(true);
        }
        $property->setValue($instance, $interval);
    }

    /**
     * Generates a beat instance.
     *
     * @return MockObject|AbstractBeats
     */
    private function getInstance()
    {
        return $this->getMockForAbstractClass(AbstractBeats::class);
    }

    /**
     * Returns a connected frame from server side.
     *
     * @param integer $server interval
     * @param integer $client interval
     * @return Frame
     */
    private function getServerConnectedFrame($server, $client)
    {
        $frame = new Frame(AbstractBeats::FRAME_SERVER_CONNECTED);
        $frame['heart-beat'] = sprintf('%d,%d', $server, $client);
        return $frame;
    }

    /**
     * Returns a connect frame from client side.
     *
     * @param integer $server interval
     * @param integer $client interval
     * @return Frame
     */
    private function getClientConnectFrame($server, $client)
    {
        $frame = new Frame(AbstractBeats::FRAME_CLIENT_CONNECT);
        $frame['heart-beat'] = sprintf('%d,%d', $client, $server);
        return $frame;
    }
}
