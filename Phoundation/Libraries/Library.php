<?php

namespace Phoundation\Libraries;

use Phoundation\Core\Log;
use Phoundation\Core\Strings;
use Phoundation\Developer\Debug;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Path;
use Phoundation\Utils\PhpStatistics;


/**
 * Library class
 *
 * This library can initialize all other libraries
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Libraries
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

        if ($this->library === 'libraries') {
            // The libraries library does not have init support itself
            return;
        }

        // Get the Init object
        $this->loadUpdatesObject();
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
     * @return string|null The next version available for this init, or NULL if none are available
     */
    public function init(?string $comments = null): ?string
    {
        // TODO Check later if we should be able to let init initialize itself
        if ($this->library === 'libraries') {
            // Never initialize the Init library itself!
            Log::warning(tr('Not initializing library "library", it has no versioning control available'));
            return null;
        }

        if ($this->updates === null) {
            // This library has no Init available, skip!
            Log::warning(tr('Not processing library ":library", it has no versioning control available', [
                ':library' => $this->library
            ]));
            return null;
        }

        return $this->updates->init($comments);
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
     *pLr3o297s&&i
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
     * Load the Init object for this library
     */
    protected function loadUpdatesObject(): void
    {
        $file = Strings::slash($this->path) . 'Updates.php';

        if (!file_exists($file)) {
            // There is no init class available
            return;
        }

        // Load the PHP file
        include_once($file);

        $updates_class_path = Debug::getClassPath($file);
        $updates            = new $updates_class_path();

        if (!($updates instanceof Updates)) {
            Log::Warning(tr('The Updates.php file for the library ":library" in ":path" is invalid, it should contain a class being an instance of the \Phoundation\Libraries\Updates. This updates file will be ignored', [
                ':path'    => $this->path,
                ':library' => $this->library
            ]));
        }

        $this->updates = $updates;
    }
}