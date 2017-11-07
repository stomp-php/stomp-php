<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Tests\Functional\RabbitMq;

use Stomp\Client;
use Stomp\Tests\Functional\Stomp\StatefulTestBase;
use Stomp\Transport\Message;

/**
 * StatefulTest on RabbitMq
 *
 * @package Stomp\Tests\Functional\RabbitMq
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class StatefulTest extends StatefulTestBase
{
    /**
     * @return Client
     */
    protected function getClient()
    {
        $client = ClientProvider::getClient();
        $client->setReceiptWait(2);
        return $client;
    }

    public function testNackRequeue()
    {
        $queue = '/queue/tests-ack-nack';
        $receiver = $this->getStatefulStomp();
        $producer = $this->getStatefulStomp();

        $receiver->subscribe($queue, null, 'client-individual');

        $producer->send($queue, new Message('message-a', ['persistent' => 'true']));
        $producer->getClient()->disconnect(true);


        $frameA = $receiver->read();
        $receiver->nack($frameA, true);

        $frameA2 = $receiver->read();
        $receiver->nack($frameA2, false);

        $this->assertFalse($receiver->read());
        $receiver->unsubscribe();
    }
}
