<?php

namespace Phoundation\Core;

use Phoundation\Core\Exception\ConfigException;
use Phoundation\Core\Exception\ConfigNotExistsException;
use Phoundation\Core\Log\Log;
use Phoundation\Developer\Debug;
use Phoundation\Developer\Project\Configuration;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\File;
use Phoundation\Filesystem\Path;
use Phoundation\Filesystem\Restrictions;
use Throwable;


/**
 * Class Config
 *
 * This class contains the methods to read, write and manage configuration options. Default configuration values are
 * specified in the classes themselves whereas users can add configuration sections in the YAML file
 * PATH_ROOT/config/ENVIRONMENT/CLASSNAME and this class will apply those values.
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Core
 */
class Config
{
    /**
     * Singleton variable
     *
     * @var Config|null $instance
     */
    protected static ?Config $instance = null;

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
     * Singleton, ensure to always return the same Log object.
     *
     * @return static
     */
    public static function getInstance(): static
    {
        if (!isset(self::$instance)) {
            self::$instance = new static();
        }

        return self::$instance;
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
     * Lets the Config object use the specified (or if not specified, the current global) environment
     *
     * @param string $environment
     * @return void
     */
    public static function setEnvironment(string $environment): void
    {
        if (!$environment) {
            // Environment was specified as "", use no environment!
            static::$environment = null;
            static::reset();
            return;
        }

        // Use the specified environment
        static::$environment = strtolower(trim($environment));

        static::reset();
        static::read();
    }



    /**
     * Return configuration BOOLEAN for the specified key path
     *
     * @note Will cause an exception if a non-boolean value is returned!
     * @param string|array $path
     * @param bool|null $default
     * @param mixed|null $specified
     * @return bool
     */
    public static function getBoolean(string|array $path, ?bool $default = null, mixed $specified = null): bool
    {
        $return = static::get($path, $default, $specified);

        try {
            if (is_bool($return)) {
                return $return;
            }

            // Try to interpret as boolean
            return Strings::getBoolean($return);
        } catch(OutOfBoundsException) {
            // Do nothing, following exception will do the job
        }

        throw new ConfigException(tr('The configuration path ":path" should be a boolean value (Accepted are true, "true", "yes", "y", "1", false, "false", "no", "n", or 1), but has value ":value" instead', [
            ':path'  => $path,
            ':value' => $return
        ]));
    }



    /**
     * Return configuration INTEGER for the specified key path
     *
     * @note Will cause an exception if a non integer value is returned!
     * @param string|array $path
     * @param int|null $default
     * @param mixed|null $specified
     * @return int
     */
    public static function getInteger(string|array $path, ?int $default = null, mixed $specified = null): int
    {
        $return = static::get($path, $default, $specified);

        if (is_integer($return)) {
            return $return;
        }

        throw new ConfigException(tr('The configuration path ":path" should be an integer number but has value ":value"', [
            ':path'  => $path,
            ':value' => $return
        ]));
    }



    /**
     * Return configuration NUMBER for the specified key path
     *
     * @note Will cause an exception if a non-numeric value is returned!
     * @param string|array $path
     * @param int|float|null $default
     * @param mixed|null $specified
     * @return int|float
     */
    public static function getNatural(string|array $path, int|float|null $default = null, mixed $specified = null): int|float
    {
        $return = static::get($path, $default, $specified);

        if (is_natural($return)) {
            return $return;
        }

        throw new ConfigException(tr('The configuration path ":path" should be a natural number, integer 0 or above, but has value ":value"', [
            ':path'  => $path,
            ':value' => $return
        ]));
    }



    /**
     * Return configuration NUMBER for the specified key path
     *
     * @note Will cause an exception if a non-numeric value is returned!
     * @param string|array $path
     * @param int|float|null $default
     * @param mixed|null $specified
     * @return int|float
     */
    public static function getFloat(string|array $path, int|float|null $default = null, mixed $specified = null): int|float
    {
        $return = static::get($path, $default, $specified);

        if (is_float($return)) {
            return $return;
        }

        throw new ConfigException(tr('The configuration path ":path" should be a number but has value ":value"', [
            ':path'  => $path,
            ':value' => $return
        ]));
    }



    /**
     * Return configuration ARRAY for the specified key path
     *
     * @note Will cause an exception if a non array value is returned!
     * @param string|array $path
     * @param array|null $default
     * @param mixed|null $specified
     * @return array
     */
    public static function getArray(string|array $path, array|null $default = null, mixed $specified = null): array
    {
        $return = static::get($path, $default, $specified);

        if (is_array($return)) {
            return $return;
        }

        throw new ConfigException(tr('The configuration path ":path" should be an array but has value ":value"', [
            ':path'  => $path,
            ':value' => $return
        ]));
    }



    /**
     * Return configuration STRING for the specified key path
     *
     * @note Will cause an exception if a non string value is returned!
     * @param string|array $path
     * @param string|null $default
     * @param mixed|null $specified
     * @return string
     */
    public static function getString(string|array $path, string|null $default = null, mixed $specified = null): string
    {
        $return = static::get($path, $default, $specified);

        if (is_string($return)) {
            return $return;
        }

        throw new ConfigException(tr('The configuration path ":path" should be a string but has value ":value"', [
            ':path'  => $path,
            ':value' => $return
        ]));
    }



    /**
     * Returns true of the specified configuration path exists
     *
     * @param string|array $path The key path to search for. This should be specified either as an array with key names
     *                           or a . separated string
     * @return bool
     */
    public static function exists(string|array $path): bool
    {
        try {
            static::get($path);
            return true;

        } catch (ConfigNotExistsException) {
            // Ignore, just return null
            return false;
        }
    }



    /**
     * Returns the value for the specified configuration path, if it exists.
     *
     * No error will be thrown if the specified configuration path does not exist
     *
     * @param string|array $path The key path to search for. This should be specified either as an array with key names
     *                           or a . separated string
     * @return mixed
     */
    public static function test(string|array $path): mixed
    {
        try {
            return static::get($path);

        } catch (ConfigNotExistsException) {
            // Ignore, just return null
            return null;
        }
    }



    /**
     * Return configuration data for the specified key path
     *
     * @param string|array $path    The key path to search for. This should be specified either as an array with key
     *                              names or a . separated string
     * @param mixed|null $default   The default value to return if no value was found in the configuration files
     * @param mixed|null $specified A value that might have been specified by a calling function. IF this value is not
     *                              NULL, it will automatically be returned as we will assume that that is the user
     *                             (developer) specified value we should be using, overriding configuration and defaults
     * @return mixed
     */
    public static function get(string|array $path, mixed $default = null, mixed $specified = null): mixed
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
        $cache_key = Strings::force($path, '.');

        if (array_key_exists($cache_key, static::$cache)) {
            return static::$cache[$cache_key];
        }

        static::getInstance();

        if ($specified) {
            return $specified;
        }

        $path = Arrays::force($path, '.');
        $data = &static::$data;

        // Go over each key and if the value for the key is an array, request a subsection
        foreach ($path as $section) {
            if (!is_array($data)) {
//                echo "<pre>";var_dump($path);var_dump($section);var_dump($data);echo "\n";

                if ($data !== null) {
                    Log::warning(tr('Encountered invalid configuration structure whilst looking for ":path". Section ":section" should contain sub values but does not. Please check your configuration files that this structure exists correctly', [
                        ':path'    => $path,
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
                    throw new ConfigNotExistsException(tr('The configuration section ":section" from key path ":path" does not exist. Please check "production.yaml" AND ":environment.yaml"', [
                        ':environment' => ENVIRONMENT,
                        ':section'     => $section,
                        ':path'        => Strings::force($path, '.')
                    ]));
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
     * @param string|array $path    The key path to search for. This should be specified either as an array with key
     *                              names or a . separated string
     * @param mixed $value
     * @return mixed
     */
    public static function set(string|array $path, mixed $value = null): mixed
    {
        static::getInstance();

        $cache_key = Strings::force($path, '.');
        $path      = Arrays::force($path, '.');
        $data      = &static::$data;

        // Go over each key and if the value for the key is an array, request a subsection
        foreach ($path as $section) {
            if (!is_array($data)) {
                // Oops, this data section should be an array
                throw new ConfigException(tr('The configuration section ":section" from requested path ":path" does not exist', [
                    ':section' => $section,
                    ':path' => $path
                ]));
            }

            if (!array_key_exists($section, $data)) {
                // The requested key does not exist, initialize with an array just in case
                $data[$section] = [];
            }

            // Get the requested subsection
            $data = &$data[$section];
        }

        // Clear config cache
        static::$cache = [];

        // The variable $data should now be the correct leaf node. Assign it $value and return it.
        $data = $value;
        return static::$cache[$cache_key] = $value;
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
            } else {
                $environments = ['production', static::$environment];
            }

            // Read the section for each environment
            foreach ($environments as $environment) {
                $file = PATH_ROOT . 'config/' . $environment . '.yaml';
                Restrictions::new(PATH_ROOT . 'config/')->check($file, false);

                // Check if a configuration file exists for this environment
                if (!file_exists($file)) {
                    // Do NOT use tr() here as it will cause endless loops!
                    throw ConfigException::new('Configuration file "' . Strings::from($file, PATH_ROOT) . '" for environment "' . Strings::log($environment) . '" does not exist')
                        ->makeWarning();
                }

                try {
                    // Read the configuration data and merge it in the internal configuration data array
                    $data = yaml_parse_file($file);
                } catch (Throwable $e) {
                    // Failed to read YAML data from configuration file
                    static::$fail = true;
                    throw ConfigException::new('Configuration file "' . Strings::from($file, PATH_ROOT) . '" for environment "' . Strings::log($environment) . '" does not exist', null, null, $e)
                        ->makeWarning();
                }

                if (!is_array($data)) {
                    throw new OutOfBoundsException(tr('Configuration data in file ":file" has an invalid format', [
                        ':file' => $file
                    ]));
                }

                static::$data = Arrays::mergeFull(static::$data, $data);
            }

        } catch (ConfigException $e) {
            static::$fail = true;
            // TODO Log here that configuration loading failed.
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
        Path::new(PATH_ROOT, PATH_ROOT)->execute()
            ->addSkipPaths([PATH_DATA, PATH_ROOT . 'tests', PATH_ROOT . 'garbage'])
            ->setRecurse(true)
            ->setServerRestrictions(new Restrictions(PATH_ROOT))
            ->onFiles(function (string $file) use (&$store) {
                $files = File::new($file, PATH_ROOT)->grep(['Config::get(\'', 'Config::set(\'']);

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
                                $path = str_replace(['"', "'"], '', trim($matches[1][$match]));
                                $default = str_replace(['"', "'"], '', trim($matches[2][$match]));

                                // Log all Config::get() and Config::set() calls that have the same configuration path but different
                                // default values
                                if (array_key_exists($path, $store)) {
                                    if ($store[$path] !== $default) {
                                        Log::warning(tr('Configuration path ":path" has two different default values ":1" and ":2"', [
                                            ':path' => $path,
                                            ':1' => $default,
                                            ':2' => $store[$path],
                                        ]));
                                    }
                                }

                                // Store the configuration path
                                $store[$path] = $default;
                            }
                        }
                    }
                }
            });

        // Convert all entries ending in . to array values (these typically have variable subkeys following)
        foreach ($store as $path => $default) {
            if (str_ends_with($path, '.')) {
                $store[substr($path, 0, -1)] = [];
                unset($store[$path]);
            }
        }

        // Fix all entries that have variables or weird values
        foreach ($store as $path => $default) {
            if (!is_scalar($default)) {
                continue;
            }

            if (str_starts_with($default, '$')) {
                $store[$path] = null;
            }

            if (str_contains($default, '::')) {
                $store[$path] = null;
            }

            if (str_contains($default, ',')) {
                $store[$path] = Strings::from($store[$path], ',');
                $store[$path] = trim($store[$path]);
            }
        }

        // Sort so that we have a nice alphabetically ordered list
        asort($store);

        // Great, we have all used configuration paths and their default values! Now construct the config/default.yaml
        // file
        $data = [];

        // Convert the store to an array map
        foreach ($store as $path => $default) {
            $path = explode('.', $path);
            $section = &$data;
            $count++;

            foreach ($path as $key) {
                if (!array_key_exists($key, $section)) {
                    // Initialize with sub array and jump in
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
        file_put_contents(PATH_ROOT . 'config/' . static::$environment . '.yaml', $data);
    }



    /**
     * Import data from the specified setup configuration and save it in a yaml config file for the current environment
     *
     * @param Configuration $configuration
     * @return void
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