<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Tests\Unit\Stomp\Broker\ActiveMq\Mode;

use PHPUnit_Framework_TestCase;
use Stomp\Broker\ActiveMq\Mode\DurableSubscription;
use Stomp\Client;

/**
 * DurableSubscriptionTest
 *
 * @package Stomp\Tests\Unit\Stomp\Broker\ActiveMq\Mode
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class DurableSubscriptionTest extends PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \Stomp\Exception\StompException
     */
    public function testDurableSubscriptionIsOnlyPossibleWithClientId()
    {
        new DurableSubscription(new Client('tcp://127.0.0.1'), 'destination');
    }
}
