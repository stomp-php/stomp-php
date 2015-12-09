<?php

/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Tests\Unit\Stomp;

use Stomp\Message\Map;
use Stomp\Parser;

/* vim: set expandtab tabstop=3 shiftwidth=3: */

/**
 * Connection test case.
 *
 * @package Stomp
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class ParserTest extends \PHPUnit_Framework_TestCase
{
    public function testParseFrameTransformsToMapIfJmsMapHeaderIsSet()
    {
        $body = json_encode(array('var' => 'value'));
        $msg = "CMD\nheader1:value1\ntransformation:jms-map-json\n\n" . $body . "\x00";

        $parser = new Parser();
        $parser->addData($msg);
        $parser->parse();
        $result = $parser->getFrame();
        $this->assertInstanceOf('\Stomp\Message\Map', $result);
        /** @var Map $result */
        $this->assertEquals('value', $result->map['var']);
    }

    public function testParseFrameTransformsToFrameByDefault()
    {
        $body = 'var';
        $msg = "CMD\nheader1:value1\n\n\n" . $body . "\x00";

        $parser = new Parser();
        $parser->addData($msg);
        $parser->parse();
        $result = $parser->getFrame();

        $this->assertInstanceOf('\Stomp\Frame', $result);
        $this->assertEquals('var', $result->body);
        $this->assertEquals('value1', $result->headers['header1']);
    }

    public function testParseFrameTransformsToFrameZeroByteContent()
    {
        $body = "var\x00var\x002";
        $msg = "CMD\nheader1:value1\ncontent-length:" . strlen($body) . "\n\n" . $body . "\x00";

        $parser = new Parser();
        $parser->addData($msg);
        $parser->parse();
        $result = $parser->getFrame();

        $this->assertInstanceOf('\Stomp\Frame', $result);
        $this->assertEquals($body, $result->body);
        $this->assertEquals('value1', $result->headers['header1']);
    }

    public function testParserOnFrameWithIncorrectHeaderValue()
    {
        $body = 'var';
        $msg = "CMD\nheader1 value1\n\n\n" . $body . "\x00";

        $parser = new Parser();
        $parser->addData($msg);
        $parser->parse();
        $result = $parser->getFrame();

        $this->assertInstanceOf('\Stomp\Frame', $result);
        $this->assertEquals('var', $result->body);
        $this->assertEquals(true, $result->headers['header1 value1']);
    }
}
