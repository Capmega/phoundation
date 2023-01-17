<?php

namespace Phoundation\System\Environment;

use Phoundation\Accounts\Users\User;
use Phoundation\Core\Log;
use Phoundation\Core\Strings;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\File;
use Phoundation\Filesystem\Restrictions;



/**
 * Project class
 *
 * This is the prototype Init class that contains the basic methods for all other Init classes in all other libraries
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package \Phoundation\System
 */
class Project
{
    /**
     * The project name
     *
     * @var string $name
     */
    protected static string $name;

    /**
     * The local environment for this project
     *
     * @var Environment $environment
     */
    protected static Environment $environment;



    /**
     * Project class constructor
     */
    protected function __construct()
    {
        self::$name = self::load();
    }



    /**
     * Returns a new project with the specified name
     *
     * @param string $project
     * @param bool $force
     * @return void
     */
    public static function create(string $project, bool $force = false): void
    {
        if (self::exists()) {
            if (!$force) {
                throw new OutOfBoundsException(tr('Project file "config/project" already exist'));
            }

            File::new(PATH_ROOT . 'config/project')->delete();
        }

        self::$name = $project;
        self::save();
    }



    /**
     * Returns the specified project
     *
     * @return Project
     */
    public static function get(): Project
    {
        if (!self::exists()) {
            throw new OutOfBoundsException(tr('Project file "config/project" does not exist'));
        }

        return new Project();
    }



    /**
     * Returns the configuration for this project
     *
     * @param string $environment
     * @return Environment
     */
    public static function setEnvironment(string $environment): Environment
    {
        $environment = Environment::sanitize($environment);

        if (Environment::exists($environment)) {
            if (!FORCE) {
                throw OutOfBoundsException::new(tr('Specified environment ":environment" has already been setup', [
                    ':environment' => $environment
                ]))->makeWarning();
            }

            self::removeEnvironment($environment);
        }

        self::$environment = Environment::new($environment);
        return self::$environment;
    }



    /**
     * Returns the configuration for this project
     *
     * @return Environment
     */
    public static function getEnvironment(): Environment
    {
        return self::$environment;
    }



    /**
     * Returns all available environments
     *
     * @return array
     */
    public static function getEnvironments(): array
    {
        $return = [];
        $files  = scandir(PATH_ROOT . '/config/*.yaml');

        foreach ($files as $file)
        {
            if ($file[0] === '.') {
                // No hidden files no . no ..
                continue;
            }

            $return[] = Strings::fromReverse($file, '/');
        }

        return $return;
    }



    /**
     * Remove this project
     *
     * This will remove all databases and configuration files for this project
     * @return void
     */
    public static function remove(): void
    {
        Log::action(tr('Removing project'));

        foreach (self::getEnvironments() as $environment) {
            // Delete this environment
            self::removeEnvironment($environment);
        }

        // Remove the project file
        File::new(PATH_ROOT . 'config/project', Restrictions::new(PATH_ROOT . 'config/project', true))->delete();
    }



    /**
     * Create this project
     *
     * @return void
     */
    public static function setup(): void
    {
        $configuration = self::$environment->getConfiguration();

        Log::information(tr('Initializing project ":project", this can take a little while...', [
            ':project' => self::$name
        ]));

        self::getEnvironment()->setup();

        Log::action(tr('Creating administrative user ":email", almost done...', [
            ':email' => $configuration->getEmail()
        ]));

        $user = User::new()
            ->setEmail($configuration->getEmail())
            ->save();

        $user->setPassword($configuration->getPassword(), $configuration->getPassword());
    }



    /**
     * Returns if the project file exists
     *
     * @return bool
     */
    public static function exists(): bool
    {
        return file_exists(PATH_ROOT . 'config/project');
    }



    /**
     * Loads and returns the project name
     *
     * @return string|null
     */
    protected static function load(): ?string
    {
        $project = file_get_contents(PATH_ROOT . 'config/project');
        $project = self::sanitize($project);

        return $project;
    }



    /**
     * Saves the project name
     *
     * @return void
     */
    protected static function save(): void
    {
        file_put_contents(PATH_ROOT . 'config/project', self::$name);
    }



    /**
     * Returns if the specified project name is valid or not
     *
     * @param string $project
     * @return string
     */
    protected static function sanitize(string $project): string
    {
        if (!$project) {
            throw OutOfBoundsException::new(tr('No project name specified in the project file ":file"', [':file' => 'config/project']))->makeWarning();
        }

        if (strlen($project) > 32) {
            throw OutOfBoundsException::new(tr('Specified project name is ":size" characters long, please specify a project name equal or less than 32 characters', [
                ':size' => strlen($project)
            ]))->makeWarning();
        }

        $project = strtoupper($project);

        if (!preg_match('/[A-Z0-9_]+/', $project)) {
            throw OutOfBoundsException::new(tr('Specified project ":project" contains invalid characters, please ensure it has only A-Z, 0-9 or _', [
                ':project' => $project
            ]))->makeWarning();
        }

        return $project;
    }



    /**
     * Remove the specified environment
     *
     * @param string $environment
     * @return bool
     */
    protected static function removeEnvironment(string $environment): bool
    {
        if (!Environment::exists($environment)) {
            return false;
        }

        // Get the environment and remove all environment specific data
        return Environment::get($environment)->remove();
    }

}