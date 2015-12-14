<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Tests\Functional\ApolloMq;

use Stomp\Client;
use Stomp\Network\Connection;

/**
 * ClientProvider
 * @package Stomp\Tests\Functional\ApolloMq
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class ClientProvider
{
    /**
     * ApolloMq Test Client
     *
     * @return Client
     */
    public static function getClient()
    {
        $client = new Client(new Connection('tcp://127.0.0.1:61020'));
        $client->setLogin('admin', 'password');
        return $client;
    }
}
