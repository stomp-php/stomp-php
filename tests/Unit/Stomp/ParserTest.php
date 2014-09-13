<?php
namespace FuseSource\Tests\Unit;

use FuseSource\Stomp\Parser;
use PHPUnit_Framework_TestCase;
/**
 *
 * Copyright 2005-2006 The Apache Software Foundation
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
/* vim: set expandtab tabstop=3 shiftwidth=3: */

/**
 * Connection test case.
 *
 * @package Stomp
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class ParserTest extends PHPUnit_Framework_TestCase
{
    public function testParseFrameTransformsToMapIfJmsMapHeaderIsSet()
    {

        $body = json_encode(array('var' => 'value'));
        $msg = "CMD\nheader1:value1\ntransformation:jms-map-json\n\n" . $body . "\x00";

        $parser = new Parser();
        $parser->addData($msg);
        $parser->parse();
        $result = $parser->getFrame();
        $this->assertInstanceOf('\FuseSource\Stomp\Message\Map', $result);
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

        $this->assertInstanceOf('\FuseSource\Stomp\Frame', $result);
        $this->assertEquals('var', $result->body);
        $this->assertEquals('value1', $result->headers['header1']);
    }

}
