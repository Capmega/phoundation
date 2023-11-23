<?php

declare(strict_types=1);

namespace Phoundation\Utils;

use Exception;
use Phoundation\Core\Exception\ConfigException;
use Phoundation\Core\Exception\ConfigurationDoesNotExistsException;
use Phoundation\Core\Interfaces;
use Phoundation\Core\Interfaces\ConfigInterface;
use Phoundation\Core\Log\Log;
use Phoundation\Developer\Debug;
use Phoundation\Developer\Project\Configuration;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Directory;
use Phoundation\Filesystem\File;
use Phoundation\Filesystem\Restrictions;
use Throwable;


/**
 * Class Config
 *
 * This class contains the methods to read, write and manage configuration options. Default configuration values are
 * specified in the classes themselves whereas users can add configuration sections in the YAML file
 * DIRECTORY_ROOT/config/ENVIRONMENT/CLASSNAME and this class will apply those values.
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Core
 */
class Config implements Interfaces\ConfigInterface
{
    /**
     * Singleton variable for main config object
     *
     * @var ConfigInterface|null $instance
     */
    protected static ?ConfigInterface $instance = null;

    /**
     * Alternative environment instances
     *
     * @var array $instances
     */
    protected static array $instances = [];

    /**
     * Keeps track of configuration failures
     *
     * @var bool $fail
     */
    protected static bool $fail = false;

    /**
     * The generic system register to store data
     *
     * @var array $data
     */
    protected static array $data = [];

    /**
     * Configuration files that have been read
     *
     * @var array $files
     */
    protected static array $files = [];

    /**
     * Configuration cache
     *
     * @var array
     */
    protected static array $cache = [];

    /**
     * The environment used by this configuration object
     *
     * @var string|null $environment
     */
    protected static ?string $environment = null;

    /**
     * If true, Config will always first read the production configuration file, then the specified environment
     *
     * @var bool $include_production
     */
    protected static bool $include_production = true;


    /**
     * Singleton, ensure to always return the same Log object.
     *
     * @return static
     */
    public static function getInstance(): static
    {
        if (!isset(static::$instance)) {
            static::$instance = new static();
        }

        return static::$instance;
    }


    /**
     * Returns the current environment for the configuration object
     *
     * @return string|null
     */
    public static function getEnvironment(): ?string
    {
        return static::$environment;
    }


    /**
     * Returns a config object for the specified environment
     *
     * @param string $environment
     * @return ConfigInterface
     */
    public static function forEnvironment(string $environment): ConfigInterface
    {
        if (empty(static::$instances[$environment])) {
            static::$instances[$environment] = new static();
            static::$instances[$environment]->setEnvironment($environment);
        }

        return static::$instances[$environment];
    }


    /**
     * Lets the Config object use the specified (or if not specified, the current global) environment
     *
     * @param string $environment
     * @param bool $include_production
     * @return void
     */
    public static function setEnvironment(string $environment, bool $include_production = true): void
    {
        if (!$environment) {
            // Environment was specified as "", use no environment!
            static::$environment = null;
            static::reset();
            return;
        }

        // Use the specified environment
        static::$include_production = $include_production;
        static::$environment        = strtolower(trim($environment));

        static::reset();
        static::read();
    }


    /**
     * Return configuration BOOLEAN for the specified key path
     *
     * @note Will cause an exception if a non-boolean value is returned!
     * @param string|array $directory
     * @param bool|null $default
     * @param mixed|null $specified
     * @return bool
     */
    public static function getBoolean(string|array $directory, ?bool $default = null, mixed $specified = null): bool
    {
        $return = static::get($directory, $default, $specified);

        try {
            if (is_bool($return)) {
                return $return;
            }

            // Try to interpret as boolean
            return Strings::toBoolean($return);
        } catch(OutOfBoundsException) {
            // Do nothing, following exception will do the job
        }

        throw new ConfigException(tr('The configuration directory ":directory" should be a boolean value (Accepted are true, "true", "yes", "y", "1", false, "false", "no", "n", or 1), but has value ":value" instead', [
            ':directory'  => $directory,
            ':value' => $return
        ]));
    }


