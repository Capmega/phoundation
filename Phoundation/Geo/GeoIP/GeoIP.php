<?php



/**
 * GeoIP class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation/Geo
 */
class GeoIP
{
    /**
     * GeoIP class constructor
     *
     * @param string|null $ip
     */
    public function __construct(?string $ip = null)
    {
        if ($ip === null) {
            // Default to remote IP
            $ip = $_SERVER['REMOTE_ADDR'];
        }
    }



    /**
     * Returns a new GeoIP object
     *
     * @param string|null $ip
     * @return static
     */
    public static function new(?string $ip = null): static
    {
        return new static($ip);
    }



    /**
     * Returns true if the IP is European
     *
     * @return bool
     */
    public function isEuropean(): bool
    {

    }
}