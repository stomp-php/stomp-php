<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\States;

use Stomp\States\Exception\InvalidStateException;

/**
 * ProducerTransactionState client is working in an transaction scope as a message producer.
 *
 * @package Stomp\States
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class ProducerTransactionState extends ProducerState
{
    use TransactionsTrait;

    /**
     * @inheritdoc
     */
    protected function init(array $options = [])
    {
        $this->initTransaction($options);
        parent::init($options);
    }

    /**
     * @inheritdoc
     */
    public function commit()
    {
        $this->transactionCommit();
        $this->setState(new ProducerState($this->getClient(), $this->getBase()), parent::getOptions());
    }

    /**
     * @inheritdoc
     */
    public function abort()
    {
        $this->transactionAbort();
        $this->setState(new ProducerState($this->getClient(), $this->getBase()), parent::getOptions());
    }

    /**
     * @inheritdoc
     */
    public function subscribe($destination, $selector, $ack, array $header = [])
    {
        return $this->setState(
            new ConsumerTransactionState($this->getClient(), $this->getBase()),
            $this->getOptions() +
            [
                'destination' => $destination,
                'selector' => $selector,
                'ack' => $ack,
                'header' => $header
            ]
        );
    }

    /**
     * @inheritdoc
     */
    public function begin()
    {
        throw new InvalidStateException($this, __FUNCTION__);
    }
}
