<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Tests\Functional\ActiveMq;

use PHPUnit_Framework_TestCase;
use Stomp\Client;
use Stomp\SimpleStomp;
use Stomp\Transport\Frame;

/**
 * ActiveMqFunctionalTestCase
 *
 * @package Stomp\Tests\Functional\ActiveMq
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
abstract class ActiveMqFunctionalTestCase extends PHPUnit_Framework_TestCase
{

    /**
     * ActiveMq Stomp Test Client
     *
     * @return Client
     */
    protected function getClient()
    {
        return ClientProvider::getClient();
    }

    /**
     * Get current activeMq DLQ (and clean it)
     *
     * @return Frame[]
     */
    protected function getCurrentDLQ()
    {
        $messages = [];
        $client = $this->getClient();
        $client->getConnection()->setReadTimeout(0, 500000);

        $dlq = new SimpleStomp($client);
        $dlq->subscribe('ActiveMQ.DLQ', 'dlq-cleaner');
        while ($message = $dlq->read()) {
            $messages[] = $message;
        }
        $dlq->unsubscribe('ActiveMQ.DLQ', 'dlq-cleaner');
        $client->disconnect(true);
        return $messages;
    }

    /**
     * Clear activeMq Dead Letter Queue
     *
     * @return void
     */
    protected function clearDLQ()
    {
        $this->getCurrentDLQ();
    }
}