    /**
     * Return configuration INTEGER for the specified key path
     *
     * @note Will cause an exception if a non integer value is returned!
     * @param string|array $directory
     * @param int|null $default
     * @param mixed|null $specified
     * @return int
     */
    public static function getInteger(string|array $directory, ?int $default = null, mixed $specified = null): int
    {
        $return = static::get($directory, $default, $specified);

        if (is_integer($return)) {
            return $return;
        }

        throw new ConfigException(tr('The configuration directory ":directory" should be an integer number but has value ":value"', [
            ':directory'  => $directory,
            ':value' => $return
        ]));
    }


    /**
     * Return configuration NUMBER for the specified key path
     *
     * @note Will cause an exception if a non-numeric value is returned!
     * @param string|array $directory
     * @param int|float|null $default
     * @param mixed|null $specified
     * @return int|float
     */
    public static function getNatural(string|array $directory, int|float|null $default = null, mixed $specified = null): int|float
    {
        $return = static::get($directory, $default, $specified);

        if (is_natural($return)) {
            return $return;
        }

        throw new ConfigException(tr('The configuration directory ":directory" should be a natural number, integer 0 or above, but has value ":value"', [
            ':directory'  => $directory,
            ':value' => $return
        ]));
    }


    /**
     * Return configuration NUMBER for the specified key path
     *
     * @note Will cause an exception if a non-numeric value is returned!
     * @param string|array $directory
     * @param int|float|null $default
     * @param mixed|null $specified
     * @return int|float
     */
    public static function getFloat(string|array $directory, int|float|null $default = null, mixed $specified = null): int|float
    {
        $return = static::get($directory, $default, $specified);

        if (is_float($return)) {
            return $return;
        }

        throw new ConfigException(tr('The configuration directory ":directory" should be a number but has value ":value"', [
            ':directory'  => $directory,
            ':value' => $return
        ]));
    }


    /**
     * Return configuration ARRAY for the specified key path
     *
     * @note Will cause an exception if a non array value is returned!
     * @param string|array $directory
     * @param array|null $default
     * @param mixed|null $specified
     * @return array
     */
    public static function getArray(string|array $directory, array|null $default = null, mixed $specified = null): array
    {
        $return = static::get($directory, $default, $specified);

        if (is_array($return)) {
            return static::fixKeys($return);
        }

        throw new ConfigException(tr('The configuration directory ":directory" should be an array but has value ":value"', [
            ':directory'  => $directory,
            ':value' => $return
        ]));
    }


    /**
     * Return configuration STRING for the specified key path
     *
     * @note Will cause an exception if a non string value is returned!
     * @param string|array $directory
     * @param string|null $default
     * @param mixed|null $specified
     * @return string
     */
    public static function getString(string|array $directory, string|null $default = null, mixed $specified = null): string
    {
        $return = static::get($directory, $default, $specified);

        if (is_string($return)) {
            return $return;
        }

        throw new ConfigException(tr('The configuration directory ":directory" should be a string but has value ":value"', [
            ':directory'  => $directory,
            ':value' => $return
        ]));
    }


    /**
     * Return configuration STRING or BOOLEAN for the specified key path
     *
     * @note Will cause an exception if a non string or bool value is returned!
     * @param string|array $directory
     * @param string|bool|null $default
     * @param mixed|null $specified
     * @return string|bool
     */
    public static function getBoolString(string|array $directory, string|bool|null $default = null, mixed $specified = null): string|bool
    {
        $return = static::get($directory, $default, $specified);

        if (is_string($return) or is_bool($return)) {
            return $return;
        }

        throw new ConfigException(tr('The configuration directory ":directory" should be a string but has value ":value"', [
            ':directory'  => $directory,
            ':value' => $return
        ]));
    }


    /**
     * Returns true of the specified configuration path exists
     *
     * @param string|array $directory The key path to search for. This should be specified either as an array with key names
     *                           or a . separated string
     * @return bool
     */
    public static function exists(string|array $directory): bool
    {
        try {
            static::get($directory);
            return true;

        } catch (ConfigurationDoesNotExistsException) {
            // Ignore, just return null
            return false;
        }
    }


    /**
     * Returns the value for the specified configuration path, if it exists.
     *
     * No error will be thrown if the specified configuration path does not exist
     *
     * @param string|array $directory The key path to search for. This should be specified either as an array with key names
     *                           or a . separated string
     * @return mixed
     */
    public static function test(string|array $directory): mixed
    {
        try {
            return static::get($directory);

        } catch (ConfigurationDoesNotExistsException) {
            // Ignore, just return null
            return null;
        }
    }


