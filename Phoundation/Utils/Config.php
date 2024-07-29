<?php

/**
 * Class Config
 *
 * This class contains the methods to read, write and manage configuration options. Default configuration values are
 * specified in the classes themselves whereas users can add configuration sections in the YAML file
 * DIRECTORY_ROOT/config/ENVIRONMENT/CLASSNAME and this class will apply those values.
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Utils
 */

declare(strict_types=1);

namespace Phoundation\Utils;

use Exception;
use Phoundation\Core\Interfaces\ConfigInterface;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Developer\Debug;
use Phoundation\Developer\Project\Configuration;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\FsDirectory;
use Phoundation\Filesystem\FsFile;
use Phoundation\Filesystem\FsRestrictions;
use Phoundation\Utils\Exception\ConfigException;
use Phoundation\Utils\Exception\ConfigFailedException;
use Phoundation\Utils\Exception\ConfigFileDoesNotExistsException;
use Phoundation\Utils\Exception\ConfigParseFailedException;
use Phoundation\Utils\Exception\ConfigPathDoesNotExistsException;
use Throwable;

class Config implements ConfigInterface
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
     * @var string|false $failed
     */
    protected static string|false $failed = false;

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
     * The configuration section used by this configuration object
     *
     * @var string|null $section
     */
    protected static ?string $section = null;

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
     * Tracks if configuration access is allowed without environment available
     *
     * @var bool
     */
    protected static bool $allow_no_environment = false;


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
     * @param bool   $include_production
     *
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


    /**environment
     * Returns the current section for the configuration object
     *
     * @return string|null
     */
    public static function getSection(): ?string
    {
        return substr(static::$section, 0, -1);
    }


    /**
     * Lets the Config object use the specified (or if not specified, the current global) environment
     *
     * @param string $section
     * @param string $environment
     * @param bool   $include_production
     *
     * @return void
     */
    public static function setSection(string $section, string $environment, bool $include_production = true): void
    {
        static::$section = get_null(strtolower(trim($section)));

        if (static::$section) {
            static::$section .= '/';
        }

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
     * Returns a config object for the specified environment
     *
     * @param string $environment
     *
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
     * Returns a config object for the specified environment
     *
     * @param string $section
     * @param string $environment
     *
     * @return ConfigInterface
     */
    public static function forSection(string $section, string $environment): ConfigInterface
    {
        $key = $section . '-' . $environment;

        if (empty(static::$instances[$key])) {
            static::$instances[$key] = new static();
            static::$instances[$key]->setSection($section, $environment);
        }

        return static::$instances[$key];
    }


    /**
     * Returns true if the Config object has failed
     *
     * @return string|false
     */
    public static function getFailed(): string|false
    {
        return static::$failed;
    }


    /**
     * Return configuration BOOLEAN for the specified key path
     *
     * @note Will cause an exception if a non-boolean value is returned!
     *
     * @param string|array $path
     * @param bool|null    $default
     * @param mixed|null   $specified
     *
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
            return Strings::toBoolean($return);

        } catch (OutOfBoundsException) {
            if (($default === null) and static::$allow_no_environment) {
                // In the allow no environment mode, we can return default (if not specified, default will be false)
                return false;
            }

            // Do nothing, following exception will do the job

            throw new ConfigException(tr('The configuration path ":path" should be a boolean value (Accepted are true, "true", "yes", "y", "1", false, "false", "no", "n", or 1), but has value ":value" instead', [
                ':path'  => $path,
                ':value' => $return,
            ]));
        }
    }


    /**
     * Return configuration data for the specified key path
     *
     * @note An environment must be available when calling this method. If (during early startup) the environment is not
     *       available (yet) a ConfigException will be thrown
     *
     * @note If no environment is available (usually during early startup) and static::$allow_no_environment is true,
     *       this method will not throw an exception but will always return the default value instead
     *
     * @param string|array $path      The key path to search for. This should be specified either as an array with key
     *                                names or a "." separated string
     * @param mixed|null   $default   The default value to return if no value was found in the configuration files
     * @param mixed|null   $specified A value that might have been specified by a calling function. IF this value is
     *                                not
     *                                NULL, it will automatically be returned as we will assume that that is the user
     *                                (developer) specified value we should be using, overriding configuration and
     *                                defaults
     *
     * @return mixed
     */
    public static function get(string|array $path = '', mixed $default = null, mixed $specified = null): mixed
    {
        if (empty(static::$environment)) {
            // We don't really have an environment, don't check configuration
            // NOTE: DO NOT USE TR() HERE AS THE FUNCTIONS FILE MAY NOT YET BE LOADED
            if (!static::$allow_no_environment) {
                throw new ConfigException('Cannot access configuration, environment has not been determined yet');
            }

            // Non environment configuration access will ALWAYS return the default value
            return $default;
        }

        Debug::counter('Config::get()')->increase();

        if (static::$failed) {
            // Config class failed, return all default values when not NULL
            if ($default === null) {
                throw new ConfigFailedException(tr('Cannot get configuration, Config failed with ":e"', [
                    ':e' => static::$failed,
                ]));
            }

            return $default;
        }

        // Do we have cached configuration information?
        $path = Strings::force($path, '.');

        if (array_key_exists($path, static::$cache)) {
            return static::$cache[$path];
        }

        static::getInstance();

        if ($specified) {
            return $specified;
        }

        if (!$path) {
            // No path specified, return everything
            return static::fixKeys(static::$data);
        }

        // Replace escaped "." in the path
        $path = str_replace('\\.', ':', $path);
        $data = &static::$data;

        // Go over each key and if the value for the key is an array, request a subsection
        foreach (Arrays::force($path, '.') as $section) {
            $section = str_replace(':', '.', $section);

            if (!is_array($data)) {
//                echo "<pre>";var_dump($path);var_dump($section);var_dump($data);echo "\n";
                if ($data !== null) {
                    Log::warning(tr('Encountered invalid configuration structure whilst looking for ":path". Section ":section" should contain sub values but does not. Please check your configuration files that this structure exists correctly', [
                        ':path'    => $path,
                        ':section' => $section,
                    ]));
                }

                // This section is missing in config files. No biggie, initialize it as an array
                $data = [];
            }

            if (!array_key_exists($section, $data)) {
                // The requested key does not exist
                if ($default === null) {
                    // We have no default configuration either
                    throw ConfigPathDoesNotExistsException::new(tr('The configuration section ":section" from key path ":path" does not exist. Please check "production.yaml" AND ":environment.yaml"', [
                        ':environment' => ENVIRONMENT,
                        ':section'     => $section,
                        ':path'        => Strings::force($path, '.'),
                    ]));
                }

                // The requested key does not exist in configuration, return the default value instead
                return static::$cache[$path] = $default;
            }

            // Get the requested subsection. This subsection must be an array!
            $data = &$data[$section];
        }

        return static::$cache[$path] = $data;
    }


    /**
     * Singleton, ensure to always return the same Config object.
     *
     * @return ConfigInterface
     */
    public static function getInstance(): ConfigInterface
    {
        if (!isset(static::$instance)) {
            static::$instance = new static();
        }

        return static::$instance;
    }


    /**
     * Fixes configuration key names, - will be replaced with _
     *
     * @param array $data
     *
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
     * Return configuration INTEGER for the specified key path
     *
     * @note Will cause an exception if a non integer value is returned!
     *
     * @param string|array $path
     * @param int|null     $default
     * @param mixed|null   $specified
     *
     * @return int
     */
    public static function getInteger(string|array $path, ?int $default = null, mixed $specified = null): int
    {
        $return = static::get($path, $default, $specified);

        if (is_integer($return)) {
            return $return;
        }

        if (($default === null) and static::$allow_no_environment) {
            // In the allow no environment mode, we can return default (if not specified, default will be 0)
            return 0;
        }

        throw new ConfigException(tr('The configuration path ":path" should be an integer number but has value ":value"', [
            ':path'  => $path,
            ':value' => $return,
        ]));
    }


    /**
     * Return configuration NUMBER for the specified key path
     *
     * @note Will cause an exception if a non-numeric value is returned!
     *
     * @param string|array   $path
     * @param int|float|null $default
     * @param mixed|null     $specified
     *
     * @return int|float
     */
    public static function getNatural(string|array $path, int|float|null $default = null, mixed $specified = null): int|float
    {
        $return = static::get($path, $default, $specified);

        if (is_natural($return)) {
            return $return;
        }

        if (($default === null) and static::$allow_no_environment) {
            // In the allow no environment mode, we can return default (if not specified, default will be 0)
            return 0;
        }

        throw new ConfigException(tr('The configuration path ":path" should be a natural number, integer 0 or above, but has value ":value"', [
            ':path'  => $path,
            ':value' => $return,
        ]));
    }


    /**
     * Return configuration NUMBER for the specified key path
     *
     * @note Will cause an exception if a non-numeric value is returned!
     *
     * @param string|array   $path
     * @param int|float|null $default
     * @param mixed|null     $specified
     *
     * @return int|float
     */
    public static function getFloat(string|array $path, int|float|null $default = null, mixed $specified = null): int|float
    {
        $return = static::get($path, $default, $specified);

        if (is_float($return)) {
            return $return;
        }

        if (($default === null) and static::$allow_no_environment) {
            // In the allow no environment mode, we can return default (if not specified, default will be 0)
            return 0;
        }

        throw new ConfigException(tr('The configuration path ":path" should be a number but has value ":value"', [
            ':path'  => $path,
            ':value' => $return,
        ]));
    }


    /**
     * Return configuration IteratorInterface for the specified key path
     *
     * @note Will cause an exception if a non-array value is returned!
     *
     * @param string|array $path
     * @param array|null   $default
     * @param mixed|null   $specified
     *
     * @return IteratorInterface
     */
    public static function getIterator(string|array $path, array|null $default = null, mixed $specified = null): IteratorInterface
    {
        return new Iterator(static::getArray($path, $default, $specified));
    }


    /**
     * Return configuration ARRAY for the specified key path
     *
     * @note Will cause an exception if a non-array value is returned!
     *
     * @param string|array $path
     * @param array|null   $default
     * @param mixed|null   $specified
     *
     * @return array
     */
    public static function getArray(string|array $path, array|null $default = null, mixed $specified = null): array
    {
        $return = static::get($path, $default, $specified);

        if (is_array($return)) {
            return static::fixKeys($return);
        }

        if (($default === null) and static::$allow_no_environment) {
            // In the allow no environment mode, we can return default (if not specified, default will be array)
            return [];
        }

        throw new ConfigException(tr('The configuration path ":path" should be an array but has value ":value"', [
            ':path'  => $path,
            ':value' => $return,
        ]));
    }


    /**
     * Return configuration STRING for the specified key path
     *
     * @note Will cause an exception if a non string value is returned!
     *
     * @param string|array $path
     * @param string|null  $default
     * @param mixed|null   $specified
     *
     * @return string
     */
    public static function getString(string|array $path, string|null $default = null, mixed $specified = null): string
    {
        $return = static::get($path, $default, $specified);

        if (is_string($return)) {
            return $return;
        }

        if (($default === null) and static::$allow_no_environment) {
            // In the allow no environment mode, we can return default (if not specified, default will be "")
            return '';
        }

        throw new ConfigException(tr('The configuration path ":path" should be a string but has value ":value"', [
            ':path'  => $path,
            ':value' => $return,
        ]));
    }


    /**
     * Return configuration STRING or BOOLEAN for the specified key path
     *
     * @note Will cause an exception if a non string or bool value is returned!
     *
     * @param string|array     $path
     * @param string|bool|null $default
     * @param mixed|null       $specified
     *
     * @return string|bool
     */
    public static function getBoolString(string|array $path, string|bool|null $default = null, mixed $specified = null): string|bool
    {
        $return = static::get($path, $default, $specified);

        if (is_string($return) or is_bool($return)) {
            return $return;
        }

        if (($default === null) and static::$allow_no_environment) {
            // In the allow no environment mode, we can return default (if not specified, default will be false)
            return false;
        }

        throw new ConfigException(tr('The configuration path ":path" should be a string but has value ":value"', [
            ':path'  => $path,
            ':value' => $return,
        ]));
    }


    /**
     * Returns true of the specified configuration path exists
     *
     * @param string|array $path The key path to search for. This should be specified either as an array with key names
     *                           or a . separated string
     *
     * @return bool
     */
    public static function exists(string|array $path): bool
    {
        $uuid = Strings::getUuid();

        if (static::get($path, $uuid) === $uuid) {
            // We got the default value, the requested path does not exist
            return false;
        }

        return true;
    }


    /**
     * Return configuration data for the specified key path
     *
     * @param string|array $path    The key path to search for. This should be specified either as an array with key
     *                              names or a . separated string
     * @param mixed        $value
     *
     * @return mixed
     */
    public static function set(string|array $path, mixed $value = null): mixed
    {
        static::getInstance();

        $path = Strings::force($path, '.');
        $path = str_replace('\\.', ':', $path);
        $data = &static::$data;

        // Go over each key and if the value for the key is an array, request a subsection
        foreach (Arrays::force($path, '.') as $section) {
            $section = str_replace(':', '.', $section);

            if (!is_array($data)) {
                // Oops, this data section should be an array
                throw ConfigException::new(tr('The configuration section ":section" from requested path ":path" does not exist', [
                    ':section' => $section,
                    ':path'    => $path,
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

        return static::$cache[$path] = $value;
    }


    /**
     * Returns true if a configuration file for the specified environment exists, false if not
     *
     * @param string $environment
     *
     * @return bool
     */
    public static function environmentExists(string $environment): bool
    {
        return file_exists(DIRECTORY_ROOT . 'config/' . $environment . '.yaml');
    }


    /**
     * Scan the entire project from ROOT for Config::get() and Config::set() and generate a config/default.yaml file
     * with all default values
     *
     * @return int The number of configuration paths processed
     */
    public static function generateDefaultYaml(): int
    {
        $count = 0;
        $store = [];

        // Scan all files for Config::get() and Config::set() calls
        FsDirectory::new(DIRECTORY_ROOT, FsRestrictions::getWritable(DIRECTORY_ROOT, 'Config::generateDefaultYaml()'))
                 ->execute()
                 ->addSkipDirectories([
                     DIRECTORY_DATA,
                     DIRECTORY_ROOT . 'tests',
                     DIRECTORY_ROOT . 'garbage',
                 ])
                 ->setRecurse(true)
                 ->setRestrictions(new FsRestrictions(DIRECTORY_ROOT))
                 ->onFiles(function (string $file) use (&$store) {
                     $files = FsFile::new($file, FsRestrictions::getReadonly(DIRECTORY_ROOT, 'Config::generateDefaultYaml()'))
                                    ->grep([
                                      'Config::get(\'',
                                      'Config::set(\'',
                                  ]);

                     foreach ($files as $file) {
                         foreach ($file as $lines) {
                             foreach ($lines as $line) {
                                 // Extract the configuration path and default value for each call
                                 if (!preg_match_all('/Config::[gs]et\s?\([\'"](.+?)[\'"](?:.+?)?(?:,\s?(.+?))\)/i', $line, $matches)) {
                                     if (!preg_match_all('/Config::[gs]et\s?\([\'"](.+?)[\'"](?:.+?)?(?:,\s?(.+?))?\)/i', $line, $matches)) {
                                         Log::warning(tr('Failed to extract a Config::get() or Config::set() from line ":line" in file ":file"', [
                                             ':file' => $file,
                                             ':line' => $line,
                                         ]));
                                         continue;
                                     }
                                 }

                                 // Pass over all matches
                                 foreach ($matches[0] as $match => $value) {
                                     $path    = str_replace(['"', "'"], '', trim($matches[1][$match]));
                                     $default = str_replace(['"', "'"], '', trim($matches[2][$match]));

                                     // Log all Config::get() and Config::set() calls that have the same configuration path but different
                                     // default values
                                     if (array_key_exists($path, $store)) {
                                         if ($store[$path] !== $default) {
                                             Log::warning(tr('Configuration path ":path" has two different default values ":1" and ":2"', [
                                                 ':path' => $path,
                                                 ':1'    => $default,
                                                 ':2'    => $store[$path],
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

        // Convert all entries ending in "." to array values (these typically have variable sub-keys following)
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
            $path    = explode('.', $path);
            $section = &$data;
            $count++;

            foreach ($path as $key) {
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
     *
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
     *
     * @return void
     * @throws Exception
     */
    public static function import(Configuration $configuration): void
    {
        // Reset data, then import data
        static::reset();

        static::$data = [
            'security'      => [
                'seed' => Strings::getRandom(random_int(16, 32)),
            ],
            'debug'         => [
                'enabled'    => (static::$environment !== 'production'),
                'production' => (static::$environment === 'production'),
            ],
            'project'       => [
                'name'    => $configuration->getProject(),
                'version' => '0.0.0',
            ],
            'languages'     => [
                'supported' => ['en'],
                'default'   => 'en',
            ],
            'databases'     => [
                'sql' => [
                    'debug'     => (static::$environment === 'production'),
                    'instances' => [
                        'system' => [
                            'type'   => 'mysql',
                            'server' => $configuration->getDatabase()
                                                      ->getHost(),
                            'name'   => $configuration->getDatabase()
                                                      ->getName(),
                            'user'   => $configuration->getDatabase()
                                                      ->getUser(),
                            'pass'   => $configuration->getDatabase()
                                                      ->getPass(),
                        ],
                    ],
                ],
                'memcached' => [
                    'instances' => [
                        'system' => null,
                    ],
                ],
            ],

            'notifications' => [
                'groups' => [
                    'developer' => [
                        $configuration->getEmail(),
                    ],
                ],
            ],

            'web'           => [
                'minify'   => false,
                'sessions' => [
                    'cookies' => [
                        'secure' => false,
                        'domain' => 'auto',
                    ],
                ],

                'domains'  => [
                    'primary'     => [
                        'www' => 'http://' . $configuration->getDomain() . '/:LANGUAGE/',
                        'cdn' => 'http://cdn.' . $configuration->getDomain() . '/:LANGUAGE/',
                    ],
                    'whitelabel1' => [
                        'www' => 'https://whitelabel1.phoundation.org/:LANGUAGE/',
                        'cdn' => 'https://cdn.whitelabel1.phoundation.org/:LANGUAGE/',
                    ],
                ],

                'route'    => [
                    'known-hacks' => [],
                ],
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
        static::$failed = false;
        static::$data   = [];
        static::$files  = [];
        static::$cache  = [];
    }


    /**
     * Escapes . in the specified path section
     *
     * @param string $path
     *
     * @return string
     */
    public static function escape(string $path): string
    {
        return str_replace('.', '\\.', $path);
    }


    /**
     * Reads the configuration file for the specified configuration environment
     *
     * @return void
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
                $environments = [
                    'default',
                    'production',
                ];

            } elseif (static::$include_production) {
                $environments = [
                    'default',
                    'production',
                    static::$environment,
                ];

            } else {
                // Read only the specified environment
                $environments = [static::$environment];
            }

            // Read the section for each environment
            foreach ($environments as $environment) {
                $file = DIRECTORY_ROOT . 'config/' . self::$section . $environment . '.yaml';

                FsRestrictions::new(DIRECTORY_ROOT . 'config/')
                            ->check($file, false);

                // Check if a configuration file exists for this environment
                if (!file_exists($file)) {
                    // Do NOT use tr() here as it will cause endless loops!
                    throw ConfigFileDoesNotExistsException::new('Configuration file "' . Strings::from($file, DIRECTORY_ROOT) . '" for environment "' . Strings::log(static::$environment) . '" does not exist')
                                                          ->makeWarning();
                }

                try {
                    // Read the configuration data and merge it in the internal configuration data array
                    $data = yaml_parse_file($file);

                } catch (Throwable $e) {
                    // Failed to read YAML data from configuration file
                    static::$failed = 'Failed to read configuration file "' . Strings::from($file, DIRECTORY_ROOT) . '" for environment "' . Strings::log(static::$environment) . '" because "' . $e->getMessage() . '"';

                    throw ConfigParseFailedException::new(static::$failed, $e)
                                                    ->makeWarning();
                }

                if (!is_array($data)) {
                    if ($data) {
                        throw new OutOfBoundsException(tr('Configuration data in file ":file" has an invalid format', [
                            ':file' => $file,
                        ]));
                    }

                    // It looks like the configuration file was empty
                    $data = [];
                }

                static::$data = Arrays::mergeFull(static::$data, $data);
            }

        } catch (ConfigException $e) {
            // Do NOT use Log class here as log class requires config which just now failed... Same goes for tr()!
            static::$failed = 'Failed to load configuration file "' . isset_get($file) . '" because: ' . $e->getMessage();
            throw $e;

// TODO The following section may be deleted
//            if (Core::inStartupState()) {
//                Log::errorLog(static::$failed);
//
//                echo 'Failed to start, see framework and web server logs for more information';
//
//                if (PLATFORM_CLI) {
//                    echo PHP_EOL;
//                    exit(1);
//                }
//
//                exit();
//            }
//
//            echo static::$failed . PHP_EOL;
        }
    }


    /**
     * Will allow Config::get() calls with ENVIRONMENT not (yet) defined
     *
     * @return void
     */
    public static function allowNoEnvironment(): void
    {
        static::$failed               = false;
        static::$allow_no_environment = true;
    }


    /**
     * Will not allow Config::get() calls with ENVIRONMENT not (yet) defined
     *
     * @return void
     */
    public static function disallowNoEnvironment(): void
    {
        static::$allow_no_environment = false;
    }
}
