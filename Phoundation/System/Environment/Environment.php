<?php

namespace Phoundation\System\Environment;

use Phoundation\Core\Config;
use Phoundation\Core\Log;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\File;
use Phoundation\System\Libraries;



/**
 * Environment class
 *
 * This is the prototype Init class that contains the basic methods for all other Init classes in all other libraries
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package \Phoundation\System
 */
class Environment
{
    /**
     * The environment name
     *
     * @var string $name
     */
    protected string $name;

    /**
     * The core configuration for this environment
     *
     * @var Configuration $config
     */
    protected Configuration $config;



    /**
     * Environment class constructor
     *
     * @param string $environment
     */
    protected function __construct(string $environment)
    {
        $this->name   = self::sanitize($environment);
        $this->config = new Configuration();
    }



    /**
     * Returns if the specified environment name is valid or not
     *
     * @param string $environment
     * @return string
     */
    public static function sanitize(string $environment): string
    {
        if (!$environment) {
            throw OutOfBoundsException::new(tr('No environment specified'))->makeWarning();
        }

        if (strlen($environment) > 32) {
            throw OutOfBoundsException::new(tr('Specified environment is ":size" characters long, please specify an environment equal or less than 32 characters', [
                ':size' => strlen($environment)
            ]))->makeWarning();
        }

        $environment = strtoupper($environment);

        if (!preg_match('/[A-Z0-9_]+/', $environment)) {
            throw OutOfBoundsException::new(tr('Specified environment ":environment" contains invalid characters, please ensure it has only A-Z, 0-9 or _', [
                ':environment' => $environment
            ]))->makeWarning();
        }

        return $environment;
    }



    /**
     * Returns if the specified environment exists or not
     *
     * For an environment to exist, a configuration file must be available
     *
     * @param string $environment
     * @return bool
     */
    public static function exists(string $environment): bool
    {
        $environment = self::sanitize($environment);
        return file_exists(self::getConfigurationFile($environment));
    }



    /**
     * Returns the configuration file for the specified environment
     *
     * @param string $environment
     * @return string
     */
    public static function getConfigurationFile(string $environment): string
    {
        return PATH_ROOT  . 'config/' . strtolower($environment) . '.yaml';
    }



    /**
     * Returns a new environment with the specified name
     *
     * @param string $environment
     * @return Environment
     */
    public static function new(string $environment): Environment
    {
        if (self::exists($environment)) {
            throw new OutOfBoundsException(tr('Specified environment ":environment" already exist', [
                ':environment' => $environment
            ]));
        }

        return new Environment($environment);
    }



    /**
     * Returns the specified environment
     *
     * @param string $environment
     * @return Environment
     */
    public function get(string $environment): Environment
    {
        if (!self::exists($environment)) {
            throw new OutOfBoundsException(tr('Specified environment ":environment" does not exist', [
                ':environment' => $environment
            ]));
        }

        return new Environment($environment);
    }



    /**
     * Returns the configuration for this environment
     *
     * @return Configuration
     */
    public function getConfiguration(): Configuration
    {
        return $this->config;
    }



    /**
     * Remove this environment
     *
     * This will remove all databases and configuration files for this environment
     * @return void
     */
    public function remove(): void
    {
        // Use the requested environment
        Config::useEnvironment($this->name);

        // Drop core database
        sql()->drop();

        // Delete the configuration file
        Config::useEnvironment();
        File::new(self::getConfigurationFile($this->name))->delete();
    }



    /**
     * Create this environment
     *
     * @return void
     */
    public function setup(): void
    {
        Log::action(tr('Generating configuration...'));
        Config::import($this->getConfiguration());
        Config::save();
        Config::useEnvironment($this->name);

        Log::action(tr('Initializing system...'));
        Libraries::initialize(true, true, true, 'System setup');
    }
}