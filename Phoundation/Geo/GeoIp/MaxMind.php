<?php

namespace Phoundation\Geo\GeoIp;

use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use GeoIp2\Model\City;
use MaxMind\Db\Reader\InvalidDatabaseException;
use Phoundation\Core\Config;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Traits\DataPath;
use Phoundation\Network\Network;
use Phoundation\Notifications\Notification;

/**
 * MaxMind class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation/Geo
 */
class MaxMind extends GeoIp
{
    Use DataPath;

    /**
     * The location record
     *
     * @var City|null $record
     */
    protected ?City $record = null;

    /**
     * If true, we'll be running the pro version which uses a different filename
     *
     * @var bool $pro
     */
    protected bool $pro = false;

    /**
     * MaxMind class constructor
     */
    public function __construct()
    {
        $this->path = PATH_DATA . 'sources/geo/ip/maxmind/';
        $this->pro  = Config::getBoolean('geo.ip.maxmind.pro', false);
    }


    /**
     * Returns if this is the maxmind pro version or not
     *
     * @return bool
     */
    public function getPro(): bool
    {
        return $this->pro;
    }


    /**
     * Returns a GeoIp object for the specified IP address
     *
     * @param string|null $ip_address
     * @return static
     */
    public static function detect(?string $ip_address): static
    {
        $return = new static();
        $return->setIpAddress($ip_address);

        return $return;
    }


    /**
     * Set the IP address for the max mind reader
     *
     * @note Taken code from https://github.com/maxmind/GeoIP2-php
     * @param string|null $ip_address
     * @return $this
     * @throws AddressNotFoundException
     * @throws InvalidDatabaseException
     */
    public function setIpAddress(?string $ip_address): static
    {
        try {
            $cityDbReader = new Reader($this->path . ($this->pro ? 'GeoIP2-City.mmdb' : 'GeoLite2-City.mmdb'));

            $this->ip_address = $ip_address;
            $this->record     = $cityDbReader->city($ip_address);

        } catch (InvalidDatabaseException $e) {
            // For the moment, just log the failure and continue
            Notification::new()
                ->setTitle(tr('Failed to do GeoIP lookup'))
                ->setMessage(tr('Failed to do GeoIP lookup with the following error. Most likely, the MaxMind GeoIP data files have not yet been imported. Please refer to ":command"', [
                    ':command' => './pho system geo ip import -H'
                ]))
                ->setException($e)
                ->send();

        } catch (AddressNotFoundException $e) {
            if (isset($cityDbReader) and str_contains($e->getMessage(), '127.0.0.1')) {
                // THIS... IS... LOCALHOST!!!! We can't get any GeoIP data from this address.
                // Spoof the IP address, use the public IP address for this machine
                $ip_address = Network::getPublicIpAddress();

                if ($ip_address) {
                    Log::warning(tr('Connection is localhost, spoofing IP address with public IP address ":ip" for this machine', [
                        ':ip' => $ip_address
                    ]));
                } else {
                    // Fine, if that doesn't work then spoof the IP address by using the IP for phoundation.org
                    $ip_address = gethostbyname('phoundation.org');

                    if ($ip_address) {
                        Log::warning(tr('Connection is localhost, spoofing IP address with ":ip" from phoundation.org', [
                            ':ip' => $ip_address
                        ]));
                    } else {
                        // FINE! We failed...
                        Log::warning(tr('Unable to get any GeoIP data. Connection is localhost and unable to spoof IP address'));
                        return $this;
                    }
                }

                if ($ip_address === '127.0.0.1') {
                    // Avoid endless looping over 127.0.0.1 here!
                    $ip_address = '';
                }

                $this->ip_address = $ip_address;
                $this->record     = $cityDbReader->city($ip_address);
            } else {
                // For the moment, just log the failure and continue
                Log::warning($e);
            }
        }

        return $this;

//        print($record->country->isoCode . "\n"); // 'US'
//        print($record->country->name . "\n"); // 'United States'
//        print($record->country->names['zh-CN'] . "\n"); // '美国'
//
//        print($record->mostSpecificSubdivision->name . "\n"); // 'Minnesota'
//        print($record->mostSpecificSubdivision->isoCode . "\n"); // 'MN'
//
//        print($record->city->name . "\n"); // 'Minneapolis'
//
//        print($record->postal->code . "\n"); // '55455'
//
//        print($record->location->latitude . "\n"); // 44.9733
//        print($record->location->longitude . "\n"); // -93.2323
//
//        print($record->traits->network . "\n"); // '128.101.101.101/32'
//
//        return parent::setIpAddress($ip_address); // TODO: Change the autogenerated stub
    }


    /**
     * Returns City information
     *
     * @return City|null
     */
    public function getLocation(): ?City
    {
        return $this->record;
    }
}