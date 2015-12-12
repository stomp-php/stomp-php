<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Tests\Functional\ApolloMq;

use Stomp\Tests\Functional\Stomp\StatefulTestBase;

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
}
