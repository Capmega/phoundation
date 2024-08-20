<?php

/**
 * Class FsMimetypes
 *
 * This class represents multiple entries in the "filesystem_mimetypes" table, or multiple mimetypes
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Filesystem
 */


declare(strict_types=1);

namespace Phoundation\Filesystem\Mimetypes;

use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntry\DataIterator;
use Phoundation\Filesystem\Mimetypes\Exception\FilesystemMimetypeNotSupported;


class FsMimetypes extends DataIterator
{
    /**
     * Returns the table name used by this object
     *
     * @return string|null
     */
    public static function getTable(): ?string
    {
        return 'filesystem_mimetypes';
    }


    /**
     * Returns the field that is unique for this object
     *
     * @return string|null
     */
    public static function getUniqueColumn(): ?string
    {
        return null;
    }


    /**
     * Returns the class for a single DataEntry in this Iterator object
     *
     * @return string|null
     */
    public static function getDefaultContentDataTypes(): ?string
    {
        return FsMimetype::class;
    }


    /**
     * Returns true if the specified extension matches the specified mimetype
     *
     * @param string $extension
     * @param string $mimetype
     *
     * @return bool
     */
    public function extensionMatchesMimetype(string $extension, string $mimetype): bool
    {
        $this->load(['mimetype' => $mimetype]);

        if ($this->isEmpty()) {
            throw new FilesystemMimetypeNotSupported(tr('The specified mimetype ":mimetype" is not supported', [
                ':mimetype' => $mimetype
            ]));
        }

        foreach ($this as $mimetype) {
            if ($mimetype->hasExtension($extension)) {
                return true;
            }
        }

        return false;
    }


    /**
     * Returns true if the specified mimetype matches the specified extension
     *
     * @param string $mimetype
     * @param string $extension
     *
     * @return bool
     */
    public function mimetypeMatchesExtension(string $mimetype, string $extension): bool
    {
        $this->load(['extension' => $extension]);

        if ($this->isEmpty()) {
            throw new FilesystemMimetypeNotSupported(tr('The specified extension ":extension" is not supported', [
                ':extension' => $extension
            ]));
        }

        foreach ($this as $extension) {
            if ($extension->hasMimetype($mimetype)) {
                return true;
            }
        }

        return false;
    }


    /**
     * Returns the highest found priority for the specified extension
     *
     * @param string $extension
     *
     * @return int
     */
    public static function getHighestExtensionPriority(string $extension): int
    {
        return sql()->getInteger('SELECT MAX(`priority`) FROM `filesystem_mimetypes` WHERE `extension` = :extension', [
            ':extension' => $extension
        ]);
    }


    /**
     * Returns the highest found priority for the specified mimetype
     *
     * @param string $mimetype
     *
     * @return int
     */
    public static function getHighestMimetypePriority(string $mimetype): int
    {
        return sql()->getInteger('SELECT MAX(`priority`) FROM `filesystem_mimetypes` WHERE `mimetype` = :mimetype', [
            ':mimetype' => $mimetype
        ]);
    }


    /**
     * Returns the best extension for the specified mimetype
     *
     * @param string $mimetype
     *
     * @return string
     */
    public static function getBestExtensionForMimetype(string $mimetype): string
    {
        return FsMimetype::load([
            'mimetype' => $mimetype,
            'priority' => static::getHighestMimetypePriority($mimetype)
        ])->getExtension();
    }


    /**
     * Returns the best mimetype for the specified extension
     *
     * @param string $extension
     *
     * @return string
     */
    public static function getBestMimetypeForExtension(string $extension): string
    {
        return FsMimetype::load([
            'mimetype' => $extension,
            'priority' => static::getHighestExtensionPriority($extension)
        ])->getMimetype();
    }
}
