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
     * @param array|object|string $body string will get decoded (receive), otherwise the body will be encoded (send)
     * @param array $headers
     * @param string $command
     */
    public function __construct($body, array $headers = [], $command = 'SEND')
    {
        if (is_string($body)) {
            parent::__construct($body, $headers);
            $this->command = $command;
            $this->map = json_decode($body, true);
        } else {
            parent::__construct(json_encode($body), $headers + ['transformation' => 'jms-map-json']);
            $this->command = $command;
            $this->map = $body;
        }
    }

    /**
     * Returns the received decoded json.
     *
     * @return mixed
     */
    public function getMap()
    {
        return $this->map;
    }
}
