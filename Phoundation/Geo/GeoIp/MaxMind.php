<?php

namespace Phoundation\Geo\GeoIp;

use GeoIp2\WebService\Client;


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
    /**
     * Returns a GeoIp object for the specified IP address
     *
     * @param string|null $ip
     * @return static
     */
    public static function detect(?string $ip): static
    {
        $return = new GeoIp();
        $return->setIpAddress($ip);

        return $return;
    }
}

showdie('MAXMIND GEOIP');
// This creates a Client object that can be reused across requests.
// Replace "42" with your account ID and "license_key" with your license
// key. Set the "host" to "geolite.info" in the fourth argument options
// array to use the GeoLite2 web service instead of the GeoIP2 web
// service.
$client = new Client(42, 'abcdef123456');

// Replace "city" with the method corresponding to the web service that
// you are using, e.g., "country", "insights".
$record = $client->city('128.101.101.101');

print($record->country->isoCode . "\n"); // 'US'
print($record->country->name . "\n"); // 'United States'
print($record->country->names['zh-CN'] . "\n"); // '美国'

print($record->mostSpecificSubdivision->name . "\n"); // 'Minnesota'
print($record->mostSpecificSubdivision->isoCode . "\n"); // 'MN'

print($record->city->name . "\n"); // 'Minneapolis'

print($record->postal->code . "\n"); // '55455'

print($record->location->latitude . "\n"); // 44.9733
print($record->location->longitude . "\n"); // -93.2323

print($record->traits->network . "\n"); // '128.101.101.101/32'