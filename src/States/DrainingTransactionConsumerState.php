<?php


namespace Stomp\States;

use Stomp\States\Exception\DrainingMessageException;
use Stomp\Transport\Frame;

class DrainingTransactionConsumerState extends ConsumerState
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
    public function read()
    {
        if ($frame = $this->getClient()->readFrame()) {
            return $frame;
        }
        $this->setState(
            new ProducerTransactionState($this->getClient(), $this->getBase()),
            ['transactionId' => $this->transactionId]
        );
        return false;
    }

    /**
     * @inheritdoc
     */
    public function commit()
    {
        throw new DrainingMessageException($this->getClient(), $this, __FUNCTION__);
    }

    /**
     * @inheritdoc
     */
    public function abort()
    {
        throw new DrainingMessageException($this->getClient(), $this, __FUNCTION__);
    }
}
