<?php

/**
 * Class PhoDuplicates
 *
 * This class adds duplicate file control functionalities to the PhoFiles class
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
use Phoundation\Filesystem\Interfaces\PhoDirectoryInterface;
use Phoundation\Filesystem\Interfaces\PhoDuplicatesInterface;
use Phoundation\Filesystem\Interfaces\PhoFilesInterface;


class PhoDuplicates extends Iterator implements PhoDuplicatesInterface
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
     * @var PhoFilesInterface
     */
    protected PhoFilesInterface $deleted_files;

    /**
     * The parent_directory object that generated this FsDuplicates object
     *
     * @var PhoDirectoryInterface $parent_directory
     */
    protected PhoDirectoryInterface $parent_directory;


    /**
     * PhoDuplicates class constructor
     *
     * @param PhoDirectoryInterface|null                       $parent_directory
     * @param IteratorInterface|array|string|PDOStatement|null $source
     */
    public function __construct(?PhoDirectoryInterface $parent_directory, IteratorInterface|array|string|PDOStatement|null $source = null)
    {
        $this->setAcceptedDataTypes(PhoFilesInterface::class);
        $this->parent_directory = $parent_directory;
        parent::__construct($source);
    }


    /**
     * Returns the number of bytes freed by the deleting of duplicate files
     *
     * @return PhoDirectoryInterface|null
     */
    public function getParentDirectory(): ?PhoDirectoryInterface
    {
        return $this->parent_directory;
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
     * @return PhoFilesInterface
     */
    public function getDeletedFiles(): PhoFilesInterface
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
        $this->deleted_files = new PhoFiles($this->parent_directory);

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
