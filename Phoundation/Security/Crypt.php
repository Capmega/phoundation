<?php

declare(strict_types=1);

namespace Phoundation\Security;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\File;
use Phoundation\Filesystem\Interfaces\FileInterface;
use Phoundation\Filesystem\Interfaces\RestrictionsInterface;
use Phoundation\Filesystem\Restrictions;


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
    public static function createCryptString(int $size = 32): string
    {
        return File::new('/dev/urandom')->readBytes($size);
    }


    /**
     * Returns a file containing random bytes directly from /dev/urandom
     *
     * @param string $filename
     * @param RestrictionsInterface $restrictions
     * @param int $size
     * @return FileInterface
     */
    public static function createCryptFile(string $filename, RestrictionsInterface $restrictions, int $size = 4_096): FileInterface
    {
        if ($size > 16_777_216) {
            // Yeah, 16M keys is not enough? Really?
            throw new OutOfBoundsException(tr('Invalid key size ":size" specified, please specify key smaller than 16_777_216', [
                ':size' => $size
            ]));
        }

        $bytes = File::new('/dev/urandom', '/dev/')->readBytes($size);

        return File::new($filename, $restrictions)->putContents($bytes);
    }

}
