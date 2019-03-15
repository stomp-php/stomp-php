<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Tests\Cases\PCNTL;

use Exception;
use Stomp\Client;
use Stomp\Network\Connection;
use Stomp\StatefulStomp;

/**
 * ConsumerPCNTLTestCase a simulated long running consumer which is capable of signal handling.
 *
 * @package Stomp\Tests\Cases\PCNTL
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class ConsumerPCNTLTestCase
{
    private $stomp;
    private $stopSignalled = false;

    const MAX_RUNTIME = 4;

    /**
     * ConsumerPCNTLTestCase constructor.
     */
    public function __construct()
    {
        $this->stomp = new StatefulStomp(new Client(new Connection('tcp://127.0.0.1:61010')));
    }


    /**
     * Starts the long running consumer process.
     *
     * Will only return true if the process was stopped by a signal.
     *
     * @return bool
     */
    public function testRegisteredAndTriggeredSignalHandlerWontLeadToConnectionException()
    {
        $this->registerListeners();
        $this->stomp->subscribe('/queue/test');
        $this->stomp->getClient()->getConnection()->setReadTimeout(0, 50000);
        echo 'INFO: Started to listen for new messages...', PHP_EOL;
        $time = @time();
        while ((@time() - $time) < self::MAX_RUNTIME && (!$this->stopSignalled)) {
            try {
                pcntl_signal_dispatch();
                $this->stomp->read();
            } catch (Exception $exception) {
                echo 'FAILED: Received an unexpected exception: ', $exception->getMessage(), PHP_EOL;
                return false;
            }
        }


        if (!$this->stopSignalled) {
            echo 'FAILED: The stop signal was not received!', PHP_EOL;
            return false;
        }
        return true;
    }

    /**
     * Starts the long running consumer process, ensures that we have a long read timeout.
     *
     * Will only return true if the process was stopped by a signal and the wait callable was called, returning false.
     * Makes sure that we can inject logic that get's executed faster than the configured read timeout.
     *
     * @return bool
     */
    public function testRegisteredWaitCallableWillDirectlyReturnFromRead()
    {
        $this->registerListeners();
        $this->stomp->subscribe('/queue/test');
        $this->stomp->getClient()->getConnection()->setReadTimeout(self::MAX_RUNTIME);
        $this->stomp->getClient()->getConnection()->setWaitCallback(
            function () {
                pcntl_signal_dispatch();
                // return false when stop was signaled
                return (!$this->stopSignalled);
            }
        );
        echo 'INFO: Started to listen for new messages...', PHP_EOL;
        $time = @time();
        while ((@time() - $time) < self::MAX_RUNTIME && (!$this->stopSignalled)) {
            try {
                pcntl_signal_dispatch();
                $this->stomp->read();
            } catch (Exception $exception) {
                echo 'FAILED: Received an unexpected exception: ', $exception->getMessage(), PHP_EOL;
                return false;
            }
        }


        if (!$this->stopSignalled) {
            echo 'FAILED: The stop signal was not received!', PHP_EOL;
            return false;
        }

        if (@time() - $time > 2) {
            echo 'FAILED: The process returned after the timeout was reached!', PHP_EOL;
            return false;
        }
        return true;
    }

    private function registerListeners()
    {
        pcntl_signal(SIGUSR1, [$this, 'onSignal']);
    }


    private function onSignal()
    {
        $this->stopSignalled = true;
    }
}
