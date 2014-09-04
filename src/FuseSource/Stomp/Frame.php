<?php

namespace FuseSource\Stomp;

use FuseSource\Stomp\Exception\StompException;
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
 * Stomp Frames are messages that are sent and received on a stomp connection.
 *
 * @package Stomp
 */
class Frame
{
    public $command;
    public $headers = array();
    public $body;

    /**
     * Constructor
     *
     * @param string $command
     * @param array  $headers
     * @param string $body
     * @throws StompException
     */
    public function __construct ($command = null, array $headers = array(), $body = null)
    {
        $this->command = $command;
        $this->headers = $headers ?: array();
        $this->body = $body;
    }

    /**
     * Set a specific header value.
     *
     * @param string $key
     * @param string $value
     * @return void
     */
    public function setHeader($key, $value)
    {
        $this->headers[$key] = $value;
    }

    /**
     * Add given headers to currently set headers.
     *
     * Will override existing keys.
     *
     * @param array $header
     * @return void
     */
    public function addHeaders(array $header)
    {
        $this->headers += $header;
    }

    /**
     * Stomp message Id
     *
     * @return string
     */
    public function getMessageId ()
    {
        return isset($this->headers['message-id']) ? $this->headers['message-id'] : null;
    }

    /**
     * Is error frame.
     *
     * @return boolean
     */
    public function isErrorFrame ()
    {
        return ($this->command == 'ERROR');
    }

    /**
     * Convert frame to transportable string
     *
     * @return string
     */
    public function __toString()
    {
        $data = $this->command . "\n";

        foreach ($this->headers as $name => $value) {
            $data .= $name . ":" . $value . "\n";
        }

        $data .= "\n";
        $data .= $this->body;
        return $data .= "\x00";
    }
}