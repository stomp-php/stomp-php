<?php

/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Tests\Unit\Stomp;

use Stomp\Connection;
use Stomp\Exception\UnexpectedResponseException;
use Stomp\Frame;
use Stomp\Stomp;
use PHPUnit_Framework_TestCase;
use ReflectionMethod;

/* vim: set expandtab tabstop=3 shiftwidth=3: */

/**
 * Stomp test case.
 *
 * @package Stomp
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class StompTest extends PHPUnit_Framework_TestCase
{
    public function testConnectWillThrowExceptionIfUnexpectedFrameArrives()
    {
        $frame = new Frame('FAIL');
        $stomp = $this->getStompWithInjectedMockedConnectionReadResult($frame);
        try {
            $stomp->connect();
            $this->fail('Expected exception!');
        } catch (UnexpectedResponseException $connectionException) {
            $this->assertSame($frame, $connectionException->getFrame());
        }

    }

    /**
     * @expectedException \Stomp\Exception\ConnectionException
     */
    public function testConnectWillThrowExceptionIfNoFrameWasRead()
    {
        $stomp = $this->getStompWithInjectedMockedConnectionReadResult(false);
        $stomp->connect();

    }

    public function testConnectWillDetermineRabbitMqDialect()
    {
        $connectFrame = new Frame('CONNECTED');
        $connectFrame->setHeader('session', '-');
        $connectFrame->setHeader('server', 'rabbitmq');

        $stomp = $this->getStompWithInjectedMockedConnectionReadResult($connectFrame);

        $stomp->connect();

        $this->assertInstanceOf('\Stomp\Protocol\RabbitMq', $stomp->getProtocol(), 'Unexpected Protocol.');
    }


    public function testConnectWillDetermineSessionIdAndUsesActiveMqAsDefaultDialect()
    {
        $connectFrame = new Frame('CONNECTED');
        $connectFrame->setHeader('session', 'your-session-id');
        $connectFrame->setHeader('server', 'not-supported');

        $stomp = $this->getStompWithInjectedMockedConnectionReadResult($connectFrame);

        $stomp->connect();

        $this->assertInstanceOf('\Stomp\Protocol\ActiveMq', $stomp->getProtocol(), 'Unexpected Protocol.');
        $this->assertEquals('your-session-id', $stomp->getSessionId(), 'Wrong session id.');
    }


    /**
     * @expectedException Stomp\Exception\StompException
     */
    public function testWaitForReceiptWillThrowExceptionOnIdMissmatch()
    {
        $receiptFrame = new Frame('RECEIPT');
        $receiptFrame->setHeader('receipt-id', 'not-matching-id');

        $stomp = $this->getStompWithInjectedMockedConnectionReadResult($receiptFrame);

        $waitForReceipt = new ReflectionMethod($stomp, '_waitForReceipt');
        $waitForReceipt->setAccessible(true);

        // expect a receipt for another id
        $waitForReceipt->invoke($stomp, 'your-id');
    }


    public function testCalculateReceiptWaitEnd()
    {

        $stomp = new Stomp('http://127.0.0.1/');

        $stomp->setReceiptWait(2.9);
        $calculateWaitEnd = new ReflectionMethod($stomp, 'calculateReceiptWaitEnd');
        $calculateWaitEnd->setAccessible(true);

        $now = microtime(true);
        $result = $calculateWaitEnd->invoke($stomp);

        $this->assertGreaterThan($now, $result, 'Wait end should be in future.');

        $resultDiff = round($result - $now, 1);
        $this->assertGreaterThanOrEqual($resultDiff, 2.9, 'Wait diff should be greater than /equal to 2.9.');
    }


    /**
     * @expectedException Stomp\Exception\MissingReceiptException
     * @expectedExceptionMessage my-expected-receive-id
     */
    public function testWaitForReceiptWillThrowExceptionIfConnectionReadTimeoutOccurs()
    {
        $stomp = $this->getStompWithInjectedMockedConnectionReadResult(false);
        $stomp->setReceiptWait(0);

        $waitForReceipt = new ReflectionMethod($stomp, '_waitForReceipt');
        $waitForReceipt->setAccessible(true);

        // MuT
        $waitForReceipt->invoke($stomp, 'my-expected-receive-id');
    }

    /**
     * Get stomp, configured to use a connection which will return the given result on read.
     *
     * @param mixed   $readFrameResult
     * @return Stomp
     */
    protected function getStompWithInjectedMockedConnectionReadResult($readFrameResult)
    {
        $connection = $this->getMockBuilder('\Stomp\Connection')
            ->setMethods(array('readFrame', 'writeFrame'))
            ->disableOriginalConstructor()
            ->getMock();
        $connection
            ->expects($this->once())
            ->method('readFrame')
            ->will(
                $this->returnValue($readFrameResult)
            );

        return new Stomp($connection);
    }


    public function testSendWillAddDestinationAndHeaderToAnyFrameAndSetSyncState()
    {
        $stomp = $this->getStompMockWithSendFrameCatcher($lastSendFrame, $lastSyncState);

        // test default frame send
        $headers = array(
            'destination' => 'wrong-destination',
            'myheader' => 'myvalue',
        );
        $frame = new Frame('CMD', $headers, 'body');

        // MuT
        $stomp->send('correct-destination', $frame, $headers, true);

        // verify
        $this->assertInstanceOf('\Stomp\Frame', $lastSendFrame);
        $this->assertEquals($frame->command, $lastSendFrame->command, 'Send must not change frame command.');
        $this->assertEquals(
            'correct-destination',
            $lastSendFrame->headers['destination'], 'Send must override destination header.'
        );
        $this->assertEquals(
            'myvalue',
            $lastSendFrame->headers['myheader'], 'Send must keep headers from given frame.'
        );
        $this->assertTrue(
            $lastSyncState, 'Send must pass sync state.'
        );
    }

    public function testSendWillConvertStringToFrameBodyAndSetSyncState()
    {
        $stomp = $this->getStompMockWithSendFrameCatcher($lastSendFrame, $lastSyncState);

        // test data
        $headers = array(
            'destination' => 'wrong-destination',
            'myheader' => 'myvalue',
        );
        $framebody = 'body';

        // MuT
        $stomp->send('correct-destination', $framebody, $headers, false);

        // verify
        $this->assertInstanceOf('\Stomp\Frame', $lastSendFrame);
        $this->assertEquals('SEND', $lastSendFrame->command, 'Send must set SEND as frame command, if frame was text.');
        $this->assertEquals(
            'correct-destination',
            $lastSendFrame->headers['destination'], 'Send must override destination header.'
        );
        $this->assertEquals(
            'myvalue',
            $lastSendFrame->headers['myheader'], 'Send must keep headers from given frame.'
        );
        $this->assertEquals(
            'body',
            $lastSendFrame->body, 'Send must set message as Frame body.'
        );
        $this->assertFalse(
            $lastSyncState, 'Send must pass sync state.'
        );
    }


    /**
     * Get a stomp mock which will catch arguments passed to lasSendFrame and SyncState
     *
     * @param Frame $lastSendFrame reference to last send frame
     * @param mixed $lastSyncState reference to last syn argument
     * @return Stomp
     */
    protected function getStompMockWithSendFrameCatcher(&$lastSendFrame, &$lastSyncState)
    {
        $stomp = $this->getMockBuilder('\Stomp\Stomp')
            ->setMethods(array('sendFrame'))
            ->disableOriginalConstructor()
            ->getMock();

        $stomp->expects($this->any())
            ->method('sendFrame')
            ->will(
                $this->returnCallback(
                    function (Frame $frame, $sync) use (&$lastSendFrame, &$lastSyncState)
                    {
                        $lastSendFrame = $frame;
                        $lastSyncState = $sync;
                    }
                )
            );
        return $stomp;
    }



    public function testSendFrameWithSyncWillLeadToMessageWithReceiptHeader()
    {
        $connection = $this->getMockBuilder('\Stomp\Connection')
            ->setMethods(array('writeFrame', 'readFrame'))
            ->disableOriginalConstructor()
            ->getMock();

        $connection
            ->expects($this->once())
            ->method('readFrame')
            ->will(
                $this->returnValue(false)
            );

        $lastWriteFrame = null;
        $connection
            ->expects($this->once())
            ->method('writeFrame')
            ->will(
                $this->returnCallback(
                    function ($frame) use (&$lastWriteFrame) {
                        $lastWriteFrame = $frame;
                    }
                )
            );
        $stomp = new Stomp($connection);


        try {
            $stomp->setReceiptWait(0);
            $stomp->sendFrame(new Frame(), true);
        } catch (\Stomp\Exception\MissingReceiptException $ex) {
            // is allowed, since we send no receipt...
        }

        $this->assertInstanceOf('\Stomp\Frame', $lastWriteFrame);
        $this->assertArrayHasKey('receipt', $lastWriteFrame->headers, 'Written frame should have a "receipt" header.');

    }


    public function testWaitForReceiptWillQueueUpFramesWithNoReceiptCommand()
    {
        $connection = $this->getMockBuilder('\Stomp\Connection')
            ->setMethods(array('readFrame'))
            ->disableOriginalConstructor()
            ->getMock();

        $readFrames = array(
            new Frame('OTHER'),
            new Frame('OTHER-2'),
            new Frame('OTHER-3'),
        );

        $expectedFrames = array_values($readFrames);

        $readFrames[] = new Frame('RECEIPT', array('receipt-id' => 'my-id'));

        $connection
            ->expects($this->exactly(4))
            ->method('readFrame')
            ->will(
                $this->returnCallback(
                    function () use (&$readFrames) {
                        return array_shift($readFrames);
                    }
                )
            );

       $stomp = new Stomp($connection);


       $waitForReceipt = new ReflectionMethod($stomp, '_waitForReceipt');
       $waitForReceipt->setAccessible(true);

       $result = $waitForReceipt->invoke($stomp, 'my-id');

       $this->assertTrue($result, 'Wait for receipt must return true if correct receipt was received');

       $receivedFrames = array(
           $stomp->readFrame(),
           $stomp->readFrame(),
           $stomp->readFrame(),
       );

       foreach ($expectedFrames as $index => $frame) {
           $this->assertEquals(
               $frame, $receivedFrames[$index],
               'Frame must be the correct Frame from queued up frames in given order. (FIFO)'
            );
       }
    }

    public function testAckWillUseMessageAsMessageIdForAckFrame()
    {
        $connection = $this->getMockBuilder('\Stomp\Connection')
            ->setMethods(array('readFrame', 'writeFrame'))
            ->disableOriginalConstructor()
            ->getMock();
        $connection
            ->expects($this->once())
            ->method('readFrame')
            ->will(
                $this->returnValue(
                    new Frame('CONNECTED', array('session' => 'id', 'server' => 'activemq'))
                )
            );
        $stomp = $this->getMockBuilder('\Stomp\Stomp')
            ->setMethods(array('sendFrame'))
            ->setConstructorArgs(array($connection))
            ->getMock();

        $lastSendFrame = null;
        $lastSyncState = null;
        $stomp->expects($this->any())
            ->method('sendFrame')
            ->will(
                $this->returnCallback(
                    function (Frame $frame, $sync) use (&$lastSendFrame, &$lastSyncState)
                    {
                        $lastSendFrame = $frame;
                        $lastSyncState = $sync;
                    }
                )
            );

        $stomp->connect();
        $stomp->ack('my-message-id', 'my-transaction-id');

        $this->assertEquals(
            'my-message-id', $lastSendFrame->headers['message-id'],
            'Ack must set param message as message-id if no frame was given!'
        );
    }

    function testGetConnectionReturnsUsedConnection()
    {
        $connection = new Connection('tcp://myhost');
        $stomp = new Stomp($connection);

        $this->assertSame($connection, $stomp->getConnection(), 'getConnection must return passed connection instance.');
    }
}
