<?php

/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp;

use Stomp\Exception\StompException;

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
    public function __construct($command = null, array $headers = array(), $body = null)
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
    public function getMessageId()
    {
        return isset($this->headers['message-id']) ? $this->headers['message-id'] : null;
    }

    /**
     * Is error frame.
     *
     * @return boolean
     */
    public function isErrorFrame()
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
