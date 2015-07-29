<?php

/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Message;

use Stomp\Message;

/* vim: set expandtab tabstop=3 shiftwidth=3: */

/**
 * Message that contains a stream of uninterpreted bytes
 *
 * @package Stomp
 */
class Bytes extends Message
{
    /**
     * Constructor
     *
     * @param string $body
     * @param array $headers
     */
    function __construct ($body, array $headers = array())
    {
        parent::__construct($body, $headers);
        $this->headers['content-length'] = count(unpack("c*", $body));
    }
}