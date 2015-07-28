<?php

/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Exception;

use Stomp\Frame;

/* vim: set expandtab tabstop=3 shiftwidth=3: */

/**
 * Exception that occurs, when a frame / response was received that was not expected at this moment.
 *
 *
 * @package Stomp
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class UnexpectedResponseException extends StompException
{
    /**
     *
     * @var Frame
     */
    private $_frame;

    /**
     *
     * @param Frame $frame
     * @param string $expectedInfo
     */
    function __construct(Frame $frame, $expectedInfo)
    {
        $this->_frame = $frame;
        parent::__construct(sprintf('Unexpected response received. %s', $expectedInfo));
    }

    /**
     *
     * @return Frame
     */
    public function getFrame()
    {
        return $this->_frame;
    }

}
