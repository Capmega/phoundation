<?php

declare(strict_types=1);

namespace Phoundation\Geo\GeoIp;

use Phoundation\Filesystem\Interfaces\RestrictionsInterface;
use Phoundation\Filesystem\Restrictions;
use Stringable;


/**
 * GeoIpImport class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation/Geo
 */
abstract class GeoIpImport extends Import
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
     * @return Stringable|string
     */
    abstract public static function download(): Stringable|string;

    /**
     * Process downloaded GeoIP files
     *
     * @param Stringable|string $source_path
     * @param Stringable|string|null $target_path
     * @param RestrictionsInterface|array|string|null $restrictions
     * @return string
     */
    abstract public static function process(Stringable|string $source_path, Stringable|string|null $target_path = null, RestrictionsInterface|array|string|null $restrictions = null): string;
}