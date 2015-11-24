<?php

/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp;

use Stomp\Message\Map;

/* vim: set expandtab tabstop=3 shiftwidth=3: */

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
     * Frames that have been parsed and queued up.
     *
     * @var Frame[]
     */
    private $frames = array();

    /**
     * Current buffer for new frames.
     *
     * @var string
     */
    private $buffer = '';

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
     * There are frames which have been parsed.
     *
     * @return boolean
     */
    public function hasBufferedFrames()
    {
        return !empty($this->frames);
    }

    /**
     * Get next parsed frame.
     *
     * @return Frame
     */
    public function getFrame()
    {
        return array_shift($this->frames);
    }

    /**
     * Parse current data for new frames.
     *
     * Will return true if any new frame has been detected.
     *
     * @return boolean
     */
    public function parse()
    {
        $offset = 0;
        $len = strlen($this->buffer);
        while (($offset < $len) && (($frameEnd = $this->getFrameEnd($offset)) !== false)) {
            $frameSource = substr($this->buffer, $offset, $frameEnd - $offset);
            $offset = $frameEnd + strlen(self::FRAME_END);
            $this->frames[] = $this->parseToFrame($frameSource);
        }
        if ($offset > 0) {
            $this->buffer = substr($this->buffer, $offset);
        }
        return $offset > 0;
    }

    /**
     * Parse a frame from source.
     *
     * @param string $source
     * @return Map|Frame
     */
    private function parseToFrame($source)
    {
        list ($header, $body) = explode("\n\n", ltrim($source), 2);
        $header = explode("\n", $header);
        $headers = array();
        $command = null;
        foreach ($header as $v) {
            if (isset($command)) {
                list ($name, $value) = explode(':', $v, 2);
                $headers[$name] = $value;
            } else {
                $command = $v;
            }
        }
        $frame = new Frame($command, $headers, trim($body));
        if (isset($frame->headers['transformation']) && $frame->headers['transformation'] == 'jms-map-json') {
            return new Map($frame);
        }

        return $frame;
    }

    /**
     * Test for the frame-end in the current buffer.
     *
     * @param int $offset
     * @return bool|int
     */
    private function getFrameEnd($offset)
    {
        // newer stomp version allows \r\n as eol instead of \n
        $endMarkers = array("\n\n", "\r\n\r\n");
        $headerEnd = false;
        $activeMarker = false;
        foreach ($endMarkers as $marker) {
            $activeMarker = $marker;
            if (($headerEnd = strpos($this->buffer, $activeMarker, $offset)) !== false) {
                break;
            }
        }

        // No headers yet, so no full frame
        if ($headerEnd === false) {
            return false;
        }

        $headers = substr($this->buffer, $offset, $headerEnd);

        // See if there is a content-length header
        if (preg_match('_(?:^|\n)\s*content-length\s*:\s*([0-9]+)\s*(?:\n|$)_i', $headers, $matches)) {
            $frameEnd = $offset + $headerEnd + strlen($activeMarker) + $matches[1];

            if ($frameEnd > strlen($this->buffer)) {
                return false;
            }

            return $frameEnd;
        }

        // No content-length, search for first 0-byte
        return strpos($this->buffer, self::FRAME_END, $offset);
    }
}
