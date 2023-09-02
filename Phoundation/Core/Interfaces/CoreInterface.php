<?php

namespace Phoundation\Core\Interfaces;

use Phoundation\Core\Enums\Interfaces\EnumRequestTypesInterface;
use Throwable;


/**
 * Class Core
 *
 * This is the core class for the entire system.
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Core
 */
interface CoreInterface
{
    /**
     * Returns true if the system is in maintenance mode
     *
     * @return string|null
     */
    public static function getMaintenanceMode(): ?string;

    /**
     * Sets if the system is in maintenance mode
     *
     * @note This mode is global, and will immediately block all future web requests and block all future commands with
     * the exception of commands under ./pho system. Maintenance mode will remain enabled until disabled either by this
     * call or manually with ./pho system maintenance disable
     *
     * @param bool $enable
     * @return void
     */
    public static function enableMaintenanceMode(bool $enable): void;

    /**
     * Singleton
     *
     * @return static
     */
    public static function getInstance(): static;

    /**
     * The core::startup() method will start up the core class
     *
     * This method starts the correct call type handler
     *
     * @return void
     */
    public static function startup(): void;

    /**
     * A sleep() method that is process interrupt signal safe.
     *
     * The sleep() and usleep() calls can be messed up with pcntl signals, as these stop the sleep commands.
     * This Core::sleep() method is pcntl safe
     *
     * @see https://www.php.net/manual/en/function.pcntl-signal.php#124049
     * @param int $seconds
     * @return void
     */
    public static function sleep(int $seconds): void;

    /**
     * A usleep() method that is process interrupt signal safe.
     *
     * The sleep() and usleep() calls can be messed up with pcntl signals, as these stop the sleep commands.
     * This Core::usleep() method is pcntl safe
     *
     * @see https://www.php.net/manual/en/function.pcntl-signal.php#124049
     * @param int $micro_seconds
     * @return void
     */
    public static function usleep(int $micro_seconds): void;

    /**
     * Returns true if the Core is running in failed state
     *
     * @return bool
     */
    public static function getFailed(): bool;

    /**
     * Read and return the specified key / sub key from the core register.
     *
     * @note Will return NULL if the specified key does not exist
     * @param string $key
     * @param string|null $subkey
     * @param mixed|null $default
     * @return mixed
     */
    public static function readRegister(string $key, ?string $subkey = null, mixed $default = null): mixed;

    /**
     * write the specified variable to the specified key / sub key in the core register
     *
     * @param mixed $value
     * @param string $key
     * @param string|null $subkey
     * @return void
     */
    public static function writeRegister(mixed $value, string $key, ?string $subkey = null): void;

    /**
     * Delete the specified variable from the core register
     *
     * @param string $key
     * @param string|null $subkey
     * @return void
     */
    public static function deleteRegister(string $key, ?string $subkey = null): void;

    /**
     * Compare the specified value with the registered value for the specified key / sub key in the core register.
     *
     * @note Will return NULL if the specified key does not exist
     * @param mixed $value
     * @param string $key
     * @param string|null $subkey
     * @return bool
     */
    public static function compareRegister(mixed $value, string $key, ?string $subkey = null): bool;

    /**
     * Returns Core system state
     *
     * Can be one of
     *
     * setup    System is in setup mode
     * startup  System is starting up
     * script   Script execution is now running
     * shutdown System is shutting down after normal script execution
     * error    System is processing an uncaught exception and will die soon
     * phperror System encountered a PHP error, which (typically, but not always) will end un an uncaught exception,
     *          switching system state to "error"
     *
     * @return string
     */
    public static function getState(): string;

    /**
     * Returns true once script processing has started
     *
     * @return bool
     */
    public static function scriptStarted(): bool;

    /**
     * Returns true if the Core state is the same as the specified state
     * @param string $state
     * @return bool
     */
    public static function stateIs(string $state): bool;

    /**
     * Allows to change the Core class state
     *
     * @note This method only allows a change to the states "error" or "phperror"
     * @param string|null $state
     * @return void
     */
    public static function setState(?string $state): void;

    /**
     * Returns true if the system is still starting up
     *
     * @param string|null $state If specified will return the startup state for the specified state instead of the
     *                           internal Core state
     * @return bool
     * @see Core::getState()
     * @see Core::initState()
     */
    public static function startupState(?string $state = null): bool;

    /**
     * Returns true if the system is still starting up
     *
     * @param string|null $state If specified will return the startup state for the specified state instead of the
     *                           internal Core state
     * @return bool
     * @see Core::getState()
     * @see Core::startupState()
     */
    public static function initState(?string $state = null): bool;

