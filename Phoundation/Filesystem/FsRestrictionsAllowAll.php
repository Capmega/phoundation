<?php

/**
 * FsRestrictionsAllowAll class
 *
 * This class extends the FsRestrictions class, but allows everything, always. Currently only used by
 * FsPathCore::realPath() to avoid endless loops with FsRestrictions, and that method requires
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Filesystem
 */


declare(strict_types=1);

namespace Phoundation\Filesystem;

use PDOStatement;
use Phoundation\Cli\CliCommand;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Traits\TraitDataSourceArray;
use Phoundation\Developer\Debug;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Exception\RestrictionsException;
use Phoundation\Filesystem\Exception\WriteRestrictionsException;
use Phoundation\Filesystem\Interfaces\FsRestrictionsInterface;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Strings;
use Phoundation\Web\Requests\Request;
use Stringable;
use Throwable;


class FsRestrictionsAllowAll extends FsRestrictions implements FsRestrictionsInterface
{
    public function addDirectory(Stringable|string|null $directory, bool $write = false): static
    {
        return $this;
    }


    /**
     * Checks if the specified pattern is restricted.
     *
     * Will always just return as nothing is restricted for this class
     *
     * @param Stringable|string $pattern
     * @param bool              $write
     * @param Throwable|null    $e
     *
     * @return void
     *
     * @throws WriteRestrictionsException|RestrictionsException
     */
    public function check(Stringable|string $pattern, bool $write, ?Throwable $e = null): void
    {
        return;
    }


    /**
     * Returns always false, as nothing is restricted
     *
     * @param Stringable|string $pattern
     * @param bool              $write
     * @param Throwable|null    $e
     *
     * @return false|string
     */
    public function isRestricted(Stringable|string $pattern, bool $write, ?Throwable $e = null): false|string
    {
        return false;
    }


    /**
     * Returns a restrictions object with parent directories for all directories in this restrictions object
     *
     * This is useful for the Directory object where one will want to be able to access or create the parent directory
     * of the file that needs to be accessed
     *
     * @param int|null $levels
     *
     * @return FsRestrictionsInterface
     */
    public function getParent(?int $levels = null): FsRestrictionsInterface
    {
        return $this;
    }


    /**
     * Returns a restrictions object with the current directory and the specified child directory attached
     *
     * This is useful when we want more strict restrictions
     *
     * @param string|array $child_directories
     * @param bool|null    $write
     *
     * @return FsRestrictionsInterface
     */
    public function getChild(string|array $child_directories, ?bool $write = null): FsRestrictionsInterface
    {
        return $this;
    }


    /**
     * Return these restrictions but with write enabled
     *
     * @return FsRestrictionsInterface
     */
    public function makeWritable(): FsRestrictionsInterface
    {
        return $this;
    }
}
