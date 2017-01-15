<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Tests\Unit\Stomp\Network\Mocks;

/**
 * FakeStream allows to simulate a stream.
 *
 * @package Stomp\Tests\Unit\Stomp\Network\Mocks
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 * @codingStandardsIgnoreFile
 */
class FakeStream
{
    /**
     * @var resource
     */
    public $context;

    /**
     * @var resource real dirty way to trick the stream_cast() call.
     */
    private $resource;

    /**
     * Data which should be presented to the stream.
     *
     * @var string
     */
    public static $serverSend = '';

    /**
     * Data which has been received by the stream.
     *
     * @var string
     */
    public static $clientSend = '';

    /**
     *
     * @see streamWrapper::stream_open()
     *
     * @param string $path
     * @param string $mode
     * @param int $options
     * @param string $opened_path
     * @return bool
     */
    public function stream_open($path, $mode, $options, &$opened_path)
    {
        $this->resource = fopen(__FILE__, 'r');
        return true;
    }

    /**
     * @see streamWrapper::stream_read()
     *
     * @param int $count
     * @return string
     */
    public function stream_read($count)
    {
        $data = substr(self::$serverSend, 0, $count);
        self::$serverSend = substr(self::$serverSend, strlen($data));
        return $data;
    }

    /**
     * @see streamWrapper::stream_write()
     *
     * @param string $data
     * @return int
     */
    public function stream_write($data)
    {
        self::$clientSend .= $data;
        return strlen($data);
    }

    /**
     * @see streamWrapper::stream_write()
     *
     * @return bool
     */
    public function stream_eof()
    {
        return strlen(self::$serverSend) == 0;
    }

    /**
     * @see streamWrapper::stream_close()
     *
     * @return void
     */
    public function stream_close()
    {
        fclose($this->resource);
    }

    /**
     * @see streamWrapper::stream_stat()
     *
     * @return array
     */
    public function stream_stat()
    {
        $stat = [
            'dev' => 0,
            'ino' => 0,
            'mode' => 0,
            'nlink' => 0,
            'uid' => 0,
            'gid' => 0,
            'rdev' => 0,
            'size' => 0,
            'atime' => time(),
            'mtime' => time(),
            'ctime' => time(),
            'blksize' => -1,
            'blocks' => -1,
        ];
        return array_values($stat);
    }

    /**
     * @see streamWrapper::url_stat()
     *
     * @return array
     */
    public function url_stat()
    {
        $stat = [
            'dev' => 0,
            'ino' => 0,
            'mode' => 0,
            'nlink' => 0,
            'uid' => 0,
            'gid' => 0,
            'rdev' => 0,
            'size' => 0,
            'atime' => time(),
            'mtime' => time(),
            'ctime' => time(),
            'blksize' => -1,
            'blocks' => -1,
        ];
        return array_values($stat);
    }

    /**
     * @param $cast_as
     * @return bool
     */
    public function stream_cast($cast_as)
    {
        return $this->resource;
    }
}
