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
class ASyncTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Client
     */
    private $Stomp;

    /**
     * @var LegacyStomp
     */
    private $legacy;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->Stomp = new Client('tcp://localhost:61010');
        $this->Stomp->setSync(false);
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
     * Tests Stomp->connect(), send(), and subscribe() - out of order. the messages should be received in FIFO order.
     */
    public function testAsyncSub()
    {
        $this->assertTrue($this->Stomp->connect());

        $this->assertTrue($this->Stomp->send('/queue/test', 'test 1'));
        $this->assertTrue($this->Stomp->send('/queue/test', 'test 2'));
        $this->assertTrue($this->legacy->subscribe('/queue/test'));

        $frame = $this->Stomp->readFrame();
        $this->assertEquals($frame->body, 'test 1', 'test 1 was not received!');

        $frame = $this->Stomp->readFrame();
        $this->assertEquals($frame->body, 'test 2', 'test 2 was not received!');
    }
}
