<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\States;

use Stomp\States\Exception\InvalidStateException;
use Stomp\Transport\Frame;

/**
 * ConsumerTransactionState client is a consumer within an transaction.
 *
 * @package Stomp\States
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class ConsumerTransactionState extends ConsumerState
{
    use TransactionsTrait;

    /**
     * @inheritdoc
     */
    protected function init(array $options = [])
    {
        $this->initTransaction($options);
        return parent::init($options);
    }

    /**
     * @inheritdoc
     */
    public function commit()
    {
        $this->getClient()->sendFrame(
            $this->getProtocol()->getCommitFrame($this->transactionId)
        );
        $this->setState(new ConsumerState($this->getClient(), $this->getBase()), parent::getOptions());
    }

    /**
     * @inheritdoc
     */
    public function abort()
    {
        $this->transactionAbort();
        $this->setState(new ConsumerState($this->getClient(), $this->getBase()), parent::getOptions());
    }

    /**
     * @inheritdoc
     */
    public function ack(Frame $frame)
    {
        $this->getClient()->sendFrame($this->getProtocol()->getAckFrame($frame, $this->transactionId), false);
    }

    /**
     * @inheritdoc
     */
    public function nack(Frame $frame, $requeue = null)
    {
        $this->getClient()->sendFrame(
            $this->getProtocol()->getNackFrame($frame, $this->transactionId, $requeue),
            false
        );
    }

    /**
     * @inheritdoc
     */
    public function unsubscribe($subscriptionId = null)
    {
        if ($this->endSubscription($subscriptionId)) {
            $this->setState(
                new ProducerTransactionState($this->getClient(), $this->getBase()),
                ['transactionId' => $this->transactionId]
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function begin()
    {
        throw new InvalidStateException($this, __FUNCTION__);
    }
}
