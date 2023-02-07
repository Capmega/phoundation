<?php

namespace Phoundation\Developer\Project;

use Phoundation\Accounts\Users\User;
use Phoundation\Core\Arrays;
use Phoundation\Core\Libraries\Library;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Strings;
use Phoundation\Data\Validator\Validator;
use Phoundation\Developer\Phoundation\Phoundation;
use Phoundation\Developer\Project\Exception\EnvironmentExists;
use Phoundation\Developer\Versioning\Git\Git;
use Phoundation\Developer\Versioning\Git\Traits\GitPath;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Filesystem\File;
use Phoundation\Filesystem\Path;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Filesystem\Traits\ServerRestrictions;
use Phoundation\Processes\Commands\Rsync;
use Phoundation\Processes\Process;
use Throwable;



/**
 * Project class
 *
 * This is the prototype Init class that contains the basic methods for all other Init classes in all other libraries
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package \Phoundation\Developer
 */
class Project
{
    use GitPath;
    use ServerRestrictions;



    /**
     * The project name
     *
     * @var string $name
     */
    protected static string $name;

    /**
     * The local environment for this project
     *
     * @var Environment|null $environment
     */
    protected static ?Environment $environment = null;

    /**
     * The branch the phoundation project currently is on
     *
     * @var string|null $phoundation_branch
     */
    protected ?string $phoundation_branch = null;

    /**
     * The Phoundation directories
     *
     * @var array|string[] $phoundation_directories
     */
    protected array $phoundation_directories = [
        'Phoundation/',
        'scripts/system/'
    ];



    /**
     * Phoundation constructor
     *
     * @param string|null $path
     */
    public function __construct(?string $path = null)
    {
        if (!$path) {
            // Default to this project
            $path = PATH_ROOT;
        }

        $this->path = $path;
        $this->initializeGit();
    }



    /**
     * Returns a new Phoundation object
     *
     * @param string|null $path
     * @return static
     */
    public static function new(?string $path = null): static
    {
        return new static($path);
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
        if (static::projectFileExists()) {
            if (!$force) {
                throw new OutOfBoundsException(tr('Project file "config/project" already exist'));
            }

            File::new(PATH_ROOT . 'config/project')->delete();
        }

        static::$name = $project;
        static::save();
    }



    /**
     * Returns the git object for this project
     *
     * @return Git
     */
    protected function getGit(): Git
    {
        return $this->git;
    }



    /**
     * Returns the project name
     *
     * @return string
     */
    public static function getName():string
    {
        return static::$name;
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
                throw EnvironmentExists::new(tr('Specified environment ":environment" has already been setup', [
                    ':environment' => $environment
                ]))->makeWarning();
            }

