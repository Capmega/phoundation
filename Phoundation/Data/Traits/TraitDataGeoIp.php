<?php

/**
 * Trait TraitDataEntryGeoIp
 *
 * This trait contains methods for DataEntry objects that require GEO data (country, state and city)
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Data\DataEntries\Traits\TraitDataEntryGeo;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryLongLat;
use Phoundation\Geo\GeoIp\GeoIp;
use Phoundation\Geo\GeoIp\Interfaces\GeoIpInterface;


trait TraitDataGeoIp
{
    use TraitDataEntryGeo;
    use TraitDataEntryLongLat;


    /**
     * A GeoIP object
     *
     * @var GeoIpInterface|null
     */
    protected ?GeoIpInterface $geo_ip = null;


    /**
     * Returns the GeoIP object
     *
     * @return GeoIpInterface|null
     */
    public function getGeoIpObject(): ?GeoIpInterface
    {
        return $this->geo_ip;
    }


    /**
     * Set GeoIP data
     *
     * @param GeoIpInterface|null $geo_ip
     *
     * @return static
     */
    public function setGeoIpObject(?GeoIpInterface $geo_ip): static
    {
        $this->setLatitude($geo_ip?->getCity()?->location->latitude)
            ->setLongitude($geo_ip?->getCity()?->location->longitude)
            ->geo_ip = $geo_ip;

        return $this;
    }
}
