<?php

/**
 * Library class
 *
 * This library can initialize all other libraries
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */

declare(strict_types=1);

namespace Phoundation\Core\Libraries;

use Error;
use Phoundation\Core\Core;
use Phoundation\Core\Enums\EnumLibraryType;
use Phoundation\Core\Libraries\Exception\LibrariesException;
use Phoundation\Core\Libraries\Exception\LibrariesInitException;
use Phoundation\Core\Libraries\Exception\LibraryExistsException;
use Phoundation\Core\Libraries\Interfaces\LibraryInterface;
use Phoundation\Core\Libraries\Interfaces\UpdatesInterface;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Plugins\Interfaces\PluginInterface;
use Phoundation\Exception\Exception;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\FsDirectory;
use Phoundation\Filesystem\FsFile;
use Phoundation\Filesystem\Interfaces\FsDirectoryInterface;
use Phoundation\Filesystem\FsRestrictions;
use Phoundation\Os\Processes\Commands\Cp;
use Phoundation\Utils\Json;
use Phoundation\Utils\Strings;
use Throwable;

class Library implements LibraryInterface
{
    /**
     * The library vendor
     *
     * @var string $vendor
     */
    protected string $vendor;

    /**
     * The library name
     *
     * @var string $library
     */
    protected string $library;

    /**
     * The library path
     *
     * @var FsDirectoryInterface $directory
     */
    protected FsDirectoryInterface $directory;

    /**
     * The Updates object for this library
     *
     * @var UpdatesInterface|null
     */
    protected ?UpdatesInterface $updates = null;

    /**
     * The plugin object for this library
     *
     * @var PluginInterface|null $plugin
     */
    protected ?PluginInterface $plugin = null;

    /**
     * Tracks if the structure check has been executed and what the result was
     *
     * @var bool|null
     */
    protected bool|null $structure_ok = null;


    /**
     * Library constructor
     *
     * @param FsDirectoryInterface $directory
     */
    public function __construct(FsDirectoryInterface $directory)
    {
        // Extract vendor and library names
        $this->directory = new FsDirectory($directory, FsRestrictions::new([
            DIRECTORY_WEB,
            LIBRARIES::CLASS_DIRECTORY_SYSTEM,
            LIBRARIES::CLASS_DIRECTORY_PLUGINS,
            LIBRARIES::CLASS_DIRECTORY_TEMPLATES,
        ]));

        $directory     = Strings::slash($directory);
        $this->library = Strings::fromReverse(Strings::unslash($directory), '/');
        $this->library = strtolower($this->library);
        $this->vendor  = strtolower($directory);
        $this->vendor  = Strings::from(Strings::unslash($directory), DIRECTORY_ROOT);
        $this->vendor  = strtolower($this->vendor);
        $this->vendor  = Strings::untilReverse($this->vendor, '/' . $this->library);
        $this->vendor  = strtolower($this->vendor);

        if (str_starts_with($this->vendor, 'plugin')) {
            $this->vendor = Strings::from($this->vendor, 'plugins/');
        }

        // Get the Init object
        $this->loadUpdatesObject()
             ->loadPluginObject();
    }


    /**
     * Loads the Plugin object for this library
     *
     * @return static
     */
    protected function loadPluginObject(): static
    {
        static::loadLibraryFile('Plugin', function (object $object) {
            if ($object instanceof PluginInterface) {
                $this->plugin = $object;

            } else {
                Log::Warning(tr('The Plugin.php file for the library ":library" in ":directory" is invalid, it should contain a class being an instance of the \Phoundation\Libraries\Interfaces\PluginInterface. This file has been ignored', [
                    ':directory' => $this->directory,
                    ':library'   => $this->library,
                ]));
            }
        });

        return $this;
    }


