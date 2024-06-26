<?php

declare(strict_types=1);

namespace Phoundation\Security;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\FsFile;
use Phoundation\Filesystem\FsRestrictions;
use Phoundation\Filesystem\Interfaces\FsFileInterface;
use Phoundation\Filesystem\Interfaces\FsRestrictionsInterface;

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
        return FsFile::new('/dev/urandom', FsRestrictions::getReadonly('/dev/', 'Crypt::createCryptString()'))
                     ->readBytes($size);
    }


    /**
     * Returns a file containing random bytes directly from /dev/urandom
     *
     * @param string                  $filename
     * @param FsRestrictionsInterface $restrictions
     * @param int                     $size
     *
     * @return FsFileInterface
     */
    public static function createCryptFile(string $filename, FsRestrictionsInterface $restrictions, int $size = 4_096): FsFileInterface
    {
        if ($size > 16_777_216) {
            // Yeah, 16M keys is not enough? Really?
            throw new OutOfBoundsException(tr('Invalid key size ":size" specified, please specify key smaller than 16_777_216', [
                ':size' => $size,
            ]));
        }
        $bytes = FsFile::new('/dev/urandom', FsRestrictions::getReadonly('/dev/', 'Crypt::createCryptFile()'))
                       ->readBytes($size);

        return FsFile::new($filename, $restrictions)
                     ->putContents($bytes);
    }

}
