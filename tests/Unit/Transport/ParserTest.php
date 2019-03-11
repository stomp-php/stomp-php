<?php

/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Tests\Unit\Transport;

use PHPUnit\Framework\TestCase;
use Stomp\Network\Observer\ConnectionObserver;
use Stomp\Transport\Frame;
use Stomp\Transport\Map;
use Stomp\Transport\Parser;

/**
 * Connection test case.
 *
 * @package Stomp
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 * @coversDefaultClass \Stomp\Transport\Parser
 */
class ParserTest extends TestCase
{

    /**
     * @var Parser
     */
    private $parser;

    protected function setUp()
    {
        parent::setUp();
        $this->parser = new Parser();
    }


    public function testEndOfLineWithCarriageReturn()
    {
        $frame = "COMMAND\r\nheader1:value1\r\nheader2:value2\r\n\r\nBody\x00";
        $this->parser->addData($frame);
        $expected = new Frame('COMMAND', ['header1' => 'value1', 'header2' => 'value2'], 'Body');
        $actual = $this->parser->nextFrame();

        $this->assertEquals($expected, $actual);
    }


    public function testEndOfLineWithoutCarriageReturn()
    {
        $frame = "COMMAND\nheader1:value1\nheader2:value2\n\nBody\x00";
        $this->parser->addData($frame);
        $expected = new Frame('COMMAND', ['header1' => 'value1', 'header2' => 'value2'], 'Body');
        $actual = $this->parser->nextFrame();

        $this->assertEquals($expected, $actual);
    }


    public function testLengthHeaderSetContentContainsNullByteAtEnd()
    {
        $frame = "COMMAND\ncontent-length:5\n\nBody" . "\x00" . "\x00";
        $this->parser->addData($frame);
        $expected = new Frame('COMMAND', ['content-length' => 5], "Body" . "\x00");
        $actual = $this->parser->nextFrame();

        $this->assertEquals($expected, $actual);
    }

    public function testHeaderDecode()
    {
        $frame = "COMMAND\nX-Proof:Hello\\c\\r\\n  \\\\World!\n\nBody\x00";
        $this->parser->addData($frame);
        $expected = new Frame('COMMAND', ['X-Proof' => 'Hello:' . "\r\n  " . '\\World!'], "Body");
        $actual = $this->parser->nextFrame();

        $this->assertEquals($expected, $actual);
    }

    public function testParseFrameTransformsToMapIfJmsMapHeaderIsSet()
    {
        $body = json_encode(['var' => 'value']);
        $msg = "CMD\nheader1:value1\ntransformation:jms-map-json\n\n" . $body . "\x00";


        $this->parser->addData($msg);
        $result = $this->parser->nextFrame();
        $this->assertInstanceOf(Map::class, $result);
        /** @var Map $result */
        $this->assertEquals('value', $result->map['var']);
    }

    public function testParseFrameTransformsToFrameByDefault()
    {
        $body = 'var';
        $msg = "CMD\nheader1:value1\n\n\n" . $body . "\x00";

        $this->parser->addData($msg);
        $result = $this->parser->nextFrame();

        $this->assertInstanceOf(Frame::class, $result);
        $this->assertEquals("\nvar", $result->body);
        $this->assertEquals('value1', $result['header1']);
    }

    public function testParserWontDecodeHeadersInLegacyMode()
    {
        $frame = "COMMAND\nX-Proof:Hello\\c\\r\\n  \\\\World!\n\nBody\x00";
        $this->parser->legacyMode(true);
        $this->parser->addData($frame);
        $expected = new Frame('COMMAND', ['X-Proof' => "Hello\\c\\r\n  \\\\World!"], "Body");
        $expected->legacyMode(true);
        $actual = $this->parser->nextFrame();

        $this->assertEquals($expected, $actual);
    }
    public function testParserIsChunkSafe()
    {
        $frame = "COMMAND\nheader1:values\\c[1,2]\nheader2:value2\n\nBody\x00";
        $frame .= "\r\n\r\n\r\n";
        $frame .= "COMMAND2\nheader3:value2\n\nBody \x00";
        $frame .= "\r\n\r\n";

        $this->assertFalse($this->parser->parse());

        $detectedFrames = [];
        for ($i = 0; $i < strlen($frame); $i++) {
            $this->parser->addData(substr($frame, $i, 1));
            if ($newFrame = $this->parser->nextFrame()) {
                $detectedFrames[] = $newFrame;
            }
        }


        $expectedFrameA = new Frame('COMMAND', ['header1' => 'values:[1,2]', 'header2' => 'value2'], 'Body');
        $expectedFrameB = new Frame('COMMAND2', ['header3' => 'value2'], 'Body ');


        $this->assertEquals($expectedFrameA, $detectedFrames[0]);
        $this->assertEquals($expectedFrameB, $detectedFrames[1]);
    }

