<?php

/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Tests\Functional;

use Stomp\Stomp;

/* vim: set expandtab tabstop=3 shiftwidth=3: */

/**
 * Stomp test case.
 *
 * @package Stomp
 * @author Michael Caplan <mcaplan@labnet.net>
 * @version $Revision: 35 $
 */
class StompFailoverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Stomp
     */
    private $Stomp;
    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->Stomp = new Stomp('failover://(tcp://localhost:61614,tcp://localhost:61613)?randomize=false');
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
