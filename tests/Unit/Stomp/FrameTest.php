<?php

namespace FuseSource\Tests\Unit\Stomp;


use FuseSource\Stomp\Frame;
use PHPUnit_Framework_TestCase;

class FrameTest extends PHPUnit_Framework_TestCase
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
        $frame = new Frame('SEND', array(), 'hello');

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
}
