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

/**
 * Stomp test case.
 *
 * @package Stomp
 * @author Mark R. <mark+gh@mark.org.il>
 */
class ASyncTest extends TestCase
{
    /**
     * @var Client
     */
    private $Stomp;

    /**
     * @var SimpleStomp
     */
    private $simpleStomp;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->Stomp = ClientProvider::getClient();
        $this->Stomp->setSync(false);
        $this->simpleStomp = new SimpleStomp($this->Stomp);
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
        $this->Stomp->disconnect(true);
        $this->Stomp = null;
        parent::tearDown();
    }

    /**
     * Tests Stomp->connect(), send(), and subscribe() - out of order. the messages should be received in FIFO order.
     */
    public function testAsyncSub()
    {
        $this->assertTrue($this->Stomp->connect());

        $this->assertTrue($this->Stomp->send('queue/async_sub', 'test 1', ['durable' => true]));
        $this->assertTrue($this->Stomp->send('queue/async_sub', 'test 2'));
        $this->assertTrue($this->simpleStomp->subscribe('queue/async_sub', 'mysubid'));

        $frame = $this->Stomp->readFrame();
        $this->assertEquals($frame->body, 'test 1', 'test 1 was not received!');

        $frame = $this->Stomp->readFrame();
        $this->assertEquals($frame->body, 'test 2', 'test 2 was not received!');
    }
}