    /**
     * Loads the specified library file
     */
    protected function loadLibraryFile(string $file, callable $callback): void
    {
        // Scan for the Updates.php file
        $file = Strings::slash($this->directory) . 'Library/' . $file . '.php';

        if (!file_exists($file)) {
            // There is no init class available
            return;
        }

        try {
            // Load the PHP file
            include_once($file);

            $class_path = static::getClassPath($file);
            $object     = new $class_path();

            $callback($object);

        } catch (Error $e) {
            Log::warning(tr('Failed to load the ":file" file for library ":library", see the following exception for more information', [
                ':file'    => $file,
                ':library' => $this->getName(),
            ]));

            Exception::new($e)
                     ->log()
                     ->registerDeveloperIncident()
                     ->getNotificationObject()
                     ->send();

            $this->updates = null;
        }
    }


    /**
     * Get the class path from the specified .php file
     *
     * @param string $file
     *
     * @return Object
     */
    public static function getClassPath(string $file): string
    {
        $restrictions = FsRestrictions::getReadonly([
            DIRECTORY_ROOT . 'Phoundation',
            DIRECTORY_ROOT . 'Plugins',
            DIRECTORY_ROOT . 'Templates'
        ], 'Library::getClassPath()');

        if (!FsFile::new($file, $restrictions)->isPhp()) {
            throw new OutOfBoundsException(tr('The specified file ":file" is not a PHP file', [':file' => $file]));
        }

        // Scan for namespace and class lines
        $namespace = null;
        $class     = null;
        $results   = FsFile::new($file, $restrictions)->grep([
            'namespace ',
            'class ',
        ], 100);

        // Get the namespace
        foreach ($results['namespace '] as $line) {
            if (preg_match_all('/^namespace\s+(.+?);$/i', $line, $matches)) {
                $namespace = $matches[1][0];
            }
        }

        if (!$namespace) {
            throw new LibrariesException(tr('Failed to find a namespace for file ":file"', [':file' => $file]));
        }

        // Get the class name
        foreach ($results['class '] as $line) {
            if (preg_match_all('/^(?:abstract )?class\s+([a-z0-9_]+)(?:(?:\s+extends\s+.+?)?\s+\{)?/i', $line, $matches)) {
                $class = $matches[1][0];
            }
        }

        if (!$class) {
            throw new LibrariesException(tr('Failed to find a class for file ":file"', [':file' => $file]));
        }

        // Now we can return the class path
        return Strings::ensureEndsWith($namespace, '\\') . $class;
    }


    /**
     * Returns the library vendor
     *
     * @return string
     */
    public function getVendor(): string
    {
        return $this->vendor;
    }


    /**
     * Returns the library name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->library;
    }


    /**
     * Loads the Updates object for this library
     */
    protected function loadUpdatesObject(): static
    {
        static::loadLibraryFile('Updates', function (object $object) {
            if ($object instanceof UpdatesInterface) {
                $this->updates = $object;

            } else {
                Log::Warning(tr('The Updates.php file for the library ":library" in ":directory" is invalid, it should contain a class being an instance of the \Phoundation\Libraries\Interfaces\UpdatesInterface. This file has been ignored', [
                    ':directory' => $this->directory,
                    ':library'   => $this->library,
                ]));
            }
        });

        return $this;
    }


    /**
     * Includes the .php file for the specified class path and returns the specified class path
     *
     * @param object|string $class_path
     * @param bool|null     $check_php
     *
     * @return string
     */
    public static function includeClassFile(object|string $class_path, ?bool $check_php = null): string
    {
        if ($check_php === null) {
            // PHP check only done on non-production environments (on production we want to save the CPU cycles)
            $check_php = !Core::isProductionEnvironment();
        }

        $file = Library::getClassFile($class_path, $check_php);

        Log::action(tr('Manually including class file ":file"', [':file' => $file]), 2);

        include_once($file);

        return $class_path;
    }


