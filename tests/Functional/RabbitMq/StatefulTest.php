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
}
