<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Network\Observer;

use Stomp\Network\Observer\Exception\HeartbeatException;
use Stomp\Transport\Frame;

/**
 * ServerAliveObserver an observer that checks for signals from server side.
 *
 * Use this to ensure that the server your listening to is still alive.
 *
 * If you want to signal the server that your client is still available check HeartbeatEmitter.
 *
 * @example $client->setHeartbeat(0, 2000); // indicate that we would receive server beats within a 2 second interval
 *          $client->getConnection()->getObservers()->addObserver(new ServerAliveObserver());
 *
 * @see HeartbeatEmitter
 * @package Stomp\Network\Observer
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class ServerAliveObserver extends AbstractBeats
{
    /**
     * Defines the percentage amount of the calculated interval that will be used without emitting a beat.
     *
     * @var float
     */
    private $intervalUsage;

    /**
     * Emitter constructor.
     *
     * What is the interval usage?
     * The usage (percentage) defines the amount of agreed beat interval time,
     * that is allowed to pass before the observer decides that the server is delayed. (not alive anymore)
     *
     * A higher value increases the risk that a dead server is not detected over a given period.
     * A lower value increases the risk that a server is declared as dead when not.
     *
     * @param float $intervalUsage 150% default
     */
    public function __construct($intervalUsage = 1.5)
    {
        $this->intervalUsage = max(1, $intervalUsage);
    }

    /**
     * @inheritdoc
     */
    protected function onPotentialConnectionStateActivity()
    {
        $this->checkDelayed();
    }

    /**
     * @inheritdoc
     */
    protected function onServerActivity()
    {
        $this->rememberActivity();
    }

    /**
     * @inheritdoc
     */
    protected function onClientActivity()
    {
        // ignored here, as we see failures when the write fails
    }

    /**
     * @inheritdoc
     */
    protected function onDelay()
    {
        throw new HeartbeatException('The server failed to send expected heartbeats.');
    }

    /**
     * @inheritdoc
     */
    protected function onHeartbeatFrame(Frame $frame, array $beats)
    {
        if ($frame->getCommand() === self::FRAME_CLIENT_CONNECT) {
            $this->intervalClient = $beats[1];
        } else {
            $this->intervalServer = $beats[0];
            $this->rememberActivity();
        }
    }

    /**
     * @inheritdoc
     */
    protected function calculateInterval($maximum)
    {
        return $maximum * $this->intervalUsage;
    }
}
