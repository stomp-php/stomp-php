<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Tests\Functional\ActiveMq;

use Stomp\Tests\Functional\Stomp\StatefulTestBase;
use Stomp\Transport\Frame;
use Stomp\Transport\Message;

/**
 * StatefulTest on ActiveMq
 *
 * @package Stomp\Tests\Functional\ActiveMq
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class StatefulTest extends StatefulTestBase
{
    protected function getClient()
    {
        return ClientProvider::getClient();
    }

    public function testAckAndNack()
    {
        $queue = '/queue/tests-ack-nack';
        $receiver = $this->getStatefulStomp();
        $producer = $this->getStatefulStomp();

        $receiver->subscribe($queue, null, 'client-individual');

        $producer->send($queue, new Message('message-a', ['persistent' => 'true']));
        $producer->send($queue, new Message('message-b', ['persistent' => 'true']));
        $producer->getClient()->disconnect(true);

        $frameA = $receiver->read();
        $this->assertInstanceOf(Frame::class, $frameA);
        $this->assertEquals('message-a', $frameA->body);
        $receiver->nack($frameA);

        $frameB = $receiver->read();
        $this->assertInstanceOf(Frame::class, $frameB);
        $this->assertEquals('message-b', $frameB->body);
        $receiver->ack($frameB);

        $this->assertFalse($receiver->read());
        $receiver->unsubscribe();
    }
}
