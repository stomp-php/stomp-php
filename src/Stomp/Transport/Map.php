<?php

/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Transport;

/**
 * Message that contains a set of name-value pairs
 *
 * @package Stomp
 */
class Map extends Message
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
            parent::__construct($msg->body, $msg->headers);
            $this->command = $msg->command;
            $this->map = json_decode($msg->body, true);
        } else {
            parent::__construct($msg, $headers);
            $this['transformation'] = 'jms-map-json';
            $this->body = json_encode($msg);
            $this->command = 'SEND';
        }
    }
}