    /**
     * Return configuration data for the specified key path
     *
     * @param string|array $directory    The key path to search for. This should be specified either as an array with key
     *                              names or a . separated string
     * @param mixed|null $default   The default value to return if no value was found in the configuration files
     * @param mixed|null $specified A value that might have been specified by a calling function. IF this value is not
     *                              NULL, it will automatically be returned as we will assume that that is the user
     *                             (developer) specified value we should be using, overriding configuration and defaults
     * @return mixed
     */
    public static function get(string|array $directory, mixed $default = null, mixed $specified = null): mixed
    {
        if (!static::$environment) {
            // We don't really have an environment, don't check configuration, just return default values
            return $default;
        }

        Debug::counter('Config::get()')->increase();

        if (static::$fail) {
            // Config class failed, always return all default values
            return $default;
        }

        // Do we have cached configuration information?
        $cache_key = Strings::force($directory, '.');

        if (array_key_exists($cache_key, static::$cache)) {
            return static::$cache[$cache_key];
        }

        static::getInstance();

        if ($specified) {
            return $specified;
        }

        if (!$directory) {
            // No path specified, return everything
            return static::fixKeys(static::$data);
        }

        $directory = Arrays::force($directory, '.');
        $data = &static::$data;

        // Go over each key and if the value for the key is an array, request a subsection
        foreach ($directory as $section) {
            if (!is_array($data)) {
//                echo "<pre>";var_dump($directory);var_dump($section);var_dump($data);echo "\n";

                if ($data !== null) {
                    Log::warning(tr('Encountered invalid configuration structure whilst looking for ":directory". Section ":section" should contain sub values but does not. Please check your configuration files that this structure exists correctly', [
                        ':directory'    => $directory,
                        ':section' => $section
                    ]));
                }

                // This section is missing in config files. No biggie, initialize it as an array
                $data = [];
            }

            if (!array_key_exists($section, $data)) {
                // The requested key does not exist
                if ($default === null) {
                    // We have no default configuration either
                    throw ConfigurationDoesNotExistsException::new(tr('The configuration section ":section" from key directory ":directory" does not exist. Please check "production.yaml" AND ":environment.yaml"', [
                        ':environment' => ENVIRONMENT,
                        ':section'     => $section,
                        ':directory'        => Strings::force($directory, '.')
                    ]))->makeWarning();
                }

                // The requested key does not exist in configuration, return the default value instead
                return static::$cache[$cache_key] = $default;
            }

            // Get the requested subsection. This subsection must be an array!
            $data = &$data[$section];
        }

        return static::$cache[$cache_key] = $data;
    }


    /**
     * Return configuration data for the specified key path
     *
     * @param string|array $directory    The key path to search for. This should be specified either as an array with key
     *                              names or a . separated string
     * @param mixed $value
     * @return mixed
     */
    public static function set(string|array $directory, mixed $value = null): mixed
    {
        static::getInstance();

        $cache_key = Strings::force($directory, '.');
        $directory      = Arrays::force($directory, '.');
        $data      = &static::$data;

        // Go over each key and if the value for the key is an array, request a subsection
        foreach ($directory as $section) {
            if (!is_array($data)) {
                // Oops, this data section should be an array
                throw ConfigException::new(tr('The configuration section ":section" from requested directory ":directory" does not exist', [
                    ':section' => $section,
                    ':directory'    => $directory
                ]))->makeWarning();
            }

            if (!array_key_exists($section, $data)) {
                // The requested key does not exist, initialize with an array just in case
                $data[$section] = [];
            }

            // Get the requested subsection
            $data = &$data[$section];
        }

        // The variable $data should now be the correct leaf node. Assign it $value and return it.
        $data = $value;
        return static::$cache[$cache_key] = $value;
    }


    /**
     * Returns true if a configuration file for the specified environment exists, false if not
     *
     * @param string $environment
     * @return bool
     */
    public static function environmentExists(string $environment): bool
    {
        return file_exists(DIRECTORY_ROOT . 'config/' . $environment . '.yaml');
    }


