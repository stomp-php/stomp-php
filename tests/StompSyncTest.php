<?php

use FuseSource\Stomp\Stomp;

/**
 * Stomp test case.
 *
 * @package Stomp
 * @author Mark R. <mark+gh@mark.org.il>
  */
class StompSyncTest extends PHPUnit_Framework_TestCase
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
        $this->Stomp->sync = true;
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
     * Tests Stomp->connect(), send() and subscribe() in order.
     */
    public function testSyncSub()
    {
        $this->assertTrue($this->Stomp->connect());
        $this->assertTrue($this->Stomp->subscribe('/queue/test'));
        $this->assertTrue($this->Stomp->send('/queue/test', 'test 1'));
        $this->assertTrue($this->Stomp->send('/queue/test', 'test 2'));
        

        $this->Stomp->setReadTimeout(5);
        
        $frame = $this->Stomp->readFrame();
        $this->assertEquals('test 1', $frame->body, 'test 1 not received!');
        $this->Stomp->ack($frame);

        $frame = $this->Stomp->readFrame();
        $this->assertEquals('test 2', $frame->body, 'test 2 not received!');
        $this->Stomp->ack($frame);
    }
}

