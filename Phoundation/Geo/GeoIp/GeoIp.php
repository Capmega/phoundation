<?php

namespace Phoundation\Geo\GeoIp;

use Phoundation\Core\Config;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\UnderConstructionException;



/**
 * GeoIp class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation/Geo
 */
class GeoIp extends DataEntry
{
    /**
     * The IP for this GeoIp entry
     *
     * @var ?string $ip
     */
    protected ?string $ip_address = null;



    /**
     * Returns a GeoIp object for the specified IP address
     *
     * @param string|null $ip
     * @return static
     */
    public static function detect(?string $ip): static
    {
        $return = self::getProvider();
        $return->setIpAddress($ip);

        return $return;
    }


    /**
     * Returns the class for the specified provider
     *
     * @param string|null $provider
     * @return static
     */
    public static function getProvider(?string $provider = null): static
    {
        $provider = Config::get('geo.ip.provider', null, $provider);

        switch ($provider) {
            case 'maxmind':
                return new MaxMind();

            case 'ip2location':
                throw new UnderConstructionException();

            default:
                throw new OutOfBoundsException(tr('Unknown GeoIP provider ":provider" specified', [
                    ':provider' => $provider
                ]));
        }
    }



    /**
     * Returns the ip address for this user
     *
     * @return string|null
     */
    public function getIpAddress(): ?string
    {
        return $this->getDataValue('ip_address');
    }



    /**
     * Sets the ip address for this user
     *
     * @param string|null $ip_address
     * @return static
     */
    public function setIpAddress(string|null $ip_address): static
    {
        return $this->setDataValue('ip_address', $ip_address);
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