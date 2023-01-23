<?php

namespace Phoundation\Developer;

use Phoundation\Core\Strings;



/**
 * Class TestDataGenerator
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Developer
 */
class TestDataGenerator
{
    /**
     * Returns a random code
     *
     * @return string
     */
    public static function code(int $min = 3, int $max = 6): string
    {
        return Strings::random(mt_rand(3, 6));
    }



    /**
     * Returns a random number
     *
     * @return int
     */
    public static function number(int $min = 0, int $max = 1000000): int
    {
        return (int) Strings::random(mt_rand($min, $max));
    }



    /**
     * Returns a random percentage
     *
     * @return int
     */
    public static function percentage(): int
    {
        return (int) Strings::random(mt_rand(0, 100));
    }



    /**
     * Returns a random name
     *
     * @return string
     */
    public static function name(): string
    {
        return Strings::random(mt_rand(3, 10));
    }



    /**
     * Returns a random domain
     *
     * @return string
     */
    public static function domain(): string
    {
        return Strings::random(mt_rand(3,16) . '.' . pick_random('com', 'org', 'net', 'info'));
    }



    /**
     * Returns a random email address
     *
     * @return string
     */
    public static function email(): string
    {
        return self::name() . '@' . self::domain();
    }
}