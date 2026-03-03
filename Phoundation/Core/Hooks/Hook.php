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
 * @see       \Phoundation\Data\DataEntries\DataEntry
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Core
 */


declare(strict_types=1);

namespace Phoundation\Core\Hooks;

use Phoundation\Core\Core;
use Phoundation\Core\Hooks\Exception\HookNotExistsException;
use Phoundation\Core\Hooks\Interfaces\HookInterface;
use Phoundation\Core\Log\Log;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Exception\FileNotReadableException;
use Phoundation\Filesystem\PhoDirectory;
use Phoundation\Filesystem\PhoRestrictions;
use Phoundation\Filesystem\Interfaces\PhoDirectoryInterface;
use Phoundation\Filesystem\Interfaces\PhoFileInterface;
use Phoundation\Utils\Strings;
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
     * @var PhoDirectoryInterface $_directory
     */
    protected PhoDirectoryInterface $_directory;

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
        $this->_directory = new PhoDirectory(DIRECTORY_HOOKS, PhoRestrictions::newHooks());

        if ($class) {
            $this->class      = Strings::ensureEndsNotWith(trim($class), '/') . '/';
            $this->_directory = $this->_directory->addDirectory($this->class);
        }
    }


    /**
     * Returns this hook object as a string
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->getFileObject()->getSource();
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
            'file'      => $this->getFileObject(),
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
        return $this->_directory->addPath(Strings::ensureEndsWith($hook, '.php'))->exists();
    }


    /**
     * Checks if the specified hook file exists for the specified class and throws a HookNotExistsException if it does not
     *
     * @param string      $class            The hook class (typically the parent directory where the hook files are located) must be a sub directory of ROOT/hooks/
     * @param string|null $hook             The hook filename that should exist
     * @param bool        $exception [true] If true, will throw an exception if the specified class / hook does not exist, else returns false instead
     *
     * @return bool
     */
    public static function checkExists(string $class, ?string $hook, bool $exception = true): bool
    {
        if ($hook === null) {
            $hook  = Strings::fromReverse($class, '/');
            $class = Strings::untilReverse($class, '/');
        }

        if (Hook::new($class)->exists($hook)) {
            return true;
        }

        if ($exception) {
            throw HookNotExistsException::new(ts('The specified hook ":class:hook" does not exist', [
                ':class' => $class,
                ':hook'  => $hook,
            ]));
        }

        return false;
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
     * Returns the PhoFileInterface file object for the specified hook
     *
     * @param string|null $hook
     *
     * @return PhoFileInterface
     */
    public function getFileObject(?string $hook = null): PhoFileInterface
    {
        if ($hook) {
            $this->setHook($hook);
        }

        return $this->_directory->addPath($this->hook . '.php');
    }


    /**
     * Sets the hook file for this Hook object
     *
     * @param string|null $hook The hook identifier
     *
     * @return static
     */
    protected function setHook(?string $hook): static
    {
        if ($hook) {
            if (empty($this->class)) {
                $this->setClass(Strings::untilReverse($hook, '/'))
                     ->hook  = Strings::ensureEndsNotWith(Strings::fromReverse($hook, '/'), '.php');

            } else {
                $this->hook = Strings::ensureEndsNotWith(Strings::ensureBeginsNotWith($hook, '/'), '.php');
            }

        } else {
            $this->hook = null;
        }

        return $this;
    }


    /**
     * Sets the class directory for this Hook object
     *
     * @param string|null $class The class directory
     *
     * @return static
     */
    protected function setClass(?string $class): static
    {
        if ($class) {
            $this->class = Strings::ensureEndsWith(Strings::ensureBeginsNotWith($class, '/'), '/');

        } else {
            $this->class = null;
        }

        return $this;
    }


    /**
     * Sets arguments to pass to the hook
     *
     * @param array|null $arguments
     *
     * @return $this
     */
    protected function setArguments(?array $arguments = []): static
    {
        $this->arguments = $arguments;
        return $this;
    }


    /**
     * Attempts to execute the specified hooks
     *
     * Reminders:
     * * ROOT is the root directory of this project
     * * All hooks classes start in ROOT/hooks (symlink to ROOT/data/system/cache/hooks)
     * * A hook class is a partial path, so it may contain /
     * * A hook class may (for example) be accounts/users
     * * A hook itself is a filename may only contain letters, numbers, and dashes and may (optionally, and not recommended for clarity) end with .php
     * * The hook "notify" in the class "accounts/users" will execute the hook ROOT/data/system/cache/hooks/accounts/users/notify.php IF the file exists
     *
     * @param string|null $hook             The hook filename to execute. To execute, the filename must be in the directory for the specified class.
     * @param array|null  $arguments [null] The arguments to pass along to the hook, if it exists
     *
     * @return mixed
     */
    public function execute(?string $hook, ?array $arguments = null): mixed
    {
        if (Core::inInitState()) {
            // Do not execute hooks during project initialization, too many unexpected side effects are possible!
            Log::warning(ts('Not executing hook ":hook" due to system being in init state', [
                ':hook' => $hook
            ]), 3);

            return $this;
        }

        if (empty($hook)) {
            // No hook specified, do not execute anything
            return null;
        }

        $this->setHook($hook)
             ->setArguments($arguments);

        $_file = $this->getFileObject($hook);

        if (!$_file->exists()) {
            // Only execute existing files
            return null;
        }

        // Try executing it!
        Log::action(ts('Executing hook ":hook"', [
            ':hook' => $this->class . $this->hook,
        ]));

        try {
            return execute_hook($_file->getSource(), $this);

        } catch (Throwable $e) {
            // Ensure its readable, not a path, within the filesystem restrictions, etc...
            try {
                $_file->checkReadable();

            } catch (FileNotReadableException $e) {
                Log::Warning(ts('Failed to execute hook ":hook". The file exists, but is not readable', [
                    ':hook' => $hook,
                ]));

            } catch (Throwable $e) {
                // fall through
            }

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
