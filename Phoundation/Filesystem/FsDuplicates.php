<?php

/**
 * FsDuplicates class
 *
 * This class adds duplicate file control functionalities to the FsFiles class
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Filesystem
 */


declare(strict_types=1);

namespace Phoundation\Filesystem;

use PDOStatement;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Filesystem\Interfaces\FsDirectoryInterface;
use Phoundation\Filesystem\Interfaces\FsDuplicatesInterface;
use Phoundation\Filesystem\Interfaces\FsFilesInterface;


class FsDuplicates extends Iterator implements FsDuplicatesInterface
{
    /**
     * The number of files deleted
     *
     * @var int
     */
    protected int $deleted_count = 0;

    /**
     * The total size of the files deleted
     *
     * @var int $deleted_bytes
     */
    protected int $deleted_bytes = 0;

    /**
     * Contains a list of all files that were deleted
     *
     * @var FsFilesInterface
     */
    protected FsFilesInterface $deleted_files;

    /**
     * The parent object that generated this FsDuplicates object
     *
     * @var FsDirectoryInterface
     */
    protected ?FsDirectoryInterface $parent;


    /**
     * FsDuplicates class constructor
     *
     * @param FsDirectoryInterface|null                        $parent
     * @param IteratorInterface|array|string|PDOStatement|null $source
     */
    public function __construct(?FsDirectoryInterface $parent, IteratorInterface|array|string|PDOStatement|null $source = null)
    {
        $this->setAcceptedDataTypes(FsFilesInterface::class);
        $this->parent = $parent;
        parent::__construct($source);
    }


    /**
     * Returns the number of bytes freed by the deleting of duplicate files
     *
     * @return FsDirectoryInterface|null
     */
    public function getPArent(): ?FsDirectoryInterface
    {
        return $this->parent;
    }


    /**
     * Returns the number of bytes freed by the deleting of duplicate files
     *
     * @return int
     */
    public function getDeletedBytes(): int
    {
        return $this->deleted_bytes;
    }


    /**
     * Returns the number of bytes freed by the deleting of duplicate files
     *
     * @return int
     */
    public function getDeletedCount(): int
    {
        return $this->deleted_count;
    }


    /**
     * Returns the number of bytes freed by the deleting of duplicate files
     *
     * @return FsFilesInterface
     */
    public function getDeletedFiles(): FsFilesInterface
    {
        return $this->deleted_files;
    }


    /**
     * Deletes the duplicate files, keeping the first entry
     *
     * @return static
     */
    public function deleteKeepFirst(): static
    {
        $this->deleted_files = new FsFiles($this->parent);

        foreach ($this->source as $files) {
            $first = true;

            foreach ($files as $file) {
                if ($first) {
                    $first = false;
                    continue;
                }

                Log::warning(tr('Deleting duplicate file ":file"', [
                    ':file' => $file
                ]));

                $this->deleted_count++;
                $this->deleted_bytes += $file->getSize();
                $this->deleted_files->add($file);

                $file->delete();
            }
        }

        return $this;
    }
}
