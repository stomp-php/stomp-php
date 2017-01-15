<?php

/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Broker\OpenMq;

use Stomp\Protocol\Protocol;
use Stomp\Transport\Frame;
use Stomp\Protocol\Version;

/**
 * OpenMq Stomp dialect.
 *
 * @package Stomp
 * @author Markus Staab <maggus.staab@googlemail.com>
 */
class OpenMq extends Protocol
{
    /**
     * @inheritdoc
     */
    public function getAckFrame(Frame $frame, $transactionId = null)
    {
        $ack = $this->createFrame('ACK');
        $ack['transaction'] = $transactionId;
        if ($this->hasVersion(Version::VERSION_1_2)) {
            $ack['id'] = $frame->getMessageId();
        } else {
            $ack['message-id'] = $frame->getMessageId();
        }
        // spec quote: "ACK should always specify a "subscription" header for the subscription id
        //              that the message to be acked was delivered to ."
        // see https://mq.java.net/4.4-content/stomp-funcspec.html
        $ack['subscription'] = $frame['subscription'];
        return $ack;
    }
}
