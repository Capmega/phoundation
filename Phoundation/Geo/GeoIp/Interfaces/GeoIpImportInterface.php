<?php

namespace Phoundation\Geo\GeoIp\Interfaces;

use Phoundation\Filesystem\Interfaces\FsDirectoryInterface;

interface GeoIpImportInterface
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
    public static function download(): FsDirectoryInterface;

    /**
     * Process downloaded GeoIP files
     *
     * @param FsDirectoryInterface      $source_directory
     * @param FsDirectoryInterface|null $target_directory
     *
     * @return FsDirectoryInterface
     */
    public static function process(FsDirectoryInterface $source_directory, FsDirectoryInterface|null $target_directory = null): FsDirectoryInterface;
}