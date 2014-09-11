<?php
namespace FuseSource\Stomp\Message;

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
    function __construct ($msg, array $headers = array())
    {
        if ($msg instanceof Frame) {
            parent::__construct($msg->command, $msg->headers, $msg->body);
            $this->map = json_decode($msg->body, true);
        } else {
            parent::__construct("SEND", $headers, $msg);
            $this->headers['transformation'] = 'jms-map-json';
            $this->body = json_encode($msg);
        }
    }
}