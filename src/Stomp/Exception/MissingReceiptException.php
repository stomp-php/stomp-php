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
 * Exception that occurs, when a frame receipt was not received.
 *
 *
 * @package Stomp
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class MissingReceiptException extends StompException
{
    /**
     *
     * @var Frame
     */
    private $receiptId;

    /**
     *
     * @param string $receiptId
     */
    public function __construct($receiptId)
    {
        $this->receiptId = $receiptId;
        parent::__construct(
            sprintf(
                'Missing receipt Frame for id "%s". Maybe the queue server is under heavy load. ' .
                'Try to increase timeouts.',
                $receiptId
            )
        );
    }

    /**
     * Expected receipt id.
     *
     * @return String
     */
    public function getReceiptId()
    {
        return $this->receiptId;
    }
}
