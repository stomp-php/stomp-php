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

    /** @var IStateful */
    private $state;

    /** @var string|null */
    private $hint;

    /**
     * InvalidStateException constructor.
     *
     * @param IStateful $state
     * @param string $method
     * @param string|null $hint
     */
    public function __construct(IStateful $state, $method, $hint = null)
    {
        $this->state = $state;
        $this->hint = $hint;
        if ($hint !== null) {
            parent::__construct(sprintf('"%s" is not allowed in "%s". %s', $method, get_class($state), $hint));
        } else {
            parent::__construct(sprintf('"%s" is not allowed in "%s".', $method, get_class($state)));
        }
    }

    /**
     * @return IStateful
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @return string|null
     */
    public function getHint()
    {
        return $this->hint;
    }
}