    /**
     * Returns true if the system is running in PHPUnit
     *
     * @return bool
     */
    public static function isPhpUnitTest(): bool;

    /**
     * Returns true if the system has finished starting up
     *
     * @param string|null $state If specified will return the startup state for the specified state instead of the
     *                           internal Core state
     * @return bool
     * @see Core::getState()
     */
    public static function readyState(?string $state = null): bool;

    /**
     * Returns true if the system is in error state
     *
     * @return bool
     * @see Core::getState()
     */
    public static function errorState(): bool;

    /**
     *
     *
     * @return void
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package system
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     */
    public static function executedQuery($query_data);

    /**
     * This method will return the request type for this call, as is stored in the private variable core::request_type
     *
     * @return EnumRequestTypesInterface
     */
    public static function getRequestType(): EnumRequestTypesInterface;

    /**
     * Will return true if $call_type is equal to core::callType, false if not.
     *
     * @param EnumRequestTypesInterface $type The call type you wish to compare to
     * @return bool This function will return true if $type matches core::callType, or false if it does not.
     */
    public static function isRequestType(EnumRequestTypesInterface $type): bool;

    /**
     * Convert all PHP errors in exceptions. With this function the entirety of base works only with exceptions, and
     * function output normally does not need to be checked for errors.
     *
     * @note This method should never be called directly
     * @note This method uses untranslated texts as using tr() could potentially cause other issues
     *
     * @param int $errno
     * @param string $errstr
     * @param string $errfile
     * @param int $errline
     * @return void
     * @throws \Exception
     */
    public static function phpErrorHandler(int $errno, string $errstr, string $errfile, int $errline): void;

    /**
     * This function is called automatically
     *
     * @param Throwable $e
     * @param boolean $die Specify false if this exception should be a warning and continue, true if it should die
     * @return never
     * @note: This function should never be called directly
     * @todo Refactor this, its a godawful mess
     */
    public static function uncaughtException(Throwable $e, bool $die = true): never;

    /**
     * Set the timeout value for this script
     *
     * @param null|int $timeout The amount of seconds this script can run until it is aborted automatically
     * @return bool Returns TRUE on success, or FALSE on failure.
     * @see set_time_limit()
     * @version 2.7.5: Added function and documentation
     *
     */
    public static function setTimeout(int $timeout = null): bool;

    /**
     * Apply the specified or configured locale
     *
     * @return void
     * @todo what is this supposed to return anyway?
     */
    public static function setLocale(): void;

    /**
     * ???
     *
     * @param string $section
     * @param bool $writable
     * @return string
     */
    public static function getGlobalDataPath(string $section = '', bool $writable = true): string;

    /**
     * Register a shutdown function
     *
     * @note Function can be either a function name, a callable function, or an array with static object::method or an
     *       array with [$object, 'methodname']
     *
     * @param string $identifier
     * @param array|string|callable $function
     * @param mixed $data
     * @return void
     */
    public static function registerShutdown(string $identifier, array|string|callable $function, mixed $data = null): void;

    /**
     * Unregister the specified shutdown function
     *
     * This function will ensure that the specified function will not be executed on shutdown
     *
     * @param string $identifier
     * @return bool
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package system
     * @see shutdown()
     * @see Core::registerShutdown()
     * @version 1.27.0: Added function and documentation
     *
     */
    public static function unregisterShutdown(string $identifier): bool;

    /**
     * THIS METHOD SHOULD NOT BE RUN BY ANYBODY! IT IS EXECUTED AUTOMATICALLY ON SHUTDOWN
     *
     * This function facilitates execution of multiple registered shutdown functions
     *
     * @param int|null $error_code
     * @return void
     * @todo Somehow hide this method so that nobody can call it directly
     */
    public static function shutdown(?int $error_code = null): void;

    /**
     * Returns the framework database version
     *
     * @param string $type
     * @return string
     */
    public static function getVersion(string $type): string;

    /**
     * Returns the memory limit in bytes
     *
     * @return int
     */
    public static function getMemoryLimit(): int;

    /**
     * Returns the memory limit in bytes
     *
     * @return int
     */
    public static function getMemoryAvailable(): int;

    /**
     * Will execute the specified callback only when not running in TEST mode
     *
     * @param callable $function
     * @param string $task
     * @return void
     */
    public static function ExecuteNotInTestMode(callable $function, string $task): void;
}
