<?php

/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Tests\Unit\Stomp\Network;

use Exception;
use PHPUnit_Framework_TestCase;
use ReflectionMethod;
use Stomp\Exception\ConnectionException;
use Stomp\Network\Connection;
use Stomp\Tests\Unit\Stomp\Network\Mocks\FakeStream;
use Stomp\Transport\Frame;

/**
 * Connection test case.
 *
 * @package Stomp
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class ConnectionTest extends PHPUnit_Framework_TestCase
{
    public function testBrokerUriParseFailover()
    {
        $connection = new Connection('failover://(tcp://host1:61614,ssl://host2:61612)');
        $getHostList = new ReflectionMethod($connection, 'getHostList');
        $getHostList->setAccessible(true);

        $list = $getHostList->invoke($connection);

        $this->assertEquals('host1', $list[0]['host'], 'List is not in expected order.');
        $this->assertEquals('host2', $list[1]['host'], 'List is not in expected order.');
    }

    public function testBrokerUriParseSimple()
    {
        $connection = new Connection('tcp://host1');
        $getHostList = new ReflectionMethod($connection, 'getHostList');
        $getHostList->setAccessible(true);

        $hostList = $getHostList->invoke($connection);
        $host = array_shift($hostList);
        $this->assertEquals('tcp', $host['scheme']);
        $this->assertEquals('host1', $host['host']);
        $this->assertEquals(61613, $host['port'], 'Default port must be set!');
    }

    public function testBrokerUriParseUnderscoreInHost()
    {
        $connection = new Connection('tcp://host_test');
        $getHostList = new ReflectionMethod($connection, 'getHostList');
        $getHostList->setAccessible(true);

        $hostList = $getHostList->invoke($connection);
        $host = array_shift($hostList);
        $this->assertEquals('tcp', $host['scheme']);
        $this->assertEquals('host_test', $host['host']);
    }

    public function testBrokerUriParseSpecificPort()
    {
        $connection = new Connection('tcp://host1:55');
        $getHostList = new ReflectionMethod($connection, 'getHostList');
        $getHostList->setAccessible(true);

        $hostList = $getHostList->invoke($connection);
        $host = array_shift($hostList);
        $this->assertEquals('tcp', $host['scheme']);
        $this->assertEquals('host1', $host['host']);
        $this->assertEquals(55, $host['port']);
    }

    /**
     * @expectedException \Stomp\Exception\StompException
     */
    public function testBrokerUriParseWithEmptyListWillLeadToException()
    {
        new Connection('-');
    }

    public function testConnectionSetupTriesFullHostListBeforeGivingUp()
    {
        /** @var Connection|\PHPUnit_Framework_MockObject_MockObject $connection */
        $connection = $this->getMockBuilder(Connection::class)
            ->setMethods(['connectSocket'])
            ->setConstructorArgs(['failover://(tcp://host1,tcp://host2,tcp://host3)'])
            ->getMock();

        $expectedHosts = [
            'host1', 'host2', 'host3'
        ];

        $test = $this;
        $connection->expects($this->exactly(3))->method('connectSocket')->will(
            $this->returnCallback(
                function ($host) use (&$expectedHosts, $test) {
                    $current = array_shift($expectedHosts);
                    $test->assertEquals($current, $host['host'], 'Wrong host given to connect.');
                    throw new ConnectionException('Connection failed.', $host);
                }
            )
        );

        try {
            $connection->connect();
            $this->fail('No connection was established, expected exception!');
        } catch (Exception $ex) {
            $this->assertContains('Could not connect to a broker', $ex->getMessage());
        }
    }

    /**
     * @expectedException \Stomp\Exception\StompException
     */
    public function testHasDataToReadThrowsExceptionIfNotConnected()
    {
        $connection = new Connection('tcp://localhost');
        $connection->hasDataToRead();
    }

    /**
     * @expectedException \Stomp\Exception\StompException
     */
    public function testReadFrameThrowsExceptionIfNotConnected()
    {
        $connection = new Connection('tcp://localhost');
        $connection->readFrame();
    }

    /**
     * @expectedException \Stomp\Exception\StompException
     */
    public function testWriteFrameThrowsExceptionIfNotConnected()
    {
        $connection = new Connection('tcp://localhost');
        $connection->writeFrame(new Frame());
    }

    /**
     * https://github.com/stomp-php/stomp-php/issues/39
     */
    public function testMessageWithNullBytesAfterFullReadWontCauseReadException()
    {
        stream_wrapper_register('stompFakeStream', FakeStream::class);

        $mock = $this->getMockBuilder(Connection::class)
            ->setMethods(['getConnection'])
            ->setConstructorArgs(['stompFakeStream://notInUse'])
            ->getMock();
        $fakeStreamResource = fopen('stompFakeStream://notInUse', 'rw');
        $mock->method('getConnection')->willReturn($fakeStreamResource);

        /**
         * @var $mock Connection
         */
        $mock->connect();

        $header = 'MESSAGE' . "\ncontent-length:8165\n\n"; //29 bytes
        $body = substr(str_repeat('1234', 2048), 0, -29) . 'X' . "\x00"; //8164 bytes + 1 byte = 8165 bytes
        $frame = $header . $body; // 8194 bytes = one full 8192 read (+1 byte for marker) + zero byte next read
        FakeStream::$serverSend = $frame;

        $frame = $mock->readFrame();
        $this->assertEquals($body, $frame->body);
        fclose($fakeStreamResource);
        stream_wrapper_unregister('stompFakeStream');
    }
}
