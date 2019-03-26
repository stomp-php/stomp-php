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
 * ConnectionObserverCollection a collection of connection observers.
 *
 * @package Stomp\Network\Observer
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class ConnectionObserverCollection implements ConnectionObserver
{
    /**
     * @var ConnectionObserver[]
     */
    private $observers = [];

    /**
     * Adds new observers to the collection.
     *
     * @param ConnectionObserver $observer
     * @return ConnectionObserverCollection this collection
     */
    public function addObserver(ConnectionObserver $observer)
    {
        if (!in_array($observer, $this->observers, true)) {
            $this->observers[] = $observer;
        }
        return $this;
    }

    /**
     * Removes the observers from the collection.
     *
     * @param ConnectionObserver $observer
     * @return ConnectionObserverCollection this collection
     */
    public function removeObserver(ConnectionObserver $observer)
    {
        $index = array_search($observer, $this->observers, true);
        if ($index !== false) {
            unset($this->observers[$index]);
        }
        return $this;
    }

    /**
     * Returns the observers inside this collection.
     *
     * @return ConnectionObserver[]
     */
    public function getObservers()
    {
        return array_values($this->observers);
    }

    /**
     * Indicates that during a read call no frame was received, but an EOL line.
     *
     * @return void
     */
    public function emptyLineReceived()
    {
        foreach ($this->observers as $item) {
            $item->emptyLineReceived();
        }
    }

    /**
     * Indicates that a frame has been received.
     *
     * @param Frame $frame that has been received
     * @return void
     */
    public function receivedFrame(Frame $frame)
    {
        foreach ($this->observers as $item) {
            $item->receivedFrame($frame);
        }
    }

    /**
     * Indicates that a frame has been transmitted.
     *
     * @param Frame $frame
     * @return void
     */
    public function sentFrame(Frame $frame)
    {
        foreach ($this->observers as $item) {
            $item->sentFrame($frame);
        }
    }

    /**
     * Indicates that the connection has no pending data.
     *
     * @return void
     */
    public function emptyBuffer()
    {
        foreach ($this->observers as $item) {
            $item->emptyBuffer();
        }
    }

    /**
     * Indicates that the connection tried to read signaled data, but no data was returned.
     *
     * @return void
     */
    public function emptyRead()
    {
        foreach ($this->observers as $item) {
            $item->emptyRead();
        }
    }
}
