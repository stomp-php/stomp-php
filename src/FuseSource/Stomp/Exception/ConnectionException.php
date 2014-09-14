<?php
namespace FuseSource\Stomp\Exception;

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
