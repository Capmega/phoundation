<?php

/**
 * Updates class
 *
 * This is the prototype Init class that contains the basic methods for all other Init classes in all other libraries
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   \Phoundation\Developer
 */


declare(strict_types=1);

namespace Phoundation\Core\Libraries;

use Phoundation\Core\Libraries\Exception\LibraryInvalidVendorException;
use Phoundation\Core\Libraries\Interfaces\UpdatesInterface;
use Phoundation\Core\Log\Log;
use Phoundation\Developer\Debug\Debug;
use Phoundation\Developer\Exception\DoubleVersionException;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\PhoException;
use Phoundation\Exception\UnexpectedValueException;
use Phoundation\Filesystem\Interfaces\PhoFileInterface;
use Phoundation\Filesystem\PhoDirectory;
use Phoundation\Utils\Strings;

abstract class Updates implements UpdatesInterface
{
    /**
     * The name for this library
     *
     * @var string|null $library
     */
    protected ?string $library = null;

    /**
     * The vendor name for this library
     *
     * @var string|null $vendor
     */
    protected ?string $vendor = null;

    /**
     * The $file for this library
     *
     * @var PhoFileInterface|null $file
     */
    protected ?PhoFileInterface $file = null;

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
     */
    public function __construct()
    {
        // Detect the library name
        $library = strtolower(get_class($this));
        $plugin  = str_contains($library, 'plugins\\');
        $vendor  = Strings::from($library, 'plugins\\');
        $vendor  = Strings::until($vendor, '\\');

        do {
            $library = Strings::untilReverse($library, '\\');
            $test    = Strings::fromReverse($library, '\\');

        } while ($test === 'library');

        if (!$plugin) {
            // If the library is not a plugin, the vendor is ALWAYS "system"
            $vendor = 'system';

        } else {
            // If the library is a plugin, the vendor may NEVER be "system"
            if ($vendor === 'system') {
                throw new LibraryInvalidVendorException(tr('The plugin library ":library" has the vendor "system" which is not allowed. The "system" vendor is reserved for Phoundation system libraries', [
                    ':library' => $library
                ]));
            }
        }

        // Load the updates and the code version
        $this->updates();

        $library      = $test;
        $code_version = $this->version();

        if (!$code_version) {
            throw new OutOfBoundsException(tr('No code version specified for library ":library" init file', [
                ':library' => $library,
            ]));
        }

        if (!strings::isVersion($code_version)) {
            throw new OutOfBoundsException(tr('Invalid code version ":version" specified for library ":library" init file', [
                ':version' => $code_version,
                ':library' => $library,
            ]));
        }

        if ($code_version === '0.0.0') {
            throw new OutOfBoundsException(tr('Invalid code version ":version" specified for library ":library" init file. The minimum version is "0.0.1"', [
                ':version' => $code_version,
                ':library' => $library,
            ]));
        }

        $this->file         = PhoDirectory::newRootObject()->addFile(str_replace('\\', '/', get_class($this)) . '.php');
        $this->vendor       = $vendor;
        $this->library      = $library;
        $this->code_version = $code_version;
    }


    /**
     * Adds the list of updates
     *
     * @return void
     */
    abstract public function updates(): void;


    /**
     * Returns the library version
     *
     * @return string
     */
    abstract public function version(): string;


    /**
     * Returns the file for this library
     *
     * @return PhoFileInterface
     */
    public function getFile(): PhoFileInterface
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
     * Registers the specified version and the function containing all tasks that should be executed to get to that
     * version
     *
     * @param string   $version
     * @param callable $function
     *
     * @return Updates
     */
    public function addUpdate(string $version, callable $function): Updates
    {
        if (array_key_exists($version, $this->updates)) {
            throw new DoubleVersionException(tr('The version ":version" is specified twice in the init file ":file"', [
                ':version' => $version,
                ':file'    => $this->file,
            ]));
        }

        $this->updates[$version] = $function;

        // Make sure the updates table is ordered by versions
        uksort($this->updates, function ($a, $b) {
            return version_compare($a, $b);
        });

        return $this;
    }


