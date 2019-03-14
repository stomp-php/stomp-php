<?php

/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Transport;

use Stomp\Network\Observer\ConnectionObserver;

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
     * @var Frame|null
     */
    private $frame;

    /**
     * Active Frame command
     *
     * @var string
     */
    private $command;

    /**
     * Active Frame headers
     *
     * @var array
     */
    private $headers = [];

    /**
     * Active Frame expected body size (content-length header)
     *
     * @var integer|null
     */
    private $expectedBodyLength;

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
     * @var FrameFactory
     */
    private $factory;

    /**
     * @var ConnectionObserver|null
     */
    private $observer;

    /**
     * Parser constructor.
     *
     * @param FrameFactory $factory
     */
    public function __construct(FrameFactory $factory = null)
    {
        $this->factory = $factory ?: new FrameFactory();
    }

    /**
     * Sets the observer for the parser, in order to receive heartbeat information.
     *
     * @param ConnectionObserver $observer
     * @return Parser
     */
    public function setObserver(ConnectionObserver $observer)
    {
        $this->observer = $observer;
        return $this;
    }


    /**
     * Returns the factory that will be used to create frame instances.
     *
     * @return FrameFactory
     */
    public function getFactory()
    {
        return $this->factory;
    }

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
     * Get next parsed frame.
     *
     * @deprecated Will be removed in next version. Please use nextFrame().
     * @return Frame
     */
    public function getFrame()
    {
        return $this->frame;
    }

    /**
     * Parse current buffer and return the next available frame, otherwise return null.
     *
     * @return null|Frame
     */
    public function nextFrame()
    {
        if ($this->parse()) {
            $frame = $this->getFrame();
            $this->frame = null;
            if ($this->observer) {
                $this->observer->receivedFrame($frame);
            }
            return $frame;
        }
        return null;
    }


    /**
     * Parse current buffer for frames.
     *
     * @deprecated Will become private in next version. Please use nextFrame().
     *
     * @return bool
     */
    public function parse()
    {
        if ($this->buffer === '') {
            return false;
        }
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
            }
            break;
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
        $foundHeartbeat = false;
        while ($this->offset < $this->bufferSize) {
            $char = substr($this->buffer, $this->offset, 1);
            if ($char === "\x00" || $char === "\n" || $char === "\r") {
                $this->offset++;
                $foundHeartbeat = true;
            } else {
                break;
            }
        }
        if ($foundHeartbeat && $this->observer) {
            $this->observer->emptyLineReceived();
        }
    }

    /**
     * Detect frame header end marker, starting from current offset.
     *
     * @return bool
     */
    private function detectFrameHead()
    {
        $firstCrLf = strpos($this->buffer, self::HEADER_STOP_CR_LF, $this->offset);
        $firstLf = strpos($this->buffer, self::HEADER_STOP_LF, $this->offset);

        // we need to use the first available marker, so we need to make sure that cr lf don't overrule lf
        if ($firstCrLf !== false && ($firstLf === false || $firstLf > $firstCrLf)) {
            $this->extractFrameMeta(substr($this->buffer, $this->offset, $firstCrLf - $this->offset));
            $this->offset = $firstCrLf + strlen(self::HEADER_STOP_CR_LF);
            return true;
        }

        if ($firstLf !== false) {
            $this->extractFrameMeta(substr($this->buffer, $this->offset, $firstLf - $this->offset));
            $this->offset = $firstLf + strlen(self::HEADER_STOP_LF);
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
        if ($this->expectedBodyLength) {
            if (($this->bufferSize - $this->offset) >= $this->expectedBodyLength) {
                $bodySize = $this->expectedBodyLength;
            }
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
        $this->frame = $this->factory->createFrame(
            $this->command,
            $this->headers,
            (string)substr($this->buffer, $this->offset, $bodySize),
            $this->legacyMode
        );

        $this->expectedBodyLength = null;
        $this->headers = [];
        $this->mode = self::MODE_HEADER;
    }


    /**
     * Extracts command and headers from given header source.
     *
     * @param string $source
     * @return void
     */
    private function extractFrameMeta($source)
    {
        $headers = preg_split("/(\r?\n)+/", $source);

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
            $this->expectedBodyLength = (int)$this->headers['content-length'];
        }
    }

    /**
     * Decodes header values.
     *
     * @param string $value
     * @return string
     */
    private function decodeHeaderValue($value)
    {
        if ($this->legacyMode) {
            return str_replace(['\n'], ["\n"], $value);
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