    /**
     * Reads the configuration file for the specified configuration environment
     *
     * @return void
     * @throws ConfigException
     * @throws OutOfBoundsException
     */
    protected static function read(): void
    {
        try {
            if (!static::$environment) {
                // We don't really have an environment, don't read configuration
                return;
            }

            // What environments should be read?
            if (static::$environment === 'production') {
                $environments = ['production'];

            } elseif(static::$include_production) {
                $environments = ['production', static::$environment];

            } else {
                // Read only the specified environment
                $environments = [static::$environment];
            }

            // Read the section for each environment
            foreach ($environments as $environment) {
                $file = DIRECTORY_ROOT . 'config/' . $environment . '.yaml';
                Restrictions::new(DIRECTORY_ROOT . 'config/')->check($file, false);

                // Check if a configuration file exists for this environment
                if (!file_exists($file)) {
                    // Do NOT use tr() here as it will cause endless loops!
                    throw ConfigException::new('Configuration file "' . Strings::from($file, DIRECTORY_ROOT) . '" for environment "' . Strings::log($environment) . '" does not exist')
                        ->makeWarning();
                }

                try {
                    // Read the configuration data and merge it in the internal configuration data array
                    $data = yaml_parse_file($file);

                } catch (Throwable $e) {
                    // Failed to read YAML data from configuration file
                    static::$fail = true;
                    throw ConfigException::new('Configuration file "' . Strings::from($file, DIRECTORY_ROOT) . '" for environment "' . Strings::log($environment) . '" does not exist', null, null, $e)
                        ->makeWarning();
                }

                if (!is_array($data)) {
                    if ($data) {
                        throw new OutOfBoundsException(tr('Configuration data in file ":file" has an invalid format', [
                            ':file' => $file
                        ]));
                    }

                    // Looks like configuration file was empty
                    $data = [];
                }

                static::$data = Arrays::mergeFull(static::$data, $data);
            }

        } catch (ConfigException $e) {
            // Do NOT use Log class here as log class requires config which just now failed... Same goes for tr()!
            echo 'Failed to load configuration file "' . $file . '"' . PHP_EOL;

            static::$fail = true;
        }
    }


    /**
     * Scan the entire project from ROOT for Config::get() and Config::set() and generate a config/default.yaml file
     * with all default values
     *
     * @return int The amount of configuration paths processed
     */
    public static function generateDefaultYaml(): int
    {
        $count = 0;
        $store = [];

        // Scan all files for Config::get() and Config::set() calls
        Directory::new(DIRECTORY_ROOT, DIRECTORY_ROOT)->execute()
            ->addSkipDirectories([DIRECTORY_DATA, DIRECTORY_ROOT . 'tests', DIRECTORY_ROOT . 'garbage'])
            ->setRecurse(true)
            ->setRestrictions(new Restrictions(DIRECTORY_ROOT))
            ->onFiles(function (string $file) use (&$store) {
                $files = File::new($file, DIRECTORY_ROOT)->grep(['Config::get(\'', 'Config::set(\'']);

                foreach ($files as $file) {
                    foreach ($file as $lines) {
                        foreach ($lines as $line) {
                            // Extract the configuration path and default value for each call
                            if (!preg_match_all('/Config::[gs]et\s?\([\'"](.+?)[\'"](?:.+?)?(?:,\s?(.+?))\)/i', $line, $matches)) {
                                if (!preg_match_all('/Config::[gs]et\s?\([\'"](.+?)[\'"](?:.+?)?(?:,\s?(.+?))?\)/i', $line, $matches)) {
                                    Log::warning(tr('Failed to extract a Config::get() or Config::set() from line ":line" in file ":file"', [
                                        ':file' => $file,
                                        ':line' => $line
                                    ]));

                                    continue;
                                }
                            }

                            // Pass over all matches
                            foreach ($matches[0] as $match => $value) {
                                $directory = str_replace(['"', "'"], '', trim($matches[1][$match]));
                                $default = str_replace(['"', "'"], '', trim($matches[2][$match]));

                                // Log all Config::get() and Config::set() calls that have the same configuration path but different
                                // default values
                                if (array_key_exists($directory, $store)) {
                                    if ($store[$directory] !== $default) {
                                        Log::warning(tr('Configuration directory ":directory" has two different default values ":1" and ":2"', [
                                            ':directory' => $directory,
                                            ':1'    => $default,
                                            ':2'    => $store[$directory],
                                        ]));
                                    }
                                }

                                // Store the configuration path
                                $store[$directory] = $default;
                            }
                        }
                    }
                }
            });

        // Convert all entries ending in . to array values (these typically have variable subkeys following)
        foreach ($store as $directory => $default) {
            if (str_ends_with($directory, '.')) {
                $store[substr($directory, 0, -1)] = [];
                unset($store[$directory]);
            }
        }

        // Fix all entries that have variables or weird values
        foreach ($store as $directory => $default) {
            if (!is_scalar($default)) {
                continue;
            }

            if (str_starts_with($default, '$')) {
                $store[$directory] = null;
            }

            if (str_contains($default, '::')) {
                $store[$directory] = null;
            }

            if (str_contains($default, ',')) {
                $store[$directory] = Strings::from($store[$directory], ',');
                $store[$directory] = trim($store[$directory]);
            }
        }

        // Sort so that we have a nice alphabetically ordered list
        asort($store);

        // Great, we have all used configuration paths and their default values! Now construct the config/default.yaml
        // file
        $data = [];

        // Convert the store to an array map
        foreach ($store as $directory => $default) {
            $directory = explode('.', $directory);
            $section = &$data;
            $count++;

            foreach ($directory as $key) {
                if (!array_key_exists($key, $section)) {
                    // Initialize with subarray and jump in
                    $section[$key] = [];
                }

                $section = &$section[$key];
            }

            $section = $default;
            unset($section);
        }

        // Save and return count
        static::save();
        return $count;
    }


