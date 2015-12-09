<?php

/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Tests\Unit\Stomp\Network;

use Exception;
use ReflectionMethod;
use Stomp\Exception\ConnectionException;
use Stomp\Network\Connection;

/* vim: set expandtab tabstop=3 shiftwidth=3: */

/**
 * Connection test case.
 *
 * @package Stomp
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class ConnectionTest extends \PHPUnit_Framework_TestCase
{
    public function testBrokerUriParseFailover()
    {
        $connection = new \Stomp\Network\Connection('failover://(tcp://host1:61614,ssl://host2:61612)');
        $getHostList = new ReflectionMethod($connection, 'getHostList');
        $getHostList->setAccessible(true);

        $list = $getHostList->invoke($connection);

        $this->assertEquals('host1', $list[0]['host'], 'List is not in expected order.');
        $this->assertEquals('host2', $list[1]['host'], 'List is not in expected order.');
    }

    public function testBrokerUriParseRandom()
    {
        $connection = new Connection('failover://(tcp://host1:61614,ssl://host2:61612)?randomize=true');
        $getHostList = new ReflectionMethod($connection, 'getHostList');
        $getHostList->setAccessible(true);

        $arrayHash = function (array $array) {
            $hash = '';
            foreach ($array as $host) {
                $hash .= $host['host'];
            }
            return $hash;
        };

        $calls = [
           $arrayHash($getHostList->invoke($connection)),
           $arrayHash($getHostList->invoke($connection)),
           $arrayHash($getHostList->invoke($connection)),
           $arrayHash($getHostList->invoke($connection)),
           $arrayHash($getHostList->invoke($connection)),
           $arrayHash($getHostList->invoke($connection)),
           $arrayHash($getHostList->invoke($connection)),
           $arrayHash($getHostList->invoke($connection)),
        ];


        $orders = array_unique($calls);
        $this->assertCount(
            2,
            $orders,
            'Hostlist should be returned in random order. Expected 2 possible orders for given host list.'
        );
    }

    public function testBrokerUriParseSimple()
    {
        $connection = new Connection('tcp://host1');
        $getHostList = new ReflectionMethod($connection, 'getHostList');
        $getHostList->setAccessible(true);

        $hostlist = $getHostList->invoke($connection);
        $host = array_shift($hostlist);
        $this->assertEquals('tcp', $host['scheme']);
        $this->assertEquals('host1', $host['host']);
        $this->assertEquals(61613, $host['port'], 'Default port must be set!');
    }

    public function testBrokerUriParseSpecificPort()
    {
        $connection = new \Stomp\Network\Connection('tcp://host1:55');
        $getHostList = new ReflectionMethod($connection, 'getHostList');
        $getHostList->setAccessible(true);

        $hostlist = $getHostList->invoke($connection);
        $host = array_shift($hostlist);
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
        $connection->writeFrame(new \Stomp\Transport\Frame());
    }
}
