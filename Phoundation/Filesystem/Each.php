<?php

namespace Phoundation\Filesystem;

use Exception;
use Phoundation\Core\Arrays;
use Phoundation\Core\Core;
use Phoundation\Core\Log;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Exception\FilesystemException;


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
     * File object
     *
     * @var File $file
     */
    protected File $file;

    /**
     * Filesystem restrictions
     *
     * @var Restrictions $restrictions
     */
    protected Restrictions $restrictions;

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
     * The mode that paths will receive when executing this each
     *
     * @var string|int|null $mode
     */
    protected string|int|null $mode = null;

    /**
     * If set, will NOT execute on the specified extensions
     *
     * @var array|null $blacklist_extensions
     */
    protected ?array $blacklist_extensions = null;

    /**
     * If set, will only execute on the specified extensions
     *
     * @var array|null $whitelist_extensions
     */
    protected ?array $whitelist_extensions = null;

    /**
     * The paths to $skip
     *
     * @var array $skip
     */
    protected array $skip = [];



    /**
     * Each class constructor
     *
     * @param File $file The filesystem object that creates this Each object
     * @param array|string|null $paths
     */
    public function __construct(File $file, array|string|null $paths = null)
    {
        $this->file         = $file;
        $this->paths        = $paths;
        $this->restrictions = $file->getRestrictions();
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
     * Returns the extensions that are blacklisted
     *
     * @return array
     */
    public function getBlacklistExtensions(): array
    {
        return $this->blacklist_extensions;
    }



    /**
     * Sets the extensions that are blacklisted
     *
     * @param string|array|null $blacklist_extensions
     * @return Each
     */
    public function setBlacklistExtensions(array|string|null $blacklist_extensions): Each
    {
        $this->blacklist_extensions = Arrays::force($blacklist_extensions);
        return $this;
    }



    /**
     * Returns the extensions that are whitelisted
     *
     * @return array
     */
    public function getWhitelistExtensions(): array
    {
        return $this->whitelist_extensions;
    }



    /**
     * Sets the extensions that are whitelisted
     *
     * @param string|array|null $whitelist_extensions
     * @return Each
     */
    public function setWhitelistExtensions(array|string|null $whitelist_extensions): Each
    {
        $this->whitelist_extensions = Arrays::force($whitelist_extensions);
        return $this;
    }



    /**
     * Returns the path mode that will be set for each path
     *
     * @return int
     */
    public function getPathMode(): int
    {
        return $this->mode;
    }



    /**
     * Sets the path mode that will be set for each path
     *
     * @param string|int|null $mode
     * @return Each
     * @throws OutOfBoundsException if the specified threshold is invalid.
     */
    public function setPathMode(string|int|null $mode): Each
    {
        $this->mode = get_null($mode);
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

        $this->restrictions->check($this->paths, true);

        foreach (Arrays::force($this->paths, '') as $path) {
            // Get al files in this directory
            $path  = Path::absolute($path);

            if (!$this->skip($path)) {
                if ($this->mode) {
                    // Temporarily change mode for this callback
                    $mode = $this->file->mode($path);
                    $this->file->chmod($path, $this->mode);
                }

                try {
                    $files = scandir($path);
                } catch (Exception $e) {
                    Path::checkReadable($path, previous_e:  $e);
                }
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
                    $extension = $this->file->getExtension($file);

                    if ($this->whitelist_extensions) {
                        // Extension MUST be on this list
                        if (!array_key_exists($extension, $this->whitelist_extensions)) {
                            Log::warning(tr('Not executing callback function on file ":file", the extension is not whitelisted', [
                                ':file' => $path . $file
                            ]), 2);
                        }
                    }

                    if ($this->blacklist_extensions) {
                        // Extension MUST NOT be on this list
                        if (array_key_exists($extension, $this->whitelist_extensions)) {
                            Log::warning(tr('Not executing callback function on file ":file", the extension is blacklisted', [
                                ':file' => $path . $file
                            ]), 2);
                        }
                    }

                    Log::action(tr('Executing callback function on file ":file"', [
                        ':file' => $path . $file
                    ]), 2);

                    $callback($path . $file);
                } else {
                    Log::warning(tr('Not executing callback function on file ":file", it does not exist (probably dead symlink)', [
                        ':file' => $path . $file
                    ]));
                }
            }

            // Return original file mode
            $this->file->chmod($path, $mode);
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