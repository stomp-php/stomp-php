<?php

/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Tests\Unit\Stomp\Transport;

use PHPUnit_Framework_TestCase;
use stdClass;
use Stomp\Transport\Map;

/**
 * MapTest
 *
 * @package Stomp\Tests\Unit\Stomp
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class MapTest extends PHPUnit_Framework_TestCase
{
    public function testMapWillCreateJmsMapJsonIfObjectIsPassed()
    {
        $body = new stdClass();
        $body->property = true;
        $map = new Map($body);

        $this->assertEquals('SEND', $map->getCommand());
        $this->assertEquals('jms-map-json', $map['transformation']);
        $this->assertEquals(json_encode($body), $map->getBody());
        $this->assertSame($body, $map->getMap());
    }

    public function testMapWillCreateJmsMapJsonIfArrayIsPassed()
    {
        $body = ['property' => true];
        $map = new Map($body);
        $this->assertEquals('SEND', $map->getCommand());
        $this->assertEquals('jms-map-json', $map['transformation']);
        $this->assertEquals(json_encode($body), $map->getBody());
        $this->assertSame($body, $map->getMap());
    }

    public function testMapWillDecodeJsonIfStringIsPassed()
    {
        $body = json_encode(['property' => true]);
        $map = new Map($body, ['transformation' => 'jms-map-json'], 'MESSAGE');

        $this->assertEquals('MESSAGE', $map->getCommand());
        $this->assertEquals('jms-map-json', $map['transformation']);
        $this->assertEquals($body, $map->getBody());
        $this->assertEquals(['property' => true], $map->getMap());
    }
}
