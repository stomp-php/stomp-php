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

    /**
     * @inheritdoc
     */
    public function offsetExists($offset): bool
    {
        return isset($this->options[$offset]);
    }


    /**
     * @inheritdoc
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->options[$offset];
    }

    /**
     * @inheritdoc
     */
    public function offsetSet($offset, $value): void
    {
        if (in_array($offset, $this->extensions, true)) {
            $this->options[$offset] = $value;
        }
    }

    /**
     * @inheritdoc
     */
    public function offsetUnset($offset): void
    {
        unset($this->options[$offset]);
    }

    /**
     * @return array The options.
     */
    public function getOptions(): array
    {
        return $this->options;
    }


    /**
     * Activate retroactive option.
     *
     * @return $this
     */
    public function activateRetroactive(): self
    {
        $this['activemq.retroactive'] = 'true';
        return $this;
    }

    /**
     * Active exclusive option.
     *
     * @return $this
     */
    public function activateExclusive(): self
    {
        $this['activemq.exclusive'] = 'true';
        return $this;
    }

    /**
     * Active dispatchAsync option.
     *
     * @return $this
     */
    public function activateDispatchAsync(): self
    {
        $this['activemq.dispatchAsync'] = 'true';
        return $this;
    }

    /**
     * Set the priority.
     *
     * @param $priority mixed The priority.
     * @return $this
     */
    public function setPriority($priority): self
    {
        $this['activemq.priority'] = $priority;
        return $this;
    }

    /**
     * Set the prefetch size.
     *
     * @param $size mixed The prefetch size to set.
     * @return $this
     */
    public function setPrefetchSize($size): self
    {
        $this['activemq.prefetchSize'] = max($size, 1);
        return $this;
    }

    /**
     * Activate the noLocal option.
     *
     * @return $this
     */
    public function activateNoLocal(): self
    {
        $this['activemq.noLocal'] = 'true';
        return $this;
    }

    /**
     * Set the maximum pending limit option.
     *
     * @param $limit mixed The max pending limit.
     * @return $this
     */
    public function setMaximumPendingLimit($limit): self
    {
        $this['activemq.maximumPendingMessageLimit'] = $limit;
        return $this;
    }
}
