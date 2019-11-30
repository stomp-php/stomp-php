<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\States\Exception;

use Stomp\Client;
use Stomp\States\IStateful;

/**
 * DrainingMessageException indicates that an call to an operation is not respecting draining messages.
 *
 * @package Stomp\States\Exception
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class DrainingMessageException extends InvalidStateException
{
    /** @var Client */
    private $client;

    public function __construct(Client $client, IStateful $state, $method)
    {
        $this->client = $client;
        parent::__construct(
            $state,
            $method,
            'Please make sure that there is no draining message left. Call read until it returns false.'
        );
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }
}
