<?php

/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Exception;

/* vim: set expandtab tabstop=3 shiftwidth=3: */

/**
 * Any kind of connection problems.
 *
 *
 * @package Stomp
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class ConnectionException extends StompException
{
    /**
     *
     * @var array
     */
    private $_connectionInfo;

    /**
     *
     * @param string $info
     * @param array $connection
     * @param ConnectionException $previous
     */
    function __construct($info, array $connection = array(), ConnectionException $previous = null)
    {
        $this->_connectionInfo = $connection;

        $host = ($previous ? $previous->getHostname() : null) ?: $this->getHostname();
        parent::__construct(sprintf('%s (Host: %s)', $info, $host), 0, $previous);
    }


    /**
     * Active used connection.
     *
     * @return array
     */
    public function getConnectionInfo()
    {
        return $this->_connectionInfo;
    }


    protected function getHostname()
    {
        return isset($this->_connectionInfo['host']) ? $this->_connectionInfo['host'] : null;
    }
}
