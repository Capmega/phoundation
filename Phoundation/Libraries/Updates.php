<?php

namespace Phoundation\Libraries;

use Phoundation\Core\Arrays;
use Phoundation\Core\Log;
use Phoundation\Core\Strings;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\UnexpectedValueException;
use Phoundation\Libraries\Exception\DoubleVersionException;



/**
 * Updates class
 *
 * This is the prototype Init class that contains the basic methods for all other Init classes in all other libraries
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package \Phoundation\Libraries
 */
class Updates
{
    /**
     * The name for this library
     *
     * @var string|null $library
     */
    protected ?string $library = null;

    /**
     * The $file for this library
     *
     * @var string|null $file
     */
    protected ?string $file = null;

    /**
     * The code version for this library
     *
     * @var string|null $code_version
     */
    protected ?string $code_version = null;

    /**
     * The available updates to apply
     *
     * @var array $updates
     */
    protected array $updates = [];

    /**
     * Cache on if versions table exists or not
     *
     * @var bool $versions_exists
     */
    protected bool $versions_exists;



    /**
     * Init constructor
     *
     * @param string $code_version The code version of this library
     */
    protected function __construct(string $code_version)
    {
        // Detect the library name
        $library = Strings::untilReverse(get_class($this), '\\');
        $library = Strings::fromReverse($library, '\\');
        $library = strtolower($library);

        if (!$code_version) {
            throw new OutOfBoundsException(tr('No code version specified for library ":library" init file', [
                ':library' => $library
            ]));
        }

        if (!strings::isVersion($code_version)) {
            throw new OutOfBoundsException(tr('Invalid code version ":version" specified for library ":library" init file', [
                ':version' => $code_version,
                ':library' => $library
            ]));
        }

        if ($code_version === '0.0.0') {
            throw new OutOfBoundsException(tr('Invalid code version ":version" specified for library ":library" init file. The minimum version is "0.0.1"', [
                ':version' => $code_version,
                ':library' => $library
            ]));
        }

        $this->file         = PATH_ROOT . str_replace('\\', '/', get_class($this)) . '.php';
        $this->library      = $library;
        $this->code_version = $code_version;
    }



    /**
     * Returns the file for this library
     *
     * @return string
     */
    public function getFile(): string
    {
        return $this->file;
    }



    /**
     * Returns the current code version for this library
     *
     * @return string
     */
    public function getCodeVersion(): string
    {
        return $this->code_version;
    }



    /**
     * Returns the current database version for this library
     *
     * @return string|null
     */
    public function getDatabaseVersion(): ?string
    {
        if (!$this->versionsTableExists()) {
            return null;
        }

        return sql()->getColumn('SELECT MAX(`version`) 
                                       FROM   `versions` 
                                       WHERE  `library` = :library', [':library' => $this->library]);
    }



    /**
     * Returns the next version available for execution, if any
     *
     * @return string|null The next version available for init execution, or NULL if none.
     */
    public function getNextInitVersion(): ?string
    {
        if ($this->isFuture(array_key_first($this->updates))) {
            // The first available init version is already future version and will not be executed!
            return null;
        }

        // Get the current version for the database
        $version = $this->getDatabaseVersion();

        if ($version === null) {
            // There is no version registered in the database at all, so the first available init is it!
            return array_key_first($this->updates);
        }

        try {
            // Get the next available version
            $version = Arrays::nextKey($this->updates, $version);

            if ($this->isFuture($version)) {
                // The next available version is a future version and will not be executed
                return null;
            }

            return $version;

        } catch (OutOfBoundsException $e) {
            // There is no next available!
            return null;
        }
    }



    /**
     * Registers the specified version and the function containing all tasks that should be executed to get to that
     * version
     *
     * @param string $version
     * @param callable $function
     * @return Updates
     */
    public function addUpdate(string $version, callable $function): Updates
    {
        if (array_key_exists($version, $this->updates)) {
            throw new DoubleVersionException(tr('The version ":version" is specified twice in the init file ":file"', [
                ':version' => $version,
                ':file' => $this->file
            ]));
        }

        $this->updates[$version] = $function;

        // Make sure the updates table is ordered by versions
        uksort($this->updates, function($a, $b) {
            return version_compare($a, $b);
        });

        return $this;
    }



    /**
     * Update to the specified version
     *
     * @param string|null $comments
     * @return string|null The next version available for this init, or NULL if none are available
     */
    public function init(?string $comments = null): ?string
    {
        $version = $this->getNextInitVersion();

        // Execute this init, register the version as executed, and return the next version
        Log::action(tr('Updating ":library" library with init version ":version"', [
            ':library' => $this->library,
            ':version' => $version
        ]));

        // Execute the update and clear the versions_exists as after any update, the versions table should exist
        $this->updates[$version]();
        unset($this->versions_exists);

        // Register the version update and return the next available init
        $this->addVersion($version, $comments);
        return $this->getNextInitVersion();
    }



    /**
     * Add a new version data row in the versions table
     *
     * @param string $version
     * @param string|null $comments
     * @return void
     */
    protected function addVersion(string $version, ?string $comments = null): void
    {
        sql()->insert('versions', [
            'library'  => $this->library,
            'version'  => $version,
            'comments' => $comments
        ]);
    }



    /**
     * Returns true if this init file has already been executed before (and should not be executed again)
     *
     * @param string $version
     * @return bool
     */
    protected function hasBeenExecuted(string $version): bool
    {
        $database_version = $this->getDatabaseVersion();

        if ($database_version === null) {
            // This library has had no init executed whatsoever, so no, it had not been executed, whatever the version
            return false;
        }

        $result = version_compare($version, $database_version);

        switch ($result) {
            case 1:
                // The init version is later than the specified version
                return false;

            case 0:
                // The init version is the same as the current version, it has been executed
                // no-break
            case -1:
                // The file version is newer than the specified version
                Log::warning(tr('Skipping init version ":version" for library ":library" because it already has been executed', [
                    ':library' => $this->library,
                    ':version' => $version
                ]), 5);

                return true;
        }

        throw new UnexpectedValueException(tr('Php version_compare() gave the unexpected output ":output"', [
            ':output' => $result
        ]));
    }



    /**
     * Returns true if this init file has a version above the current version (and should not yet be executed)
     *
     * @param string $version
     * @return bool
     */
    protected function isFuture(string $version): bool
    {
        $result = version_compare($version, $this->code_version);

        switch ($result) {
            case -1:
                // The init version is newer than the specified version and may be executed
                return false;

            case 0:
                // The init version is the same as the current version and may be executed
                return false;

            case 1:
                // The file version is later than the specified version
                Log::warning(tr('Skipping init version ":version" for library ":library" because it is a future update', [
                    ':library' => $this->library,
                    ':version' => $version
                ]));

                return true;
        }

        throw new UnexpectedValueException(tr('Php version_compare() gave the unexpected output ":output"', [
            ':output' => $result
        ]));
    }



    /**
     * Returns true if the versions table exists, false otherwise
     *
     * @return bool
     */
    protected function versionsTableExists(): bool
    {
        if (!isset($this->versions_exists)) {
            $this->versions_exists = (bool) sql()->get('SHOW TABLES LIKE "versions"');
        }

        return $this->versions_exists;
    }
}