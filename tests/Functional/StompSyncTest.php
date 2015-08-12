<?php

/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Tests\Functional;

use Stomp\Stomp;

/**
 * Stomp test case.
 *
 * @package Stomp
 * @author Mark R. <mark+gh@mark.org.il>
  */
class StompSyncTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Stomp
     */
    private $Stomp;
    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->Stomp = new Stomp('tcp://localhost:61613');
        $this->Stomp->sync = true;
    }
    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
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

    public function testCommitTransaction()
    {
        $this->assertTrue($this->Stomp->connect());
        $this->assertTrue($this->Stomp->begin('my-id'));
        $this->assertTrue($this->Stomp->send('/queue/test', 'test 1', array('transaction' => 'my-id')));
        $this->assertTrue($this->Stomp->commit('my-id'));

        $this->assertTrue($this->Stomp->subscribe('/queue/test'));

        $frame = $this->Stomp->readFrame();
        $this->assertEquals('test 1', $frame->body, 'test 1 not received!');
        $this->Stomp->ack($frame);
    }

    public function testAbortTransaction()
    {
        $this->assertTrue($this->Stomp->connect());
        $this->assertTrue($this->Stomp->begin('my-id'));
        $this->assertTrue($this->Stomp->send('/queue/test', 'test t-id', array('transaction' => 'my-id')));
        $this->assertTrue($this->Stomp->abort('my-id'));

        $this->assertTrue($this->Stomp->subscribe('/queue/test'));

        $this->Stomp->getConnection()->setReadTimeout(array(1, 0));

        $frame = $this->Stomp->readFrame();
        $this->assertFalse($frame);
    }
}
