<?php

namespace Phoundation\Geo\GeoIp\Interfaces;

use Phoundation\Filesystem\Interfaces\PhoDirectoryInterface;

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
     * @return PhoDirectoryInterface
     */
    public static function download(): PhoDirectoryInterface;

    /**
     * Process downloaded GeoIP files
     *
     * @param PhoDirectoryInterface      $source_directory
     * @param PhoDirectoryInterface|null $target_directory
     *
     * @return PhoDirectoryInterface
     */
    public static function process(PhoDirectoryInterface $source_directory, PhoDirectoryInterface|null $target_directory = null): PhoDirectoryInterface;
}
