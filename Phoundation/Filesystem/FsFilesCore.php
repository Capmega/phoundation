<?php

/**
 * FsFilesCore class
 *
 * This class manages a list of files that are not necessarily confined to the same directory
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category  Function reference
 * @package   Phoundation\Filesystem
 */

declare(strict_types=1);

namespace Phoundation\Filesystem;

use Phoundation\Data\IteratorCore;
use Phoundation\Filesystem\Interfaces\FsDirectoryInterface;
use Phoundation\Filesystem\Interfaces\FsFilesInterface;
use Phoundation\Filesystem\Interfaces\FsPathInterface;
use Phoundation\Filesystem\Interfaces\FsRestrictionsInterface;
use Phoundation\Data\Traits\TraitDataRestrictions;
use ReturnTypeWillChange;
use Stringable;

class FsFilesCore extends IteratorCore implements FsFilesInterface
{
    use TraitDataRestrictions;


    /**
     * The parent directory containing these files
     *
     * @var FsDirectoryInterface|null
     */
    protected ?FsDirectoryInterface $parent = null;


    /**
     * Returns the parent Path (if available) that contains these files
     *
     * @return FsPathInterface|null
     */
    public function getParent(): ?FsPathInterface
    {
        return $this->parent;
    }


    /**
     * Returns the parent Path (if available) that contains these files
     *
     * @param FsPathInterface|null $parent
     *
     * @return FsFiles
     */
    public function setParent(?FsPathInterface $parent): static
    {
        $this->parent = $parent;

        return $this;
    }


    /**
     * Move all files to the specified target
     *
     * @note The specified target MUST be a directory, as multiple files will be moved there
     * @note The specified target either must exist or will be created automatically
     *
     * @param Stringable|string   $target
     * @param FsRestrictions|null $restrictions
     *
     * @return $this
     */
    public function move(Stringable|string $target, ?FsRestrictionsInterface $restrictions = null): static
    {
        $restrictions = $this->ensureRestrictions($restrictions);

        FsDirectory::new($target, $restrictions)->ensure();

        foreach ($this->source as $file) {
            FsFile::new($file)->movePath($target, $restrictions);
        }

        return $this;
    }


    /**
     * Copy all files to the specified target
     *
     * @note The specified target MUST be a directory, as multiple files will be moved there
     * @note The specified target either must exist or will be created automatically
     *
     * @param Stringable|string            $target
     * @param FsRestrictionsInterface|null $restrictions
     * @param callable|null                $callback
     * @param mixed|null                   $context
     *
     * @return $this
     */
    public function copy(Stringable|string $target, ?FsRestrictionsInterface $restrictions = null, ?callable $callback = null, mixed $context = null): static
    {
        $restrictions = $this->ensureRestrictions($restrictions);
        FsDirectory::new($target, $restrictions)->ensure();

        foreach ($this->source as $file) {
            FsFile::new($file)->copy($target, $restrictions, $callback, $context);
        }

        return $this;
    }


    /**
     * Returns the current file
     *
     * @return FsPathInterface
     */
    #[ReturnTypeWillChange] public function current(): FsPathInterface
    {
        $current = current($this->source);

        if (is_string($current)) {
            while (true) {
                switch ($current) {
                    case '.':
                        // No break

                    case '..':
                        // Skip the "." and ".." directories
                        $this->next();
                        $current = current($this->source);
                        break;

                    default:
                        break 2;
                }
            }

            if (!str_starts_with($current, '/')) {
                // Prefix the file with the parent path ONLY IF it's not absolute and a parent was specified
                $current = $this->parent?->getPath() . $current;
            }

            if (is_dir($current)) {
                return FsDirectory::new($current, $this->restrictions);
            }

            if (file_exists($current)) {
                return FsFile::new($current, $this->restrictions);
            }

            // Non-existing file, just return the path
            return FsPath::new($current, $this->restrictions);
        }

        // The file is already stored in an FsPathInterface object
        $file = current($this->source);
        $file->makeAbsolute($this->parent?->getPath(), false);

        return $file;
    }


    /**
     * Returns if the current pointer is valid or not
     *
     * Since FsFiles classes skip the "." and ".." directories, valid will ensure these get skipped too
     *
     * @return bool
     */
    public function valid(): bool
    {
        $valid = parent::valid();

        if ($valid) {
            $current = current($this->source);

            while (true) {
                switch ($current) {
                    case '':
                        return false;

                    case '.':
                        // No break

                    case '..':
                        // Skip the "." and ".." directories
                        parent::next();
                        $current = current($this->source);
                        break;

                    default:
                        break 2;
                }
            }

        }

        return $valid;
    }


    public function delete(): static
    {

    }



    public function shred(int $passes = 3, bool $simultaneously = true, bool $randomized = false, int $block_size = 4096): static
    {

    }
}
