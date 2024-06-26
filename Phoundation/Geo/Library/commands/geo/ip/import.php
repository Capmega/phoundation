<?php

declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Filesystem\FsRestrictions;
use Phoundation\Geo\GeoIp\Import;


/**
 * Command geo/ip/import
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */
CliDocumentation::setUsage('./pho geo ip import');

CliDocumentation::setHelp('This command will download and import the MaxMind geoip data files



ARGUMENTS



[-p / --provider PROVIDER]              One of "maxmind", "ip2location"

                                        maxmind     : https://dev.maxmind.com/geoip/geolite2-free-geolocation-data
                                        ip2location : https://ip2location.com/, https://lite.ip2location.com/

[-s / --source-path PATH]               If specified, this script will not download the files but use the files
                                        available in the specified PATH instead

[-t / --target-path PATH]               If specified, this script will move the Geo IP files to the specified target
                                        path instead of the default of ROOT/data/sources/geoip/max-mind/');


$argv = ArgvValidator::new()
                     ->select('-p,--provider', true)->isOptional()->hasMaxCharacters(24)->isInArray([
                                                                                                        'maxmind',
                                                                                                        'ip2location',
                                                                                                    ])
                     ->select('-s,--source_path', true)->isOptional()->isDirectory(DIRECTORY_DATA)
                     ->select('-t,--target_path', true)->isOptional()
                     ->validate();


// Get a provider
$provider = Import::getProvider($argv['provider']);


// Go!
Log::information(tr('Downloading and importing max mind Geo IP data'));

if ($argv['source_path']) {
    // Use files that are available in the specified source path
    $directory = $argv['source_path'];
} else {
    // Download the files
    $directory = $provider::download();
}


// Process the files
$provider::process($directory, $argv['target_path'], FsRestrictions::new([
                                                                           $directory->getRestrictions(),
                                                                           DIRECTORY_DATA,
                                                                       ], true));

Log::success(tr('Finished importing all GeoIP data for provider ":provider"', [
    ':provider' => $provider->getName(),
]));
