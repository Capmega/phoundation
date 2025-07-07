<?php

/**
 * Class PhoFilesCore
 *
 * This class manages a list of files that are not necessarily confined to the same directory
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Filesystem
 */


declare(strict_types=1);

namespace Phoundation\Filesystem;

use PDOStatement;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Data\IteratorCore;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Filesystem\Exception\NoPathSpecifiedException;
use Phoundation\Filesystem\Interfaces\PhoDirectoryInterface;
use Phoundation\Filesystem\Interfaces\PhoFilesInterface;
use Phoundation\Filesystem\Interfaces\PhoPathInterface;
use Phoundation\Filesystem\Interfaces\PhoRestrictionsInterface;
use Phoundation\Data\Traits\TraitDataRestrictions;
use ReturnTypeWillChange;
use Stringable;


class PhoFilesCore extends IteratorCore implements PhoFilesInterface
{
    use TraitDataRestrictions;


    /**
     * The parent directory containing these files
     *
     * @var PhoDirectoryInterface|null
     */
    protected PhoDirectoryInterface|null $o_parent_directory = null;

    /**
     * Tracks if this files object has empty entries added
     *
     * @var bool $added_empty
     */
    protected bool $added_empty = false;


    /**
     * Adds an empty entry to the files list
     *
     * @return static
     */
    public function addEmpty(): static
    {
        $this->added_empty  = true;
        $this->source[null] = null;

        return $this;
    }


    /**
     * Sets the source array for this FilesCore object
     *
     * @param array|string|PDOStatement|IteratorInterface|null $source
     * @param array|null                                       $execute
     * @param bool                                             $filter_meta *
     *
     * @return static
     */
    public function setSource(IteratorInterface|array|string|PDOStatement|null $source = null, ?array $execute = null, bool $filter_meta = false): static
    {
        parent::setSource($source, $execute);
        return $this->init();
    }


    /**
     * Returns the parent Path (if available) that contains these files
     *
     * @return PhoPathInterface|null
     */
    public function getParentDirectory(): ?PhoPathInterface
    {
        return $this->o_parent_directory;
    }


    /**
     * Sets the parent path where the files for this object are located.
     *
     * @note By default, will then load the files in that path
     *
     * @param PhoPathInterface|null $parent_directory
     * @param bool                  $load
     *
     * @return PhoFiles
     */
    public function setParentDirectory(?PhoPathInterface $parent_directory, bool $load = true): static
    {
        $this->o_parent_directory = $parent_directory;

        if ($load) {
            return $this->load();
        }

        return $this;
    }


    /**
     * Loads the files for the current parent_directory into the source array
     *
     * @return static
     */
    protected function load(): static
    {
        if (empty($this->o_parent_directory)) {
            throw new NoPathSpecifiedException(tr('Cannot load files, no parent directory specified'));
        }

        $this->setSource($this->o_parent_directory->scan());
    }


    /**
     * Move all files to the specified target
     *
     * @note The specified target MUST be a directory, as multiple files will be moved there
     * @note The specified target either must exist or will be created automatically
     *
     * @param PhoDirectoryInterface         $o_target
     * @param PhoRestrictionsInterface|null $o_restrictions
     *
     * @return static
     */
    public function move(PhoDirectoryInterface $o_target, ?PhoRestrictionsInterface $o_restrictions = null): static
    {
        PhoDirectory::new($o_target, $o_restrictions)->ensure();

        foreach ($this->source as $file) {
            PhoFile::new($file)->move($o_target, $o_target->getRestrictionsObject());
        }

        return $this;
    }


