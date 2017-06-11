<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Tests\Unit\Stomp\Network\Observer\Heartbeat;


use PHPUnit\Framework\TestCase;
use Stomp\Network\Observer\ConnectionObserver;
use Stomp\Network\Observer\ConnectionObserverCollection;
use Stomp\Transport\Message;

/**
 * ConnectionObserverCollectionTest
 *
 * @package Stomp\Tests\Unit\Stomp\Network\Observer\Heartbeat
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class ConnectionObserverCollectionTest extends TestCase
{
    /**
     * @var ConnectionObserverCollection
     */
    private $instance;

    protected function setUp()
    {
        parent::setUp();
        $this->instance = new ConnectionObserverCollection();
    }

    public function testEventForward()
    {
        $frameA = new Message('message-a');
        $frameB = new Message('message-b');
        $observerA = $this->getMockBuilder(ConnectionObserver::class)
            ->setMethods(['sentFrame', 'emptyLineReceived', 'emptyBuffer', 'receivedFrame'])
            ->getMock();
        $observerA->expects($this->exactly(1))->method('sentFrame')->with($frameA);
        $observerA->expects($this->exactly(1))->method('emptyLineReceived');
        $observerA->expects($this->exactly(1))->method('emptyBuffer');
        $observerA->expects($this->exactly(1))->method('receivedFrame')->with($frameB);

        $observerB = $this->getMockBuilder(ConnectionObserver::class)
            ->setMethods(['sentFrame', 'emptyLineReceived', 'emptyBuffer', 'receivedFrame'])
            ->getMock();
        $observerB->expects($this->exactly(1))->method('sentFrame')->with($frameA);
        $observerB->expects($this->exactly(1))->method('emptyLineReceived');
        $observerB->expects($this->exactly(1))->method('emptyBuffer');
        $observerB->expects($this->exactly(1))->method('receivedFrame')->with($frameB);

        $this->instance->addObserver($observerA);
        $this->instance->addObserver($observerB);

        $this->instance->receivedFrame($frameB);
        $this->instance->sentFrame($frameA);
        $this->instance->emptyBuffer();
        $this->instance->emptyLineReceived();
    }


    public function testRemoveObserver()
    {
        $observerA = $this->getMockBuilder(ConnectionObserver::class)->getMock();
        $observerB = $this->getMockBuilder(ConnectionObserver::class)->getMock();
        $this->instance->addObserver($observerA);
        $this->instance->addObserver($observerB);

        self::assertContains($observerA, $this->instance->getObservers());
        self::assertContains($observerB, $this->instance->getObservers());

        $this->instance->removeObserver($observerA);

        self::assertNotContains($observerA, $this->instance->getObservers());
        self::assertContains($observerB, $this->instance->getObservers());
    }
}