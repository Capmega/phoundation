<?php

namespace Phoundation\Developer\Tests;

use Phoundation\Core\Log\Log;
use Phoundation\Developer\Mtime;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\File;
use Phoundation\Filesystem\Path;
use Phoundation\Filesystem\Interfaces\RestrictionsInterface;
use Stringable;


/**
 * Class BomFile
 *
 * This class can check and remove the Unicode Byte Order Mark from the specified file. This is important as PHP can
 * choke on this BOM
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Developer
 */
class BomFile extends File
{
    public function __construct(Path|Stringable|string|null $file = null, RestrictionsInterface|array|string|null $restrictions = null)
    {
        parent::__construct($file, $restrictions);

        // Only allow PHP files
        if (!str_ends_with($this->path, '.php')) {
            throw new OutOfBoundsException(tr('Cannot check file ":file" for BOM, only PHP files are supported', [
                ':file' => $this->path
            ]));
        }
    }


    /**
     * Returns true if this file has a BOM
     *
     * @return bool
     */
    public function hasBom(): bool
    {
        // Only check unmodified files
        if (Mtime::isModified($this->path)) {
            $data = $this->readBytes(3);

            if($data === chr(0xEF) . chr(0xBB) . chr(0xBF)){
                // Found a twitcher! Gotta shootem in the head!
                Log::warning(tr('Found BOM in file ":file"', [':file' => $this->path]));
                return true;
            }
        }

        return false;
    }


    /**
     * Will scan for and if found, clear the file of the BOM
     *
     * @return $this
     */
    public function clearBom(): static
    {
        // Only newer files
        if ($this->hasBom()) {
            $data = $this->getContentsAsString();
            $this->write(substr($data, 3));

            Log::warning(tr('Cleared BOM from file ":file"', [':file' => $this->path]));
        }

        return $this;
    }
}
