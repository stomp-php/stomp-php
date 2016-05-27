<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Tests\Unit\Stomp\Broker\OpenMq;

use Stomp\Broker\OpenMq\OpenMq;
use Stomp\Protocol\Version;
use Stomp\Tests\Unit\Stomp\Protocol\ProtocolTestCase;
use Stomp\Transport\Frame;

/**
 * OpenMqTest
 *
 * @package Stomp\Tests\Unit\Stomp\Broker\OpenMq
 * @author Markus Staab <maggus.staab@googlemail.com>
 */
class OpenMqTest extends ProtocolTestCase
{
    public function testAckSubscription()
    {
        $instance = $this->getProtocol(Version::VERSION_1_0);

        $resultAckBased = $instance->getAckFrame(new Frame(null, ['subscription' => '1234']), 'my-transaction');
        $this->assertEquals('1234', $resultAckBased['subscription']);
        $this->assertIsAckFrame($resultAckBased);
    }

    protected function getProtocolClassFqn()
    {
        return OpenMq::class;
    }
}
