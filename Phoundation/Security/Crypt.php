<?php

namespace Phoundation\Security;

use Phoundation\Filesystem\File;
use Phoundation\Filesystem\Interfaces\FileInterface;


/**
 * Class Crypt
 *
 * This class contains various encryption / decryption methods
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Security
 */
class Crypt
{
    /**
     * Returns random bytes directly from /dev/urandom
     *
     * @param int $size
     * @return string
     */
    public function createCryptString(int $size = 32): string
    {
        return File::new('/dev/urandom')->readBytes($size);
    }


    /**
     * Returns a file containing random bytes directly from /dev/urandom
     *
     * @param int $size
     * @return FileInterface
     */
    public function createCryptFile(int $size = 4096, string $filename): FileInterface
    {
        $bytes = File::new('/dev/urandom')->readBytes($size);

        return File::newTemporary(false)
            ->putContents($bytes)
            ->move($filename);
    }

}