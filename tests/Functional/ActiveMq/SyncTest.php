<?php

/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Tests\Functional\ActiveMq;

use Stomp\Client;
use Stomp\LegacyStomp;

/**
 * Stomp test case.
 *
 * @package Stomp
 * @author Mark R. <mark+gh@mark.org.il>
  */
class SyncTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LegacyStomp
     */
    private $legacy;
    /**
     * @var Client
     */
    private $Stomp;
    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->Stomp = new Client('tcp://localhost:61010');
        $this->Stomp->setSync(true);
        $this->legacy = new LegacyStomp($this->Stomp);
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
        $this->assertTrue($this->legacy->subscribe('/queue/test'));
        $this->assertTrue($this->Stomp->send('/queue/test', 'test 1'));
        $this->assertTrue($this->Stomp->send('/queue/test', 'test 2'));

        $this->Stomp->getConnection()->setReadTimeout(0, 500000);

        $frame = $this->Stomp->readFrame();
        $this->assertEquals('test 1', $frame->body, 'test 1 not received!');
        $this->legacy->ack($frame);

        $frame = $this->Stomp->readFrame();
        $this->assertEquals('test 2', $frame->body, 'test 2 not received!');
        $this->legacy->ack($frame);
    }

    public function testCommitTransaction()
    {
        $this->assertTrue($this->Stomp->connect());
        $this->Stomp->setSync(true);
        $this->assertTrue($this->legacy->begin('my-id'));
        $this->assertTrue($this->Stomp->send('/queue/test', 'test 1', ['transaction' => 'my-id']));
        $this->assertTrue($this->legacy->commit('my-id'));

        $this->assertTrue($this->legacy->subscribe('/queue/test'));

        $frame = $this->Stomp->readFrame();
        $this->assertEquals('test 1', $frame->body, 'test 1 not received!');
        $this->legacy->ack($frame);
    }

    public function testAbortTransaction()
    {
        $this->assertTrue($this->Stomp->connect());
        $this->assertTrue($this->legacy->begin('my-id'));
        $this->assertTrue($this->Stomp->send('/queue/test', 'test t-id', ['transaction' => 'my-id']));
        $this->assertTrue($this->legacy->abort('my-id'));

        $this->assertTrue($this->legacy->subscribe('/queue/test'));

        $this->Stomp->getConnection()->setReadTimeout(0, 500000);

        $frame = $this->Stomp->readFrame();
        $this->assertFalse($frame);
    }
}
