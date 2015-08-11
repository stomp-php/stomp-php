<?php

/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp;

/* vim: set expandtab tabstop=3 shiftwidth=3: */


/**
 * Basic text stomp message
 *
 * @package Stomp
 */
class Message extends Frame
{
    public function __construct($body, array $headers = array())
    {
        parent::__construct('SEND', $headers, $body);
    }
}
