<?php

namespace Phoundation\Filesystem;

use Exception;
use Phoundation\Core\Arrays;
use Phoundation\Core\Core;
use Phoundation\Core\Log;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Servers\Server;
use Throwable;


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
class Execute
{
    /**
     * The server object
     *
     * @var Server $server
     */
    protected Server $server;

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
     * Each class constructor
     *
     * @param array|string|null $paths
     * @param Server|array|string|null $server
     */
    public function __construct(array|string|null $paths = null, Server|array|string|null $server = null)
    {
        $this->paths  = $paths;
        $this->server = Core::ensureServer($server);
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
     * @return Execute
     * @throws OutOfBoundsException if the specified threshold is invalid.
     */
    public function setPath(string|array $paths): Execute
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
     * @return Execute
     */
    public function setBlacklistExtensions(array|string|null $blacklist_extensions): Execute
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
     * @return Execute
     */
    public function setWhitelistExtensions(array|string|null $whitelist_extensions): Execute
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
     * @return Execute
     * @throws OutOfBoundsException if the specified threshold is invalid.
     */
    public function setMode(string|int|null $mode): Execute
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
     * @return Execute
     * @throws OutOfBoundsException if the specified threshold is invalid.
     */
    public function setIgnoreExceptions(bool $ignore_exceptions): Execute
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
     * @return Execute
     * @throws OutOfBoundsException if the specified threshold is invalid.
     */
    public function setFollowSymlinks(bool $follow_symlinks): Execute
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
     * @return Execute
     * @throws OutOfBoundsException if the specified threshold is invalid.
     */
    public function setFollowHidden(bool $follow_hidden): Execute
    {
        $this->follow_hidden = $follow_hidden;
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
     * @return Execute
     * @throws OutOfBoundsException if the specified threshold is invalid.
     */
    public function setSkipPaths(string|array $paths): Execute
    {
        $this->skip = [];
        return $this->addSkipPaths(Arrays::force($paths, ''));
    }



    /**
     * Sets the path that will be skipped
     *
     * @param string|array $paths
     * @return Execute
     * @throws OutOfBoundsException if the specified threshold is invalid.
     */
    public function addSkipPaths(string|array $paths): Execute
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
     * @return Execute
     * @throws OutOfBoundsException if the specified threshold is invalid.
     */
    public function addSkipPath(string $path): Execute
    {
        if ($path) {
            $this->skip[] = Filesystem::absolute($path);
        }

        return $this;
    }



    /**
     * Returns the server and filesystem restrictions
     *
     * @return Server
     */
    public function getServer(): Server
    {
        return $this->server;
    }



    /**
     * Sets the server and filesystem restrictions
     *
     * @param Server|array|string|null $server
     * @return static
     */
    public function setServer(Server|array|string|null $server = null): static
    {
        $this->server = Core::ensureServer($server);
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
     * @return Execute
     */
    public function setRecurse(bool $recurse): Execute
    {
        $this->recurse = $recurse;
        return $this;
    }




    /**
     * Execute the callback function on each file in the specified path
     *
     * @param callable $callback
     * @return void
     */
    public function onPathOnly(callable $callback): void
    {
        $this->server->checkRestrictions($this->paths, true);

        foreach (Arrays::force($this->paths, '') as $path) {
            // Get al files in this directory
            $path  = Filesystem::absolute($path);

            // Skip this path
            if ($this->skip($path)) {
                continue;
            }

            if ($this->mode) {
                $mode = File::new($path, $this->server)->switchMode($this->mode);
            }

            Log::action(tr('Executing callback function on path ":path"', [
                ':file' => $path
            ]), 2);

            $callback($path);

            // Return original file mode
            if (isset($mode)) {
                File::new($path, $this->server)->chmod($mode);
            }
        }
    }



    /**
     * Execute the callback function on each file in the specified path
     *
     * @param callable $callback
     * @return int
     */
    public function onFiles(callable $callback): int
    {
        $count = 0;
        $files = [];

        $this->server->checkRestrictions($this->paths, true);

        foreach (Arrays::force($this->paths, '') as $path) {
            // Get al files in this directory
            $path = Filesystem::absolute($path);

            // Skip this path?
            if (!$this->skip($path)) {
                continue;
            }

            if ($this->mode) {
                // Temporarily change mode for this callback
                $mode = File::new($path, $this->server)->switchMode($this->mode);
            }

            try {
                $files = scandir($path);
            } catch (Exception $e) {
                Path::new($path, $this->server)->checkReadable(previous_e: $e);
            }

            foreach ($files as $file) {
                if (($file === '.') or ($file === '..')) {
                    // skip these
                    continue;
                }

                if ($file[0] === '.') {
                    if (!$this->follow_hidden) {
                        Log::warning(tr('Not following path ":path", hidden files are ignored', [
                            ':path' => $path . $file
                        ]), 2);
                    }
                }

                if (is_link($file)) {
                    if (!$this->follow_symlinks) {
                        Log::warning(tr('Not following path ":path", symlinks are ignored', [
                            ':path' => $path . $file
                        ]), 2);
                    }
                }

                if (is_dir($path . $file)) {
                    // Directory! Recurse?
                    if (!$this->recurse) {
                        continue;
                    }

                    $recurse = clone $this;

                    $count += $recurse
                        ->setPath($path . $file)
                        ->onFiles($callback);

                } elseif (file_exists($path . $file)) {
                    // Execute the callback
                    $count++;
                    $extension = Filesystem::getExtension($file);

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

                    try {
                        $callback($path . $file);

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
                        ':file' => $path . $file
                    ]));
                }
            }

            // Return original file mode
            File::new($path, $this->server)->chmod($mode);
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