<?php

/**
 * FsFilesCore class
 *
 * This class manages a list of files that are not necessarily confined to the same directory
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Filesystem
 */


declare(strict_types=1);

namespace Phoundation\Filesystem;

use PDOStatement;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\IteratorCore;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\UnderConstructionException;
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
    protected FsDirectoryInterface|null $parent_directory = null;


    /**
     * @param IteratorInterface|array|string|PDOStatement|null $source
     * @param array|null                                       $execute
     *
     * @return $this
     */
    public function setSource(IteratorInterface|array|string|PDOStatement|null $source = null, ?array $execute = null): static
    {
        parent::setSource($source);
        return $this->init();
    }


    /**
     * Returns the parent Path (if available) that contains these files
     *
     * @return FsPathInterface|null
     */
    public function getParentDirectory(): ?FsPathInterface
    {
        return $this->parent_directory;
    }


    /**
     * Returns the parent Path (if available) that contains these files
     *
     * @param FsPathInterface|null $parent_directory
     *
     * @return FsFiles
     */
    public function setParentDirectory(?FsPathInterface $parent_directory): static
    {
        $this->parent_directory = $parent_directory;

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
     * @return static
     */
    public function move(Stringable|string $target, ?FsRestrictionsInterface $restrictions = null): static
    {
        $restrictions = $this->ensureRestrictions($restrictions);

        FsDirectory::new($target, $restrictions)->ensure();

        foreach ($this->source as $file) {
            FsFile::new($file)->move($target, $restrictions);
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
     * @return static
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
     * @inheritDoc
     */
    #[ReturnTypeWillChange] public function current(): FsPathInterface
    {
        return parent::current();
    }


    /**
     * @inheritDoc
     */
    #[ReturnTypeWillChange] public function get(Stringable|int|string $key, bool $exception = true): FsPathInterface
    {
        return parent::get($key, $exception);
    }


    /**
     * @inheritDoc
     */
    #[ReturnTypeWillChange] public function getFirstValue(): FsPathInterface
    {
        return parent::getFirstValue();
    }


    /**
     * @inheritDoc
     */
    #[ReturnTypeWillChange] public function getLastValue(): FsPathInterface
    {
        return parent::getLastValue();
    }


    /**
     * Initializes the source files, ensures all files are FsPathInterface objects
     *
     * @return static
     */
    protected function init(): static
    {
        foreach ($this->source as $key => &$file) {
            if (is_string($file)) {
                switch ($file) {
                    case '.':
                        // No break

                    case '..':
                        // Skip the "." and ".." directories
                        unset($this->source[$key]);
                        continue 2;
                }

                if (!str_starts_with($file, '/')) {
                    // Prefix the file with the parent path ONLY IF it's not absolute and a parent was specified
                    $file = $this->parent_directory?->getSource() . $file;
                }

                if (is_dir($file)) {
                    $file = FsDirectory::new($file, $this->restrictions);

                } elseif (file_exists($file)) {
                    $file = FsFile::new($file, $this->restrictions);

                } else {
                    // Non-existing file, just return the path
                    $file = FsPath::new($file, $this->restrictions);
                }

                continue;
            }

            if ($file instanceof FsPathInterface) {
                // Ensure $file is an absolute path
                $file->makeAbsolute($this->parent_directory?->getSource(), false);
                continue;
            }

            throw new OutOfBoundsException(tr('Iterator ":class" key ":key" contains unsupported data ":data"', [
                ':class' => get_class($this),
                ':key'   => $key,
                ':data'  => $file,
            ]));
        }

        return $this;
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


    /**
     * Returns all files that match the specified mimetype
     *
     * @param string $mimetype
     * @param bool   $remove
     *
     * @return $this
     */
    public function getFilesWithMimetype(string $mimetype, bool $remove = false): static
    {
        $files  = new static($this->parent_directory);
        $delete = [];

        foreach ($this as $key => $file) {
            if ($file->mimetypeMatches($mimetype)) {
                $files->add($file);

                if ($remove) {
                    $delete[] = $key;
                }
            }
        }

        if (count($delete)) {
            $this->removeKeys($delete);
        }

        return $files;
    }


    /**
     * Will delete all files in this FsFiles object
     *
     * @note This will remove the files from this FsFiles object
     *
     * @return $this
     */
    public function delete(string|bool $clean_path = true, bool $sudo = false, bool $escape = true, bool $use_run_file = true): static
    {
        foreach ($this as $key => $file) {
            $file->delete($clean_path, $sudo, $escape, $use_run_file);

            unset($this->source[$key]);
        }

        return $this;
    }


    /**
     * @param int  $passes
     * @param bool $simultaneously
     * @param bool $randomized
     * @param int  $block_size
     *
     * @return $this
     *
     * @todo Implement support for $simultaneously
     */
    public function shred(int $passes = 3, bool $simultaneously = false, bool $randomized = false, int $block_size = 4096): static
    {
        if ($simultaneously) {
            // Delete the files all simultaneously
            // This may require reimplementing FsCorePath::doInitialize() all anew!
            throw new UnderConstructionException();

        } else {
            // Delete the files one after the other
            foreach ($this as $key => $file) {
                $file->shred($passes, $randomized, $block_size);

                unset($this->source[$key]);
            }
        }

        return $this;
    }


    /**
     * @inheritDoc
     */
    public function append(mixed $value, Stringable|int|string|null $key = null, bool $skip_null_values = true, bool $exception = true): static
    {
        // specified value must match FsPathInterface::class
        $this->checkDataType($value);

        if ($key === null) {
            $key = $value->getSource();
        }

        return parent::append($value, $key, $skip_null_values, $exception);
    }
}
