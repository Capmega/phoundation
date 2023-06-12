<?php

declare(strict_types=1);

namespace Phoundation\Geo\GeoIp;

use Phoundation\Core\Config;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Strings;
use Phoundation\Filesystem\File;
use Phoundation\Filesystem\Filesystem;
use Phoundation\Filesystem\Path;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Processes\Commands\Wget;
use Throwable;

/**
 * MaxMind class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation/Geo
 */
class MaxMindImport extends GeoIpImport
{
    /**
     * Import class constructor
     *
     * @param bool $demo
     * @param int|null $min
     * @param int|null $max
     */
    public function __construct(bool $demo = false, ?int $min = null, ?int $max = null)
    {
        parent::__construct($demo, $min, $max);
        $this->name = 'GeoIP / MaxMind';
    }


    /**
     * Download the GeoIP files
     *
     * @note Using this functionality requires an account on https://www.maxmind.com/
     *
     * @note Using this functionality requires that you have an API key configured on the page
     *       https://www.maxmind.com/en/accounts/YOUR_ACCOUNT_ID/license-key and configured in the configuration path
     *       geo.ip.max-mind.api-key
     *
     * @return string
     */
    public static function download(): string
    {
        $license_key = Config::getString('geo.ip.max-mind.api-key');
        $wget        = Wget::new();
        $path        = $wget->getProcess()->setTimeout(1200)->setExecutionPathToTemp()->getExecutionPath();

        Log::action(tr('Storing GeoIP files in path ":path"', [':path' => $path]));

        foreach (self::getMaxMindFiles(true) as $file => $url) {
            Log::action(tr('Downloading MaxMind URL ":url"', [':url' => $url]));

            $wget
                ->setSource(str_replace('YOUR_LICENSE_KEY', $license_key, $url))
                ->setTarget($file)
                ->execute();
        }

        return $path;
    }


    /**
     * Process downloaded GeoIP files
     *
     * @param string $source_path
     * @param string|null $target_path
     * @return string
     */
    public static function process(string $source_path, ?string $target_path = null, Restrictions|array|string|null $restrictions = null): string
    {
        if (!$restrictions) {
            $restrictions = Restrictions::new(PATH_DATA, true);
        }

        // Determine what target path to use
        $target_path = Config::getString('geo.ip.max-mind.path', PATH_DATA . 'sources/geo/ip/maxmind/', $target_path);
        $target_path = Filesystem::absolute($target_path, PATH_ROOT, false);

        Path::new($target_path, $restrictions)->ensure();
        Log::action(tr('Processing GeoIP files and moving to path ":path"', [':path' => $target_path]));

        try {
            // Clean source path GeoLite2 directories and garbage path and move the current data files to the garbage
            File::new(PATH_DATA . 'garbage/maxmind', $restrictions->addPath(PATH_DATA . 'garbage/'))->delete();
            File::new($source_path . 'GeoLite2-*', $restrictions)->delete(false, false, false);

            $previous = Path::new($target_path, $restrictions)->move(PATH_DATA . 'garbage/');
            $shas     = [];

            // Perform sha256 check on all files
            foreach (self::getMaxMindFiles(true) as $file => $url) {
                if (str_ends_with($file, 'sha256')) {
                    // Get the required sha256 code for the following file
                    $sha = File::new($source_path . $file, $restrictions)->checkReadable()->getContentsAsString();
                    $sha = Strings::until($sha, ' ');

                    $shas[Strings::until($file, '.sha256')] = $sha;
                    continue;
                }

                Log::action(tr('Processing GeoIP file ":file"', [':file' => $file]));

                // Take the downloaded file, check sha256, untar it, and move the datafile from the resulting directory
                // to the target
                $path = File::new($source_path . $file, $restrictions)
                    ->checkReadable()
                    ->checkSha256($shas[$file])
                    ->untar()
                    ->getSingleDirectory('/GeoLite2.+?/i');

                // Move the file to the target path and delete the source path
                $path->getSingleFile('/.+?.mmdb/i')->move($target_path);
                $path->delete();
            }

            // Delete the previous data files from garbage
            $previous->delete();

        } catch (Throwable $e) {
            // Something borked. Move the previous data files back from the garbage to their original path so the system
            // will remain functional
            if (isset($previous)) {
                $previous->move($target_path);
            }

            throw $e;
        }

        return $target_path;
    }


    /**
     * Returns a list of MaxMind files that will be downloaded
     *
     * @note Using this functionality requires an account on https://www.maxmind.com/
     *
     * @note The list of these files can be found on https://www.maxmind.com/en/accounts/YOUR_ACCOUNT_ID/geoip/downloads
     *
     * @param bool $return_sha_files
     * @return array
     */
    protected static function getMaxMindFiles(bool $return_sha_files): array
    {
        $files = [
            'geolite2-asn.tar.gz' => 'https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-ASN&license_key=YOUR_LICENSE_KEY&suffix=tar.gz',
            'cities.tar.gz'       => 'https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-City&license_key=YOUR_LICENSE_KEY&suffix=tar.gz',
            'countries.tar.gz'    => 'https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-Country&license_key=YOUR_LICENSE_KEY&suffix=tar.gz',
        ];

        $sha_files = [
            'geolite2-asn.tar.gz.sha256' => 'https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-ASN&license_key=YOUR_LICENSE_KEY&suffix=tar.gz.sha256',
            'cities.tar.gz.sha256'       => 'https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-City&license_key=YOUR_LICENSE_KEY&suffix=tar.gz.sha256',
            'countries.tar.gz.sha256'    => 'https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-Country&license_key=YOUR_LICENSE_KEY&suffix=tar.gz.sha256',
        ];

        if ($return_sha_files) {
            // Return the list of files AND the sha256 files
            return array_merge($sha_files, $files);
        }

        // Return only the files themselves
        return $files;
    }
}