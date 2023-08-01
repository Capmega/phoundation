<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Data\DataEntry\Traits\DataEntryGeo;
use Phoundation\Data\DataEntry\Traits\DataEntryLongLat;
use Phoundation\Geo\GeoIp\GeoIp;


/**
 * Trait DataEntryGeoIp
 *
 * This trait contains methods for DataEntry objects that require GEO data (country, state and city)
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataGeoIp
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
     * Set GeoIP data
     *
     * @param GeoIp|null $geo_ip
     * @return $this
     */
    public function setGeoIp(?GeoIp $geo_ip): static
    {
        $this->geo_ip = $geo_ip;

        if ($geo_ip) {
            $this->setLatitude($geo_ip->getLocation()->location->latitude);
            $this->setLongitude($geo_ip->getLocation()->location->longitude);
        }

        return $this;
    }
}