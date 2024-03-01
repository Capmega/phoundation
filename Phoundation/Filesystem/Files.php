<?php

declare(strict_types=1);

namespace Phoundation\Filesystem;

use Phoundation\Data\Iterator;
use Phoundation\Filesystem\Interfaces\FilesInterface;
use Phoundation\Filesystem\Interfaces\PathInterface;
use Phoundation\Filesystem\Interfaces\RestrictionsInterface;
use Phoundation\Filesystem\Traits\DataRestrictions;
use Phoundation\Utils\Arrays;
use Stringable;


/**
 * Files class
 *
 * This class manages a list of files that are not necessarily confined to the same directory
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package Phoundation\Filesystem
 */
class Files extends Iterator implements FilesInterface
{
    use DataRestrictions;


    /**
     * The parent directory containing these files
     *
     * @var PathInterface|null
     */
    protected ?PathInterface $parent = null;


    /**
     * Files class constructor
     *
     * @param mixed $source
     * @param RestrictionsInterface|array|string|null $restrictions
     */
    public function __construct(mixed $source = null, RestrictionsInterface|array|string|null $restrictions = null)
    {
        $this->data_type    = Path::class;
        $this->source       = Arrays::force($source, null);
        $this->restrictions = $restrictions;

        parent::__construct();
    }


    /**
     * Returns a new Files object
     *
     * @param mixed|null $source
     * @param RestrictionsInterface|array|string|null $restrictions
     * @return static
     */
    public static function new(mixed $source = null, RestrictionsInterface|array|string|null $restrictions = null): static
    {
        return new static($source, $restrictions);
    }


    /**
     * Returns the parent Path (if available) that contains these files
     *
     * @return PathInterface|null
     */
    public function getParent(): ?PathInterface
    {
        return $this->parent;
    }


    /**
     * Returns the parent Path (if available) that contains these files
     *
     * @param PathInterface|null $parent
     * @return Files
     */
    public function setParent(?PathInterface $parent): static
    {
        $this->parent = $parent;
        return $this;
    }


    /**
     * Move all files to the specified target
     *
     * @note The specified target MUST be a directory, as multiple files will be moved there
     * @note The specified target either must exist or will be created automatically
     * @param Stringable|string $target
     * @param Restrictions|null $restrictions
     * @return $this
     */
    public function move(Stringable|string $target, ?RestrictionsInterface $restrictions = null): static
    {
        $restrictions = $this->ensureRestrictions($restrictions);

        Directory::new($target, $restrictions)->ensure();

        foreach ($this->source as $file) {
            File::new($file)->movePath($target, $restrictions);
        }

        return $this;
    }


    /**
     * Copy all files to the specified target
     *
     * @note The specified target MUST be a directory, as multiple files will be moved there
     * @note The specified target either must exist or will be created automatically
     * @param Stringable|string $target
     * @param RestrictionsInterface|null $restrictions
     * @param callable|null $callback
     * @return $this
     */
    public function copy(Stringable|string $target, ?RestrictionsInterface $restrictions = null, ?callable $callback = null): static
    {
        $restrictions = $this->ensureRestrictions($restrictions);

        Directory::new($target, $restrictions)->ensure();

        foreach ($this->source as $file) {
            File::new($file)->copy($target, $callback, $restrictions);
        }

        return $this;
    }


    /**
     * Returns the current file
     *
     * @return PathInterface
     */
    #[ReturnTypeWillChange] public function current(): PathInterface
    {
        $current = current($this->source);

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

        $current = $this->parent?->getPath() . $current;

        if (is_dir($current)) {
            return Directory::new($current, $this->restrictions);
        }

        if (file_exists($current)) {
            return File::new($current, $this->restrictions);
        }

        // Non existing file, just return the path
        return Path::new($current, $this->restrictions);
    }


    /**
     * Returns if the current pointer is valid or not
     *
     * Since Files classes skip the "." and ".." directories, valid will ensure these get skipped too
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
}
