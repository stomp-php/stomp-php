<?php

/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Transport;

use ArrayAccess;

/**
 * Stomp Frames are messages that are sent and received on a stomp connection.
 *
 * @package Stomp
 */
class Frame implements ArrayAccess
{
    /**
     * Stomp Command
     *
     * @var string
     */
    protected $command;

    /**
     * Frame Headers
     *
     * @var array
     */
    protected $headers;

    /**
     * Frame Content
     *
     * @var mixed
     */
    public $body;

    /**
     * Frame should set an content-length header on transmission
     *
     * @var bool
     */
    private $addLengthHeader = false;

    /**
     * Frame is in stomp 1.0 mode
     *
     * @var bool
     */
    private $legacyMode = false;

    /**
     * Constructor
     *
     * @param string $command
     * @param array  $headers
     * @param string $body
     */
    public function __construct($command = null, array $headers = [], $body = null)
    {
        $this->command = $command;
        $this->headers = $headers ?: [];
        $this->body = $body;
    }

    /**
     * Add given headers to currently set headers.
     *
     * Will override existing keys.
     *
     * @param array $header
     * @return Frame
     */
    public function addHeaders(array $header)
    {
        $this->headers += $header;
        return $this;
    }

    /**
     * Stomp message Id
     *
     * @return string
     */
    public function getMessageId()
    {
        return $this['message-id'];
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
     * Tell the frame that we expect an length header.
     *
     * @param bool|false $expected
     */
    public function expectLengthHeader($expected = false)
    {
        $this->addLengthHeader = $expected;
    }

    /**
     * Enable legacy mode for this frame
     *
     * @param bool|false $legacy
     */
    public function legacyMode($legacy = false)
    {
        $this->legacyMode = $legacy;
    }

    /**
     * Frame is in legacy mode.
     *
     * @return bool
     */
    public function isLegacyMode()
    {
        return $this->legacyMode;
    }

    /**
     * Command
     *
     * @return string
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * Body
     *
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Headers
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Convert frame to transportable string
     *
     * @return string
     */
    public function __toString()
    {
        $data = $this->command . "\n";

        if (!$this->legacyMode) {
            if ($this->body && ($this->addLengthHeader || stripos($this->body, "\x00") !== false)) {
                $this['content-length'] = $this->getBodySize();
            }
        }

        foreach ($this->headers as $name => $value) {
            $data .= $this->encodeHeaderValue($name) . ':' . $this->encodeHeaderValue($value) . "\n";
        }

        $data .= "\n";
        $data .= $this->body;
        return $data . "\x00";
    }

    /**
     * Size of Frame body.
     *
     * @return int
     */
    protected function getBodySize()
    {
        return strlen($this->body);
    }

    /**
     * Encodes header values.
     *
     * @param string $value
     * @return string
     */
    protected function encodeHeaderValue($value)
    {
        if ($this->legacyMode) {
            return str_replace(["\n"], ['\n'], $value);
        }
        return str_replace(["\\", "\r", "\n", ':'], ["\\\\", '\r', '\n', '\c'], $value);
    }

    /**
     * @inheritdoc
     */
    public function offsetExists($offset)
    {
        return isset($this->headers[$offset]);
    }

    /**
     * @inheritdoc
     */
    public function offsetGet($offset)
    {
        if (isset($this->headers[$offset])) {
            return $this->headers[$offset];
        }
        return null;
    }

    /**
     * @inheritdoc
     */
    public function offsetSet($offset, $value)
    {
        if ($value !== null) {
            $this->headers[$offset] = $value;
        }
    }


    /**
     * @inheritdoc
     */
    public function offsetUnset($offset)
    {
        unset($this->headers[$offset]);
    }
}
