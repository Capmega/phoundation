<?php

declare(strict_types=1);

namespace Phoundation\Filesystem;

use Exception;
use Phoundation\Core\Log\Log;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Interfaces\ExecuteInterface;
use Phoundation\Filesystem\Traits\DataRestrictions;
use Phoundation\Utils\Arrays;
use Throwable;


/**
 * class Execute
 *
 * This library contains various filesystem file related functions
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package Phoundation\Filesystem
 */
class Execute extends Directory implements ExecuteInterface
{
    use DataRestrictions;


    /**
     * Sets if the object will recurse or not
     *
     * @var bool $recurse
     */
    protected bool $recurse = false;

    /**
     * The mode that directories will receive when executing this each
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
     * The directories to $skip
     *
     * @var array $skip
     */
    protected array $skip = [];

    /**
     * Sets if symlinks should be processed
     *
     * @var bool $follow_symlinks
     */
    protected bool $follow_symlinks = false;

    /**
     * Sets if hidden file should be processed
     *
     * @var bool $follow_hidden
     */
    protected bool $follow_hidden = false;

    /**
     * Sets if exceptions will be ingored while processing multiple files
     *
     * @var bool $ignore_exceptions
     */
    protected bool $ignore_exceptions = false;


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
     * @return static
     */
    public function setBlacklistExtensions(array|string|null $blacklist_extensions): static
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
     * @return static
     */
    public function setWhitelistExtensions(array|string|null $whitelist_extensions): static
    {
        $this->whitelist_extensions = Arrays::force($whitelist_extensions);
        return $this;
    }


    /**
     * Returns the directory mode that will be set for each directory
     *
     * @return string|int|null
     */
    public function getMode(): string|int|null
    {
        return $this->mode;
    }


    /**
     * Sets the directory mode that will be set for each directory
     *
     * @param string|int|null $mode
     * @return static
     * @throws OutOfBoundsException if the specified threshold is invalid.
     */
    public function setMode(string|int|null $mode): static
    {
        $this->mode = get_null($mode);
        return $this;
    }


    /**
     * Returns if exceptions will be ignored during the processing of multiple files
     *
     * @return bool
     */
    public function getIgnoreExceptions(): bool
    {
        return $this->ignore_exceptions;
    }


    /**
     * Sets if exceptions will be ignored during the processing of multiple files
     *
     * @param bool $ignore_exceptions
     * @return static
     * @throws OutOfBoundsException if the specified threshold is invalid.
     */
    public function setIgnoreExceptions(bool $ignore_exceptions): static
    {
        $this->ignore_exceptions = $ignore_exceptions;
        return $this;
    }


    /**
     * Returns if symlinks should be processed
     *
     * @return bool
     */
    public function getFollowSymlinks(): bool
    {
        return $this->follow_symlinks;
    }


    /**
     * Sets if symlinks should be processed
     *
     * @param bool $follow_symlinks
     * @return static
     * @throws OutOfBoundsException if the specified threshold is invalid.
     */
    public function setFollowSymlinks(bool $follow_symlinks): static
    {
        $this->follow_symlinks = $follow_symlinks;
        return $this;
    }


    /**
     * Returns if hidden file should be processed
     *
     * @return bool
     */
    public function getFollowHidden(): bool
    {
        return $this->follow_hidden;
    }


    /**
     * Sets if hidden file should be processed
     *
     * @param bool $follow_hidden
     * @return static
     * @throws OutOfBoundsException if the specified threshold is invalid.
     */
    public function setFollowHidden(bool $follow_hidden): static
    {
        $this->follow_hidden = $follow_hidden;
        return $this;
    }


    /**
     * Returns the directory that will be skipped
     *
     * @return array
     */
    public function getSkipDirectories(): array
    {
        return $this->skip;
    }


    /**
     * Clears the directories that will be skipped
     *
     * @return static
     */
    public function clearSkipDirectories(): static
    {
        $this->skip = [];
        return $this;
    }


    /**
     * Sets the directories that will be skipped
     *
     * @param string|array $directories
     * @return static
     * @throws OutOfBoundsException if the specified threshold is invalid.
     */
    public function setSkipDirectories(string|array $directories): static
    {
        $this->skip = [];
        return $this->addSkipDirectories(Arrays::force($directories, ''));
    }


    /**
     * Adds the directories that will be skipped
     *
     * @param string|array $directories
     * @return static
     * @throws OutOfBoundsException if the specified threshold is invalid.
     */
    public function addSkipDirectories(string|array $directories): static
    {
        foreach ($directories as $directory) {
            $this->addSkipDirectory($directory);
        }

        return $this;
    }


