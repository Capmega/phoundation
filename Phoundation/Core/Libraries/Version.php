<?php

namespace Phoundation\Core\Libraries;

use Phoundation\Core\Strings;
use Phoundation\Exception\OutOfBoundsException;


/**
 * Version class
 *
 * This class manages library versions
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Developer
 */
class Version
{
    /**
     * Returns a string version of the integer version
     *
     * @param int $version
     * @return string
     */
    public static function getString(int $version): string
    {
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
        if (!is_version($version)) {
            throw new OutOfBoundsException(tr('Specified version ":version" is not valid, should be of format "\d{1,4}.\d{1,4}.\d{1,4}"', [
                ':version' => $version
            ]));
        }

        $major    = (int) Strings::until($version, '.') * 1000000;
        $minor    = (int) Strings::until(Strings::from($version, '.'), '.') * 1000;
        $revision = (int) Strings::fromReverse($version, '.');

        return $major + $minor + $revision;
    }
}