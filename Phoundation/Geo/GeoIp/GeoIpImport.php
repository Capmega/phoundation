<?php

/**
 * GeoIpImport class
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

use Phoundation\Filesystem\Interfaces\FsDirectoryInterface;
use Phoundation\Geo\GeoIp\Interfaces\GeoIpImportInterface;

abstract class GeoIpImport extends Import implements GeoIpImportInterface
{
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
    abstract public static function download(): FsDirectoryInterface;


    /**
     * Process downloaded GeoIP files
     *
     * @param FsDirectoryInterface      $source_directory
     * @param FsDirectoryInterface|null $target_directory
     *
     * @return FsDirectoryInterface
     */
    abstract public static function process(FsDirectoryInterface $source_directory, FsDirectoryInterface|null $target_directory = null): FsDirectoryInterface;
}
