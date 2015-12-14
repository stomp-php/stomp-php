<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Util;

/**
 * IdGenerator generates Ids which are unique during the runtime scope.
 *
 * @package Stomp\Util
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class IdGenerator
{
    /**
     * @var array
     */
    private static $generatedIds = [];

    /**
     * Generate a not used id.
     *
     * @return int
     */
    public static function generateId()
    {
        while ($rand = rand(1, PHP_INT_MAX)) {
            if (!in_array($rand, static::$generatedIds, true)) {
                static::$generatedIds[] = $rand;
                return $rand;
            }
        }
    }

    /**
     * Removes a previous generated id from currently used ids.
     *
     * @param int $generatedId
     */
    public static function releaseId($generatedId)
    {
        $index = array_search($generatedId, static::$generatedIds, true);
        if ($index !== false) {
            unset(static::$generatedIds[$index]);
        }
    }
}
