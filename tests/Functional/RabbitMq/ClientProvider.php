<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Tests\Functional\RabbitMq;

use Stomp\Client;
use Stomp\Network\Connection;

/**
 * ClientProvider
 *
 * @package Stomp\Tests\Functional\RabbitMq
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class ClientProvider
{
    /**
     * RabbitMq Test Client
     *
     * @return Client
     */
    public static function getClient()
    {
        $client = new Client(new Connection('tcp://127.0.0.1:61030'));
        $client->setLogin('guest', 'guest');
        $client->setVhostname('/');
        return $client;
    }
}
