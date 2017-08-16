<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Broker\Apollo\Mode;

use Stomp\Client;

/**
 * SequenceQueueBrowser ApolloMq util to browse a queue sequence based without removing messages from it.
 *
 * @see http://activemq.apache.org/apollo/documentation/stomp-manual.html
 *      #Using_Queue_Browsers_to_Implement_Durable_Topic_Subscriptions
 *
 * @package Stomp\Broker\Apollo\Mode
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class SequenceQueueBrowser extends QueueBrowser
{
    /**
     * Browser start at head at current queue
     */
    const START_HEAD = 0;
    /**
     * Browser start at for new messages in queue
     */
    const START_NEW = -1;

    /**
     * @var int
     */
    private $startAt;

    /**
     * Current offset.
     *
     * @var null|int
     */
    private $seq;

    /**
     * SequenceQueueBrowser constructor.
     *
     * @param Client $client
     * @param string $destination
     * @param int $startAt
     * @param bool $stopOnEnd
     */
    public function __construct(Client $client, $destination, $startAt = self::START_HEAD, $stopOnEnd = true)
    {
        $this->startAt = $startAt;
        parent::__construct($client, $destination, $stopOnEnd);
    }

    /**
     * @inheritdoc
     */
    protected function getHeader()
    {
        return parent::getHeader() + ['include-seq' => 'seq', 'from-seq' => $this->startAt];
    }

    /**
     * @inheritdoc
     */
    public function read()
    {
        if ($frame = parent::read()) {
            $this->seq = $frame['seq'];
        }
        return $frame;
    }

    /**
     * Returns the last received sequence.
     *
     * @return null|int
     */
    public function getSeq()
    {
        return $this->seq;
    }
}
