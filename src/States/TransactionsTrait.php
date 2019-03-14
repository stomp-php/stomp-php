<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\States;

use Stomp\Client;
use Stomp\Protocol\Protocol;
use Stomp\Transport\Message;
use Stomp\Util\IdGenerator;

/**
 * TransactionsTrait provides base logic for all transaction based states.
 *
 * @package Stomp\States
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
trait TransactionsTrait
{

    /**
     * @return Protocol
     */
    abstract public function getProtocol();


    /**
     * @return Client
     */
    abstract public function getClient();

    /**
     * Id used for current transaction.
     *
     * @var int|string
     */
    protected $transactionId;

    /**
     * Init the transaction state.
     *
     * @param array $options
     */
    protected function initTransaction(array $options = [])
    {
        if (!isset($options['transactionId'])) {
            $this->transactionId = IdGenerator::generateId();
            $this->getClient()->sendFrame(
                $this->getProtocol()->getBeginFrame($this->transactionId)
            );
        } else {
            $this->transactionId = $options['transactionId'];
        }
    }

    /**
     * Options for this transaction state.
     *
     * @return array
     */
    protected function getOptions()
    {
        return ['transactionId' => $this->transactionId];
    }

    /**
     * Send a message within this transaction.
     *
     * @param string $destination
     * @param \Stomp\Transport\Message $message
     * @return bool
     */
    public function send($destination, Message $message)
    {
        return $this->getClient()->send($destination, $message, ['transaction' => $this->transactionId], false);
    }

    /**
     * Commit current transaction.
     */
    protected function transactionCommit()
    {
        $this->getClient()->sendFrame($this->getProtocol()->getCommitFrame($this->transactionId));
        IdGenerator::releaseId($this->transactionId);
    }

    /**
     * Abort the current transaction.
     */
    protected function transactionAbort()
    {
        $this->getClient()->sendFrame($this->getProtocol()->getAbortFrame($this->transactionId));
        IdGenerator::releaseId($this->transactionId);
    }
}
