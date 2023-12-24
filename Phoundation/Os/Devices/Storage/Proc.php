<?php

declare(strict_types=1);

namespace Phoundation\Os\Devices\Storage;

use Phoundation\Data\Iterator;
use Phoundation\Filesystem\File;
use Phoundation\Utils\Strings;


/**
 * Class Proc
 *
 * Access to the kernel /proc directory
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Os
 */
class Proc
{
    /**
     * Returns a list of supported file types
     *
     * @return Iterator
     */
    public static function getSupportedFiletypes(): Iterator
    {
        $types = File::new('/proc/filesystems', '/proc/filesystems')->getContentsAsArray();

        foreach ($types as &$type) {
            $type = Strings::from($type, 'nodev');
            $type = trim($type);
        }

        unset($trim);
        return Iterator::new()->setSource($types);
    }
}
