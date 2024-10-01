<?php

/**
 * Hook class
 *
 * This class can manage and (attempt to) execute specified hook scripts.
 *
 * Hook scripts are optional scripts that will be executed if they exist. Hook scripts are located in
 * DIRECTORY_DATA/system/hooks/HOOK and DIRECTORY_DATA/hooks/CLASS/HOOK. CLASS is an identifier for multiple hook
 * scripts that all have to do with the same system, to group them together. HOOK is the script to be executed
 *
 * @see       \Phoundation\Data\DataEntry\DataEntry
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Core
 */


declare(strict_types=1);

namespace Phoundation\Core\Hooks;

use Phoundation\Core\Core;
use Phoundation\Core\Hooks\Interfaces\HookInterface;
use Phoundation\Core\Log\Log;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Exception\FilesystemException;
use Phoundation\Filesystem\FsDirectory;
use Phoundation\Filesystem\FsRestrictions;
use Phoundation\Filesystem\Interfaces\FsDirectoryInterface;
use Phoundation\Filesystem\Interfaces\FsFileInterface;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Components\P;
use Throwable;


class Hook implements HookInterface
{
    /**
     * The class of hooks that executes
     *
     * @var string|null $class
     */
    protected ?string $class;

    /**
     * The name of the hook that executes
     *
     * @var string|null $hook
     */
    protected ?string $hook;

    /**
     * The place where all hook scripts live
     *
     * @var FsDirectoryInterface $directory
     */
    protected FsDirectoryInterface $directory;

    /**
     * Parameters sent by the executing script
     *
     * @var array
     */
    protected array $arguments;


    /**
     * Hook class constructor
     *
     * @param string|null $class
     */
    public function __construct(?string $class = null)
    {
        $this->directory = new FsDirectory(DIRECTORY_HOOKS, FsRestrictions::newHooks());
        $this->class     = Strings::ensureEndsNotWith(trim($class), '/') . '/';

        if ($this->class) {
            $this->directory = $this->directory->addDirectory($this->class);
        }
    }


    /**
     * Returns this hook object as a string
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->getFile();
    }


    /**
     * Returns this hook object as an array
     *
     * @return array
     */
    public function __toArray(): array
    {
        return [
            'class'     => $this->class,
            'hook'      => $this->hook,
            'file'      => $this->getFile(),
            'arguments' => $this->arguments,
        ];
    }


    /**
     * Returns a new Hook object
     *
     * @param string|null $class
     *
     * @return static
     */
    public static function new(?string $class = null): static
    {
        return new static($class);
    }


    /**
     * Returns true if the specified hook exists, false otherwise
     *
     * @param string $hook
     *
     * @return mixed
     */
    public function exists(string $hook): bool
    {
        return $this->directory->addFile($hook . '.php')->exists();
    }


    /**
     * Returns the hook class
     *
     * @return string
     */
    public function getClass(): string
    {
        return $this->class;
    }


    /**
     * Returns the FsFileInterface file object for the specified hook
     *
     * @param string|null $hook
     *
     * @return mixed
     */
    public function getFile(?string $hook = null): FsFileInterface
    {
        return $this->directory->addFile(($hook ?? $this->hook) . '.php');
    }


    /**
     * Attempts to execute the specified hooks
     *
     * @param string     $hook
     * @param array|null $arguments
     *
     * @return mixed
     */
    public function execute(string $hook, ?array $arguments = []): mixed
    {
        if (Core::inInitState()) {
            // Do not execute hooks during system initialization, too many unexpected side effects are possible!
            Log::warning(tr('Not executing hook ":hook" due to system being in init state', [
                ':hook' => $hook
            ]), 3);

            return $this;
        }

        $this->hook      = $hook;
        $this->arguments = $arguments;
        $file            = $this->getFile($hook);

        if (!$file->exists()) {
            // Only execute existing files
            return null;
        }

        // Ensure its readable, not a path, within the filesystem restrictions, etc...
        try {
            $file->checkReadable();

        } catch (FilesystemException $e) {
            Log::Warning(tr('Failed to execute hook ":hook". The file exists, but is not readable', [
                ':hook' => $hook,
            ]));
        }

        // Try executing it!
        Log::action(tr('Executing hook ":hook"', [
            ':hook' => $this->class . $hook,
        ]));

        try {
            return execute_hook($file->getSource(), $this);

        } catch (Throwable $e) {
            Log::error(tr('Execution of hook ":hook" failed with the following exception', [
                ':hook' => $file
            ]));

            Log::error($e);

            throw $e;
        }
    }


    /**
     * Returns the specified parameters key, or exception if it does not exist
     *
     * @param string $key
     *
     * @return mixed
     */
    public function get(string $key): mixed
    {
        if (array_key_exists($key, $this->arguments)) {
            return $this->arguments[$key];
        }

        throw new OutOfBoundsException(tr('The specified hook parameter key ":key" does not exist', [
            ':key' => $key
        ]));
    }


    /**
     * Returns all source argumentS
     *
     * @return mixed
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }


    /**
     * Returns the requested source argument
     *
     * @param string|int $key
     *
     * @return mixed
     */
    public function getArgument(string|int $key): mixed
    {
        return isset_get($this->arguments[$key]);
    }


    /**
     * Returns the specified hook
     *
     * This is used in hook files themselves so that editors and static compilers know where the hell the variable
     * $hook came from
     *
     * @param HookInterface $hook
     *
     * @return HookInterface
     */
    public static function ensure(HookInterface &$hook): HookInterface
    {
        return $hook;
    }
}
