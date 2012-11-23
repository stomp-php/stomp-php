<?php

use FuseSource\Stomp\Stomp;

/**
 * Stomp test case.
 *
 * @package Stomp
 * @author Mark R. <mark+gh@mark.org.il>
  */
class StompASyncTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Stomp
     */
    private $Stomp;
    /**
     * Prepares the environment before running a test.
     */
    protected function setUp ()
    {
        parent::setUp();

        $this->Stomp = new Stomp('tcp://localhost:61613');
        $this->Stomp->sync = false;
    }
    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown ()
    {
        $this->Stomp->disconnect();
        $this->Stomp = null;
        parent::tearDown();
    }

    /**
     * Tests Stomp->connect(), send(), and subscribe() - out of order. the messages should be received in FIFO order.
     */
    public function testAsyncSub()
    {
        $this->assertTrue($this->Stomp->connect());
        
        $this->assertTrue($this->Stomp->send('/queue/test', 'test 1'));
        $this->assertTrue($this->Stomp->send('/queue/test', 'test 2'));
        $this->assertTrue($this->Stomp->subscribe('/queue/test'));

        $frame = $this->Stomp->readFrame();
        $this->assertEquals($frame->body, 'test 1', 'test 1 was not received!');
        $this->Stomp->ack($frame);

        $frame = $this->Stomp->readFrame();
        $this->assertEquals($frame->body, 'test 2', 'test 2 was not received!');
        $this->Stomp->ack($frame);
    }
}