    /**
     * Sets the directory that will be skipped
     *
     * @param string $directory
     * @return static
     * @throws OutOfBoundsException if the specified threshold is invalid.
     */
    public function addSkipDirectory(string $directory): static
    {
        if ($directory) {
            $this->skip[] = Filesystem::absolute($directory);
        }

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
     * @return static
     */
    public function setRecurse(bool $recurse): static
    {
        $this->recurse = $recurse;
        return $this;
    }



    /**
     * Execute the callback function on each file in the specified directory
     *
     * @param callable $callback
     * @return void
     */
    public function onDirectoryOnly(callable $callback): void
    {
        $this->restrictions->check($this->path, true);

        foreach (Arrays::force($this->path, '') as $this->path) {
            // Get al files in this directory
            $this->path = Filesystem::absolute($this->path);

            // Skip this directory
            if ($this->skip($this->path)) {
                continue;
            }

            if ($this->mode) {
                $mode = $this->switchMode($this->mode);
            }

            Log::action(tr('Executing callback function on directory ":directory"', [
                ':directory' => $this->path
            ]), 2);

            $callback($this->path);

            // Return original file mode
            if (isset($mode)) {
                $this->chmod($mode);
            }
        }
    }


    /**
     * Execute the callback function on each file in the specified directory
     *
     * @param callable $callback
     * @return int
     */
    public function onFiles(callable $callback): int
    {
        $count = 0;
        $files = [];

        // Get al files in this directory
        $this->path = Filesystem::absolute($this->path);

        // Skip this directory?
        if ($this->skip($this->path)) {
            return 0;
        }

        if ($this->mode) {
            // Temporarily change mode for this callback
            $mode = $this->switchMode($this->mode);
        }

        try {
            $files = scandir($this->path);
        } catch (Exception $e) {
            Directory::new($this->path, $this->restrictions)->checkReadable(previous_e: $e);
        }

        foreach ($files as $file) {
            if (($file === '.') or ($file === '..')) {
                // skip these
                continue;
            }

            if ($file[0] === '.') {
                if (!$this->follow_hidden) {
                    Log::warning(tr('Not following directory ":directory", hidden files are ignored', [
                        ':directory' => $this->path . $file
                    ]), 2);
                }
            }

            if (is_link($file)) {
                if (!$this->follow_symlinks) {
                    Log::warning(tr('Not following directory ":directory", symlinks are ignored', [
                        ':directory' => $this->path . $file
                    ]), 2);
                }
            }

            if (is_dir($this->path . $file)) {
                // Directory! Recurse?
                if (!$this->recurse) {
                    continue;
                }

                $recurse = clone $this;

                $count += $recurse
                    ->setPath($this->path . $file)
                    ->onFiles($callback);

            } elseif (file_exists($this->path . $file)) {
                // Execute the callback
                $count++;
                $extension = Filesystem::getExtension($file);

                if ($this->whitelist_extensions) {
                    // Extension MUST be on this list
                    if (!array_key_exists($extension, $this->whitelist_extensions)) {
                        Log::warning(tr('Not executing callback function on file ":file", the extension is not whitelisted', [
                            ':file' => $this->path . $file
                        ]), 2);
                    }
                }

                if ($this->blacklist_extensions) {
                    // Extension MUST NOT be on this list
                    if (array_key_exists($extension, $this->whitelist_extensions)) {
                        Log::warning(tr('Not executing callback function on file ":file", the extension is blacklisted', [
                            ':file' => $this->path . $file
                        ]), 2);
                    }
                }

                Log::action(tr('Executing callback function on file ":file"', [
                    ':file' => $this->path . $file
                ]), 2);

                try {
                    $callback($this->path . $file);

                } catch (Throwable $e) {
                    if (!$this->ignore_exceptions) {
                        // Exceptions will pass!
                        throw $e;
                    }

                    // Exceptions will be ignored
                    Log::warning(tr('File ":file" encountered exception ":e" which will be ignored', [
                        ':file' => $file,
                        ':e'    => $e->getMessage()
                    ]));
                }
            } else {
                Log::warning(tr('Not executing callback function on file ":file", it does not exist (probably dead symlink)', [
                    ':file' => $this->path . $file
                ]));
            }

            // Return original file mode
            if (isset($mode)) {
                $this->chmod($mode);
            }
        }

        return $count;
    }


    /**
     * Returns true if this directory is on the skip list
     *
     * If part of this directory is on the skip list as well, true will also be returned
     *
     * @param string $directory
     * @return bool
     */
    protected function skip(string $directory): bool
    {
        foreach ($this->skip as $skip) {
            if (str_starts_with($directory, $skip)) {
                // Parent of this directory (or the directory itself) must be skipped
                return true;
            }
        }

        return false;
    }
}