    /**
     * Update to the specified version
     *
     * @param string|null $comments
     *
     * @return string|null The next version available for this init, or NULL if none are available
     */
    public function init(?string $comments = null): ?string
    {
        $version = $this->getNextInitVersion();
        // Execute this init, register the version as executed, and return the next version
        switch ($version) {
            case 'post_once':
                // no break

            case 'post_always':
                Log::action(ts('Executing ":library" ":version" init', [
                    ':library' => $this->vendor . '/' . $this->library,
                    ':version' => $version,
                ]));
                break;

            default:
                Log::action(ts('Updating ":library" library to version ":version"', [
                    ':library' => $this->vendor . '/' . $this->library,
                    ':version' => $version,
                ]));
        }

        // Execute the update and clear the versions_exists as after any update, the versions table should exist
        try {
            if (!TEST or (in_array($this->library, ['accounts',  'core', 'meta', 'geo']))) {
                // In TEST mode only execute Core, Geo, Accounts, and Meta libraries
                $this->updates[$version]();
            }

            unset($this->versions_exists);

        } catch (PhoException $e) {
            // In init mode, we don't do warnings, only full exceptions
            $e->setWarning(false);
            throw $e;
        }

        // Register the version update and return the next available init
        $this->addVersion($version, $comments);

        return $this->getNextInitVersion($version);
    }


    /**
     * Returns the next version available for execution, if any
     *
     * @param string|null $version
     *
     * @return string|null The next version available for init execution, or NULL if none.
     */
    public function getNextInitVersion(?string $version = null): ?string
    {
        // Get the current version for the database
        $version = $version ?? $this->getDatabaseVersion();

        if (($version === null) or ($version === '0.0.0')) {
            // There is no version registered in the database at all, so the first available init is it!
            if (!$this->updates) {
                // Err, the update file contains no updates!
                return null;
            }

            $next_version = array_key_first($this->updates);

        } else {
            // Get the next available update version in the updates file. NULL if there are no versions
            $next_version = $this->getNextVersion($this->updates, $version);
        }

//Log::warning('Next version for ' . $this->library . ' after ' . ($version ?? 'N/A') . ' is ' . $next_version);
        if ($next_version) {
            if ($this->isFuture($next_version)) {
                // The next available init version is beyond the current version and will not be executed!
                return null;
            }
        }

        // This is the next version that should be executed
        return $next_version;
    }


