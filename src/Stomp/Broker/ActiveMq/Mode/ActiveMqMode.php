<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Broker\ActiveMq\Mode;

use Stomp\Broker\ActiveMq\ActiveMq;
use Stomp\Broker\ActiveMq\Options;
use Stomp\Broker\Exception\UnsupportedBrokerException;
use Stomp\Client;

/**
 * ActiveMqMode
 *
 * @package Stomp\Broker\ActiveMq\Mode
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
abstract class ActiveMqMode
{
    /**
     * @var Client
     */
    protected $client;


    /**
     * @var Options
     */
    protected $options;

    /**
     * ActiveMqMode constructor.
     *
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->options = new Options();
        $this->client = $client;
    }

    /**
     * @return ActiveMq
     * @throws \Stomp\Broker\Exception\UnsupportedBrokerException
     */
    protected function getProtocol()
    {
        $protocol = $this->client->getProtocol();
        if (!$protocol instanceof ActiveMq) {
            throw new UnsupportedBrokerException($protocol, ActiveMq::class);
        }
        return $protocol;
    }


    /**
     * @return Options
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param Options $options
     * @return ActiveMqMode
     */
    public function setOptions(Options $options)
    {
        $this->options = $options;
        return $this;
    }
}
