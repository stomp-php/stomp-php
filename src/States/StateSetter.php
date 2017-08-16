<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\States;

/**
 * StateSetter allows to change a state.
 *
 * @package Stomp\States
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
abstract class StateSetter
{
    /**
     * Change current state to given one, might return creation data from state.
     *
     * @param IStateful $state
     * @return mixed
     */
    abstract protected function setState(IStateful $state);
}
