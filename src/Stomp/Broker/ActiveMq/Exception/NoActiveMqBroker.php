<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Broker\ActiveMq\Exception;

use Stomp\Exception\StompException;
use Stomp\Protocol\Protocol;

/**
 * NoActiveMqBroker
 *
 * Indicate that given broker is not ActiveMq based.
 *
 * @package Stomp\Broker\ActiveMq\Exception
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class NoActiveMqBroker extends StompException
{

    /**
     * @param Protocol $detectedProtocol
     */
    public function __construct(Protocol $detectedProtocol)
    {
        parent::__construct('The current broker (%s) is no ActiveMq Broker.', get_class($detectedProtocol));
    }
}