    /**
     * Get the .php file for the specified class path
     *
     * @param object|string $class_path
     * @param bool          $check_php
     *
     * @return string
     */
    public static function getClassFile(object|string $class_path, bool $check_php = true): string
    {
        if (!$class_path) {
            throw new OutOfBoundsException(tr('No class path specified'));
        }

        if (is_object($class_path)) {
            $class_path = get_class($class_path);
        }

        $file = str_replace('\\', '/', $class_path);
        $file = Strings::ensureStartsNotWith($file, '/');
        $file = DIRECTORY_ROOT . $file . '.php';

        if ($check_php) {
            if (!FsFile::new($file, FsRestrictions::getReadonly([DIRECTORY_ROOT . 'Phoundation', DIRECTORY_ROOT . 'Plugins', DIRECTORY_ROOT . 'Templates']))->isPhp()) {
                throw new OutOfBoundsException(tr('The specified file ":file" is not a PHP file', [':file' => $file]));
            }
        }

        return $file;
    }


    /**
     * Creates a new library of the specified type
     *
     * @param string          $name
     * @param EnumLibraryType $type
     *
     * @return static
     */
    public static function create(string $name, EnumLibraryType $type): static
    {
        // Library names must be CamelCased
        if (!Strings::isCamelCase($name)) {
            throw new OutOfBoundsException(tr('Invalid library name ":name" specified, the library name must be CamelCase', [
                ':name' => $name,
            ]));
        }

        if (file_exists(DIRECTORY_ROOT . $type->value . $name)) {
            throw new LibraryExistsException(tr('Cannot create ":type" type library ":name", it already exists', [
                ':type' => $type,
                ':name' => $name,
            ]));
        }

        // Copy the library from the TemplateLibrary and run a search / replace
        Cp::new()
          ->archive(DIRECTORY_ROOT . 'Phoundation/.TemplateLibrary', FsRestrictions::new(DIRECTORY_ROOT . 'Phoundation/'), DIRECTORY_ROOT . $type->value . $name, FsRestrictions::new(DIRECTORY_ROOT . $type->value, true));

        foreach (['Updates.php'] as $file) {
            FsFile::new(DIRECTORY_ROOT . $type->value . $name . '/Library/' . $file, FsRestrictions::new(DIRECTORY_ROOT . $type->value, true))
                ->replace([
                    ':type' => $type,
                    ':name' => $name,
                ]);
        }

        // Done, return the library as an object
        return Library::get($name);
    }


    /**
     * Returns a new Library object for the specified library
     *
     * @param string $name
     *
     * @return LibraryInterface
     */
    public static function get(string $name): LibraryInterface
    {
        if (str_contains($name, '/')) {
            // This is TYPE/NAME
            $type = Strings::until($name, '/');
            $name = Strings::from($name, '/');

            switch ($type) {
                case 'system':
                    // no break

                case 'plugin':
                    // no break

                case 'template':
                    break;

                default:
                    throw new OutOfBoundsException(tr('Unknown library type "" specified, please specify one of "system", "plugin", or "template"', [
                        ':type' => $type,
                    ]));
            }

        } else {
            $type = null;
        }

        return Libraries::findLibrary($name, (($type === 'system') or ($type === null)), (($type === 'plugin') or ($type === null)), (($type === 'template') or ($type === null)));
    }


    /**
     * Return the object contents in JSON string format
     *
     * @return string
     */
    public function __toString(): string
    {
        return Json::encode($this);
    }


    /**
     * Return the object contents in array format
     *
     * @return array
     */
    public function __toArray(): array
    {
        return [
            'name'        => $this->getName(),
            'version'     => $this->getVersion(),
            'description' => $this->getDescription(),
        ];
    }


    /**
     * Returns the version for this library
     *
     * @return string|null
     */
    public function getVersion(): ?string
    {
        return $this->updates?->version();
    }


