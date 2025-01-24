<?php

/**
 * Class BomFile
 *
 * This class can check and remove the Unicode Byte Order Mark from the specified file. This is important as PHP can
 * choke on this BOM
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */


declare(strict_types=1);

namespace Phoundation\Developer\Tests;

use Phoundation\Core\Log\Log;
use Phoundation\Developer\Mtime;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\PhoFile;
use Phoundation\Filesystem\Interfaces\PhoRestrictionsInterface;
use Phoundation\Filesystem\PhoPath;
use Stringable;


class BomFile extends PhoFile
{
    public function __construct(PhoPath|Stringable|string|null $file = null, PhoRestrictionsInterface|array|string|null $restrictions = null)
    {
        parent::__construct($file, $restrictions);

        // Only allow PHP files
        if (!str_ends_with($this->source, '.php')) {
            throw new OutOfBoundsException(tr('Cannot check file ":file" for BOM, only PHP files are supported', [
                ':file' => $this->source,
            ]));
        }
    }


    /**
     * Will scan for and if found, clear the file of the BOM
     *
     * @return static
     */
    public function clearBom(): static
    {
        // Only newer files
        if ($this->hasBom()) {
            $data = $this->getContentsAsString();

            $this->write(substr($data, 3));

            Log::warning(tr('Cleared BOM from file ":file"', [':file' => $this->source]));
        }

        return $this;
    }


    /**
     * Returns true if this file has a BOM
     *
     * @return bool
     */
    public function hasBom(): bool
    {
        // Only check unmodified files
        if (Mtime::isModified($this->source)) {
            $data = $this->readBytes(3);

            if ($data === chr(0xEF) . chr(0xBB) . chr(0xBF)) {
                // Found a twitcher! Gotta shootem in the head!
                Log::warning(tr('Found BOM in file ":file"', [':file' => $this->source]));

                return true;
            }
        }

        return false;
    }
}
