<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Network\Observer;

use Stomp\Transport\Frame;

/**
 * AbstractBeats base for heart beat observer.
 *
 * @package Stomp\Network\Observer
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
abstract class AbstractBeats implements ConnectionObserver
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
     * The beat interval that the client wants to use.
     *
     * @var integer
     */
    protected $intervalClient;
    /**
     * The beat interval that the server wants to use.
     *
     * @var integer
     */
    protected $intervalServer;
    /**
     * The timestamp that is known as the last time a beat was detected/issued.
     *
     * @var float
     */
    private $lastbeat;

    /**
     * The interval (seconds with microseconds as fraction) that will be used to detect a delay.
     *
     * @var float
     */
    private $intervalUsed;

    /**
     * Whenever the emitter is configured to send beats.
     *
     * @var bool
     */
    private $enabled = false;

    /**
     * A frame with heartbeat details was detected.
     *
     * Child class should set client or server interval property.
     *
     * @see $intervalClient
     * @see $intervalServer
     *
     * @param Frame $frame
     * @param array $beats
     * @return void
     */
    abstract protected function onHeartbeatFrame(Frame $frame, array $beats);

    /**
     * Called whenever the server send data.
     *
     * @return void
     */
    abstract protected function onServerActivity();

    /**
     * Must return the interval (ms) that should be used to detect a delay.
     *
     * @param integer $maximum agreement from client and server in milliseconds
     * @return float
     */
    abstract protected function calculateInterval($maximum);

    /**
     * Called whenever a activity is detected that was issued by the client.
     *
     * @return void
     */
    abstract protected function onClientActivity();

    /**
     * Something on the connection state could have changed.
     *
     * @return void
     */
    abstract protected function onPotentialConnectionStateActivity();

    /**
     * Delay was detected.
     *
     * @return void
     */
    abstract protected function onDelay();

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
     * Returns if the emitter is in a state that indicates a delay.
     *
     * @return bool
     */
    public function isDelayed()
    {
        if ($this->enabled && $this->lastbeat) {
            $now = microtime(true);
            return ($now - $this->lastbeat > $this->intervalUsed);
        }
        return false;
    }

    /**
     * Returns the calculated interval for beats in seconds (with micro fraction).
     *
     * @return null|float
     */
    public function getInterval()
    {
        return $this->intervalUsed;
    }

    /**
     * Check if there is a delay and issue follow up tasks if so.
     *
     * @return void
     */
    protected function checkDelayed()
    {
        if ($this->isDelayed()) {
            $this->onDelay();
        }
    }

    /**
     * Outgoing activity event.
     *
     * @return void
     */
    protected function rememberActivity()
    {
        $this->lastbeat = microtime(true);
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
     * Enables the delay detection when preconditions are fulfilled.
     *
     * @param Frame $frame
     * @return void
     */
    private function enable(Frame $frame)
    {
        $this->onHeartbeatFrame($frame, $this->getHeartbeats($frame));
        if ($this->intervalServer && $this->intervalClient) {
            $intervalAgreement = $this->calculateInterval(max($this->intervalClient, $this->intervalServer));
            $this->intervalUsed = $intervalAgreement / 1000; // milli to micro
            if ($intervalAgreement) {
                $this->enabled = true;
                $this->rememberActivity();
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function receivedFrame(Frame $frame)
    {
        if ($this->enabled) {
            $this->onServerActivity();
            return;
        }

        if ($frame->getCommand() === self::FRAME_SERVER_CONNECTED) {
            $this->enable($frame);
        }
    }

    /**
     * @inheritdoc
     */
    public function sentFrame(Frame $frame)
    {
        if ($this->enabled) {
            $this->onClientActivity();
            return;
        }
        if ($frame->getCommand() === self::FRAME_CLIENT_CONNECT) {
            $this->enable($frame);
        }
    }

    /**
     * @inheritdoc
     */
    public function emptyLineReceived()
    {
        $this->onServerActivity();
    }

    /**
     * @inheritdoc
     */
    public function emptyRead()
    {
        $this->onPotentialConnectionStateActivity();
    }

    /**
     * @inheritdoc
     */
    public function emptyBuffer()
    {
        $this->onServerActivity();
    }
}
