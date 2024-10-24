<?php

/**
 * Class Crypt
 *
 * This class contains various encryption / decryption methods
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Security
 */


declare(strict_types=1);

namespace Phoundation\Security;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\PhoFile;
use Phoundation\Filesystem\PhoRestrictions;
use Phoundation\Filesystem\Interfaces\PhoFileInterface;
use Phoundation\Filesystem\Interfaces\PhoRestrictionsInterface;


class Crypt
{
    /**
     * Returns random bytes directly from /dev/urandom
     *
     * @param int $size
     *
     * @return string
     */
    public static function createCryptString(int $size = 32): string
    {
        return PhoFile::new('/dev/urandom', PhoRestrictions::newReadonly('/dev/'))
                      ->readBytes($size);
    }


    /**
     * Returns a file containing random bytes directly from /dev/urandom
     *
     * @param PhoFileInterface $file
     * @param int              $size
     *
     * @return PhoFileInterface
     */
    public static function createCryptFile(PhoFileInterface $file, int $size = 4_096): PhoFileInterface
    {
        if ($size > 16_777_216) {
            // Yeah, 16M keys is not enough? Really?
            throw new OutOfBoundsException(tr('Invalid key size ":size" specified, please specify key smaller than 16_777_216', [
                ':size' => $size,
            ]));
        }

        $bytes = PhoFile::new('/dev/urandom', PhoRestrictions::newReadonly('/dev/'))
                        ->readBytes($size);

        return $file->putContents($bytes);
    }

}
