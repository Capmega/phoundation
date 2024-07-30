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

use Phoundation\Core\Log\Log;
use Phoundation\Filesystem\FsDirectory;
use Phoundation\Filesystem\FsFile;
use Phoundation\Filesystem\Interfaces\FsDirectoryInterface;
use Phoundation\Filesystem\Interfaces\FsRestrictionsInterface;
use Phoundation\Filesystem\FsPath;
use Phoundation\Filesystem\FsRestrictions;
use Phoundation\Os\Processes\Commands\Wget;
use Phoundation\Utils\Config;
use Phoundation\Utils\Strings;
use Stringable;
use Throwable;

class MaxMindImport extends GeoIpImport
{
    /**
     * Import class constructor
     *
     * @param bool     $demo
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
     * @return FsDirectoryInterface
     */
    public static function download(): FsDirectoryInterface
    {
        $license_key = Config::getString('geo.ip.max-mind.api-key');
        $wget        = Wget::new();
        $directory   = $wget->setTimeout(1200)
                            ->setExecutionDirectoryToTemp()
                            ->getExecutionDirectory();

        Log::action(tr('Storing GeoIP files in directory ":directory"', [':directory' => $directory]));

        foreach (static::getMaxMindFiles(true) as $file => $url) {
            $url = str_replace('YOUR_LICENSE_KEY', $license_key, $url);

            Log::action(tr('Downloading MaxMind URL ":url"', [':url' => $url]));

            $wget->setSource($url)
                 ->setTarget($file)
                 ->execute();
        }

        return $directory;
    }


    /**
     * Returns a list of MaxMind files that will be downloaded
     *
     * @note Using this functionality requires an account on https://www.maxmind.com/
     *
     * @note The list of these files can be found on https://www.maxmind.com/en/accounts/YOUR_ACCOUNT_ID/geoip/downloads
     *
     * @param bool $return_sha_files
     *
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


    /**
     * Process downloaded GeoIP files
     *
     * @param FsDirectoryInterface      $source_directory
     * @param FsDirectoryInterface|null $target_directory
     *
     * @return FsDirectoryInterface
     */
    public static function process(FsDirectoryInterface $source_directory, FsDirectoryInterface|null $target_directory = null): FsDirectoryInterface
    {
        // Determine what target path to use
        if (empty($target_directory)) {
            $configured       = Config::getString('geo.ip.max-mind.path', DIRECTORY_DATA . 'sources/geoip/maxmind/');
            $configured       = FsDirectory::absolutePath($configured, DIRECTORY_ROOT, false);
            $target_directory = FsDirectory::new($configured, FsRestrictions::getWritable($configured));
        }

        $target_directory->ensure();
        $target_directory->getRestrictions()->addDirectory(DIRECTORY_DATA . 'garbage/', true);

        Log::action(tr('Processing GeoIP files and moving to directory ":directory"', [
            ':directory' => $target_directory
        ]));

        try {
            // Clean source path GeoLite2 directories and garbage path and move the current data files to the garbage
            FsFile::new(DIRECTORY_DATA . 'garbage/maxmind', FsRestrictions::getData(true))->delete();
            FsFile::new(
                $source_directory . 'GeoLite2-*',
                $source_directory->getRestrictions()
            )->delete(false, false, false);

            $garbage  = clone $target_directory;
            $original = clone $target_directory;
show('aaaaaaaaaaaaaaaaaaaaaa');
show($garbage->getSource());
            $garbage->movePath(DIRECTORY_DATA . 'garbage/');
show($garbage->getSource());
            $shas     = [];

            // Perform sha256 check on all files
            foreach (static::getMaxMindFiles(true) as $file => $url) {
                if (str_ends_with($file, 'sha256')) {
                    // Get the required sha256 code for the following file
                    $sha = FsFile::new($source_directory . $file, $source_directory->getRestrictions())
                                 ->checkReadable()
                                 ->getContentsAsString();

                    $sha = Strings::until($sha, ' ');
                    $shas[Strings::until($file, '.sha256')] = $sha;
                    continue;
                }

                Log::action(tr('Processing GeoIP file ":file"', [':file' => $file]));

                // Take the downloaded file, check sha256, untar it, and move the datafile from the resulting directory
                // to the target
                $directory = FsFile::new($source_directory . $file, $source_directory->getRestrictions())
                                   ->checkReadable()
                                   ->checkSha256($shas[$file])
                                   ->untar()
                                   ->getSingleDirectory('/GeoLite2.+?/i');

                // Move the file to the target path and delete the source path
                $source = clone $directory;
show('zzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzz');
                $directory->getRestrictions()->addRestrictions($target_directory->getRestrictions());
                $directory->getSingleFile('/.+?.mmdb/i')->movePath($target_directory);
                $source->delete();
            }

            // Delete the previous data files from garbage
            $garbage->delete();

        } catch (Throwable $e) {
            Log::error(tr('Failed MaxMindImport->process() with following exception. Moving original files back in place'));
            Log::exception($e);

            // Something borked. Move the previous data files back from the garbage to their original path so the system
            // will remain functional
            if (isset($garbage) and isset($original)) {
                $original->delete();
                $garbage->movePath($original);
            }

            throw $e;
        }

        return $target_directory;
    }
}
