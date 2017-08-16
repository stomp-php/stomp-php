<?php

/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Exception;

use Stomp\Transport\Frame;

/**
 * Stomp server send us an error frame.
 *
 *
 * @package Stomp
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class ErrorFrameException extends StompException
{
    /**
     *
     * @var Frame
     */
    private $frame;

    /**
     *
     * @param Frame $frame
     */
    public function __construct(Frame $frame)
    {
        $this->frame = $frame;
        parent::__construct(
            sprintf('Error "%s"', $frame['message'])
        );
    }

    /**
     *
     * @return Frame
     */
    public function getFrame()
    {
        return $this->frame;
    }
}
