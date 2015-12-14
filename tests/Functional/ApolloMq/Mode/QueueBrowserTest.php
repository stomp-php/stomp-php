<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Tests\Functional\ApolloMq\Mode;

use PHPUnit_Framework_TestCase;
use Stomp\Broker\Apollo\Mode\QueueBrowser;
use Stomp\Tests\Functional\ApolloMq\ClientProvider;
use Stomp\Transport\Frame;
use Stomp\Transport\Message;

/**
 * QueueBrowserTest
 *
 * @package Stomp\Tests\Functional\ApolloMq\Mode
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class QueueBrowserTest extends PHPUnit_Framework_TestCase
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


    public function testQueueBrowserWithStopOnEnd()
    {
        $browser = new QueueBrowser(ClientProvider::getClient(), self::$queue);
        $this->assertFalse($browser->isActive());
        $browser->subscribe();
        $this->assertTrue($browser->isActive());
        for ($i = 1; $i < 6; $i++) {
            $frame = $browser->read();
            $this->assertInstanceOf(Frame::class, $frame);
            $this->assertEquals(sprintf('message-%d', $i), $frame->body);
        }

        $this->assertFalse($browser->read());
        $this->assertFalse($browser->read());
        $this->assertTrue($browser->hasReachedEnd());
        $browser->unsubscribe();
        $this->assertEquals(self::$queue, $browser->getSubscription()->getDestination());
    }

    public function testQueueBrowserWithContinueListeningForNew()
    {
        $client = ClientProvider::getClient();
        $client->getConnection()->setReadTimeout(0, 500000);
        $browser = new QueueBrowser($client, self::$queue, false);
        $browser->subscribe();
        for ($i = 1; $i < 6; $i++) {
            $frame = $browser->read();
            $this->assertInstanceOf(Frame::class, $frame);
            $this->assertEquals(sprintf('message-%d', $i), $frame->body);
        }

        $this->assertFalse($browser->read());
        $this->assertFalse($browser->hasReachedEnd());

        $producer = ClientProvider::getClient();
        $producer->send(self::$queue, new Message('message-6', ['expires' => (self::$expires + 20000)]));
        $producer->disconnect(true);

        $frame = $browser->read();
        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertEquals('message-6', $frame->body);

        $browser->unsubscribe();
    }
}
