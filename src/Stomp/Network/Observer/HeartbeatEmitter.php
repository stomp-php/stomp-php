<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Network\Observer;


use Stomp\Network\Connection;
use Stomp\Transport\Frame;

/**
 * HeartbeatEmitter a very basic heartbeat emitter.
 *
 * @package Stomp\Network\Observer\Heartbeat
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class HeartbeatEmitter implements ConnectionObserver
{
    /**
     * Frame from client that request a connection.
     */
    const FRAME_CLIENT_CONNECT = 'CONNECT';

    /**
     * Frame from server when a connection is established.
     */
    const FRAME_SERVER_CONNECTED = 'CONNECTED';

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
    private $intervalUsage;

    /**
     * Emitter constructor.
     *
     * What is the interval usage?
     * The usage (percentage) defines the amount of agreed beat interval time,
     * that is allowed to pass before the emitter will send a beat.
     *
     * A higher value increases the risk that a beat is send after a timeout has occurred.
     * A lower value increases the beats and adds overhead to the connection.
     *
     * @param Connection $connection
     * @param float $intervalUsage
     */
    public function __construct(Connection $connection, $intervalUsage = 0.65)
    {
        $this->intervalUsage = max(0.05, min($intervalUsage, 0.95));
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
        $this->rememberBeat();
    }

    /**
     * Outgoing activity event.
     *
     * @return void
     */
    private function rememberBeat()
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
     * Indicates that a frame has been received from the server.
     *
     * @param Frame $frame that has been received
     * @return void
     */
    public function receivedFrame(Frame $frame)
    {
        if ($frame->getCommand() === HeartbeatEmitter::FRAME_SERVER_CONNECTED) {
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
     * Returns the heartbeat header.
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
     * Indicates that a frame has been sent to the server.
     *
     * @param Frame $frame
     * @return void
     */
    public function sentFrame(Frame $frame)
    {
        if ($this->enabled) {
            $this->rememberBeat();
            return;
        }
        if ($frame->getCommand() === HeartbeatEmitter::FRAME_CLIENT_CONNECT) {
            $beats = $this->getHeartbeats($frame);
            $this->intervalClient = $beats[0];
            $this->rememberBeat();
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

}