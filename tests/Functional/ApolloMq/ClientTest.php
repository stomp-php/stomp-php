<?php

/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Tests\Functional\ApolloMq;

use PHPUnit_Framework_TestCase;
use Stomp\Broker\Apollo\Apollo;

/**
 * Client test for Apollo Broker
 *
 * @package Stomp\Tests\Functional\ApolloMq
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class ClientTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var \Stomp\Client
     */
    private $client;


    protected function setUp()
    {
        parent::setUp();
        $this->client = ClientProvider::getClient();
    }


    public function testConnectOnApollo()
    {
        $this->assertTrue($this->client->connect(), 'Expected reachable broker.');
        $this->assertInstanceOf(Apollo::class, $this->client->getProtocol(), 'Expected a Apollo broker.');
    }
}
