<?php

/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Tests\Functional\ApolloMq;

use Stomp\Connection;
use Stomp\Stomp;
use PHPUnit_Framework_TestCase;

/**
 * Client test for Apollo Broker
 *
 * @package Stomp\Tests\Functional\ApolloMq
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class ClientTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var \Stomp\Stomp
     */
    private $client;


    protected function setUp()
    {
        parent::setUp();
        $connection = new Connection('tcp://localhost:61020');
        $this->client = new Stomp($connection);
    }


    public function testConnectOnApollo()
    {
        $this->assertTrue($this->client->connect('admin', 'password'), 'Expected reachable broker.');
        $this->assertInstanceOf('\Stomp\Protocol\Apollo', $this->client->getProtocol(), 'Expected a Apollo broker.');
    }
}
