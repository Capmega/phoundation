<?php

declare(strict_types=1);

namespace Phoundation\Core\Libraries;

use Error;
use Phoundation\Core\Enums\Interfaces\EnumLibraryTypeInterface;
use Phoundation\Core\Libraries\Exception\LibrariesException;
use Phoundation\Core\Libraries\Exception\LibraryExistsException;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Strings;
use Phoundation\Exception\Exception;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\File;
use Phoundation\Filesystem\Path;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Os\Processes\Commands\Cp;
use Phoundation\Utils\Json;


/**
 * Library class
 *
 * This library can initialize all other libraries
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Developer
 */
class Library
{
    /**
     * The library name
     *
     * @var string $library
     */
    protected string $library;

    /**
     * The library path
     *
     * @var string $path
     */
    protected string $path;

    /**
     * The Updates object for this library
     *
     * @var Updates|null
     */
    protected ?Updates $updates = null;

    /**
     * Tracks if the structure check has been executed and what the result was
     *
     * @var bool|null
     */
    protected bool|null $structure_ok = null;


    /**
     * Library constructor
     *
     * @param string $path
     */
    public function __construct(string $path)
    {
        $path          = Strings::slash($path);
        $this->path    = $path;
        $this->library = Strings::fromReverse(Strings::unslash($path), '/');
        $this->library = strtolower($this->library);

        // Get the Init object
        $this->loadUpdatesObject();
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
            'description' => $this->getDescription()
        ];
    }


    /**
     * Returns a new Library object for the specified library
     *
     * @param string $name
     * @return Library
     */
    public static function get(string $name): Library
    {
        if (str_contains($name, '/')) {
            // This is TYPE/NAME
            $type = Strings::until($name, '/');
            $name = Strings::from($name, '/');

            switch ($type) {
                case 'system':
                    // no-break
                case 'plugin':
                    // no-break
                case 'template':
                    break;

                default:
                    throw new OutOfBoundsException(tr('Unknown library type "" specified, please specify one of "system", "plugin", or "template"', [
                        ':type' => $type
                    ]));
            }

        } else {
            $type = null;
        }

        return Libraries::findLibrary($name, (($type === 'system') or ($type === null)), (($type === 'plugin') or ($type === null)), (($type === 'template') or ($type === null)));
    }


    /**
     * Initialize this library
     *
     * @param string|null $comments
     * @return bool True if the library had updates applied
     */
    public function init(?string $comments): bool
    {
        // Execute a structural check on the library
        $this->checkStructure($comments);

        // TODO Check later if we should be able to let init initialize itself
        if ($this->library === 'libraries') {
            // Never initialize the Init library itself!
            Log::warning(tr('Not initializing library "library", it has no versioning control available'));
            return false;
        }

        if ($this->updates === null) {
            // This library has no Init available, skip!
            Log::warning(tr('Not processing library ":library", it has no versioning control defined', [
                ':library' => $this->library
            ]), 3);
            return false;
        }

        $this->updates->init($comments);
        return true;
    }


    /**
     * Executes POST init files for this library
     *
     * @param string|null $comments
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
                ':library' => $this->library
            ]), 3);

            return false;
        }

        return $this->updates->initPost($comments);
    }


    /**
     * Returns the type of library; system or plugin
     *
     * @return string
     */
    public function getType(): string
    {
        $path = Strings::unslash($this->path);
        $path = Strings::untilReverse($path, '/');
        $path = Strings::fromReverse($path, '/');
        $path = strtolower($path);

        if ($path === 'phoundation') {
            return 'system';
        }

        if ($path === 'templates') {
            return 'template';
        }

        return 'plugin';
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
     * Returns the library path
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
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
        return Path::new($this->path, PATH_ROOT)->treeFileSize();
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
        return $this->updates?->description();
    }


    /**
     * Returns the PhpStatistics object for this library
     *
     * @return array
     */
    public function getPhpStatistics(): array
    {
        return Path::new($this->getPath(), [PATH_WWW, PATH_ROOT . '/scripts/', LIBRARIES::CLASS_PATH_SYSTEM, LIBRARIES::CLASS_PATH_PLUGINS, LIBRARIES::CLASS_PATH_TEMPLATES])->getPhpStatistics(true);
    }


    /**
     * Get the class path from the specified .php file
     *
     * @param string $file
     * @return Object
     */
    public static function getClassPath(string $file): string
    {
        if (!File::new($file, [PATH_ROOT . 'Phoundation', PATH_ROOT . 'Plugins', PATH_ROOT . 'Templates'])->isPhp()) {
            throw new OutOfBoundsException(tr('The specified file ":file" is not a PHP file', [':file' => $file]));
        }

        // Scan for namespace and class lines
        $namespace = null;
        $class     = null;
        $results   = File::new($file, [PATH_ROOT . 'Phoundation', PATH_ROOT . 'Plugins', PATH_ROOT . 'Templates'])
            ->grep(['namespace ', 'class '], 100);

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
        return Strings::endsWith($namespace, '\\') . $class;
    }


    /**
     * Get the .php file for the specified class path
     *
     * @param object|string $class_path
     * @param bool $check_php
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
        $file = Strings::startsNotWith($file, '/');
        $file = PATH_ROOT . $file . '.php';

        if ($check_php) {
            if (!File::new($file, [PATH_ROOT . 'Phoundation', PATH_ROOT . 'Plugins', PATH_ROOT . 'Templates', ])->isPhp()) {
                throw new OutOfBoundsException(tr('The specified file ":file" is not a PHP file', [':file' => $file]));
            }
        }

        return $file;
    }


    /**
     * Get the .php file for the specified class path
     *
     * @param object|string $class_path
     * @param bool $check_php
     * @return string
     */
    public static function loadClassFile(object|string $class_path, bool $check_php = true): string
    {
        $file = Library::getClassFile($class_path, $check_php);
        Log::action(tr('Including class file ":file"', [':file' => $file]), 2);
        include_once($file);
        return $class_path;
    }


    /**
     * Update the version registration for this version to be the specified version
     *
     * @param string $version
     * @param string|null $comments
     * @return void
     */
    public function setVersion(string $version, ?string $comments = null): void
    {
        Log::action(tr('Forcing version for library ":library" to ":version"', [
            ':library' => $this->getName(),
            ':version' => $version
        ]));

        $int_version = Version::getInteger($version);

        // Delete any version that is higher than the specified version
        sql()->query('DELETE FROM `core_versions` WHERE `library` = :library AND `version` > :version', [
            ':library' => $this->getName(),
            ':version' => $int_version
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

            sql()->dataEntryInsert('core_versions', [
                'library'  => $this->library,
                'version'  => $int_version,
                'comments' => $comments
            ]);
        }
    }


    /**
     * Creates a new library of the specified type
     *
     * @param string $name
     * @param EnumLibraryTypeInterface $type
     * @return static
     */
    public static function create(string $name, EnumLibraryTypeInterface $type): static
    {
        // Library names must be CamelCased
        if (!Strings::isCamelCase($name)) {
            throw new OutOfBoundsException(tr('Invalid library name ":name" specified, the library name must be CamelCase', [
                ':name' => $name
            ]));
        }

        if (file_exists(PATH_ROOT . $type->value . $name)) {
            throw new LibraryExistsException(tr('Cannot create ":type" type library ":name", it already exists', [
                ':type' => $type,
                ':name' => $name
            ]));
        }

        // Copy the library from the TemplateLibrary and run a search / replace
        Cp::new()->archive(PATH_ROOT . 'Phoundation/.TemplateLibrary', Restrictions::new(PATH_ROOT . 'Phoundation/'), PATH_ROOT . $type->value . $name, Restrictions::new(PATH_ROOT . $type->value, true));

        foreach (['.Library.php', 'Updates.php'] as $file) {
            File::new(PATH_ROOT . $type->value . $name . '/.Library/' . $file, Restrictions::new(PATH_ROOT . $type->value, true))
                ->replace([
                    ':type' => $type,
                    ':name' => $name
            ]);
        }

        // Done, return the library as an object
        return Library::get($name);
    }


    /**
     * Load the Init object for this library
     */
    protected function loadUpdatesObject(): void
    {
        $file = Strings::slash($this->path) . 'Updates.php';

        if (!file_exists($file)) {
            // There is no init class available
            return;
        }

        try {
            // Load the PHP file
            include_once($file);

            $updates_class_path = static::getClassPath($file);
            $updates            = new $updates_class_path();

            if (!($updates instanceof Updates)) {
                Log::Warning(tr('The Updates.php file for the library ":library" in ":path" is invalid, it should contain a class being an instance of the \Phoundation\Libraries\Updates. This updates file will be ignored', [
                    ':path'    => $this->path,
                    ':library' => $this->library
                ]));
            }

            $this->updates = $updates;

        } catch (Error $e) {
            Log::warning(tr('Failed to load the Updates file for library ":library", see the following exception for more information', [
                ':library' => $this->getName()
            ]));

            Exception::new($e)
                ->log()
                ->register()
                ->notification()
                    ->send();

            $this->updates = null;
        }
    }


    /**
     * This method will check the structure of the library and make sure everything is in working order
     *
     * @param string|null $comments
     * @return bool
     */
    protected function checkStructure(?string $comments): bool
    {
        if ($this->structure_ok === null) {
            // Execute the structural check for this library
            // TODO IMPLEMENT

            $this->structure_ok = true;
        }

        return $this->structure_ok;
    }


    /**
     * Returns true if the structure for this library is okay, false otherwise
     *
     * @return bool
     */
    public function getStructureOk(): bool
    {
        return $this->checkStructure();
    }
}
