<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Tests\Unit\Stomp\States;

use PHPUnit_Framework_TestCase;
use Stomp\Client;
use Stomp\StatefulStomp;
use Stomp\States\ConsumerState;

/**
 * ConsumerStateTest
 *
 * @package Stomp\Tests\Unit\Stomp\States
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class ConsumerStateTest extends PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \InvalidArgumentException
     */
    public function testUnsubscribeWillThrowExceptionIfGivenIdIsNotActive()
    {
        $client = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->setMethods(['getProtocol', 'sendFrame', 'readFrame'])
            ->getMock();

        /**
         * @var $client Client
         */
        $stateful = new StatefulStomp($client);
        $consumerState = new ConsumerState($client, $stateful);
        $consumerState->unsubscribe('not-existing');
    }
}
