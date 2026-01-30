<?php

/**
 * Class Version
 *
 * This class can contain and manage MAJOR.MINOR.REVISION type versions
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Utils
 */


declare(strict_types=1);

namespace Phoundation\Utils;


class Version extends VersionCore
{
    /**
     * Version class constructor
     *
     * @param string $version The version to work with
     */
    public function __construct(string $version, bool $short_version = false)
    {
        $this->setSource($version, $short_version);
    }


    /**
     * Returns a new static object
     *
     * @param string $version The version to work with
     */
    public static function new(string $version, bool $short_version = false)
    {
        return new static($version, $short_version);
    }
}
