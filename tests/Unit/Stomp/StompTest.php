<?php
namespace FuseSource\Tests\Unit;

use FuseSource\Stomp\Frame;
use PHPUnit_Framework_TestCase;
/**
 *
 * Copyright 2005-2006 The Apache Software Foundation
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
/* vim: set expandtab tabstop=3 shiftwidth=3: */

/**
 * Stomp test case.
 *
 * @package Stomp
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class StompTest extends PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \FuseSource\Stomp\Exception\StompException
     */
    public function testConnectWillThrowExceptionIfUnexpectedFrameArrives()
    {
        $connection = $this->getMockBuilder('\FuseSource\Stomp\Connection')
            ->setMethods(['readFrame', 'writeFrame'])
            ->disableOriginalConstructor()
            ->getMock();
        $connection
            ->expects($this->once())
            ->method('readFrame')
            ->will(
                $this->returnValue(
                    new Frame('FAIL')
                )
            );
        $stomp = $this->getMockBuilder('\FuseSource\Stomp\Stomp')
            ->setMethods(['createConnection'])
            ->disableOriginalConstructor()
            ->getMock();
        $stomp->expects($this->once())->method('createConnection')->will($this->returnValue($connection));

        $stomp->__construct(null);
        $stomp->connect();

    }

    /**
     * @expectedException \FuseSource\Stomp\Exception\StompException
     */
    public function testConnectWillThrowExceptionIfNoFrameWasRead()
    {
        $connection = $this->getMockBuilder('\FuseSource\Stomp\Connection')
            ->setMethods(['readFrame', 'writeFrame'])
            ->disableOriginalConstructor()
            ->getMock();
        $connection
            ->expects($this->once())
            ->method('readFrame')
            ->will(
                $this->returnValue(false)
            );
        $stomp = $this->getMockBuilder('\FuseSource\Stomp\Stomp')
            ->setMethods(['createConnection'])
            ->disableOriginalConstructor()
            ->getMock();
        $stomp->expects($this->once())->method('createConnection')->will($this->returnValue($connection));

        $stomp->__construct(null);
        $stomp->connect();

    }

    public function testConnectWillDetermineRabbitMqDialect()
    {
        $connection = $this->getMockBuilder('\FuseSource\Stomp\Connection')
            ->setMethods(['readFrame', 'writeFrame'])
            ->disableOriginalConstructor()
            ->getMock();

        $connectFrame = new Frame('CONNECTED');
        $connectFrame->setHeader('session', '-');
        $connectFrame->setHeader('server', 'rabbitmq');
        $connection
            ->expects($this->once())
            ->method('readFrame')
            ->will(
                $this->returnValue($connectFrame)
            );
        $stomp = $this->getMockBuilder('\FuseSource\Stomp\Stomp')
            ->setMethods(['createConnection'])
            ->disableOriginalConstructor()
            ->getMock();
        $stomp->expects($this->once())->method('createConnection')->will($this->returnValue($connection));

        $stomp->__construct(null);
        $stomp->connect();

        $this->assertInstanceOf('\FuseSource\Stomp\Protocol\RabbitMq', $stomp->getProtocol(), 'Unexpected Protocol.');
    }


    public function testConnectWillDetermineSessionIdAndUsesActiveMqAsDefaultDialect()
    {
        $connection = $this->getMockBuilder('\FuseSource\Stomp\Connection')
            ->setMethods(['readFrame', 'writeFrame'])
            ->disableOriginalConstructor()
            ->getMock();

        $connectFrame = new Frame('CONNECTED');
        $connectFrame->setHeader('session', 'your-session-id');
        $connectFrame->setHeader('server', 'not-supported');
        $connection
            ->expects($this->once())
            ->method('readFrame')
            ->will(
                $this->returnValue($connectFrame)
            );
        $stomp = $this->getMockBuilder('\FuseSource\Stomp\Stomp')
            ->setMethods(['createConnection'])
            ->disableOriginalConstructor()
            ->getMock();
        $stomp->expects($this->once())->method('createConnection')->will($this->returnValue($connection));

        $stomp->__construct(null);
        $stomp->connect();

        $this->assertInstanceOf('\FuseSource\Stomp\Protocol\ActiveMq', $stomp->getProtocol(), 'Unexpected Protocol.');
        $this->assertEquals('your-session-id', $stomp->getSessionId(), 'Wrong session id.');
    }


    /**
     * @expectedException \FuseSource\Stomp\Exception\StompException
     */
    public function testWaitForReceiptWillThrowExceptionOnIdMissmatch()
    {

        $connection = $this->getMockBuilder('\FuseSource\Stomp\Connection')
            ->setMethods(['readFrame', 'writeFrame'])
            ->disableOriginalConstructor()
            ->getMock();

        $receiptFrame = new Frame('RECEIPT');
        $receiptFrame->setHeader('receipt-id', 'not-matching-id');

        $connection
            ->expects($this->once())
            ->method('readFrame')
            ->will(
                $this->returnValue($receiptFrame)
            );
        $stomp = $this->getMockBuilder('\FuseSource\Stomp\Stomp')
            ->setMethods(['createConnection'])
            ->disableOriginalConstructor()
            ->getMock();
        $stomp->expects($this->once())->method('createConnection')->will($this->returnValue($connection));

        $stomp->__construct(null);

        $waitForReceipt = new \ReflectionMethod($stomp, '_waitForReceipt');
        $waitForReceipt->setAccessible(true);

        // expect a receipt for another id
        $waitForReceipt->invoke($stomp, 'your-id');
    }


    public function testWaitForReceiptWillReturnFalseIfConnectionReadTimeoutOccurs()
    {

        $connection = $this->getMockBuilder('\FuseSource\Stomp\Connection')
            ->setMethods(['readFrame', 'writeFrame'])
            ->disableOriginalConstructor()
            ->getMock();

        $connection
            ->expects($this->once())
            ->method('readFrame')
            ->will(
                $this->returnValue(false)
            );
        $stomp = $this->getMockBuilder('\FuseSource\Stomp\Stomp')
            ->setMethods(['createConnection'])
            ->disableOriginalConstructor()
            ->getMock();
        $stomp->expects($this->once())->method('createConnection')->will($this->returnValue($connection));

        $stomp->__construct(null);


        $waitForReceipt = new \ReflectionMethod($stomp, '_waitForReceipt');
        $waitForReceipt->setAccessible(true);

        // expect a receipt but get false
        $this->assertFalse(
            $waitForReceipt->invoke($stomp, 'your-id'),
            'If no frame was returned, wait for receipt should return false.'
        );
    }
}
