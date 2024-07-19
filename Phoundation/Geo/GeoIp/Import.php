<?php

/**
 * Importer class
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

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Geo\GeoIp\Interfaces\GeoIpImportInterface;
use Phoundation\Utils\Config;

class Import extends \Phoundation\Developer\Project\Import
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

        $this->name = 'GeoIP';
    }


    /**
     * Import the content for the languages table from a data-source file
     *
     * @return int
     */
    public function execute(): int
    {
        $provider  = static::getProvider();
        $directory = $provider->download();

        $provider->process($directory);

        return 0;
    }


    /**
     * Returns the class for the specified provider
     *
     * @param string|null $provider
     *
     * @return GeoIpImportInterface
     */
    public static function getProvider(?string $provider = null): GeoIpImportInterface
    {
        $provider = Config::get('geo.ip.provider', null, $provider);
        switch ($provider) {
            case 'maxmind':
                return new MaxMindImport();

            case 'ip2location':
                throw new UnderConstructionException();

            default:
                throw new OutOfBoundsException(tr('Unknown GeoIP provider ":provider" specified', [
                    ':provider' => $provider,
                ]));
        }
    }
}