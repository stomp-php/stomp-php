<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Tests\Cases\PCNTL;

use PHPUnit\Framework\TestCase;

/**
 * StreamSignalTest
 *
 * @package Stomp\Tests\Cases\PCNTL
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class StreamSignalTest extends TestCase
{
    public function signalHandlingProvider()
    {
        return [
            // https://github.com/stomp-php/stomp-php/pull/65
            'Signal Handler will not throw exception.'
                => ['signal_handling'],
            // https://github.com/stomp-php/stomp-php/issues/117
            'Signal Handler will not wait for read timeout to pass, when wait-callable returns false.'
                => ['signal_handling_wait_callable']
        ];
    }

    /**
     * @dataProvider signalHandlingProvider
     * @param string $case
     */
    public function testConsumerSignalProcessing($case)
    {
        if (!extension_loaded('pcntl')) {
            $this->markTestSkipped('The pcntl extension is required to run this test case.');
        }
        $descriptorspec = [
            0 => ["pipe", "r"],
            1 => ["pipe", "w"],
            2 => ["pipe", "w"]
        ];

        $process = proc_open(
            sprintf(
                'exec %s %s %s',
                PHP_BINARY,
                realpath(__DIR__ . '/Consumer.php'),
                escapeshellarg($case)
            ),
            $descriptorspec,
            $pipes
        );

        // give new process some time to start listening
        usleep(750000);

        $status = proc_get_status($process);

        $this->assertTrue($status['running']);
        $this->assertTrue(posix_kill($status['pid'], SIGUSR1));

        // give process some time to trigger a signal handler
        usleep(100000);


        $output = stream_get_contents($pipes[1]);
        $this->assertEmpty(stream_get_contents($pipes[2]));
        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $returnCode = proc_close($process);
        $this->assertStringContainsString('INFO: Started to listen for new messages...', $output);
        $this->assertStringContainsString('TEST: SUCCEEDED', $output);
        $this->assertEquals(0, $returnCode);
    }
}
