<?php

/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Tests\Unit\Stomp;

use PHPUnit_Framework_TestCase;
use ReflectionMethod;
use Stomp\Broker\RabbitMq\RabbitMq;
use Stomp\Client;
use Stomp\Exception\ConnectionException;
use Stomp\Exception\MissingReceiptException;
use Stomp\Exception\StompException;
use Stomp\Exception\UnexpectedResponseException;
use Stomp\Network\Connection;
use Stomp\Protocol\Protocol;
use Stomp\Protocol\Version;
use Stomp\Transport\Frame;
use Stomp\Transport\Message;
use Stomp\Transport\Parser;

/**
 * Stomp test case.
 *
 * @package Stomp
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class ClientTest extends PHPUnit_Framework_TestCase
{
    /**
     * Used to avoid destructor calls within single tests
     *
     * @var Client
     */
    private static $stomp;

    public static function tearDownAfterClass()
    {
        self::$stomp = null;
        parent::tearDownAfterClass();
    }

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
        $connectFrame['session'] = '-';
        $connectFrame['server'] ='rabbitmq';
        $connectFrame['session'] = 'session';

        $stomp = $this->getStompWithInjectedMockedConnectionReadResult($connectFrame);

        $stomp->connect();

        $this->assertInstanceOf(RabbitMq::class, $stomp->getProtocol(), 'Unexpected Protocol.');
    }

    public function testConnectWillDetermineSessionIdAndUsesSimpleStompAsDefaultDialect()
    {
        $connectFrame = new Frame('CONNECTED');
        $connectFrame['session'] = 'your-session-id';
        $connectFrame['server'] = 'not-supported';

        $stomp = $this->getStompWithInjectedMockedConnectionReadResult($connectFrame);

        $stomp->connect();

        $this->assertEquals(Protocol::class, get_class($stomp->getProtocol()), 'Unexpected Protocol.');
        $this->assertEquals('your-session-id', $stomp->getSessionId(), 'Wrong session id.');
    }


    public function testMultipleCallsToConnectWontLeadToMultipleConnectTries()
    {
        $connectFrame = new Frame('CONNECTED');
        $connectFrame['session'] = 'your-session-id';
        $connectFrame['server'] = 'not-supported';

        $stomp = $this->getStompWithInjectedMockedConnectionReadResult($connectFrame);

        $this->assertFalse($stomp->isConnected());
        $stomp->connect();
        $this->assertTrue($stomp->isConnected());
        $stomp->connect();
        $this->assertTrue($stomp->isConnected());
    }

    public function testSyncModeIsEnabledByDefault()
    {
        $stomp = new Client('tcp://127.0.0.1');
        $this->assertTrue($stomp->isSync());
    }

    public function testConnectWillUseConfiguredVersions()
    {
        $connection = $this->getMockBuilder(Connection::class)
            ->setMethods(['readFrame', 'writeFrame', 'getParser', 'isConnected', 'disconnect'])
            ->disableOriginalConstructor()
            ->getMock();
        $connection
            ->expects($this->once())
            ->method('readFrame')
            ->will(
                $this->returnValue(new Frame('CONNECTED'))
            );
        $sendFrame = null;
        $connection
            ->expects($this->once())
            ->method('writeFrame')
            ->willReturnCallback(
                function ($frame) use (&$sendFrame) {
                    $sendFrame = $frame;
                }
            );
        $connection
            ->expects($this->any())
            ->method('getParser')
            ->willReturn(new Parser());
        $connection
            ->expects($this->any())
            ->method('isConnected')
            ->willReturn(true);

        $client = new Client($connection);
        $client->setVersions([Version::VERSION_1_0, Version::VERSION_1_2]);
        $client->connect();
        self::$stomp = $client;

        $this->assertInstanceOf(Frame::class, $sendFrame);
        $this->assertEquals(Version::VERSION_1_0 . ',' . Version::VERSION_1_2, $sendFrame['accept-version']);
    }

    /**
     * @expectedException \Stomp\Exception\StompException
     */
    public function testWaitForReceiptWillThrowExceptionOnIdMismatch()
    {
        $receiptFrame = new Frame('RECEIPT');
        $receiptFrame['receipt-id'] = 'not-matching-id';

        $stomp = $this->getStompWithInjectedMockedConnectionReadResult($receiptFrame);

        $waitForReceipt = new ReflectionMethod($stomp, 'waitForReceipt');
        $waitForReceipt->setAccessible(true);

        // expect a receipt for another id
        $waitForReceipt->invoke($stomp, 'your-id');
    }

    public function testCalculateReceiptWaitEnd()
    {

        $stomp = new Client('http://127.0.0.1/');

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
     * @expectedException \Stomp\Exception\MissingReceiptException
     * @expectedExceptionMessage my-expected-receive-id
     */
    public function testWaitForReceiptWillThrowExceptionIfConnectionReadTimeoutOccurs()
    {
        $stomp = $this->getStompWithInjectedMockedConnectionReadResult(false);
        $stomp->setReceiptWait(0);

        $waitForReceipt = new ReflectionMethod($stomp, 'waitForReceipt');
        $waitForReceipt->setAccessible(true);

        // MuT
        $waitForReceipt->invoke($stomp, 'my-expected-receive-id');
    }

    /**
     * Get stomp, configured to use a connection which will return the given result on read.
     *
     * @param mixed $readFrameResult
     * @return Client
     */
    protected function getStompWithInjectedMockedConnectionReadResult($readFrameResult)
    {
        $connection = $this->getMockBuilder(Connection::class)
            ->setMethods(['readFrame', 'writeFrame', 'getParser', 'isConnected', 'disconnect'])
            ->disableOriginalConstructor()
            ->getMock();
        $connection
            ->expects($this->once())
            ->method('readFrame')
            ->will(
                $this->returnValue($readFrameResult)
            );
        $connection
            ->expects($this->any())
            ->method('getParser')
            ->will(
                $this->returnValue(new Parser())
            );
        $connection
            ->expects($this->any())
            ->method('isConnected')
            ->willReturn(true);

        return new Client($connection);
    }


    public function testSendWillAddDestinationAndHeaderToAnyFrameAndSetSyncState()
    {
        $stomp = $this->getStompMockWithSendFrameCatcher($lastSendFrame, $lastSyncState);
        /**
         * @var $lastSendFrame Frame
         */

        // test default frame send
        $headers = [
            'destination' => 'wrong-destination',
            'myheader' => 'myvalue',
        ];
        $frame = new Frame('CMD', $headers, 'body');

        // MuT
        $stomp->send('correct-destination', $frame, $headers, true);

        // verify
        $this->assertInstanceOf(Frame::class, $lastSendFrame);
        $this->assertEquals($frame->getCommand(), $lastSendFrame->getCommand(), 'Send must not change frame command.');
        $this->assertEquals(
            'correct-destination',
            $lastSendFrame['destination'],
            'Send must override destination header.'
        );
        $this->assertEquals(
            'myvalue',
            $lastSendFrame['myheader'],
            'Send must keep headers from given frame.'
        );
        $this->assertTrue(
            $lastSyncState,
            'Send must pass sync state.'
        );
    }

    public function testSendWillConvertStringToFrameBodyAndSetSyncState()
    {
        $stomp = $this->getStompMockWithSendFrameCatcher($lastSendFrame, $lastSyncState);
        /**
         * @var $lastSendFrame Frame
         */

        // test data
        $headers = [
            'destination' => 'wrong-destination',
            'myheader' => 'myvalue',
        ];
        $framebody = 'body';

        // MuT
        $stomp->send('correct-destination', $framebody, $headers, false);

        // verify
        $this->assertInstanceOf(Frame::class, $lastSendFrame);
        $this->assertEquals(
            'SEND',
            $lastSendFrame->getCommand(),
            'Send must set SEND as frame command, if frame was text.'
        );
        $this->assertEquals(
            'correct-destination',
            $lastSendFrame['destination'],
            'Send must override destination header.'
        );
        $this->assertEquals(
            'myvalue',
            $lastSendFrame['myheader'],
            'Send must keep headers from given frame.'
        );
        $this->assertEquals(
            'body',
            $lastSendFrame->body,
            'Send must set message as Frame body.'
        );
        $this->assertFalse(
            $lastSyncState,
            'Send must pass sync state.'
        );
    }


    /**
     * Get a stomp mock which will catch arguments passed to lasSendFrame and SyncState
     *
     * @param Frame $lastSendFrame reference to last send frame
     * @param mixed $lastSyncState reference to last syn argument
     * @return Client
     */
    protected function getStompMockWithSendFrameCatcher(&$lastSendFrame, &$lastSyncState)
    {
        $stomp = $this->getMockBuilder(Client::class)
            ->setMethods(['sendFrame', 'isConnected'])
            ->disableOriginalConstructor()
            ->getMock();

        $stomp->expects($this->any())->method('isConnected')->willReturn(true);
        $stomp->expects($this->any())
            ->method('sendFrame')
            ->will(
                $this->returnCallback(
                    function (Frame $frame, $sync) use (&$lastSendFrame, &$lastSyncState) {

                        $lastSendFrame = $frame;
                        $lastSyncState = $sync;
                    }
                )
            );
        return $stomp;
    }



    public function testSendFrameWithSyncWillLeadToMessageWithReceiptHeader()
    {
        $connection = $this->getMockBuilder(Connection::class)
            ->setMethods(['writeFrame', 'readFrame'])
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
        $stomp = $this->getMockBuilder(Client::class)
            ->setConstructorArgs([$connection])
            ->setMethods(['isConnected'])
            ->getMock();
        $stomp->expects($this->any())->method('isConnected')->willReturn(true);
        /**
         * @var $stomp Client
         */
        try {
            $stomp->setReceiptWait(0);
            $stomp->sendFrame(new Frame(), true);
        } catch (MissingReceiptException $ex) {
            // is allowed, since we send no receipt...
        }

        /** @var Frame $lastWriteFrame */
        $this->assertInstanceOf(Frame::class, $lastWriteFrame);
        $this->assertArrayHasKey('receipt', $lastWriteFrame, 'Written frame should have a "receipt" header.');
    }

    public function testWaitForReceiptWillQueueUpFramesWithNoReceiptCommand()
    {
        $connection = $this->getMockBuilder(Connection::class)
            ->setMethods(['readFrame'])
            ->disableOriginalConstructor()
            ->getMock();

        $readFrames = [
            new Frame('OTHER'),
            new Frame('OTHER-2'),
            new Frame('OTHER-3'),
        ];

        $expectedFrames = array_values($readFrames);

        $readFrames[] = new Frame('RECEIPT', ['receipt-id' => 'my-id']);

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

        $stomp = $this->getMockBuilder(Client::class)
            ->setConstructorArgs([$connection])
            ->setMethods(['isConnected'])
            ->getMock();
        $stomp->expects($this->any())->method('isConnected')->willReturn(true);
        /**
         * @var $stomp Client
         */

        $waitForReceipt = new ReflectionMethod($stomp, 'waitForReceipt');
        $waitForReceipt->setAccessible(true);

        $result = $waitForReceipt->invoke($stomp, 'my-id');

        $this->assertTrue($result, 'Wait for receipt must return true if correct receipt was received');

        $receivedFrames = [
           $stomp->readFrame(),
           $stomp->readFrame(),
           $stomp->readFrame(),
        ];

        foreach ($expectedFrames as $index => $frame) {
            $this->assertEquals(
                $frame,
                $receivedFrames[$index],
                'Frame must be the correct Frame from queued up frames in given order. (FIFO)'
            );
        }
    }

    public function testGetConnectionReturnsUsedConnection()
    {
        $connection = new Connection('tcp://myhost');
        $stomp = new Client($connection);

        $this->assertSame(
            $connection,
            $stomp->getConnection(),
            'getConnection must return passed connection instance.'
        );
    }

    public function testDisconnectWillCallConnectionDisconnectEvenWhenWriteFails()
    {
        $connection = $this->getMockBuilder(Connection::class)
            ->setConstructorArgs(['tcp://127.0.0.1:'])
            ->setMethods(['disconnect', 'readFrame', 'writeFrame', 'isConnected'])
            ->getMock();

        $connection->expects($this->any())->method('isConnected')->willReturn(true);

        $connection
            ->expects($this->once())
            ->method('readFrame')
            ->will(
                $this->returnValue(
                    new Frame('CONNECTED', ['session' => 'id', 'server' => 'activemq'])
                )
            );


        $connection->expects($this->once())->method('disconnect');
        $stomp = new Client($connection);
        $stomp->connect();

        $connection
            ->expects($this->once())
            ->method('writeFrame')
            ->will(
                $this->throwException(new StompException('Test: Writing frame failed.'))
            );
        $stomp->disconnect();

        // ensure that instance is not destroyed before mock assertions are done
        // (calling destruct would invoke disconnect outside current test)
        self::$stomp = $stomp;
    }

    public function testClientWillAutoConnectOnSendFrame()
    {
        $connection = $this->getMockBuilder(Connection::class)
            ->setMethods(['connect', 'getParser'])
            ->disableOriginalConstructor()
            ->getMock();
        $connection->expects($this->once())->method('connect');
        $connection->expects($this->any())->method('getParser')->willReturn(new Parser());
        $client = new Client($connection);
        try {
            $client->sendFrame(new Message('test'));
        } catch (ConnectionException $connectionFailed) {
            $this->addToAssertionCount(1);
        }
    }


    public function testClientWillAutoConnectOnGetProtocol()
    {
        $connection = $this->getMockBuilder(Connection::class)
            ->setMethods(['connect', 'getParser'])
            ->disableOriginalConstructor()
            ->getMock();
        $connection->expects($this->once())->method('connect');
        $connection->expects($this->any())->method('getParser')->willReturn(new Parser());
        $client = new Client($connection);
        try {
            $client->getProtocol();
        } catch (ConnectionException $connectionFailed) {
            $this->addToAssertionCount(1);
        }
    }
}