            static::removeEnvironment($environment);
        }

        static::$environment = Environment::new(static::$name, $environment);
        return static::$environment;
    }



    /**
     * Returns the selected environment for this project
     *
     * @return Environment|null
     */
    public static function getEnvironment(): ?Environment
    {
        return static::$environment;
    }



    /**
     * Returns all available environments
     *
     * @return array
     */
    public static function getEnvironments(): array
    {
        $return = [];
        $files  = glob(PATH_ROOT . 'config/*.yaml');

        foreach ($files as $file)
        {
            if ($file[0] === '.') {
                // No hidden files no . no ..
                continue;
            }

            $return[] = Strings::untilReverse(basename($file), '.');
        }

        return $return;
    }



    /**
     * Returns true if the specified filesystem location contains a valid Phoundation project installation
     *
     * @param string $path
     * @return bool
     */
    public function isPhoundationProject(string $path): bool
    {
        // Is the path readable?
        $path = Path::new($path, $this->restrictions)->checkReadable()->getFile();

        // All these files and directories must be available.
        $files = [
            'config',
            'data',
            'Phoundation',
            'Plugins',
            'scripts',
            'Templates',
            'tests',
            'vendor',
            'www',
            'cli',
        ];

        foreach ($files as $file) {
            if (!file_exists($path . $file)) {
                return false;
            }
        }

        return true;
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

        foreach (static::getEnvironments() as $environment) {
            // Delete this environment
            static::removeEnvironment($environment);
        }

        // Remove the project file
        Log::warning(tr('Removing project file "config/project"'));
        File::new(PATH_ROOT . 'config/project', Restrictions::new(PATH_ROOT . 'config/project', true))->delete();
    }



    /**
     * Setup this project
     *
     * @return void
     */
    public static function setup(): void
    {
        // Setup environment
        try {
            if (!isset(static::$environment)) {
                throw new OutOfBoundsException(tr('No environment specified'));
            }

            $configuration = static::$environment->getConfiguration();

            Log::information(tr('Initializing project ":project", this can take a little while...', [
                ':project' => static::$name
            ]));

            static::getEnvironment()->setup();

            // Create admin user
            Log::action(tr('Creating administrative user ":email", almost done...', [
                ':email' => $configuration->getEmail()
            ]));

            $user = User::new()
                ->setEmail($configuration->getEmail())
                ->save();

            $user->setPassword($configuration->getPassword(), $configuration->getPassword());
            $user->roles()->add('god');

            Log::success(tr('Finished project setup'));

        } catch (Throwable $e) {
            Log::warning('Setup failed with the following exception. Cancelling setup process and removing files.');
            Log::warning($e);

            // Remove the project and continue throwing the exception
            Project::remove();

            Log::warning(tr('Setup process was cancelled'));
            throw $e;
        }
    }



    /**
     * Returns if the project file exists
     *
     * @return bool
     */
    public static function projectFileExists(): bool
    {
        return file_exists(PATH_ROOT . 'config/project');
    }



    /**
     * Validate the specified project information
     *
     * @param Validator $validator
     * @return void
     */
    public static function validate(Validator $validator): void
    {
        $validator
            ->select('admin_email')->isEmail()
            ->select('admin_pass1')->isPassword()
            ->select('admin_pass2')->isPassword()->isEqualTo('admin_pass1')
            ->select('domain')->isDomain()
            ->select('database_host')->isDomain()
            ->select('database_name')->isVariable()
            ->select('database_user')->isVariable()
            ->select('database_pass1')->isPassword()
            ->select('database_pass2')->isPassword()->isEqualTo('database_pass1')
            ->select('project')->isVariable()
            ->select('environment')->isVariable()
            ->select('import')->isOptional()->isBoolean()
            ->validate();
    }



    /**
     * Loads and returns the project name
     *
     * @return string|null
     */
    public static function load(): ?string
    {
        $project = file_get_contents(PATH_ROOT . 'config/project');
        $project = static::sanitize($project);

        static::$name = $project;

        return $project;
    }



    /**
     * Executes data import for all libraries that support it
     *
     * @param bool $demo
     * @param int $min
     * @param int $max
     * @param array|string|null $libraries
     * @return void
     */
    public static function import(bool $demo, int $min, int $max, array|string|null $libraries): void
    {
        Log::information(tr('Starting import for all libraries that support it'));

        $libraries = Arrays::force(strtolower(Strings::force($libraries)));
        $sections  = [
            'Phoundation/' => tr('Phoundation'),
            'Plugins/'     => tr('Plugin')
        ];

        foreach ($sections as $directory => $section) {
            // Find all import object and execute them
            $files = Process::new('find')
                ->addArgument(PATH_ROOT . $directory)
                ->addArgument('-name')
                ->addArgument('Import.php')
                ->executeReturnArray();

            Log::notice(tr('Found ":count" import classes for section ":section"', [
                ':count'   => count($files),
                ':section' => $section
            ]), 5);

            // Execute all Import objects if they are valid
            foreach ($files as $file) {
                try {
                    include_once($file);
                    $class   = Library::getClassPath($file);
                    $library = Strings::until(Strings::from($file, $directory), '/');

                    if ($libraries and !in_array(strtolower($library), $libraries)) {
                        Log::warning(tr('Not executing import for library ":library" as it is filtered', [
                            ':library' => $library
                        ]));

                        continue;
                    }

                    if (is_subclass_of($class, Import::class)) {
                        Log::action(tr('Importing data for ":section" library ":library" from file ":file"', [
                            ':section' => $section,
                            ':library' => $library,
                            ':file'    => Strings::from($file, PATH_ROOT . $directory)
                        ]), 5);

                        $count = $class::new($demo, $min, $max)->execute();

                        Log::success(tr('Imported ":count" records for ":section" library ":library"', [
                            ':section' => $section,
                            ':library' => $library,
                            ':count'   => $count
                        ]), 6);
                    }
                } catch (Throwable $e) {
                    Log::action(tr('Failed to import data for ":section" library ":library" with the following exception', [
                        ':section' => $section,
                        ':library' => $library
                    ]), 3);

                    Log::error($e);
                }
            }
        }
    }



    /**
     * Checks your Phoundation project installation
     */
    public static function check(bool $repair): void
    {
        throw new UnderConstructionException();
    }



    /**
     * Checks if there are updates availabe for Phoundation
     */
    public static function checkUpdates(): void
    {
        throw new UnderConstructionException();
    }


    /**
     * Updates your Phoundation installation
     *
     * @param string|null $phoundation_path
     * @param string|null $message
     * @param bool $signed
     * @return static
     */
    public function updateLocal(?string $phoundation_path = null, ?string $message = null, bool $signed = false): static
    {
        // Add all files to index to ensure everything will be stashed
        $this->git->add(PATH_ROOT);
        $this->git->stash();

        // Copy Phoundation core files
        $this->copyPhoundationFilesLocal($phoundation_path);

        // If there are changes then add and commit
        if ($this->git->getStatus()->getCount()) {
            if (!$message) {
                $message = tr('Phoundation update');
            }

            $this->git->add([PATH_ROOT . 'Phoundation/', PATH_ROOT . 'scripts/']);
            $this->git->commit($message, $signed);
        }

        // Stash pop the previous changes and reset HEAD to ensure index is empty
        $this->git->stash()->pop();
        $this->git->reset('HEAD');

        return $this;
    }



    /**
     * Updates your Phoundation installation from Phoundation g
     */
    public static function update(): void
    {
        Phoundation::new()->updateFromPRoductionRepository();
    }



    /**
     * Saves the project name
     *
     * @return void
     */
    protected static function save(): void
    {
        file_put_contents(PATH_ROOT . 'config/project', static::$name);
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

        Log::warning(tr('Removing environment ":environment"', [
            ':environment' => $environment
        ]));

        // If we're removing the environment that is currently used then remove it from memory too
        if (static::$environment->getName() === $environment) {
            static::$environment = null;
        }

        // Get the environment and remove all environment specific data
        return Environment::get(static::$name, $environment)->remove();
    }



    /**
     * Initialize the git instances
     *
     * @return void
     */
    protected function initializeGit(): void
    {
        // Get git objects for this project and the phoundation project
        $this->git = Git::new($this->path);
    }



    /**
     * Copy all files from the local phoundation installation.
     *
     * @param string|null $path
     * @return void
     */
    protected function copyPhoundationFilesLocal(?string $path = null): void
    {
        $rsync       = Rsync::new();
        $phoundation = Phoundation::new($path);

        // Move /Phoundation and /scripts out of the way
        $phoundation = Path::new(PATH_ROOT . 'Phoundation/', Restrictions::new([PATH_ROOT . 'Phoundation/', PATH_DATA], true))->move(PATH_ROOT . 'data/garbage/Phoundation/', true);
        $scripts     = Path::new(PATH_ROOT . 'scripts/', Restrictions::new([PATH_ROOT . 'scripts/', PATH_DATA], true))->move(PATH_ROOT . 'data/garbage/scripts/', true);

        // Copy new versions
        $rsync
            ->setSource($phoundation->getPath() . 'Phoundation/')
            ->setTarget(PATH_ROOT . 'Phoundation/')
            ->execute();

        // Copy new versions
        $rsync
            ->setSource($local . 'scripts/')
            ->setTarget(PATH_ROOT . 'scripts/')
            ->execute();

        // All is well? Get rid of the garbage
        $phoundation->delete();
        $scripts->delete();
    }
}