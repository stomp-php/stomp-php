<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Network\Observer;

use Stomp\Exception\ConnectionException;
use Stomp\Network\Connection;
use Stomp\Network\Observer\Exception\HeartbeatException;
use Stomp\Transport\Frame;

/**
 * HeartbeatEmitter a very basic heartbeat emitter that sends beats from client side.
 *
 * Use this if you can guarantee that your client processes workloads within a given interval. This allows the server to
 * detect that your client is down when it fails sending heartbeats, your client will also fail with exception when the
 * server is not longer receiving heartbeats.
 *
 * If your client needs a unknown runtime to process Messages you should check ServerAliveObserver.
 *
 * @example $client->setHeartbeat(2000, 0); // indicate that we would send beats within a 2 second interval
 *          $emitter = new HeartbeatEmitter($client->getConnection());
 *          $client->getConnection()->getObservers()->addObserver($emitter);
 *
 * @see ServerAliveObserver
 * @package Stomp\Network\Observer\Heartbeat
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class HeartbeatEmitter extends AbstractBeats
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * Defines the percentage amount of the calculated interval that will be used without emitting a beat.
     *
     * @var float
     */
    private $intervalUsage;

    /**
     * Enables the pessimistic mode of the emitter, causing alive messages when we receive nothing from the socket.
     *
     * @var bool
     */
    private $pessimistic = false;

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
     * Enables the pessimistic mode.
     *
     * @param bool $pessimistic
     */
    public function setPessimistic($pessimistic)
    {
        $this->pessimistic = $pessimistic;
    }

    /**
     * Called whenever the server send data.
     *
     * @return void
     */
    protected function onServerActivity()
    {
        $this->checkDelayed();
    }

    /**
     * A frame with heartbeat details was detected.
     *
     * Class should set client or server interval.
     *
     * @param Frame $frame
     * @param array $beats
     * @return void
     */
    protected function onHeartbeatFrame(Frame $frame, array $beats)
    {
        if ($frame->getCommand() === self::FRAME_SERVER_CONNECTED) {
            $this->intervalServer = $beats[1];
            if ($this->intervalClient === null) {
                $this->intervalClient = $this->intervalServer;
            }
        } else {
            $this->intervalClient = $beats[0];
            $this->rememberActivity();
        }
    }

    /**
     * Must return the interval (ms) that should be used to detect a delay.
     *
     * @param integer $maximum
     * @return float
     */
    protected function calculateInterval($maximum)
    {
        $intervalUsed = $maximum * $this->intervalUsage;
        $this->assertReadTimeoutSufficient($intervalUsed);
        return $intervalUsed;
    }

    /**
     * Verify that the client configured heartbeats don't conflict with the connection read timeout.
     *
     * @param float $interval
     * @return void
     */
    private function assertReadTimeoutSufficient($interval)
    {
        $readTimeout = $this->connection->getReadTimeout();
        $readTimeoutMs = ($readTimeout[0] * 1000) + ($readTimeout[1] / 1000);

        if ($interval < $readTimeoutMs) {
            throw new HeartbeatException(
                'Client heartbeat is lower than connection read timeout, causing failing heartbeats.'
            );
        }
    }

    /**
     * Called whenever a activity is detected that was issued by the client.
     *
     * @return void
     */
    protected function onClientActivity()
    {
        $this->rememberActivity();
    }

    /**
     * @inheritdoc
     */
    protected function onPotentialConnectionStateActivity()
    {
        if ($this->pessimistic && $this->isEnabled()) {
            $this->onDelay();
        } else {
            $this->checkDelayed();
        }
    }

    /**
     * Send a beat to the server.
     *
     * @return void
     */
    protected function onDelay()
    {
        try {
            $this->connection->sendAlive($this->intervalClient / 1000);
        } catch (ConnectionException $e) {
            throw new HeartbeatException('Could not send heartbeat to server.', $e);
        }
        $this->rememberActivity();
    }
}
