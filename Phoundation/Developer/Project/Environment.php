<?php

declare(strict_types=1);

namespace Phoundation\Developer\Project;

use Phoundation\Core\Libraries\Libraries;
use Phoundation\Core\Log\Log;
use Phoundation\Developer\Project\Exception\EnvironmentException;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\File;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Utils\Config;
use Throwable;

/**
 * Environment class
 *
 * This is the prototype Init class that contains the basic methods for all other Init classes in all other libraries
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   \Phoundation\Developer
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
     * The project name
     *
     * @var string $project
     */
    protected string $project;

    /**
     * The core configuration for this environment
     *
     * @var Configuration $config
     */
    protected Configuration $config;


    /**
     * Environment class constructor
     *
     * @param string $project
     * @param string $environment
     */
    protected function __construct(string $project, string $environment)
    {
        $this->name    = static::sanitize($environment);
        $this->project = $project;
        $this->config  = new Configuration($project);
    }


    /**
     * Returns if the specified environment name is valid or not
     *
     * @param string $environment
     *
     * @return string
     */
    public static function sanitize(string $environment): string
    {
        if (!$environment) {
            throw OutOfBoundsException::new(tr('No environment specified'))
                                      ->makeWarning();
        }
        if (strlen($environment) > 32) {
            throw OutOfBoundsException::new(tr('Specified environment is ":size" characters long, please specify an environment equal or less than 32 characters', [
                ':size' => strlen($environment),
            ]))
                                      ->makeWarning();
        }
        $environment = strtoupper($environment);
        if (!preg_match('/[A-Z0-9_]+/', $environment)) {
            throw OutOfBoundsException::new(tr('Specified environment ":environment" contains invalid characters, please ensure it has only A-Z, 0-9 or _', [
                ':environment' => $environment,
            ]))
                                      ->makeWarning();
        }

        return $environment;
    }


    /**
     * Returns a new environment with the specified name
     *
     * @param string $project
     * @param string $environment
     *
     * @return static
     */
    public static function new(string $project, string $environment): static
    {
        Log::action(tr('Generating new environment ":env"', [':env' => $environment]));
        if (static::exists($environment)) {
            throw new OutOfBoundsException(tr('Specified environment ":environment" already exist', [
                ':environment' => $environment,
            ]));
        }

        return new static($project, $environment);
    }


    /**
     * Returns if the specified environment exists or not
     *
     * For an environment to exist, a configuration file must be available
     *
     * @param string $environment
     *
     * @return bool
     */
    public static function exists(string $environment): bool
    {
        $environment = static::sanitize($environment);

        return file_exists(static::getConfigurationFile($environment));
    }


    /**
     * Returns the configuration file for the specified environment
     *
     * @param string $environment
     *
     * @return string
     */
    public static function getConfigurationFile(string $environment): string
    {
        return DIRECTORY_ROOT . 'config/' . strtolower($environment) . '.yaml';
    }


    /**
     * Returns the specified environment
     *
     * @param string $project
     * @param string $environment
     *
     * @return Environment
     */
    public static function get(string $project, string $environment): Environment
    {
        if (!static::exists($environment)) {
            throw new OutOfBoundsException(tr('Specified environment ":environment" does not exist', [
                ':environment' => $environment,
            ]));
        }

        return new Environment($project, $environment);
    }


    /**
     * Returns the name for this environment
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }


    /**
     * Remove this environment
     *
     * This will remove all databases and configuration files for this environment
     *
     * @return bool
     */
    public function remove(): bool
    {
        // Use the requested environment
        Config::setEnvironment($this->name);
        Log::action(tr('Removing environment ":env"', [':env' => strtolower($this->name)]));
        // Drop core database
        try {
            sql('system', false)
                ->schema()
                ->database()
                ->drop();
        } catch (Throwable $e) {
            Log::warning(tr('Failed to drop system database for environment ":env" because ":message", continuing...', [
                ':env'     => strtolower($this->name),
                ':message' => $e->getMessage() . ($e->getPrevious() ? ', ' . $e->getPrevious()
                                                                               ->getMessage() : null),
            ]));
        }
        // Stop using this environment, if it was used
        if (Config::getEnvironment() === $this->name) {
            Config::setEnvironment('');
        }
        // delete the environment configuration file
        File::new(static::getConfigurationFile($this->name), Restrictions::new(DIRECTORY_ROOT . 'config/', true))
            ->delete();

        return true;
    }


    /**
     * Create this environment
     *
     * @return void
     */
    public function setup(): void
    {
        try {
            Log::action(tr('Generating configuration for environment ":env"...', [
                ':env' => strtolower($this->name),
            ]));
            // Create production configuration
            if ($this->name !== 'production') {
                Config::setEnvironment('production');
                Config::import($this->getConfiguration());
                Config::save();
            }
            Config::setEnvironment($this->name);
            Config::import($this->getConfiguration());
            Config::save();

        } catch (Throwable $e) {
            if (str_contains($e->getMessage(), 'must not be accessed before initialization')) {
                throw new EnvironmentException(tr('Failed to generate new environment configuration because not all setup parameters were copied into the project environment configuration. Please check your setup script.'));
            }
            throw new EnvironmentException(tr('Failed to generate new environment configuration because ":e"', [
                ':e' => $e->getMessage(),
            ]));
        }
        Log::action(tr('Ensuring system database is gone'));
        sql(null, false)
            ->schema()
            ->database()
            ->drop();
        Log::action(tr('Initializing system...'));
        Libraries::initialize(true, true, true, 'System setup');
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
}
