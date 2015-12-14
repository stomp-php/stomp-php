<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Tests\Functional\ApolloMq\Mode;

use PHPUnit_Framework_TestCase;
use Stomp\Broker\Apollo\Mode\SequenceQueueBrowser;
use Stomp\Tests\Functional\ApolloMq\ClientProvider;
use Stomp\Transport\Frame;
use Stomp\Transport\Message;

/**
 * SequenceQueueBrowserTest
 *
 * @package Stomp\Tests\Functional\ApolloMq\Mode
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class SequenceQueueBrowserTest extends PHPUnit_Framework_TestCase
{

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        $expires = round(microtime(true) * 1000) + 10000; // ~ 10 seconds expire time

        self::$queue = sprintf('/queue/browser-test-%d', $expires);
        self::$expires = $expires;

        $producer = ClientProvider::getClient();
        for ($i = 1; $i < 6; $i++) {
            $producer->send(self::$queue, new Message(sprintf('message-%d', $i), compact('expires')));
        }
        $producer->disconnect(true);
    }

    private static $queue;
    private static $expires;


    public function testBrowserFromStart()
    {
        $browser = new SequenceQueueBrowser(
            ClientProvider::getClient(),
            self::$queue,
            SequenceQueueBrowser::START_HEAD
        );
        $browser->subscribe();
        for ($i = 1; $i < 6; $i++) {
            $frame = $browser->read();
            $this->assertInstanceOf(Frame::class, $frame);
            $this->assertEquals(sprintf('message-%d', $i), $frame->body);
            $this->assertEquals($i, $browser->getSeq());
        }

        $this->assertFalse($browser->read());
        $this->assertTrue($browser->hasReachedEnd());
        $browser->unsubscribe();
    }


    public function testBrowserFromOffset()
    {
        $browser = new SequenceQueueBrowser(
            ClientProvider::getClient(),
            self::$queue,
            3
        );
        $browser->subscribe();
        for ($i = 3; $i < 6; $i++) {
            $frame = $browser->read();
            $this->assertInstanceOf(Frame::class, $frame);
            $this->assertEquals(sprintf('message-%d', $i), $frame->body);
            $this->assertEquals($i, $browser->getSeq());
        }

        $this->assertFalse($browser->read());
        $this->assertTrue($browser->hasReachedEnd());
        $browser->unsubscribe();
    }


    public function testBrowserFromNew()
    {
        $browser = new SequenceQueueBrowser(
            ClientProvider::getClient(),
            self::$queue,
            SequenceQueueBrowser::START_NEW
        );

        $browser->subscribe();

        $producer = ClientProvider::getClient();
        $producer->send(self::$queue, new Message('message-6', ['expires' => (self::$expires + 20000)]));
        $producer->disconnect(true);

        $frame = $browser->read();
        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertEquals('message-6', $frame->body);
        $this->assertEquals(6, $browser->getSeq());
        $browser->unsubscribe();
    }
}
