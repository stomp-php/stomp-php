<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\States;

/**
 * ProducerState client is working as a message producer.
 *
 * @package Stomp\States
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class ProducerState extends StateTemplate
{
    /**
     * @inheritdoc
     */
    protected function init(array $options = [])
    {
        // nothing to do here
    }

    /**
     * @inheritdoc
     */
    public function begin()
    {
        $this->setState(new ProducerTransactionState($this->getClient(), $this->getBase()));
    }

    /**
     * @inheritdoc
     */
    public function subscribe($destination, $selector, $ack, array $header = [])
    {
        return $this->setState(
            new ConsumerState($this->getClient(), $this->getBase()),
            ['destination' => $destination, 'selector' => $selector, 'ack' => $ack, 'header' => $header]
        );
    }

    /**
     * @inheritdoc
     */
    protected function getOptions()
    {
        return [];
    }
}
