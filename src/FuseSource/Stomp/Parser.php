<?php

namespace FuseSource\Stomp;

use FuseSource\Stomp\Message\Map;
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
     * @var Frames[]
     */
    private $_frames = array();

    /**
     * Current buffer for new frames.
     *
     * @var string
     */
    private $_buffer = '';

    /**
     * Add data to parse.
     *
     * @param string $data
     * @return void
     */
    public function addData($data)
    {
        $this->_buffer .= $data;
    }

    /**
     * There are frames which have been parsed.
     *
     * @return boolean
     */
    public function hasBufferedFrames()
    {
        return !empty($this->_frames);
    }

    /**
     * Get next parsed frame.
     *
     * @return Frame
     */
    public function getFrame()
    {
        return array_shift($this->_frames);
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
        $len = strlen($this->_buffer);
        while (($offset < $len) && (($frameEnd = strpos($this->_buffer, self::FRAME_END, $offset)) !== false)) {
            $frameSource = substr($this->_buffer, $offset, $frameEnd - $offset);
            $offset = $frameEnd + strlen(self::FRAME_END);
            $this->_frames[] = $this->parseToFrame($frameSource);
        }
        if ($offset > 0) {
            $this->_buffer = substr($this->_buffer, $offset);
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
        } else {
            return $frame;
        }
        return $frame;
    }


}