    public function testParserDetectsHeartBeats()
    {
        $frame = "\x00\x00COMMAND\nheader1:values\\c[1,2]\nheader2:value2\n\nBody\x00\x00";
        $frame .= "\r\n\r\n\r\n";
        $frame .= "\x00\x00COMMAND2\nheader3:value2\n\nBody \x00";
        $frame .= "\r\n\r\n\x00\x00";

        $this->assertFalse($this->parser->parse());

        $detectedFrames = [];
        for ($i = 0; $i < strlen($frame); $i++) {
            $this->parser->addData(substr($frame, $i, 1));
            if ($newFrame = $this->parser->nextFrame()) {
                $detectedFrames[] = $newFrame;
            }
        }


        $expectedFrameA = new Frame('COMMAND', ['header1' => 'values:[1,2]', 'header2' => 'value2'], 'Body');
        $expectedFrameB = new Frame('COMMAND2', ['header3' => 'value2'], 'Body ');


        $this->assertEquals($expectedFrameA, $detectedFrames[0]);
        $this->assertEquals($expectedFrameB, $detectedFrames[1]);
    }

    public function testParseFrameTransformsToFrameZeroByteContent()
    {
        $body = "var\x00var\x002";
        $msg = "CMD\nheader1:value1\ncontent-length:" . strlen($body) . "\n\n" . $body . "\x00";

        $parser = new Parser();
        $parser->addData($msg);
        $result = $parser->nextFrame();

        $this->assertInstanceOf(Frame::class, $result);
        $this->assertEquals($body, $result->body);
        $this->assertEquals('value1', $result['header1']);
    }


    public function testParserOnFrameWithIncorrectHeaderValue()
    {
        $body = 'var';
        $msg = "CMD\nheader1 value1\n\n" . $body . "\x00";

        $parser = new Parser();
        $parser->addData($msg);
        $result = $parser->nextFrame();

        $this->assertInstanceOf(Frame::class, $result);
        $this->assertEquals('var', $result->body);
        $this->assertEquals(true, $result['header1 value1']);
    }

    public function testFlushBufferReturnsCurrentBufferDataAndClearsIt()
    {
        $msg = "CMD\nheader1:value1\n\nvar\x00";
        $this->parser->addData($msg);

        $this->assertEquals($msg, $this->parser->flushBuffer());
        $this->assertEquals('', $this->parser->flushBuffer());
    }

    public function testParserWillWorkAfterFlushBuffer()
    {
        $msg = "CMD\nheader1:value1\n\nvar\x00";
        $this->parser->addData($msg);
        $this->assertEquals($msg, $this->parser->flushBuffer());
        $this->parser->addData($msg);
        $frame = $this->parser->nextFrame();
        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertEquals('var', $frame->body);
    }

    public function testParserWillWaitForFullContentLength()
    {
        // Make our body and split it up.
        $body = "Test\x00body with\x00NULL octets.";
        $body_parts = str_split($body, 5);

        // Send our header.
        $content_length = (string) strlen($body);
        $this->parser->addData("MESSAGE\ncontent-length:{$content_length}\n\n");

        // This should not parse yet.
        $this->assertFalse($this->parser->parse());

        // Remove our last part so we can loop over the rest.
        $last_body_part = array_pop($body_parts);

        // Send our parts, checking that we don't parse.
        foreach ($body_parts as $part) {
            $this->parser->addData($part);
            $this->assertFalse($this->parser->parse());
        }

        // Adding our last part should allow it to parse.
        $this->parser->addData($last_body_part);

        // Check our frame matches our expectation.
        $expected = new Frame('MESSAGE', ['content-length' => $content_length], $body);
        $actual = $this->parser->nextFrame();
        $this->assertEquals($expected, $actual);
    }