    /**
     * Returns the description for this library
     *
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->plugin?->getDescription();
    }


    /**
     * Initialize this library
     *
     * @param string|null $comments
     *
     * @return bool True if the library had updates applied
     */
    public function init(?string $comments): bool
    {
        if ($this->library === 'libraries') {
            // TODO Check later if we should be able to let init initialize itself
            // Never initialize the Init library itself!
            Log::warning(tr('Not initializing library "library", it has no versioning control available'));

            return false;
        }

        if ($this->updates === null) {
            // This library has no Init available, skip!
            Log::warning(tr('Not processing library ":library", it has no versioning control defined', [
                ':library' => $this->library,
            ]), 3);

            return false;
        }

        try {
            $this->updates->init($comments);

        } catch (Throwable $e) {
            throw new LibrariesInitException(tr('Failed to initialize library ":library" with version ":version"', [
                ':version' => $this->getVersion(),
                ':library' => $this->getName()
            ]), $e);
        }

        return true;
    }


    /**
     * Executes POST init files for this library
     *
     * @param string|null $comments
     *
     * @return bool True if the library had updates applied
     */
    public function initPost(?string $comments): bool
    {
        // TODO Check later if we should be able to let init initialize itself
        if ($this->library === 'libraries') {
            // Never initialize the Init library itself!
            Log::warning(tr('Not initializing library "library", it has no versioning control available'));

            return false;
        }

        if ($this->updates === null) {
            // This library has no Init available, skip!
            Log::warning(tr('Not processing library ":library", it has no versioning control defined', [
                ':library' => $this->library,
            ]), 3);

            return false;
        }

        return $this->updates->initPost($comments);
    }


    /**
     * Returns true if the library is a system library
     *
     * @return bool
     */
    public function isSystem(): bool
    {
        return $this->getType() === 'system';
    }


    /**
     * Returns the type of library; system or plugin
     *
     * @return string
     */
    public function getType(): string
    {
        $directory = Strings::unslash($this->directory);
        $directory = Strings::untilReverse($directory, '/');
        $directory = Strings::fromReverse($directory, '/');
        $directory = strtolower($directory);

        if ($directory === 'phoundation') {
            return 'system';
        }

        if ($directory === 'templates') {
            return 'template';
        }

        return 'plugin';
    }


    /**
     * Returns true if the library is a plugin library
     *
     * @return bool
     */
    public function isPlugin(): bool
    {
        return $this->getType() === 'plugins';
    }


    /**
     * Returns true if the library is a template library
     *
     * @return bool
     */
    public function isTemplate(): bool
    {
        return $this->getType() === 'template';
    }


    /**
     * Returns the code version for this library
     *
     * @return string|null
     */
    public function getCodeVersion(): ?string
    {
        return $this->updates?->getCodeVersion();
    }


    /**
     * Returns the database version for this library
     *
     * @return string|null
     */
    public function getDatabaseVersion(): ?string
    {
        return $this->updates?->getDatabaseVersion();
    }


    /**
     * Returns the database version for this library
     *
     * @return string|null
     */
    public function getNextInitVersion(): ?string
    {
        return $this->updates?->getNextInitVersion();
    }


    /**
     * Returns the size of all files in this library in bytes
     *
     * @return int
     */
    public function getSize(): int
    {
        return $this->directory->treeFileSize();
    }


    /**
     * Returns the PhpStatistics object for this library
     *
     * @return array
     */
    public function getPhpStatistics(): array
    {
        return $this->directory->getPhpStatistics(true);
    }


    /**
     * Returns the library path
     *
     * @return FsDirectoryInterface
     */
    public function getDirectory(): FsDirectoryInterface
    {
        return $this->directory;
    }


