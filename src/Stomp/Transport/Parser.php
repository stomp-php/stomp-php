<?php

/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Transport;

/**
 * A Stomp frame parser
 *
 * @package Stomp
 * @author Hiram Chirino <hiram@hiramchirino.com>
 * @author Dejan Bosanac <dejan@nighttale.net>
 * @author Michael Caplan <mcaplan@labnet.net>
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class Parser
{

    /**
     * Frame end
     */
    const FRAME_END = "\x00";

    /**
     * Frame that has been parsed last.
     *
     * @var Frame
     */
    private $frame = null;

    /**
     * Active Frame command
     *
     * @var string
     */
    private $command = null;

    /**
     * Active Frame headers
     *
     * @var array
     */
    private $headers = [];

    /**
     * Active Frame expected body size (content-length header)
     *
     * @var integer
     */
    private $expectedBodyLength = null;

    /**
     * Parser mode
     *
     * @var string
     */
    private $mode = self::MODE_HEADER;

    /**
     * Expecting header data mode
     */
    const MODE_HEADER = 'HEADER';

    /**
     * Expecting body end marker mode
     */
    const MODE_BODY = 'BODY';

    /**
     * Header end marker CR_LF
     */
    const HEADER_STOP_CR_LF = "\r\n\r\n";

    /**
     * Header end marker LF
     */
    const HEADER_STOP_LF = "\n\n";

    /**
     * Parser offset within buffer
     *
     * @var int
     */
    private $offset = 0;

    /**
     * Buffer size
     *
     * @var int
     */
    private $bufferSize;

    /**
     * Current buffer for new frames.
     *
     * @var string
     */
    private $buffer = '';

    /**
     * Parser is in stomp 1.0 mode
     *
     * @var bool
     */
    private $legacyMode = false;


    /**
     * Set parser in legacy mode.
     *
     * @param bool|false $legacy
     */
    public function legacyMode($legacy = false)
    {
        $this->legacyMode = $legacy;
    }

    /**
     * Add data to parse.
     *
     * @param string $data
     * @return void
     */
    public function addData($data)
    {
        $this->buffer .= $data;
    }

    /**
     * Get parsed frame.
     *
     * @return Frame
     */
    public function getFrame()
    {
        return $this->frame;
    }

    /**
     * Parse current buffer for frames.
     *
     * @return bool
     */
    public function parse()
    {
        $this->frame = null;
        $this->offset = 0;
        $this->bufferSize = strlen($this->buffer);
        while ($this->offset < $this->bufferSize) {
            if ($this->mode === self::MODE_HEADER) {
                $this->skipEmptyLines();
                if ($this->detectFrameHead()) {
                    $this->mode = self::MODE_BODY;
                } else {
                    break;
                }
            }
            if ($this->detectFrameEnd()) {
                $this->mode = self::MODE_HEADER;
            } else {
                break;
            }
        }

        if ($this->offset > 0) {
            // remove parsed buffer
            $this->buffer = substr($this->buffer, $this->offset);
        }
        return $this->frame !== null;
    }

    /**
     * Skips empty lines before frame headers (they are allowed after \00)
     */
    private function skipEmptyLines()
    {
        while ($this->offset < $this->bufferSize) {
            $char = substr($this->buffer, $this->offset, 1);
            if ($char === "\n" || $char === "\r") {
                $this->offset++;
            } else {
                break;
            }
        }
    }

    /**
     * Detect frame header end marker, starting from current offset.
     *
     * @return bool
     */
    private function detectFrameHead()
    {
        if (($headerEnd = strpos($this->buffer, self::HEADER_STOP_CR_LF, $this->offset)) !== false) {
            $this->extractFrameMeta(substr($this->buffer, $this->offset, $headerEnd - $this->offset));
            $this->offset = $headerEnd + strlen(self::HEADER_STOP_CR_LF);
            return true;
        } elseif (($headerEnd = strpos($this->buffer, self::HEADER_STOP_LF, $this->offset)) !== false) {
            $this->extractFrameMeta(substr($this->buffer, $this->offset, $headerEnd - $this->offset));
            $this->offset = $headerEnd + strlen(self::HEADER_STOP_LF);
            return true;
        }
        return false;
    }

    /**
     * Detect frame end marker, starting from current offset.
     *
     * @return bool
     */
    private function detectFrameEnd()
    {
        $bodySize = null;
        if ($this->expectedBodyLength && ($this->bufferSize - $this->offset) >= $this->expectedBodyLength) {
            $bodySize = $this->expectedBodyLength;
        } elseif (($frameEnd = strpos($this->buffer, self::FRAME_END, $this->offset)) !== false) {
            $bodySize = $frameEnd - $this->offset;
        }

        if ($bodySize !== null) {
            $this->setFrame($bodySize);
            $this->offset += $bodySize + strlen(self::FRAME_END); // x00
        }
        return $bodySize !== null;
    }


    /**
     * Adds a frame from current known command, headers. Uses current offset and given body size.
     *
     * @param integer $bodySize
     */
    private function setFrame($bodySize)
    {
        $frame = new Frame($this->command, $this->headers, (string) substr($this->buffer, $this->offset, $bodySize));

        if ($frame['transformation'] == 'jms-map-json') {
            $this->frame = new Map($frame);
        } else {
            $this->frame = $frame;
        }

        $this->expectedBodyLength = null;
        $this->headers = [];
        $this->mode = self::MODE_HEADER;
    }



    /**
     * Extracts command and headers from given header source.
     *
     * @param $source
     * @return array
     */
    private function extractFrameMeta($source)
    {
        $headers = preg_split("/[\r?\n]+/", $source);

        $this->command = array_shift($headers);

        foreach ($headers as $header) {
            $headerDetails = explode(':', $header, 2);
            $name = $this->decodeHeaderValue($headerDetails[0]);
            $value = isset($headerDetails[1]) ? $this->decodeHeaderValue($headerDetails[1]) : true;
            if (!isset($this->headers[$name])) {
                $this->headers[$name] = $value;
            }
        }

        if (isset($this->headers['content-length'])) {
            $this->expectedBodyLength = (int) $this->headers['content-length'];
        }
    }

    /**
     * Decodes header values.
     *
     * @param $value
     * @return string
     */
    private function decodeHeaderValue($value)
    {
        if ($this->legacyMode) {
            return $value;
        }
        return str_replace(['\r', '\n', '\c', "\\\\"], ["\r", "\n", ':', "\\"], $value);
    }

    /**
     * Resets the current buffer within this parser and returns the flushed buffer value.
     *
     * @return string
     */
    public function flushBuffer()
    {
        $this->expectedBodyLength = null;
        $this->headers = [];
        $this->mode = self::MODE_HEADER;

        $currentBuffer = substr($this->buffer, $this->offset);
        $this->offset = 0;
        $this->bufferSize = 0;
        $this->buffer = '';
        return $currentBuffer;
    }
}