    /**
     * @see https://github.com/stomp-php/stomp-php/issues/93
     */
    public function testParserWhenHeaderStopSequenceWithCarriageReturnIsPartOfBody()
    {
        $body = "{ \n\"value1\" : \"hello world\"\n,\n\n  \"value2\" : \"2002-02-01\"\r\n\r\n}";
        $header = "MESSAGE\n\n";
        $this->parser->addData($header . $body . "\x00");
        $message = $this->parser->nextFrame();
        $this->assertEquals($body, $message->getBody());
    }

    /**
     * @see https://github.com/stomp-php/stomp-php/issues/93
     */
    public function testParserWhenHeaderStopSequenceWithoutCarriageReturnIsPartOfBody()
    {
        $body = "{ \n\"value1\" : \"hello world\"\n,\n\n  \"value2\" : \"2002-02-01\"\n\n}";
        $header = "MESSAGE\r\n\r\n";
        $this->parser->addData($header . $body . "\x00");
        $message = $this->parser->nextFrame();
        $this->assertEquals($body, $message->getBody());
    }


    public function testParserTriggersObserversHeartBeatAfterFrame()
    {
        $observer = $this->getMockBuilder(ConnectionObserver::class)
            ->setMethods(['emptyLineReceived', 'emptyBuffer', 'receivedFrame', 'sentFrame', 'emptyRead'])
            ->getMock();
        $observer->expects($this->once())->method('emptyLineReceived');
        $observer->expects($this->once())->method('receivedFrame');
        $observer->expects($this->never())->method('sentFrame');
        $observer->expects($this->never())->method('emptyBuffer');
        $observer->expects($this->never())->method('emptyRead');
        $this->parser->setObserver($observer);

        $data = "RECEIPT\nreceipt-id:813b64a2909519e0a77e1025be67a648\n\n\x00\n\n";
        $this->parser->addData($data);
        self::assertInstanceOf(Frame::class, $this->parser->nextFrame());
        self::assertNull($this->parser->nextFrame());
    }

    public function testParserTriggersObserversOnSingleHeartBeat()
    {
        $observer = $this->getMockBuilder(ConnectionObserver::class)
            ->setMethods(['emptyLineReceived', 'emptyBuffer', 'receivedFrame', 'sentFrame', 'emptyRead'])
            ->getMock();
        $observer->expects($this->once())->method('emptyLineReceived');
        $observer->expects($this->never())->method('receivedFrame');
        $observer->expects($this->never())->method('sentFrame');
        $observer->expects($this->never())->method('emptyBuffer');
        $observer->expects($this->never())->method('emptyRead');
        $this->parser->setObserver($observer);

        $this->parser->addData("\n");
        self::assertNull($this->parser->nextFrame());
    }

    public function testParserTriggersObserversOnMultipleHeartBeatOnlyOnce()
    {
        $observer = $this->getMockBuilder(ConnectionObserver::class)
            ->setMethods(['emptyLineReceived', 'emptyBuffer', 'receivedFrame', 'sentFrame', 'emptyRead'])
            ->getMock();
        $observer->expects($this->once())->method('emptyLineReceived');
        $observer->expects($this->never())->method('receivedFrame');
        $observer->expects($this->never())->method('sentFrame');
        $observer->expects($this->never())->method('emptyBuffer');
        $observer->expects($this->never())->method('emptyRead');
        $this->parser->setObserver($observer);

        $this->parser->addData("\n\n");
        self::assertNull($this->parser->nextFrame());
        self::assertNull($this->parser->nextFrame());
    }

    /**
     * @see https://github.com/stomp-php/stomp-php/pull/110
     */
    public function testParserCanHandleMessagesWithReturnCharacterInHeaderValues()
    {
        $body = 'var';
        $msg = "CMD\nheader1:valu\re1\n\n" . $body . "\x00";

        $this->parser->addData($msg);
        $result = $this->parser->nextFrame();

        $this->assertInstanceOf(Frame::class, $result);
        $this->assertEquals("var", $result->body);
        $this->assertEquals("valu\re1", $result['header1']);
    }
}
