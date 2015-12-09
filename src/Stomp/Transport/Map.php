<?php

/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Transport;

/* vim: set expandtab tabstop=3 shiftwidth=3: */


/**
 * Message that contains a set of name-value pairs
 *
 * @package Stomp
 */
class Map extends Frame
{
    public $map;

    /**
     * Constructor
     *
     * @param Frame|string $msg
     * @param array $headers
     */
    public function __construct($msg, array $headers = [])
    {
        if ($msg instanceof Frame) {
            parent::__construct($msg->command, $msg->headers, $msg->body);
            $this->map = json_decode($msg->body, true);
        } else {
            parent::__construct('SEND', $headers, $msg);
            $this['transformation'] = 'jms-map-json';
            $this->body = json_encode($msg);
        }
    }
}
