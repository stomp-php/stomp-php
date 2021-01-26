<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Tests\Functional\ApolloMq;

use LogicException;
use Stomp\Tests\Functional\Stomp\StatefulTestBase;
use Stomp\Transport\Message;

/**
 * StatefulTest on ApolloMq
 *
 * @package Stomp\Tests\Functional\ApolloMq
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class StatefulTest extends StatefulTestBase
{
    protected function getClient()
    {
        return ClientProvider::getClient();
    }

    public function testNackRequeueException()
    {
        $queue = '/queue/tests-ack-nack-exception';
        $receiver = $this->getStatefulStomp();
        $producer = $this->getStatefulStomp();

        $receiver->subscribe($queue, null, 'client-individual');

        $producer->send($queue, new Message('message-a', ['persistent' => 'true']));
        $producer->send($queue, new Message('message-b', ['persistent' => 'true']));
        $producer->getClient()->disconnect(true);

        $this->expectException(LogicException::class);

        $frameA = $receiver->read();
        $receiver->nack($frameA, true);
    }
}
