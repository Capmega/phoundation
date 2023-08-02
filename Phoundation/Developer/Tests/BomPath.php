<?php

namespace Phoundation\Developer\Tests;

use Phoundation\Filesystem\File;
use Phoundation\Filesystem\Path;


/**
 * Class BomPath
 *
 * This class can check and remove the Unicode Byte Order Mark from multiple PHP files in the specified path. This is
 * important as PHP can choke on this BOM
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Developer
 */
class BomPath extends Path
{
    /**
     * Checks for BOM bytes from all files in this path and returns the amount of files that have it
     *
     * @return int
     */
    public function checkBom(): int
    {
        $count = 0;

        $this->execute()->onFiles(function ($file) use (&$count) {
            if (BomFile::new($file)->checkBom()) {
                $count++;
            }
        });

        return $count;
    }


    /**
     * Clears the BOM bytes from all files in this path
     *
     * @return int
     * @throws \Throwable
     */
    public function clearBom(): int
    {
        $count = 0;

        $this->execute()->onFiles(function ($file) use (&$count) {
            if (BomFile::new($file)->clearBom()) {
                $count++;
            }
        });

        return $count;
    }
}