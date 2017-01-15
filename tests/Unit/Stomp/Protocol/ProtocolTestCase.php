<?php

/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Tests\Unit\Stomp\Protocol;

use PHPUnit_Framework_TestCase;
use Stomp\Protocol\Protocol;
use Stomp\Protocol\Version;
use Stomp\Transport\Frame;
use Stomp\Exception\StompException;

/**
 * Protocol test cases.
 *
 * @package Stomp
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
abstract class ProtocolTestCase extends PHPUnit_Framework_TestCase
{

    /**
     * @param string $version
     * @return Protocol
     */
    final protected function getProtocol($version = Version::VERSION_1_0)
    {
        $class = $this->getProtocolClassFqn();
        return new $class('test-client-id', $version, 'amq-test');
    }

    /**
     * Must return the fqn for tested protocol.
     *
     * @return string
     */
    abstract protected function getProtocolClassFqn();

    public function testSubscribeFrame()
    {
        $protocol = $this->getProtocol();

        $actual = $protocol->getSubscribeFrame('my-destination', 'my-sub-id', 'client', 'my-selector');
        $this->assertIsSubscribeFrame($actual);
        $this->assertEquals('my-destination', $actual['destination']);
        $this->assertEquals('client', $actual['ack']);
        $this->assertEquals('my-sub-id', $actual['id']);
        $this->assertEquals('my-selector', $actual['selector']);
    }

    public function testInvalidSubscribeFrameAck()
    {
        $protocol = $this->getProtocol();

        try {
            $protocol->getSubscribeFrame('my-destination', 'my-sub-id', 'my-ack', 'my-selector');
            $this->fail();
        } catch (StompException $e) {
            $this->assertContains('"my-ack" is not a valid ack value', $e->getMessage());
        }
    }

    public function testAckVersionZero()
    {
        $instance = $this->getProtocol(Version::VERSION_1_0);

        $actual = $instance->getAckFrame(new Frame(null, ['message-id' => 'id-value']), 'my-transaction');
        $this->assertIsAckFrame($actual);
        $this->assertEquals('id-value', $actual['message-id']);
        $this->assertEquals('my-transaction', $actual['transaction']);
    }

    public function testAckVersionTwo()
    {
        $instance = $this->getProtocol(Version::VERSION_1_2);

        $actual = $instance->getAckFrame(new Frame(null, ['message-id' => 'id-value']), 'my-transaction');
        $this->assertIsAckFrame($actual);
        $this->assertEquals('id-value', $actual['id']);
        $this->assertEquals('my-transaction', $actual['transaction']);
    }

    /**
     * @expectedException \Stomp\Exception\StompException
     */
    public function testNackVersionZero()
    {
        $instance = $this->getProtocol(Version::VERSION_1_0);
        $instance->getNackFrame(new Frame(null, ['message-id' => 'id-value']));
    }

    public function testNackVersionOne()
    {
        $instance = $this->getProtocol(Version::VERSION_1_1);

        $actual = $instance->getNackFrame(new Frame(null, ['message-id' => 'id-value']), 'my-transaction');
        $this->assertIsNackFrame($actual);
        $this->assertEquals('id-value', $actual['message-id']);
        $this->assertEquals('my-transaction', $actual['transaction']);
    }

    public function testNackVersionTwo()
    {
        $instance = $this->getProtocol(Version::VERSION_1_2);

        $actual = $instance->getNackFrame(new Frame(null, ['message-id' => 'id-value']), 'my-transaction');
        $this->assertIsNackFrame($actual);
        $this->assertEquals('id-value', $actual['id']);
        $this->assertEquals('my-transaction', $actual['transaction']);
    }

    public function testUnsubscribe()
    {
        $instance = $this->getProtocol();

        $actual = $instance->getUnsubscribeFrame('my-destination', 'my-sub-id');
        $this->assertIsUnsubscribeFrame($actual);
        $this->assertEquals('my-destination', $actual['destination']);
        $this->assertEquals('my-sub-id', $actual['id']);
    }

    public function testBegin()
    {
        $instance = $this->getProtocol();

        $actual = $instance->getBeginFrame('my-transaction');
        $this->assertIsBeginFrame($actual);
        $this->assertEquals('my-transaction', $actual['transaction']);
    }

    public function testCommit()
    {
        $instance = $this->getProtocol();

        $actual = $instance->getCommitFrame('my-transaction');
        $this->assertIsCommitFrame($actual);
        $this->assertEquals('my-transaction', $actual['transaction']);
    }

    public function testAbort()
    {
        $instance = $this->getProtocol();

        $actual = $instance->getAbortFrame('my-transaction');
        $this->assertIsAbortFrame($actual);
        $this->assertEquals('my-transaction', $actual['transaction']);
    }


    public function testDisconnect()
    {
        $instance = $this->getProtocol();

        $actual = $instance->getDisconnectFrame();
        $this->assertIsDisconnectFrame($actual);
    }

    protected function assertIsNackFrame(Frame $frame)
    {
        $this->assertEquals('NACK', $frame->getCommand(), 'Frame command is no "nack" command.');
    }

    protected function assertIsAckFrame(Frame $frame)
    {
        $this->assertEquals('ACK', $frame->getCommand(), 'Frame command is no "ack" command.');
    }

    protected function assertIsSubscribeFrame(Frame $frame)
    {
        $this->assertEquals('SUBSCRIBE', $frame->getCommand(), 'Frame command is no "subscribe" command.');
    }

    protected function assertIsUnsubscribeFrame(Frame $frame)
    {
        $this->assertEquals('UNSUBSCRIBE', $frame->getCommand(), 'Frame command is no "unsubscribe" command.');
    }

    protected function assertIsDisconnectFrame(Frame $frame)
    {
        $this->assertEquals('DISCONNECT', $frame->getCommand(), 'Frame command is no "disconnect" command.');
    }

    protected function assertIsBeginFrame(Frame $frame)
    {
        $this->assertEquals('BEGIN', $frame->getCommand(), 'Frame command is no "begin" command.');
    }

    protected function assertIsConnectFrame(Frame $frame)
    {
        $this->assertEquals('CONNECT', $frame->getCommand(), 'Frame command is no "connect" command.');
    }

    protected function assertIsCommitFrame(Frame $frame)
    {
        $this->assertEquals('COMMIT', $frame->getCommand(), 'Frame command is no "commit" command.');
    }

    protected function assertIsAbortFrame(Frame $frame)
    {
        $this->assertEquals('ABORT', $frame->getCommand(), 'Frame command is no "abort" command.');
    }
}
