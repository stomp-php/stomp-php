<?php

/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Protocol;

use Stomp\Exception\StompException;
use Stomp\Transport\Frame;

/**
 * Stomp base protocol
 *
 *
 * @package Stomp
 * @author Hiram Chirino <hiram@hiramchirino.com>
 * @author Dejan Bosanac <dejan@nighttale.net>
 * @author Michael Caplan <mcaplan@labnet.net>
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class Protocol
{
    /**
     * Client id used for durable subscriptions
     *
     * @var string
     */
    private $clientId;

    /**
     * Stomp version
     *
     * @var string
     */
    private $version;

    /**
     * Server Version
     *
     * @var string
     */
    private $server;

    /**
     * Setup stomp protocol with configuration.
     *
     * @param string|null $clientId
     * @param string $version
     * @param string|null $server
     */
    public function __construct(?string $clientId, string $version = Version::VERSION_1_0, ?string $server = null)
    {
        $this->clientId = $clientId;
        $this->server = $server;
        $this->version = $version;
    }

    /**
     * Get the connect frame
     *
     * @param string|null $login The username credential.
     * @param string|null $passcode The password credential.
     * @param array $versions Array of versions to support.
     * @param string|null $host The host to connect to.
     * @param int[] $heartbeat Array of how often the client will send a heartbeat and will expect a heartbeat.
     * @return \Stomp\Transport\Frame The CONNECT frame.
     */
    final public function getConnectFrame(
        ?string $login = '',
        ?string $passcode = '',
        array $versions = [],
        ?string $host = null,
        array $heartbeat = [0, 0]
    ): Frame {
        $frame = $this->createFrame('CONNECT');
        $frame->legacyMode(true);

        if ($login || $passcode) {
            $frame->addHeaders(['login' => $login, 'passcode' => $passcode]);
        }

        if ($this->hasClientId()) {
            $frame['client-id'] = $this->getClientId();
        }

        if (!empty($versions)) {
            $frame['accept-version'] = implode(',', $versions);
        }

        $frame['host'] = $host;

        $frame['heart-beat'] = $heartbeat[0] . ',' . $heartbeat[1];

        return $frame;
    }

    /**
     * Get subscribe frame.
     *
     * @param string $destination The destination to subscribe to.
     * @param string|null $subscriptionId A subscription id.
     * @param string $ack The ACK selection.
     * @param string|null $selector An Sql 92 selector.
     * @return \Stomp\Transport\Frame A SUBSCRIBE frame.
     *
     * @throws \Stomp\Exception\StompException
     *
     * @see https://activemq.apache.org/selectors.html
     */
    public function getSubscribeFrame(
        string $destination,
        ?string $subscriptionId = null,
        string $ack = 'auto',
        ?string $selector = null
    ): Frame {
        // validate ACK types per spec
        // https://stomp.github.io/stomp-specification-1.0.html#frame-ACK
        // https://stomp.github.io/stomp-specification-1.1.html#ACK
        // https://stomp.github.io/stomp-specification-1.2.html#ACK
        if ($this->hasVersion(Version::VERSION_1_1)) {
            $validAcks = ['auto', 'client', 'client-individual'];
        } else {
            $validAcks = ['auto', 'client'];
        }
        if (!in_array($ack, $validAcks)) {
            throw new StompException(
                sprintf(
                    '"%s" is not a valid ack value for STOMP %s. A valid value is one of %s',
                    $ack,
                    $this->version,
                    implode(',', $validAcks)
                )
            );
        }
        
        $frame = $this->createFrame('SUBSCRIBE');

        $frame['destination'] = $destination;
        $frame['ack'] = $ack;
        $frame['id'] = $subscriptionId;
        $frame['selector'] = $selector;
        return $frame;
    }

    /**
     * Get unsubscribe frame.
     *
     * @param string $destination The destination to unsubscribe from.
     * @param string|null $subscriptionId The subscription id to unsubscribe from.
     * @return \Stomp\Transport\Frame The UNSUBSCRIBE frame.
     */
    public function getUnsubscribeFrame(string $destination, ?string $subscriptionId = null): Frame
    {
        $frame = $this->createFrame('UNSUBSCRIBE');
        $frame['destination'] = $destination;
        $frame['id'] = $subscriptionId;
        return $frame;
    }

    /**
     * Get transaction begin frame.
     *
     * @param string|null $transactionId The transaction ID.
     * @return \Stomp\Transport\Frame The BEGIN frame.
     */
    public function getBeginFrame(?string $transactionId = null): Frame
    {
        $frame = $this->createFrame('BEGIN');
        $frame['transaction'] = $transactionId;
        return $frame;
    }

    /**
     * Get transaction commit frame.
     *
     * @param string|null $transactionId The transaction ID.
     * @return \Stomp\Transport\Frame The COMMIT frame.
     */
    public function getCommitFrame(?string $transactionId = null): Frame
    {
        $frame = $this->createFrame('COMMIT');
        $frame['transaction'] = $transactionId;
        return $frame;
    }

    /**
     * Get transaction abort frame.
     *
     * @param string|null $transactionId The transaction ID.
     * @return \Stomp\Transport\Frame The ABORT frame.
     */
    public function getAbortFrame(?string $transactionId = null): Frame
    {
        $frame = $this->createFrame('ABORT');
        $frame['transaction'] = $transactionId;
        return $frame;
    }

    /**
     * Get message acknowledge frame.
     *
     * @param Frame $frame The frame to acknowledge.
     * @param string|null $transactionId The transaction ID (if any).
     * @return Frame The ACK frame.
     */
    public function getAckFrame(Frame $frame, ?string $transactionId = null): Frame
    {
        $ack = $this->createFrame('ACK');
        $ack['transaction'] = $transactionId;
        if ($this->hasVersion(Version::VERSION_1_2)) {
            $ack['id'] = $frame['id'] ?: $frame->getMessageId();
        } else {
            $ack['message-id'] = $frame['message-id'] ?: $frame->getMessageId();
            if ($this->hasVersion(Version::VERSION_1_1)) {
                $ack['subscription'] = $frame['subscription'];
            }
        }
        return $ack;
    }

    /**
     * Get message not acknowledge frame.
     *
     * @param \Stomp\Transport\Frame $frame The frame to not acknowledge.
     * @param string|null $transactionId The transaction ID (if any).
     * @param bool|null $requeue Requeue header (if any)
     * @return \Stomp\Transport\Frame The NACK frame.
     * @throws StompException If protocol using Stomp version 1.0
     * @throws \LogicException If requeue header specified (RabbitMQ specific NOT Stomp).
     */
    public function getNackFrame(Frame $frame, ?string $transactionId = null, ?bool $requeue = null): Frame
    {
        if ($requeue !== null) {
            throw new \LogicException('requeue header not supported');
        }
        if ($this->version === Version::VERSION_1_0) {
            throw new StompException('Stomp Version 1.0 has no support for NACK Frames.');
        }
        $nack = $this->createFrame('NACK');
        $nack['transaction'] = $transactionId;
        if ($this->hasVersion(Version::VERSION_1_2)) {
            $nack['id'] = $frame['id'] ?: $frame->getMessageId();
        } else {
            $nack['message-id'] = $frame['message-id'] ?: $frame->getMessageId();
            if ($this->hasVersion(Version::VERSION_1_1)) {
                $nack['subscription'] = $frame['subscription'];
            }
        }

        return $nack;
    }

    /**
     * Get the disconnect frame.
     *
     * @return \Stomp\Transport\Frame The DISCONNECT frame.
     */
    public function getDisconnectFrame(): Frame
    {
        $frame = $this->createFrame('DISCONNECT');
        if ($this->hasClientId()) {
            $frame['client-id'] = $this->getClientId();
        }
        return $frame;
    }

    /**
     * Client Id is set
     *
     * @return boolean
     */
    public function hasClientId(): bool
    {
        return (boolean) $this->clientId;
    }

    /**
     * Get Client Id.
     *
     * @return string The client Id.
     */
    public function getClientId(): ?string
    {
        return $this->clientId;
    }

    /**
     * Stomp Version
     *
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * Server Version Info
     *
     * @return string
     */
    public function getServer(): string
    {
        return $this->server;
    }

    /**
     * Checks if given version is included (equal or lower) in active protocol version.
     *
     * @param string $version
     * @return bool
     */
    public function hasVersion(string $version): bool
    {
        return version_compare($this->version, $version, '>=');
    }

    /**
     * Creates a Frame according to the detected STOMP version.
     *
     * @param string $command The frame command.
     * @return Frame The frame.
     */
    protected function createFrame(string $command): Frame
    {
        $frame = new Frame($command);

        if ($this->version === Version::VERSION_1_0) {
            $frame->legacyMode(true);
        }

        return $frame;
    }
}
