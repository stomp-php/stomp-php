<?php
namespace FuseSource\Stomp\Exception;

use FuseSource\Stomp\Frame;

/**
 *
 * Copyright 2005-2006 The Apache Software Foundation
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

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
    private $_receiptId;

    /**
     *
     * @param string $receiptId
     */
    function __construct($receiptId)
    {
        $this->_receiptId = $receiptId;
        parent::__construct(
            sprintf(
                'Missing receipt Frame for id "%s". Maybe the queue server is under heavy load. Try to increase timeouts.',
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
        return $this->_receiptId;
    }

}
