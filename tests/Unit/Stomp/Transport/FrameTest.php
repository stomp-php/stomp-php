<?php

/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Tests\Unit\Stomp\Transport;

use Stomp\Transport\Frame;

/**
 * FrameTest
 *
 * @package Stomp\Tests\Unit\Stomp
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class FrameTest extends \PHPUnit_Framework_TestCase
{
    /** @test */
    public function shouldConvertFrameToString()
    {
        $frame = new Frame(
            'SEND',
            [
                'destination' => '/queue/a',
                'receipt' => 'message-12345'
            ],
            'hello queue a^@'
        );

        $result = $frame->__toString();
        $this->assertEquals(
            'SEND
destination:/queue/a
receipt:message-12345

hello queue a^@' . "\x00",
            $result
        );
    }

    /** @test */
    public function shouldConvertEmptyFrameToString()
    {
        $frame = new Frame();

        $result = $frame->__toString();
        $this->assertEquals(
            "\n\n\x00",
            $result
        );
    }

    /** @test */
    public function shouldConvertFrameWithoutHeadersToString()
    {
        $frame = new Frame('SEND', [], 'hello');

        $result = $frame->__toString();
        $this->assertEquals(
            "SEND\n\nhello\x00",
            $result
        );
    }

    /** @test */
    public function shouldConvertFrameWithoutBodyToString()
    {
        $frame = new Frame('SEND', ['destination' => '/queue/a']);

        $result = $frame->__toString();
        $this->assertEquals(
            "SEND\ndestination:/queue/a\n\n\x00",
            $result
        );
    }

    public function testEncodeWillBeAppliedToHeaders()
    {
        $frame = new Frame('SEND', ['my:var' => "\\multi\nline\r!"]);

        $result = $frame->__toString();
        $expected = "SEND\nmy\\cvar:\\\\multi\\nline\\r!\n\n\x00";
        $this->assertEquals($expected, $result);
    }


    public function testFrameAddsContentLengthHeaderIfAsked()
    {
        $frame = new Frame('SEND', ['my-header' => 'my-value'], 'MyContent');
        $frame->expectLengthHeader(true);
        $result = $frame->__toString();
        $expected = "SEND\nmy-header:my-value\ncontent-length:9\n\nMyContent\x00";
        $this->assertEquals($expected, $result);
    }


    public function testFrameAddsContentLengthHeaderIfBodyContainsNullByte()
    {
        $frame = new Frame('SEND', [], 'MyContent' . "\x00");
        $result = $frame->__toString();
        $expected = "SEND\ncontent-length:10\n\nMyContent\x00\x00";
        $this->assertEquals($expected, $result);
    }

    public function testFrameInLegacyModeWontAddLengthHeader()
    {
        $frame = new Frame('SEND', [], 'MyContent' . "\x00");
        $frame->legacyMode(true);
        $result = $frame->__toString();
        $expected = "SEND\n\nMyContent\x00\x00";
        $this->assertEquals($expected, $result);
    }

    public function testFrameInLegacyModeWontEncodeHeaders()
    {
        $frame = new Frame('SEND', ['my:var' => "\\multi\nline\r!"]);
        $frame->legacyMode(true);

        $result = $frame->__toString();
        $expected = "SEND\nmy:var:\\multi\\nline\r!\n\n\x00";
        $this->assertEquals($expected, $result);
    }

    public function testFrameUnsetRemovesHeader()
    {
        $frame = new Frame();
        $frame['header'] = 'value';
        $this->assertTrue(isset($frame['header']));
        unset($frame['header']);
        $this->assertEquals(new Frame(), $frame);
    }
}