    /**
     * Copy all files to the specified target
     *
     * @note The specified target MUST be a directory, as multiple files will be moved there
     * @note The specified target either must exist or will be created automatically
     *
     * @param Stringable|string             $target
     * @param PhoRestrictionsInterface|null $o_restrictions
     * @param callable|null                 $callback
     * @param mixed|null                    $context
     *
     * @return static
     */
    public function copy(Stringable|string $target, ?PhoRestrictionsInterface $o_restrictions = null, ?callable $callback = null, mixed $context = null): static
    {
        $o_restrictions = $this->ensureRestrictions($o_restrictions);
        PhoDirectory::new($target, $o_restrictions)->ensure();

        foreach ($this->source as $file) {
            PhoFile::new($file)->copy($target, $o_restrictions, $callback, $context);
        }

        return $this;
    }


    /**
     * @inheritDoc
     */
    #[ReturnTypeWillChange] public function current(): ?PhoPathInterface
    {
        return parent::current();
    }


    /**
     * @inheritDoc
     */
    #[ReturnTypeWillChange] public function get(Stringable|string|float|int $key, bool $exception = true): ?PhoPathInterface
    {
        return parent::get($key, $exception);
    }


    /**
     * @inheritDoc
     */
    #[ReturnTypeWillChange] public function getFirstValue(): ?PhoPathInterface
    {
        return parent::getFirstValue();
    }


    /**
     * @inheritDoc
     */
    #[ReturnTypeWillChange] public function getLastValue(): ?PhoPathInterface
    {
        return parent::getLastValue();
    }


    /**
     * Returns if the current pointer is valid or not
     *
     * Since PhoFiles classes skip the "." and ".." directories, valid will ensure these get skipped too
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
                        return $this->added_empty;

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
     * Initializes the source files, ensures all files are PhoPathInterface objects
     *
     * @return static
     */
    protected function init(): static
    {
show('init');
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
                    $file = $this->o_parent_directory?->getSource() . $file;
                }

                if (is_dir($file)) {
                    $file = PhoDirectory::new($file, $this->o_restrictions);

                } elseif (file_exists($file)) {
                    $file = PhoFile::new($file, $this->o_restrictions);

                } else {
                    // Non-existing file, just return the path
                    $file = PhoPath::new($file, $this->o_restrictions);
                }

                continue;
            }

            if ($file instanceof PhoPathInterface) {
                // Ensure $file is an absolute path
                $file->makeAbsolute($this->o_parent_directory?->getSource(), false);
                continue;
            }

            throw new OutOfBoundsException(tr('Iterator ":class" key ":key" contains unsupported data ":data"', [
                ':class' => static::class,
                ':key'   => $key,
                ':data'  => $file,
            ]));
        }

        return $this;
    }


    /**
     * Returns all files that match the specified mimetype
     *
     * @param string $mimetype
     * @param bool   $remove
     *
     * @return static
     */
    public function getFilesWithMimetype(string $mimetype, bool $remove = false): static
    {
        $files  = new static($this->o_parent_directory);
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
     * Returns an iterator containing the basename for all files in this object
     *
     * @return IteratorInterface
     */
    public function getBasenames(): IteratorInterface
    {
        $source = [];

        foreach ($this->source as $file) {
            $basename = $file->getBasename();
            $source[$basename] = $basename;
        }

        return new Iterator($source);
    }


    /**
     * Will delete all files in this PhoFiles object
     *
     * @note This will remove the files from this PhoFiles object
     *
     * @param string|bool $clean_path
     * @param bool        $sudo
     * @param bool        $escape
     * @param bool        $use_run_file
     *
     * @return static
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
     * Will shred all files in this PhoFiles iterator
     *
     * @param int  $passes
     * @param bool $simultaneously
     * @param bool $randomized
     * @param int  $block_size
     *
     * @return static
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
    public function append(mixed $value, Stringable|string|float|int|null $key = null, bool $skip_null_values = true, bool $exception = true): static
    {
        // Skip NULL values?
        if ($value === null) {
            if ($skip_null_values) {
                return $this;
            }
        }

        // specified value must match PhoPathInterface::class
        $this->checkDataTypeAndContent($value, $key);

        if ($key === null) {
            $key = $value->getSource();
        }

        return parent::append($value, $key, $skip_null_values, $exception);
    }
}