    /**
     * Returns the current database version for this library
     *
     * @param bool $post
     *
     * @return string|null
     */
    public function getDatabaseVersion(bool $post = false): ?string
    {
        if (!$this->versionsTableExists()) {
            return null;
        }

        if (Libraries::supportsVendors()) {
            $version = sql()->getColumn('SELECT ' . ($post ? 'MIN' : 'MAX') . '(`version`) 
                                         FROM   `core_versions` 
                                         WHERE  `vendor`  = :vendor
                                           AND  `library` = :library', [
                ':vendor'  => $this->vendor,
                ':library' => $this->library
            ]);

        } else {
            $version = sql()->getColumn('SELECT ' . ($post ? 'MIN' : 'MAX') . '(`version`) 
                                         FROM   `core_versions` 
                                         WHERE  `library` = :library', [
                ':library' => $this->library
            ]);
        }

        return Version::getString($version);
    }


    /**
     * Returns true if the versions table exists, false otherwise
     *
     * @return bool
     */
    protected function versionsTableExists(): bool
    {
        if (!isset($this->versions_exists)) {
            $this->versions_exists = (bool) sql()->get('SHOW TABLES LIKE "core_versions"');
        }

        return $this->versions_exists;
    }


    /**
     * Returns the next key right after specified $key
     *
     * @param array      $source
     * @param string|int $current_version
     *
     * @return string|null
     * @throws OutOfBoundsException Thrown if the specified $current_version does not exist
     * @throws OutOfBoundsException Thrown if the specified $current_version does exist, but only at the end of the
     *                              specified array, so there is no next key
     */
    protected function getNextVersion(array &$source, string|int $current_version): ?string
    {
        $found = null;

        foreach ($source as $version => $callback) {
            if ($version === 'post_always') {
                // Ignore here, we'll execute that manually
                continue;
            }

            switch (Version::compare($version, $current_version)) {
                case -1:
                    // This is a previous version, ignore it.
                    break;

                case 0:
                    // It's the same version, ignore it
                    break;

                case 1:
                    // This IS a higher version! But is it the next? Let's see...
                    // Either it's the first we found ($found ie empty) or it's lower than the currently found one.
                    if (!$found or Version::compare($version, $found) === -1) {
                        // This is the lowest version, yay!
                        $found = $version;
                    }
            }
        }

        // Return the found version. Versions post_* will always be ignored here
        return match ($found) {
            'post_once', 'post_always' => null,
            default                    => $found
        };
    }


    /**
     * Returns true if this init file has a version above the current version (and should not yet be executed)
     *
     * @param string $version
     *
     * @return bool
     */
    protected function isFuture(string $version): bool
    {
        switch ($version) {
            case 'post_once':
                // no break
            case 'post_always':
                // These are never future versions
                return false;
        }

        $result = Version::compare($version, $this->code_version);

        switch ($result) {
            case -1:
                // The init version is newer than the specified version and may be executed
                return false;

            case 0:
                // The init version is the same as the current version and may be executed
                return false;

            case 1:
                // The file version is later than the specified version
                Log::warning(ts('Skipping init version ":version" for library ":library" because it is a future update', [
                    ':library' => $this->vendor . '/' . $this->library,
                    ':version' => $version,
                ]), Debug::isEnabled() ? 10 : 4);

                return true;
        }

        throw new UnexpectedValueException(tr('Php version_compare() gave the unexpected output ":output"', [
            ':output' => $result,
        ]));
    }


    /**
     * Add a new version data row in the versions table
     *
     * @param string      $version
     * @param string|null $comments
     *
     * @return void
     */
    protected function addVersion(string $version, ?string $comments = null): void
    {
        if ($version === 'post_always') {
            // Never register this in the core_versions table as this one is ALWAYS executed
            return;
        }

        if (Libraries::supportsVendors()) {
            sql()->insert('core_versions', [
                'vendor'   => $this->vendor,
                'library'  => $this->library,
                'version'  => Version::getInteger($version),
                'comments' => $comments,
            ]);

        } else {
            sql()->insert('core_versions', [
                'library'  => $this->library,
                'version'  => Version::getInteger($version),
                'comments' => $comments,
            ]);
        }
    }


    /**
     * Execute the post-init files
     *
     * @param string|null $comments
     *
     * @return bool True if any post_* files were executed
     */
    public function initPost(?string $comments = null): bool
    {
        $result = false;

        // Only execute post_* files if we're not in TEST mode
        // Execute the post_once
        if (array_key_exists('post_once', $this->updates)) {
            if (!$this->databaseVersionExists('post_once')) {
                // This post_once has not yet been executed, do so now and register
                Log::action(ts('Executing "post_once" for library ":library"', [
                    ':library' => $this->vendor . '/' . $this->library,
                ]));

                $this->updates['post_once']();
                $this->addVersion('post_once', $comments);

                $result = true;
            }
        }

        // Execute the post_always
        if (array_key_exists('post_always', $this->updates)) {
            Log::action(ts('Executing "post_always" for library ":library"', [
                ':library' => $this->vendor . '/' . $this->library,
            ]));

            $this->updates['post_always']();
            $result = true;
        }

        return $result;
    }


    /**
     * Returns if the specified database version exists for this library, or not
     *
     * @param string|int $version
     *
     * @return bool
     */
    public function databaseVersionExists(string|int $version): bool
    {
        if (!$this->versionsTableExists()) {
            return false;
        }

        if (!is_int($version)) {
            // Get the integer version of the version
            $version = Version::getInteger($version);
        }

        if (Libraries::supportsVendors()) {
            return (bool) sql()->getColumn('SELECT `version`
                                            FROM   `core_versions`
                                            WHERE  `vendor`  = :vendor
                                              AND  `library` = :library
                                              AND  `version` = :version', [
                ':vendor'  => $this->vendor,
                ':library' => $this->library,
                ':version' => $version,
            ]);
        }

        return (bool) sql()->getColumn('SELECT `version`
                                        FROM   `core_versions`
                                        WHERE  `library` = :library
                                          AND  `version` = :version', [
            ':library' => $this->library,
            ':version' => $version,
        ]);
    }


    /**
     * Returns true if this init file has already been executed before (and should not be executed again)
     *
     * @param string $version
     *
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
                // no break

            case -1:
                // The file version is newer than the specified version
                Log::warning(ts('Skipping init version ":version" for library ":library" because it already has been executed', [
                    ':library' => $this->vendor . '/' . $this->library,
                    ':version' => $version,
                ]), 5);

                return true;
        }

        throw new UnexpectedValueException(tr('Php version_compare() gave the unexpected output ":output"', [
            ':output' => $result,
        ]));
    }
}
