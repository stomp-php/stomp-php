<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Tests\Unit\Stomp\Protocol;

use Stomp\Protocol\Protocol;
use Stomp\Protocol\Version;

/**
 * ProtocolTest
 *
 * @package Stomp\Tests\Unit\Stomp\Protocol
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class ProtocolTest extends ProtocolTestCase
{
    public function testConnect()
    {
        $protocol = $this->getProtocol();

        $actual = $protocol->getConnectFrame();

        $this->assertIsConnectFrame($actual);
        $this->assertTrue($actual->isLegacyMode(), 'Connect Frame must be in legacy mode!');
        $this->assertNull($actual['accept-version']);
        $this->assertNull($actual['host']);
        $this->assertNull($actual['login']);
        $this->assertNull($actual['passcode']);
    }

    public function testConnectWithOptions()
    {
        $protocol = $this->getProtocol();

        $actual = $protocol->getConnectFrame(
            'my-user',
            'my-pass',
            [Version::VERSION_1_1, Version::VERSION_1_2],
            'fin-sn.de'
        );

        $this->assertIsConnectFrame($actual);
        $this->assertTrue($actual->isLegacyMode(), 'Connect Frame must be in legacy mode!');
        $this->assertEquals('1.1,1.2', $actual['accept-version']);
        $this->assertEquals('test-client-id', $actual['client-id']);
        $this->assertEquals('fin-sn.de', $actual['host']);
        $this->assertEquals('my-user', $actual['login']);
        $this->assertEquals('my-pass', $actual['passcode']);
    }


    public function testGetters()
    {
        $protocol = new Protocol('client-id', Version::VERSION_1_1, 'server-id');

        $this->assertEquals('client-id', $protocol->getClientId());
        $this->assertEquals(Version::VERSION_1_1, $protocol->getVersion());
        $this->assertEquals('server-id', $protocol->getServer());
    }

    protected function getProtocolClassFqn()
    {
        return Protocol::class;
    }
}
