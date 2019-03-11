<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Network\Observer\Exception;

/**
 * HeartbeatException indicate that heartbeats where not send or received as expected.
 *
 * @package src\Network\Observer\Exception
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class HeartbeatException extends \RuntimeException
{
    /**
     * ClientHeartbeatException constructor.
     *
     * @param string $message
     * @param \Exception|null $previous
     */
    public function __construct($message, \Exception $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