    /**
     * Update the version registration for this version to be the specified version
     *
     * @param string      $version
     * @param string|null $comments
     *
     * @return void
     */
    public function setVersion(string $version, ?string $comments = null): void
    {
        Log::action(tr('Forcing version for library ":library" to ":version"', [
            ':library' => $this->getName(),
            ':version' => $version,
        ]));

        $int_version = Version::getInteger($version);

        // Delete any version that is higher than the specified version
        sql()->query('DELETE FROM `core_versions` WHERE `library` = :library AND `version` > :version', [
            ':library' => $this->getName(),
            ':version' => $int_version,
        ]);

        // Get the highest version. If it's lower than requested, insert the requested version so that we're exactly at
        // the right version
        $int_current = sql()->getColumn('SELECT MAX(`version`) FROM `core_versions` WHERE `library` = :library', [
            ':library' => $this->getName(),
        ]);

        if ($int_current < $int_version) {
            if ($comments === null) {
                // Default comment
                $comments = tr('Forced library to this version');
            }

            sql()->insert('core_versions', [
                'library'  => $this->library,
                'version'  => $int_version,
                'comments' => $comments,
            ]);
        }
    }


    /**
     * This method will verify that the library is in working order, that the commands are symlinked, etc
     *
     * @return static
     */
    public function verify(): static
    {
        if ($this->structure_ok === null) {
            // Execute the structural check for this library
            // TODO IMPLEMENT MORE
            $this->structure_ok = true;
        }

        return $this;
    }


    /**
     * Ensure that the Library/commands is symlinked
     *
     * @param FsDirectoryInterface $cache
     * @param FsDirectoryInterface $tmp
     *
     * @return void
     * @todo Add support for command sharing!
     */
    public function rebuildCommandCache(FsDirectoryInterface $cache, FsDirectoryInterface $tmp): void
    {
        Log::action(tr('Rebuilding command cache for library ":library"', [
            ':library' => $this->getName(),
        ]), 3);

        $path         = Strings::slash($this->directory) . 'Library/commands/';
        $restrictions = FsRestrictions::getWritable(
            [$path, DIRECTORY_TMP],
            'Core\\Library::rebuildCommandCache(' . $this->getName() . ')'
        );
        $path         = FsDirectory::new($path, $restrictions);

        if (!$path->exists()) {
            // This library does not have a web/ directory, we're fine
            return;
        }

        $path->symlinkTreeToTarget($cache, $tmp, rename: true);
    }


    /**
     * Ensures that the Library/web directory contents are symlinked in DIRECTORY_WEB
     *
     * @param FsDirectoryInterface $cache
     * @param FsDirectoryInterface $tmp
     *
     * @return void
     * @todo Add support for command sharing!
     */
    public function rebuildWebCache(FsDirectoryInterface $cache, FsDirectoryInterface $tmp): void
    {
        Log::action(tr('Rebuilding web page cache for library ":library"', [
            ':library' => $this->getName(),
        ]), 3);

        $path         = Strings::slash($this->directory) . 'Library/web/';
        $restrictions = FsRestrictions::getWritable(
            [$path, DIRECTORY_TMP],
            'Core\\Library::rebuildWebCache(' . $this->getName() . ')'
        );
        $path         = FsDirectory::new($path, $restrictions);

        if (!$path->exists()) {
            // This library does not have a web/ directory, we're fine
            return;
        }

        $path->symlinkTreeToTarget($cache, $tmp, rename: true);
    }


    /**
     * Ensures that the Library/tests directory contents are symlinked in DIRECTORY_DATA/cache/system/tests
     *
     * @param FsDirectoryInterface $cache
     * @param FsDirectoryInterface $tmp
     *
     * @return void
     * @todo Add support for command sharing!
     */
    public function rebuildTestsCache(FsDirectoryInterface $cache, FsDirectoryInterface $tmp): void
    {
        Log::action(tr('Rebuilding web page cache for library ":library"', [
            ':library' => $this->getName(),
        ]), 3);

        $path         = Strings::slash($this->directory) . 'Library/tests/';
        $restrictions = FsRestrictions::getWritable(
            [$path, DIRECTORY_TMP],
            'Core\\Library::rebuildTestsCache(' . $this->getName() . ')'
        );
        $path         = FsDirectory::new($path, $restrictions);

        if (!$path->exists()) {
            // This library does not have a web/ directory, we're fine
            return;
        }

        $path->symlinkTreeToTarget($cache, $tmp, rename: true);
    }
}
