<?php

declare(strict_types=1);

namespace Phoundation\Geo\GeoIp;

use Phoundation\Core\Exception\ConfigurationDoesNotExistsException;
use Phoundation\Core\Log\Log;
use Phoundation\Developer\Debug;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Geo\GeoIp\Exception\GeoIpException;
use Phoundation\Utils\Config;
use Throwable;


/**
 * GeoIp class
 *
 *
 * @note See https://linklyhq.com/blog/list-of-5-free-geoip-databases-2020
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation/Geo
 */
class GeoIp
{
    /**
     * The IP for this GeoIp entry
     *
     * @var ?string $ip_address
     */
    protected ?string $ip_address = null;


    /**
     * Returns a GeoIp object for the specified IP address
     *
     * @param string|null $ip_address
     * @return static|null
     */
    public static function detect(?string $ip_address): ?static
    {
        try {
            return static::getProvider()?->detect($ip_address);

        } catch (Throwable $e) {
            throw new GeoIpException(tr('Failed to detect Geo location from IP ":ip"', [
                ':ip' => $ip_address
            ]), $e);
        }
    }


    /**
     * Returns the class for the specified provider
     *
     * @param string|null $provider
     * @return static|null
     */
    public static function getProvider(?string $provider = null): ?static
    {
        try {
            $provider = null;
            $enabled  = Config::get('geo.ip.enabled', true);
            $provider = Config::get('geo.ip.provider', null, $provider);

            if (!$enabled) {
                // GeoIP detection has been disabled
                return null;
            }

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

        } catch (ConfigurationDoesNotExistsException $e) {
            if (Debug::production()) {
                throw $e;
            }

            Log::warning($e->makeWarning());
        }

        return $provider;
    }


    /**
     * Returns the ip address for this user
     *
     * @return string|null
     */
    public function getIpAddress(): ?string
    {
        return $this->ip_address;
    }


    /**
     * Sets the ip address for this user
     *
     * @param string|null $ip_address
     * @return static
     */
    public function setIpAddress(?string $ip_address): static
    {
        $this->ip_address = $ip_address;
        return $this;
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