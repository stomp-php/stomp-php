<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Tests\Unit\Stomp;

use PHPUnit_Framework_TestCase;
use Stomp\Client;
use Stomp\SimpleStomp;
use Stomp\Protocol\Protocol;
use Stomp\Protocol\Version;
use Stomp\Transport\Frame;
use Stomp\Transport\Message;

/**
 * SimpleStompTest
 *
 * @package Stomp\Tests\Unit\Stomp
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class SimpleStompTest extends PHPUnit_Framework_TestCase
{

    public function testSendIsMappedToClient()
    {
        $queue = 'queue';
        $message = new Message('content');

        $stomp = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->setMethods(['send'])
            ->getMock();

        $stomp->expects($this->once())->method('send')->with($queue, $message);
        /**
         * @var $stomp Client
         */
        $simpleStomp = new SimpleStomp($stomp);
        $simpleStomp->send($queue, $message);
    }
    /**
     * @param $method
     * @param array $parameters
     * @param array $expectedSendFrameParameters
     * @param $result
     *
     * @dataProvider actionToProtocolProvider
     */
    public function testActionToProtocolMapping($method, array $parameters, array $expectedSendFrameParameters, $result)
    {
        $stomp = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->setMethods(['sendFrame', 'getProtocol'])
            ->getMock();

        $stomp->expects($this->any())
            ->method('getProtocol')
            ->willReturn(new Protocol('test', Version::VERSION_1_2));

        $stomp->expects($this->once())
            ->method('sendFrame')
            ->with($expectedSendFrameParameters[0], $expectedSendFrameParameters[1])
            ->willReturn($result);

        /**
         * @var $stomp Client
         */
        $client = new SimpleStomp($stomp);
        $this->assertEquals($result, call_user_func_array([$client, $method], $parameters));
    }

    public function actionToProtocolProvider()
    {
        $protocol = new Protocol('test', Version::VERSION_1_2);

        return [
            'subscribe' => [
                'subscribe',
                ['/test/queue', 55, 'auto', 'S=5',['myHeader' => 'myHeaderValue']],
                [
                    $protocol->getSubscribeFrame('/test/queue', 55, 'auto', 'S=5')
                    ->addHeaders(['myHeader' => 'myHeaderValue']),
                    null
                ],
                true
            ],
            'unsubscribe' => [
                'unsubscribe',
                ['/test/queue', 44, ['myHeader' => 'myHeaderValue']],
                [$protocol->getUnsubscribeFrame('/test/queue', 44)->addHeaders(['myHeader' => 'myHeaderValue']), null],
                true
            ],
            'begin' => [
                'begin',
                [11211],
                [$protocol->getBeginFrame(11211), false],
                true
            ],
            'commit' => [
                'commit',
                [2211],
                [$protocol->getCommitFrame(2211), false],
                true
            ],
            'abort' => [
                'abort',
                [1122],
                [$protocol->getAbortFrame(1122), false],
                true
            ],
            'ack' => [
                'ack',
                [new Frame('MESSAGE', ['id' => 121])],
                [$protocol->getAckFrame(new Frame('MESSAGE', ['id' => 121])), false],
                null
            ],
            'nack' => [
                'nack',
                [new Frame('MESSAGE', ['ack' => 212])],
                [$protocol->getNackFrame(new Frame('MESSAGE', ['ack' => 212])), false],
                null
            ]
        ];
    }
}
