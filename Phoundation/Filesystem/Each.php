<?php

namespace Phoundation\Filesystem;

use Exception;
use Phoundation\Core\Arrays;
use Phoundation\Core\Core;
use Phoundation\Core\Log;
use Phoundation\Exception\OutOfBoundsException;



/**
 * Each class
 *
 * This library contains various filesystem file related functions
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package Phoundation\Filesystem
 */
class Each
{
    /**
     * Filesystem restrictions
     *
     * @var Restrictions|null $restrictions
     */
    protected ?Restrictions $restrictions;

    /**
     * Sets if the object will recurse or not
     *
     * @var bool $recurse
     */
    protected bool $recurse = false;

    /**
     * The paths to process
     *
     * @var array $paths
     */
    protected array $paths = [];

    /**
     * The paths to $skip
     *
     * @var array $skip
     */
    protected array $skip = [];



    /**
     * Each class constructor
     *
     * @param Restrictions|null $restrictions
     */
    public function __construct(?Restrictions $restrictions = null)
    {
        $this->restrictions = $restrictions;
    }



    /**
     * Returns the path that will be processed
     *
     * @return array
     */
    public function getPath(): array
    {
        return $this->paths;
    }



    /**
     * Sets the log threshold level to the newly specified level and will return the previous level.
     *
     * @param string|array $paths
     * @return Each
     * @throws OutOfBoundsException if the specified threshold is invalid.
     */
    public function setPath(string|array $paths): Each
    {
        if (!$paths) {
            throw new OutOfBoundsException(tr('No paths specified'));
        }

        $this->paths = Arrays::force($paths, '');
        return $this;
    }



    /**
     * Returns the path that will be skipped
     *
     * @return array
     */
    public function getSkipPaths(): array
    {
        return $this->skip;
    }



    /**
     * Sets the path that will be skipped
     *
     * @param string|array $paths
     * @return Each
     * @throws OutOfBoundsException if the specified threshold is invalid.
     */
    public function setSkipPaths(string|array $paths): Each
    {
        $this->skip = [];
        return $this->addSkipPaths(Arrays::force($paths, ''));
    }



    /**
     * Sets the path that will be skipped
     *
     * @param string|array $paths
     * @return Each
     * @throws OutOfBoundsException if the specified threshold is invalid.
     */
    public function addSkipPaths(string|array $paths): Each
    {
        foreach ($paths as $path) {
            $this->addSkipPath($path);
        }

        return $this;
    }



    /**
     * Sets the path that will be skipped
     *
     * @param string $path
     * @return Each
     * @throws OutOfBoundsException if the specified threshold is invalid.
     */
    public function addSkipPath(string $path): Each
    {
        if ($path) {
            $this->skip[] = Path::absolute($path);
        }

        return $this;
    }



    /**
     * Returns the filesystem restrictions
     *
     * @return Restrictions|null
     */
    public function getRestrictions(): ?Restrictions
    {
        return $this->restrictions;
    }



    /**
     * Sets the filesystem restrictions
     *
     * @param Restrictions|null $restrictions
     * @return Each
     */
    public function setRestrictions(?Restrictions $restrictions): Each
    {
        $this->restrictions = $restrictions;
        return $this;
    }




    /**
     * Returns if the object will recurse or not
     *
     * @return bool
     */
    public function getRecurse(): bool
    {
        return $this->recurse;
    }



    /**
     * Returns if the object will recurse or not
     *
     * @param bool $recurse
     * @return Each
     */
    public function setRecurse(bool $recurse): Each
    {
        $this->recurse = $recurse;
        return $this;
    }




    /**
     * Execute the callback function on each file in the specified path
     *
     * @param callable $callback
     * @return int
     */
    public function execute(callable $callback): int
    {
        $count = 0;
        $files = [];

        Core::ensureRestrictions($this->restrictions)->check($this->paths);

        foreach (Arrays::force($this->paths, '') as $path) {
            try {
                // Get al files in this directory
                $path  = Path::absolute($path);

                if (!$this->skip($path)) {
                    $files = scandir($path);
                }

            } catch (Exception $e) {
                Path::checkReadable($path, previous_e:  $e);
            }

            foreach ($files as $file) {
                if (($file == '.') or ($file == '..')) {
                    // skip these
                    continue;
                }

                if (is_dir($path . $file)) {
                    // Directory! Recurse?
                    if (!$this->recurse) {
                        continue;
                    }

                    $recurse = clone $this;

                    $count += $recurse
                        ->setPath($path . $file)
                        ->execute($callback);

                } elseif (file_exists($path . $file)) {
                    // Execute the callback
                    $count++;

                    Log::action(tr('Executing callback function on file ":file"', [
                        ':file' => $path . $file
                    ]), 3);

                    $callback($path . $file);
                } else {
                    Log::warning(tr('Not executing callback function on file ":file", it does not exist (probably dead symlink)', [
                        ':file' => $path . $file
                    ]));
                }
            }
        }

        return $count;
    }



    /**
     * Returns true if this path is on the skip list
     *
     * If part of this path is on the skip list as well, true will also be returned
     *
     * @param string $path
     * @return bool
     */
    protected function skip(string $path): bool
    {
        foreach ($this->skip as $skip) {
            if (str_starts_with($path, $skip)) {
                // Parent of this path (or the path itself) must be skipped
                return true;
            }
        }

        return false;
    }
}