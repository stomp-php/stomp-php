<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Tests\Unit\Stomp;

use PHPUnit_Framework_TestCase;
use ReflectionMethod;
use Stomp\Client;
use Stomp\Protocol\Protocol;
use Stomp\Protocol\Version;
use Stomp\StatefulStomp;
use Stomp\States\ConsumerState;
use Stomp\States\ConsumerTransactionState;
use Stomp\States\Exception\InvalidStateException;
use Stomp\States\ProducerState;
use Stomp\States\ProducerTransactionState;
use Stomp\Transport\Message;

/**
 * StatefulTest
 *
 * @package Stomp\Tests\Unit\Stomp
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class StatefulTest extends PHPUnit_Framework_TestCase
{

    public function testInitialStateIsProducer()
    {
        $stateful = new StatefulStomp(new Client('tcp://127.0.0.1:1'));
        $this->assertInstanceOf(ProducerState::class, $stateful->getState());
    }

    /**
     * @param $state
     * @param array $init
     * @param array $transactions
     * @param array $disabled
     *
     * @dataProvider stateProvider
     */
    public function testTransitions($state, array $init, array $transactions, array $disabled)
    {
        $methods = $this->methodProvider();
        foreach ($transactions as $method => $targetState) {
            $disabled[] = $method;
            $stateful = $this->getStatefulStompWithState($state, $init);
            call_user_func_array([$stateful, $method], $methods[$method]);
            $this->assertInstanceOf(
                $targetState,
                $stateful->getState(),
                sprintf('Expected that invoking %s in state %s would lead to %s', $method, $state, $targetState)
            );
        }

        foreach ($methods as $method => $parameters) {
            if (in_array($method, $disabled, true)) {
                continue;
            }

            $stateful = $this->getStatefulStompWithState($state, $init);
            try {
                call_user_func_array([$stateful, $method], $parameters);
            } catch (InvalidStateException $stateFail) {
                // nothing to do...
                $this->addToAssertionCount(1);
            }
            $this->assertInstanceOf(
                $state,
                $stateful->getState(),
                sprintf('State should not have changed after invoking %s in state %s', $method, $state)
            );
        }
    }


    protected function getStatefulStompWithState($state, array $init)
    {
        $client = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->setMethods(['getProtocol', 'sendFrame', 'readFrame', 'isConnected'])
            ->getMock();

        $client->method('getProtocol')->willReturn(new Protocol('stateful-test-client', Version::VERSION_1_2));
        $client->method('sendFrame')->willReturn(true);
        $client->method('readFrame')->willReturn(new Message('read-frame'));
        $client->method('isConnected')->willReturn(true);

        /**
         * @var $client Client
         */
        $stateful = new StatefulStomp($client);
        $setState = new ReflectionMethod($stateful, 'setState');
        $setState->setAccessible(true);

        $stateInstance = new $state($client, $stateful);

        $initState = new ReflectionMethod($stateInstance, 'init');
        $initState->setAccessible(true);
        $initState->invoke($stateInstance, $init);

        $setState->invoke($stateful, $stateInstance);
        return $stateful;
    }

    /**
     * Transactions
     *
     * @return array
     */
    public function stateProvider()
    {
        return [
            ConsumerState::class => [
                // state to test
                ConsumerState::class,
                // init options
                ['destination' => 'test', 'selector' => 'test', 'ack' => 'auto', 'header' => []],
                // transactions
                [
                    'begin' => ConsumerTransactionState::class,
                    'unsubscribe' => ProducerState::class,
                ],
                // methods not to test
                ['subscribe']
            ],
            ConsumerTransactionState::class => [
                // state to test
                ConsumerTransactionState::class,
                // init options
                ['transactionId' => 5, 'destination' => 'test', 'selector' => 'test', 'ack' => 'auto', 'header' => []],
                // transactions
                [
                    'commit' => ConsumerState::class,
                    'abort' => ConsumerState::class,
                    'unsubscribe' => ProducerTransactionState::class,
                ],
                // methods not to test
                ['subscribe']
            ],
            ProducerState::class => [
                // state to test
                ProducerState::class,
                // init options
                [],
                // transactions
                [
                    'begin' => ProducerTransactionState::class,
                    'subscribe' => ConsumerState::class,
                ],
                // methods not to test
                []
            ],
            ProducerTransactionState::class => [
                // state to test
                ProducerTransactionState::class,
                // init options
                [],
                // transactions
                [
                    'subscribe' => ConsumerTransactionState::class,
                    'commit' => ProducerState::class,
                    'abort' => ProducerState::class,
                ],
                // methods not to test
                []
            ],
        ];
    }

    /**
     * State methods with parameters.
     *
     * @return array
     */
    public function methodProvider()
    {
        return [
            'ack' => [new Message('ack-msg')],
            'nack' => [new Message('nack-msg')],
            'send' => ['test', new Message('send-msg')],
            'begin' => [],
            'commit' => [],
            'abort' => [],
            'subscribe' => ['destination', 'selector', 'auto'],
            'unsubscribe' => [],
            'read' => [],
            'getSubscriptions' => [],
        ];
    }
}
