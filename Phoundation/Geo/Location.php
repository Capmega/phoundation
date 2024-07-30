<?php

/**
 * Location class
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation/Geo
 */

declare(strict_types=1);

namespace Phoundation\Geo;

class Location
{
    /**
     * The longitude for this location
     *
     * @var float $longitude
     */
    protected float $longitude;

    /**
     * The latitude for this location
     *
     * @var float $latitude
     */
    protected float $latitude;


    /**
     * Location class constructor
     *
     * @param float $longitude
     * @param float $latitude
     */
    public function __construct(float $longitude, float $latitude)
    {
        $this->longitude = $longitude;
        $this->latitude  = $latitude;
    }


    /**
     * Returns a Geo object for the specified IP address
     *
     * @param float $longitude
     * @param float $latitude
     *
     * @return static
     */
    public static function new(float $longitude, float $latitude): static
    {
        return new Location($longitude, $latitude);
    }
}