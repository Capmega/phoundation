<?php

/**
 * MaxMind class
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation/Geo
 */


declare(strict_types=1);

namespace Phoundation\Geo\GeoIp;

use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use GeoIp2\Model\City;
use MaxMind\Db\Reader\InvalidDatabaseException;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Traits\TraitDataDirectory;
use Phoundation\Filesystem\FsDirectory;
use Phoundation\Filesystem\FsRestrictions;
use Phoundation\Filesystem\Interfaces\FsRestrictionsInterface;
use Phoundation\Network\Network;
use Phoundation\Notifications\Notification;
use Phoundation\Utils\Config;
use Throwable;


class MaxMind extends GeoIp
{
    use TraitDataDirectory;


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
        $this->directory = new FsDirectory(DIRECTORY_DATA . 'sources/geoip/maxmind/', FsRestrictions::newReadonly(DIRECTORY_DATA . 'sources/geoip/maxmind/'));
        $this->pro       = Config::getBoolean('geo.ip.maxmind.pro', false);
    }


    /**
     * Returns a GeoIp object for the specified IP address
     *
     * @param string|null $ip_address
     *
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
     *
     * @param string|null $ip_address
     *
     * @return static
     * @throws AddressNotFoundException
     * @throws InvalidDatabaseException
     */
    public function setIpAddress(?string $ip_address): static
    {
        try {
            $cityDbReader     = new Reader($this->directory . ($this->pro ? 'GeoIP2-City.mmdb' : 'GeoLite2-City.mmdb'));
            $this->ip_address = $ip_address;
            $this->record     = $cityDbReader->city($ip_address);

        } catch (InvalidDatabaseException $e) {
            // For the moment, just log the failure and continue
            Notification::new()
                        ->setUrl('developer/incidents.html')
                        ->setTitle(tr('Failed to do GeoIP lookup'))
                        ->setMessage(tr('Failed to do GeoIP lookup with the following error. Most likely, the MaxMind GeoIP data files have not yet been imported. Please refer to ":command"', [
                            ':command' => './pho geo ip import -H',
                        ]))
                        ->setException($e)
                        ->send();

        } catch (AddressNotFoundException $e) {
            if (isset($cityDbReader) and str_contains($e->getMessage(), '127.0.0.1')) {
                // THIS... IS... LOCALHOST!!!! We can't get any GeoIP data from this address.
                // Spoof the IP address, use the public IP address for this machine
                Log::warning(tr('Connection is localhost, finding public IP address of this machine to spoof IP'));

                $ip_address = Network::getPublicIpAddress();

                if ($ip_address) {
                    Log::warning(tr('Spoofing IP address with this machine public IP address ":ip"', [
                        ':ip' => $ip_address,
                    ]));

                } else {
                    // Fine, if that doesn't work then spoof the IP address by using the IP for phoundation.org
                    $ip_address = gethostbyname('phoundation.org');

                    if ($ip_address) {
                        Log::warning(tr('Unable to get public IP address, spoofing IP address with ":ip" from phoundation.org', [
                            ':ip' => $ip_address,
                        ]));

                    } else {
                        // FINE! We failed...
                        Log::warning(tr('Unable to get any public IP address for GeoIP data.'));

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

        } catch (Throwable $e) {
            if (str_contains($e->getMessage(), 'Failed to open stream: No such file or directory')) {
                // Database file does not exist, try to download it?
                Log::warning(tr('MaxMind database file ":file" was not found, maybe try running "./pho geo ip import" ?', [
                    ':file' => $this->directory . ($this->pro ? 'GeoIP2-City.mmdb' : 'GeoLite2-City.mmdb'),
                ]));
                throw $e;
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
     * Returns if this is the maxmind pro version or not
     *
     * @return bool
     */
    public function getPro(): bool
    {
        return $this->pro;
    }


    /**
     * Returns City information
     *
     * @return City|null
     */
    public function getCity(): ?City
    {
        return $this->record;
    }
}
