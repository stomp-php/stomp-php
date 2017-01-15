<?php

/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Tests\Unit\Stomp\Transport;

use PHPUnit_Framework_TestCase;
use Stomp\Transport\Frame;
use Stomp\Transport\FrameFactory;
use Stomp\Transport\Map;

/**
 * FrameTest
 *
 * @package Stomp\Tests\Unit\Stomp
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class FrameFactoryTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var FrameFactory
     */
    private $instance;

    public function setUp()
    {
        parent::setUp();
        $this->instance = new FrameFactory();
    }

    public function testFrameFactoryWillCreateDefaultFrames()
    {
        $frame = $this->instance->createFrame('COMMAND', ['header1' => true, "header2" => 2], 'BODY', true);
        $this->assertEquals('COMMAND', $frame->getCommand());
        $this->assertEquals(['header1' => true, "header2" => 2], $frame->getHeaders());
        $this->assertEquals('BODY', $frame->getBody());
        $this->assertTrue($frame->isLegacyMode());
        $this->assertInstanceOf(Frame::class, $frame);
    }

    public function testFrameFactoryWillCreateMapInstances()
    {
        $frame = $this->instance->createFrame(
            'MESSAGE',
            ['transformation' => 'jms-map-json'],
            json_encode(['key-1' => 'val-2', 'key-2' => 'val-2']),
            false
        );

        $this->assertInstanceOf(Map::class, $frame);
        $this->assertEquals('MESSAGE', $frame->getCommand());
        $this->assertEquals(['transformation' => 'jms-map-json'], $frame->getHeaders());
        $this->assertEquals(['key-1' => 'val-2', 'key-2' => 'val-2'], $frame->getMap());
        $this->assertFalse($frame->isLegacyMode());
    }

    public function testFrameFactoryWillUseDefaultResolverAsFallback()
    {
        $calls = 0;
        $this->instance->registerResolver(
            function () use (&$calls) {
                $calls++;
            }
        );
        $frame = $this->instance->createFrame('MESSAGE', [], 'BODY', false);
        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertEquals(1, $calls, 'Custom resolver must have been called.');
    }

    public function testFrameFactoryWillApplyResolversInReverseOrder()
    {
        $resolver = [];
        $this->instance->registerResolver(
            function () use (&$resolver) {
                $resolver[] = 1;
            }
        );
        $this->instance->registerResolver(
            function () use (&$resolver) {
                $resolver[] = 2;
            }
        );

        $frame = $this->instance->createFrame('MESSAGE', [], 'BODY', false);
        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertEquals([2, 1], $resolver, 'Custom resolver must have been called in reverse order.');
    }


    public function testCustomResolverResultWillBeUsedIfPossible()
    {
        $this->instance->registerResolver(
            function ($command, $headers, $body) {
                return new Frame($command . '-modified', $headers + ['modified' => true], $body . '-modified');
            }
        );

        $frame = $this->instance->createFrame('MESSAGE', [], 'BODY', false);
        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertEquals('MESSAGE-modified', $frame->getCommand());
        $this->assertEquals('BODY-modified', $frame->getBody());
        $this->assertTrue($frame['modified']);
    }
}
