<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Broker\Exception;

use Stomp\Exception\StompException;
use Stomp\Protocol\Protocol;

/**
 * UnsupportedBrokerException
 *
 * Indicate that given broker is not supported.
 *
 * @package Stomp\Broker\Exception
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class UnsupportedBrokerException extends StompException
{

    /**
     * @param Protocol $detectedProtocol
     * @param string $expectedProtocol
     */
    public function __construct(Protocol $detectedProtocol, $expectedProtocol)
    {
        parent::__construct(
            sprintf('The current broker (%s) is no %s.', get_class($detectedProtocol), $expectedProtocol)
        );
    }
}
