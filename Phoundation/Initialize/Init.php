<?php

namespace Phoundation\Initialize;

use Phoundation\Core\Arrays;
use Phoundation\Core\Log;
use Phoundation\Core\Strings;
use Phoundation\Exception\NotExistsException;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\UnexpectedValueException;



/**
 * Init class
 *
 * This is the prototype Init class that contains the basic methods for all other Init classes in all other libraries
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Init
 */
class Init
{
    /**
     * The name for this library
     *
     * @var string|null $library
     */
    protected ?string $library = null;

    /**
     * The version for this library
     *
     * @var string|null $version
     */
    protected ?string $version = null;

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
     * @param string $version The code version of this library
     */
    protected function __construct(string $version)
    {
        // Detect the library name
        $library = Strings::untilReverse(get_class($this), '\\');
        $library = Strings::fromReverse($library, '\\');
        $library = strtolower($library);

        if (!$version) {
            throw new OutOfBoundsException(tr('No code version specified for library ":library" init file', [':library' => $library]));
        }

        $this->library = $library;
        $this->version = $version;
    }



    /**
     * Returns the current code version for this library
     *
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
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

        return sql()->getColumn('
                            SELECT `version` 
                            FROM   `versions` 
                            WHERE  `library` = :library', [
            ':library' => $this->library
        ]);
    }



    /**
     * Returns the next version available for execution, if any
     *
     * @return string|null The next version available for init execution, or NULL if none.
     */
    public function getNextExecutionVersion(): ?string
    {
        $version = $this->getDatabaseVersion();

        if ($version === null) {
            // There is no version registered in the database at all, so the first available init is it!
            return array_key_first($this->updates);
        }

        try {
            return Arrays::nextKey($this->updates, $version);
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
     * @return Init
     */
    public function addUpdate(string $version, callable $function): Init
    {
        $this->updates[$version] = $function;
        return $this;
    }



    /**
     * Execute all updates for this library
     *
     * @return int The amount of inits that were executed
     */
    public function update(): int
    {
        $count = 0;

        Log::action(tr('Initializing library ":library"', [':library' => $this->library]));

        foreach ($this->updates as $version => $execute) {
            $this->updateOne($version);
            $count++;
        }

        return $count;
    }



    /**
     * Update to the specified version
     *
     * @param string $version
     * @param string $comments
     * @return string|null The next version available for this init, or NULL if none are available
     */
    public function updateOne(string $version, string $comments): ?string
    {
        // Ensure that the specified version exists
        if (!array_key_exists($version, $this->updates)) {
            throw new NotExistsException(tr('The specified version ":version" does not exist for the library ":library"', [
                'version'  => $version,
                ':library' => $this->library
            ]));
        }

        if ($this->hasBeenExecuted($version)) {
            Log::warning(tr('Skipping init version ":version" for library ":library" because it already has been executed', [
                ':library' => $this->library,
                ':file'    => $version
            ]), 2);

            return false;
        }

        if ($this->isFuture($version)) {
            Log::warning(tr('Skipping init version ":version" for library ":library" because it is a future update', [
                ':library' => $this->library,
                ':file'    => $version
            ]), 2);

            return false;
        }

        // Execute this init, register the version as executed, and return the next version
        Log::action(tr('Updating ":library" library with init version ":version"', [
            ':library' => $this->library,
            ':version' => $version
        ]));

        $this->updates[$version]();
        $this->addVersion($version, $comments);
        return $this->getNextExecutionVersion();
    }



    /**
     * Add a new version data row in the versions table
     *
     * @param string $version
     * @param string $comments
     * @return void
     */
    protected function addVersion(string $version, string $comments): void
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
        $result = version_compare($version, $this->version);

        switch ($result) {
            case -1:
                // The file version is newer than the specified version
                return true;

            case 0:
                // The file version is the same as the current version, it has  been executed
                return true;

            case 1:
                // The file version is later than the specified version
                return false;
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
        $result = version_compare($version, $this->version);

        switch ($result) {
            case -1:
                // The file version is newer than the specified version
                return true;

            case 0:
                // The file version is the same as the current version, it has  been executed
                return false;

            case 1:
                // The file version is later than the specified version
                return false;
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