    /**
     * Save the configuration as currently in memory to the configuration file
     *
     * @param array|null $data
     * @return void
     */
    public static function save(?array $data = null): void
    {
        if ($data === null) {
            // Save the data from this Config object
            $data = static::$data;
        }

        // Convert the data into yaml and store the data in the default file
        $data = yaml_emit($data);
        $data = Strings::from($data, "\n");
        $data = Strings::untilReverse($data, "\n");
        $data = Strings::untilReverse($data, "\n") . "\n";

        Log::action(tr('Saving environment ":env"', [':env' => static::$environment]));
        file_put_contents(DIRECTORY_ROOT . 'config/' . static::$environment . '.yaml', $data);
    }


    /**
     * Import data from the specified setup configuration and save it in a yaml config file for the current environment
     *
     * @param Configuration $configuration
     * @return void
     * @throws Exception
     */
    public static function import(Configuration $configuration): void
    {
        // Reset data, then import data
        static::reset();

        static::$data = [
            'security' => [
                'seed' => Strings::random(random_int(16, 32))
            ],
            'debug' => [
                'enabled' => (static::$environment !== 'production'),
                'production' => (static::$environment === 'production')
            ],
            'project' => [
                'name' => $configuration->getProject(),
                'version' => '0.0.0'
            ],
            'languages' => [
                'supported' => ['en'],
                'default' => 'en'
            ],
            'databases' => [
                'sql' => [
                    'debug' => (static::$environment === 'production'),
                    'instances' => [
                        'system' => [
                            'type'   => 'mysql',
                            'server' => $configuration->getDatabase()->getHost(),
                            'name'   => $configuration->getDatabase()->getName(),
                            'user'   => $configuration->getDatabase()->getUser(),
                            'pass'   => $configuration->getDatabase()->getPass()
                        ]
                    ],
                ],

                'memcached' => [
                    'instances' => [
                        'system' => null
                    ]
                ]
            ],
            'notifications' => [
                'groups' => [
                    'developers' => [
                        $configuration->getEmail()
                    ]
                ]
            ],
            'web' => [
                'minify' => false,
                'sessions' => [
                    'cookies' => [
                        'secure' => false,
                        'domain' => 'auto',
                    ]
                ],
                'domains' => [
                    'primary' => [
                        'www' => 'http://' . $configuration->getDomain() . '/:LANGUAGE/',
                        'cdn' => 'http://cdn.' . $configuration->getDomain() . '/:LANGUAGE/'
                    ],
                    'whitelabel1' => [
                        'www' => 'https://whitelabel1.phoundation.org/:LANGUAGE/',
                        'cdn' => 'https://cdn.whitelabel1.phoundation.org/:LANGUAGE/'
                    ],
                ],
                'route' => [
                    'known-hacks' => [
                    ]
                ]
            ],
        ];
    }


    /**
     * Fixes configuration key names, - will be replaced with _
     *
     * @param array $data
     * @return array
     */
    protected static function fixKeys(array $data): array
    {
        $return = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                // Recurse
                $value = static::fixKeys($value);
            }

            $return[str_replace('-', '_', (string) $key)] = $value;
        }

        return $return;
    }


    /**
     * Reset this Config object
     *
     * @return void
     */
    protected static function reset(): void
    {
        static::$fail  = false;
        static::$data  = [];
        static::$files = [];
        static::$cache = [];
    }
}