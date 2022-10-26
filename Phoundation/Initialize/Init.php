<?php

namespace Phoundation\Initialize;

use Phoundation\Core\Arrays;
use Phoundation\Core\Log;
use Phoundation\Core\Strings;
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
     * @return string
     */
    public function getDatabaseVersion(): string
    {
        return sql()->getColumn('
                                SELECT `version` 
                                FROM   `versions` 
                                WHERE  `library` = :library', [
                                    ':library' => $this->library
        ]);
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
     * @return string The next version available for this init
     */
    public function updateOne(string $version): string
    {
        $execute = $this->updates[$version];

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

        // Execute this init
        Log::action(tr('Updating ":library" library with init version ":version"', [
            ':library' => $this->library,
            ':version' => $version
        ]));

        // Execute this init and return the next version
        $execute();
        return Arrays::nextKey($this->updates, $version);
    }



    protected function addVersion(): void
    {
        sql()->query();
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
}