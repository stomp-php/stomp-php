<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Network\Observer\Heartbeat;


use Stomp\Network\Connection;
use Stomp\Network\Observer\ConnectionObserver;
use Stomp\Transport\Frame;

/**
 * Emitter a very basic heartbeat emitter.
 *
 * @package Stomp\Network\Observer\Heartbeat
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class Emitter implements ConnectionObserver
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * The timestamp that is known as the last time we have send a beat.
     *
     * @var float
     */
    private $lastbeat = null;

    /**
     * The beat interval that the client has offered to the server.
     *
     * @var integer
     */
    private $intervalClient = null;

    /**
     * The beat interval that the server has requested for this connection.
     *
     * @var integer
     */
    private $intervalServer = null;

    /**
     * The interval that will be used to send beats.
     *
     * @var float
     */
    private $interval = null;

    /**
     * Whenever the emitter is configured to send beats.
     *
     * @var bool
     */
    private $enabled = false;

    /**
     * Defines the percentage amount of the calculated interval that will be used without emitting a beat.
     *
     * @var float
     */
    private $intervalUsage = 0.65;

    /**
     * Emitter constructor.
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }


    /**
     * Indicates that during a read call no frame was received, but an EOL line.
     *
     * @return void
     */
    public function emptyLineReceived()
    {
        $this->onPassiveEvent();
    }

    /**
     * An passive event was fired, all that triggers a connection activity that is not leading to outgoing traffic.
     *
     * @return void
     */
    private function onPassiveEvent()
    {
        if ($this->isDelayed()) {
            $this->sendBeat();
        }
    }

    /**
     * Check if the emitter is in a state that indicates a delay.
     *
     * @return bool
     */
    public function isDelayed()
    {
        if ($this->enabled) {
            $now = microtime(true);
            return ($now - $this->lastbeat > $this->interval);
        }
        return false;
    }

    /**
     * Send a beat to the server.
     *
     * @return void
     */
    private function sendBeat()
    {
        $this->connection->sendAlive();
        $this->notifyBeat();
    }

    /**
     * Outgoing activity event.
     *
     * @return void
     */
    private function notifyBeat()
    {
        $this->lastbeat = microtime(true);
    }

    /**
     * Indicates that during a read call no frame was received, but an EOL line.
     *
     * @return void
     */
    public function emptyBuffer()
    {
        $this->onPassiveEvent();
    }

    /**
     * Indicates that a frame has been received.
     *
     * @param Frame $frame that has been received
     * @return void
     */
    public function receivedFrame(Frame $frame)
    {
        if ($frame->getCommand() === 'CONNECTED') {
            $beats = $this->getHeartbeats($frame);
            $this->intervalServer = $beats[1];
            if ($this->intervalServer && ($this->intervalClient || $this->intervalClient === null)) {
                $this->interval = $this->intervalUsage * (max($this->intervalServer,
                            $this->intervalClient) / 1000); // milli to micro
                $this->enabled = true;
            }
        } else {
            $this->onPassiveEvent();
        }
    }

    /**
     * Returns the heart beat header.
     *
     * @param Frame $frame
     * @return array
     */
    private function getHeartbeats(Frame $frame)
    {
        $beats = $frame['heart-beat'];
        if ($beats) {
            return explode(',', $beats, 2);
        }
        return [0, 0];
    }

    /**
     * Indicates that a frame has been transmitted.
     *
     * @param Frame $frame
     * @return void
     */
    public function transmittedFrame(Frame $frame)
    {
        if ($this->enabled) {
            $this->notifyBeat();
            return;
        }
        if ($frame->getCommand() === 'CONNECT') {
            $beats = $this->getHeartbeats($frame);
            $this->intervalClient = $beats[0];
            $this->notifyBeat();
        }
    }

    /**
     * Returns the microtime for the moment when the last outgoing beat was detected.
     *
     * @return float
     */
    public function getLastbeat()
    {
        return $this->lastbeat;
    }

    /**
     * Returns the calculated interval for outgoing beats in seconds (with micro fraction).
     *
     * @return float
     */
    public function getInterval()
    {
        return $this->interval;
    }

    /**
     * Checks if the emitter was enabled.
     *
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * Returns the interval usage.
     *
     * @return float
     */
    public function getIntervalUsage()
    {
        return $this->intervalUsage;
    }

    /**
     * Sets the interval usage, must be configured before the emitter gets active to work.
     *
     * The usage (percentage) defines the amount of agreed beat interval time,
     * that is allowed to pass before the emitter will send a beat.
     *
     * A higher value increases the risk that a beat is send after a timeout has occurred.
     * A lower value increases the beats and adds overhead to the connection.
     *
     * @param float $intervalUsage
     */
    public function setIntervalUsage($intervalUsage)
    {
        $this->intervalUsage = max(0.05, min($intervalUsage, 0.95));
    }


}