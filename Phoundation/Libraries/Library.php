<?php

namespace Phoundation\Libraries;



use Phoundation\Core\Log;
use Phoundation\Core\Strings;
use Phoundation\Developer\Debug;

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
     * The Init object for this library
     *
     * @var Init|null
     */
    protected ?Init $init = null;



    /**
     * Library constructor
     *
     * @param string $path
     */
    public function __construct(string $path)
    {
        $this->path    = $path;
        $this->library = Strings::fromReverse($path, '/');

        if ($this->library === 'libraries') {
            // The libraries library does not have init support itself
            return;
        }

        // Get the Init object
        $this->loadInitObject();
    }



    /**
     * Initialize this library
     *
     * @param string|null $comments
     * @return bool
     */
    public function init(?string $comments = null): bool
    {
        // TODO Check later if we should be able to let init initialize itself
        if ($this->library === 'library') {
            // Never initialize the Init library itself!
            Log::warning(tr('Not initializing library "library", it has no versioning control available'));
            return false;
        }

        if ($this->init === null) {
            // This library has no Init available, skip!
            Log::warning(tr('Not processing library ":library", it has no versioning control available', [
                ':library' => $this->library
            ]));
            return false;
        }

        return $this->init->init($comments);
    }



    /**
     * Returns true if the library is a system library
     *
     * @return bool
     */
    public function isSytem(): bool
    {
showdie($this->path);
    }



    /**
     * Returns true if the library is a plugin library
     *
     * @return bool
     */
    public function isPlugin(): bool
    {
showdie($this->path);
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
        if ($this->init) {
            return null;
        }

        return $this->init->getCodeVersion();
    }



    /**
     * Returns the database version for this library
     *
     * @return string|null
     */
    public function getDatabaseVersion(): ?string
    {
        if ($this->init) {
            return null;
        }

        return $this->init->getDatabaseVersion();
    }



    /**
     * Returns the database version for this library
     *
     * @return string|null
     */
    public function getNextInitVersion(): ?string
    {
        if ($this->init) {
            return null;
        }

        return $this->init->getNextInitVersion();
    }



    /**
     * Load the Init object for this library
     */
    protected function loadInitObject(): void
    {
        $file = Strings::slash($this->path) . 'Init.php';

        if (!file_exists($file)) {
            // There is no init class available
            return;
        }

        // Load the PHP file
        include_once($file);

        $init_class_path = Debug::getClassPath($file);
        $init            = new $init_class_path();

        if (!($this->init instanceof Init)) {
            Log::Warning(tr('The Init.php file for the library ":library" in ":path" is invalid, it should be an instance of the class \Phoundation\Libraries\Init. This Init.php file will be ignored', [
                ':path'    => $this->path,
                ':library' => $this->library
            ]));
        }

        $this->init = $init;
    }
}