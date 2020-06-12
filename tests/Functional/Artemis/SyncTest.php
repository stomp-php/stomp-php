<?php

/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Tests\Functional\Artemis;

use PHPUnit\Framework\TestCase;
use Stomp\Client;
use Stomp\SimpleStomp;
use Stomp\Transport\Frame;

/**
 * Stomp test case.
 *
 * @package Stomp
 * @author Mark R. <mark+gh@mark.org.il>
  */
class SyncTest extends TestCase
{
    /**
     * @var SimpleStomp
     */
    private $simpleStomp;
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

        $this->Stomp = ClientProvider::getClient();
        $this->Stomp->setSync(true);
        $this->simpleStomp = new SimpleStomp($this->Stomp);
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
        $this->assertTrue($this->simpleStomp->subscribe('queue/sync_sub', 'mysubid', 'client-individual'));
        $this->assertTrue($this->Stomp->send('queue/sync_sub', 'test 1'));
        $this->assertTrue($this->Stomp->send('queue/sync_sub', 'test 2'));

        $this->Stomp->getConnection()->setReadTimeout(0, 500000);

        $frame = $this->Stomp->readFrame();
        $this->assertEquals('test 1', $frame->body, 'test 1 not received!');
        $this->simpleStomp->ack($frame);

        $frame = $this->Stomp->readFrame();
        $this->assertEquals('test 2', $frame->body, 'test 2 not received!');
        $this->simpleStomp->ack($frame);
    }

    public function testCommitTransaction()
    {
        $this->assertTrue($this->Stomp->connect());
        $this->Stomp->setSync(true);
        $this->assertTrue($this->simpleStomp->begin('my-id'));
        $this->assertTrue($this->Stomp->send('queue/transaction', 'test 1', ['transaction' => 'my-id']));
        $this->assertTrue($this->simpleStomp->commit('my-id'));

        $this->assertTrue($this->simpleStomp->subscribe('queue/transaction', 'mysubid'));

        $frame = $this->Stomp->readFrame();
        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertEquals('test 1', $frame->body, 'test 1 not received!');
    }

    public function testAbortTransaction()
    {
        $this->assertTrue($this->Stomp->connect());
        $this->assertTrue($this->simpleStomp->begin('my-id'));
        $this->assertTrue($this->Stomp->send('queue/transaction_abort', 'test t-id', ['transaction' => 'my-id']));
        $this->assertTrue($this->simpleStomp->abort('my-id'));

        $this->assertTrue($this->simpleStomp->subscribe('queue/transaction_abort', 'mysubid'));

        $this->Stomp->getConnection()->setReadTimeout(0, 500000);

        $frame = $this->Stomp->readFrame();
        $this->assertFalse($frame);
    }
}
