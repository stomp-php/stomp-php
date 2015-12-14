<?php

/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Tests\Functional\Generic;

use Stomp\Client;

/**
 * Stomp test case.
 *
 * @package Stomp
 * @author Michael Caplan <mcaplan@labnet.net>
 */
class StompFailoverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Client
     */
    private $Stomp;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->Stomp = new Client(
            'failover://(tcp://localhost:61614,tcp://localhost:61613,tcp://localhost:61020)?randomize=false'
        );
        $this->Stomp->setLogin('admin', 'password');
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
        $this->Stomp->disconnect();
        $this->Stomp = null;
        parent::tearDown();
    }

    /**
     * Tests Stomp->connect()
     */
    public function testFailoverConnect()
    {
        $this->assertTrue($this->Stomp->connect());
    }
}
