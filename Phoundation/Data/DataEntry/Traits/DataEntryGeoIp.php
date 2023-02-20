<?php

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Geo\GeoIp\GeoIp;


/**
 * Trait DataEntryGeoIp
 *
 * This trait contains methods for DataEntry objects that require GEO data (country, state and city)
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryGeoIp
{
    use DataEntryGeo;
    use DataEntryLongLat;



    /**
     * A GeoIP object
     *
     * @var GeoIp|null
     */
    protected ?GeoIp $geo_ip = null;



    /**
     * Set a
     *
     * @param GeoIp $geo_ip
     * @return $this
     */
    public function setGeoIp(GeoIp $geo_ip): static
    {
        $this->geo_ip = $geo_ip;
    }
}