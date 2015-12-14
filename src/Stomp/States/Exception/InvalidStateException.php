<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\States\Exception;

use Stomp\Exception\StompException;
use Stomp\States\IStateful;

/**
 * InvalidStateException indicates that an call to an operation is not possible in current state.
 *
 * @package Stomp\States\Exception
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class InvalidStateException extends StompException
{

    /**
     * InvalidStateException constructor.
     *
     * @param IStateful $state
     * @param string $method
     */
    public function __construct(IStateful $state, $method)
    {
        parent::__construct(sprintf('"%s" is not allowed in "%s".', $method, get_class($state)));
    }
}
