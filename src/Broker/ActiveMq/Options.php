<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Broker\ActiveMq;

use ArrayAccess;

/**
 * Options for ActiveMq Stomp
 *
 * For more details visit http://activemq.apache.org/stomp.html
 *
 * @package Stomp\Broker\ActiveMq
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class Options implements ArrayAccess
{
    private $extensions = [
        'activemq.dispatchAsync',
        'activemq.exclusive',
        'activemq.maximumPendingMessageLimit',
        'activemq.noLocal',
        'activemq.prefetchSize',
        'activemq.priority',
        'activemq.retroactive',
    ];

    private $options = [];

    /**
     * Options constructor.
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        foreach ($options as $key => $value) {
            $this[$key] = $value;
        }
    }

    public function offsetExists($offset)
    {
        return isset($this->options[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->options[$offset];
    }

    public function offsetSet($offset, $value)
    {
        if (in_array($offset, $this->extensions, true)) {
            $this->options[$offset] = $value;
        }
    }

    public function offsetUnset($offset)
    {
        unset($this->options[$offset]);
    }

    public function getOptions()
    {
        return $this->options;
    }


    public function activateRetroactive()
    {
        $this['activemq.retroactive'] = 'true';
        return $this;
    }
    public function activateExclusive()
    {
        $this['activemq.exclusive'] = 'true';
        return $this;
    }

    public function activateDispatchAsync()
    {
        $this['activemq.dispatchAsync'] = 'true';
        return $this;
    }

    public function setPriority($priority)
    {
        $this['activemq.priority'] = $priority;
        return $this;
    }


    public function setPrefetchSize($size)
    {
        $this['activemq.prefetchSize'] = max($size, 1);
        return $this;
    }


    public function activateNoLocal()
    {
        $this['activemq.noLocal'] = 'true';
        return $this;
    }

    public function setMaximumPendingLimit($limit)
    {
        $this['activemq.maximumPendingMessageLimit'] = $limit;
        return $this;
    }
}
