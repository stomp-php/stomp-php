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
    private $observer = [];

    /**
     * Adds new observers to the collection.
     *
     * @param ConnectionObserver $observer
     * @return ConnectionObserverCollection
     */
    public function addObserver(ConnectionObserver $observer)
    {
        if (!in_array($observer, $this->observer, true)) {
            $this->observer[] = $observer;
        }
        return $this;
    }

    /**
     * Removes the observers from the collection.
     *
     * @param ConnectionObserver $observer
     * @return ConnectionObserverCollection
     */
    public function removeObserver(ConnectionObserver $observer)
    {
        $index = array_search($observer, $this->observer, true);
        if ($index !== false) {
            unset($this->observer[$index]);
        }
        return $this;
    }

    /**
     * Returns the observers inside this collection.
     *
     * @return ConnectionObserver[]
     */
    public function getObserver()
    {
        return array_values($this->observer);
    }

    /**
     * Indicates that during a read call no frame was received, but an EOL line.
     *
     * @return void
     */
    public function emptyLineReceived()
    {
        foreach ($this->observer as $item) {
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
        foreach ($this->observer as $item) {
            $item->receivedFrame($frame);
        }
    }

    /**
     * Indicates that a frame has been transmitted.
     *
     * @param Frame $frame
     * @return void
     */
    public function transmittedFrame(Frame $frame)
    {
        foreach ($this->observer as $item) {
            $item->transmittedFrame($frame);
        }
    }

    /**
     * Indicates that a read was not performed as the buffer is empty.
     *
     * @return void
     */
    public function emptyBuffer()
    {
        foreach ($this->observer as $item) {
            $item->emptyBuffer();
        }
    }
}