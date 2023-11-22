<?php

declare(strict_types=1);

namespace Phoundation\Core\Libraries;

use Phoundation\Data\Validator\Validate;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Strings;


/**
 * Version class
 *
 * This class manages library versions
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Developer
 */
class Version
{
    /**
     * Returns a string version of the integer version
     *
     * @param ?int $version
     * @return string
     */
    public static function getString(?int $version): string
    {
        $version = (int) $version;

        if ($version < 0) {
            return match ($version) {
                -1 => 'post_once',
                -2 => 'post_always',
                default => throw new OutOfBoundsException(tr('Invalid version string ":version" specified', [
                        ':version' => $version
                    ]))
            };
        }

        $major    = floor($version / 1000000);
        $minor    = floor(($version - ($major * 1000000)) / 1000);
        $revision = fmod($version, 1000);

        if ($major > 999) {
            throw new OutOfBoundsException(tr('The major of version ":version" cannot be greater than "999"', [
                ':version' => $version
            ]));
        }

        if ($minor > 999) {
            throw new OutOfBoundsException(tr('The minor of version ":version" cannot be greater than "999"', [
                ':version' => $version
            ]));
        }

        if ($revision > 999) {
            throw new OutOfBoundsException(tr('The revision of version ":version" cannot be greater than "999"', [
                ':version' => $version
            ]));
        }

        return $major . '.' . $minor . '.' . $revision;
    }


    /**
     * Returns an integer version of the string version
     *
     * @param string $version
     * @return int
     */
    public static function getInteger(string $version): int
    {
        switch ($version) {
            case 'post_once':
                return -1;

            case 'post_always':
                return -2;
        }

        if (!Strings::isVersion($version)) {
            throw new OutOfBoundsException(tr('Specified version ":version" is not valid, should be of format "\d{1,4}.\d{1,4}.\d{1,4}"', [
                ':version' => $version
            ]));
        }

        $major    = (int) Strings::until($version, '.') * 1000000;
        $minor    = (int) Strings::until(Strings::from($version, '.'), '.') * 1000;
        $revision = (int) Strings::fromReverse($version, '.');

        return $major + $minor + $revision;
    }


    /**
     * Compares versions with support for "post", "post_once", "post_always"
     *
     * @param string $version1
     * @param string $version2
     * @return int
     */
    public static function compare(string $version1, string $version2): int
    {
        // Check if versions are valid
        Validate::new($version1)->isVersion(11, true);
        Validate::new($version2)->isVersion(11, true);

        // Process if the first version has "post" in it
        switch ($version1) {
            case 'post_once':
                return match ($version2) {
                    'post_always' => -1,
                    'post_once' => 0,
                    default => 1,
                };

            case 'post_always':
                return match ($version2) {
                    'post_always' => 0,
                    default => 1,
                };
        }

        // If the second version has post in it, it's easier as we have already processed all "post" version1
        if (str_starts_with($version2, 'post')) {
            return 1;
        }

        return version_compare($version1, $version2);
    }
}