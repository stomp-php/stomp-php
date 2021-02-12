<?php


namespace Stomp\States;

use Stomp\States\Exception\DrainingMessageException;
use Stomp\Transport\Frame;
use Stomp\Transport\Message;

class DrainingConsumerState extends StateTemplate
{

    /**
     * Activates the current state, after it has been applied on base.
     *
     * @param array $options
     * @return mixed
     */
    protected function init(array $options = [])
    {
    }

    /**
     * Returns the options needed in current state.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function ack(Frame $frame)
    {
        $this->getClient()->sendFrame($this->getProtocol()->getAckFrame($frame), false);
    }

    /**
     * @inheritdoc
     */
    public function nack(Frame $frame, $requeue = null)
    {
        $this->getClient()->sendFrame($this->getProtocol()->getNackFrame($frame, null, $requeue), false);
    }

    /**
     * @inheritdoc
     */
    public function send($destination, Message $message)
    {
        return $this->getClient()->send($destination, $message);
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
            new ProducerState($this->getClient(), $this->getBase())
        );
        return false;
    }

    /**
     * @inheritdoc
     */
    public function begin()
    {
        throw new DrainingMessageException($this->getClient(), $this, __FUNCTION__);
    }

    /**
     * @inheritdoc
     */
    public function subscribe($destination, $selector, $ack, array $header = [])
    {
        throw new DrainingMessageException($this->getClient(), $this, __FUNCTION__);
    }
}
