<?php

declare(strict_types=1);

namespace Phoundation\Developer\Project;

use Phoundation\Accounts\Users\User;
use Phoundation\Core\Libraries\Library;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Developer\Phoundation\Phoundation;
use Phoundation\Developer\Project\Exception\EnvironmentExists;
use Phoundation\Developer\Project\Interfaces\DeployInterface;
use Phoundation\Developer\Project\Interfaces\ProjectInterface;
use Phoundation\Developer\Versioning\Git\Interfaces\GitInterface;
use Phoundation\Developer\Versioning\Git\Traits\Git;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Filesystem\Directory;
use Phoundation\Filesystem\File;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Filesystem\Traits\DataRestrictions;
use Phoundation\Os\Processes\Commands\Command;
use Phoundation\Os\Processes\Commands\Rsync;
use Phoundation\Os\Processes\Process;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Strings;
use Throwable;


/**
 * Project class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package \Phoundation\Developer
 */
class Project implements ProjectInterface
{
    use DataRestrictions;
    use Git {
        __construct as protected construct;
    }


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
     * Project constructor
     *
     * @param string|null $directory
     */
    public function __construct(?string $directory = null)
    {
        if (!$directory) {
            // Default to this project
            $directory = DIRECTORY_ROOT;
        }

        $this->construct($directory);
    }


    /**
     * Returns a new Phoundation object
     *
     * @param string|null $directory
     * @return static
     */
    public static function new(?string $directory = null): static
    {
        return new static($directory);
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

            File::new(DIRECTORY_ROOT . 'config/project')->delete();
        }

        static::$name = $project;
        static::save();
    }


    /**
     * Returns the git object for this project
     *
     * @return GitInterface
     */
    public function getGit(): GitInterface
    {
        return $this->git;
    }


    /**
     * Returns the deploy object for this project
     *
     * @param array|null $target_environments
     * @return DeployInterface
     */
    public function getDeploy(array|null $target_environments): DeployInterface
    {
        return new Deploy($this, $target_environments);
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
        $files  = glob(DIRECTORY_ROOT . 'config/*.yaml');

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
     * @param string $directory
     * @return bool
     */
    public function isPhoundationProject(string $directory): bool
    {
        // Is the path readable?
        $directory = Directory::new($directory, $this->restrictions)->checkReadable()->getPath();

        // All these files and directories must be available.
        $files = [
            'config',
            'data',
            'Phoundation',
            'Plugins',
            'scripts',
            'Templates',
            'tests',
            'www',
            'pho',
        ];

        foreach ($files as $file) {
            if (!file_exists($directory . $file)) {
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
        File::new(DIRECTORY_ROOT . 'config/project', Restrictions::new(DIRECTORY_ROOT . 'config/project', true))->delete();
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
            $user->getRoles()->addRole('god');

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
        return file_exists(DIRECTORY_ROOT . 'config/project');
    }


    /**
     * Validate the specified project information
     *
     * @param ValidatorInterface $validator
     * @return array
     */
    public static function validate(ValidatorInterface $validator): array
    {
        return $validator
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
        $project = file_get_contents(DIRECTORY_ROOT . 'config/project');
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
    public static function import(bool $demo, int $min, int $max, array|string|null $libraries = null): void
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
                ->addArgument(DIRECTORY_ROOT . $directory)
                ->addArgument('-name')
                ->addArgument('Import.php')
                ->executeReturnArray();

            Log::notice(tr('Found ":count" import classes for section ":section"', [
                ':count'   => count($files),
                ':section' => $section
            ]), 5);

            // Execute all Import objects if they are valid
            foreach ($files as $file) {
                $library = null;

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
                            ':file'    => Strings::from($file, DIRECTORY_ROOT . $directory)
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
     *
     * @todo Change hard coded www-data to configurable option
     */
    public static function fixFileModes(): void
    {
        // Don't check for root user, check if we have sudo access to these commands individually, perhaps the user has
        // it?
        Command::sudoAvailable('chown,chmod,mkdir,touch,rm', Restrictions::new('/bin,/usr/bin'), true);

        // Fix file modes, first make everything readonly
        Process::new('chmod')
            ->setExecutionDirectory(DIRECTORY_ROOT)
            ->setSudo(true)
            ->addArguments(['-x,ug+r,g-w,o-rwx', '.', '-R'])
            ->executePassthru();

        // All directories must have the "execute" bit for users and groups
        Process::new('find')
            ->setExecutionDirectory(DIRECTORY_ROOT)
            ->setSudo(true)
            ->addArguments(['.' , '-type' , 'd', '-exec', 'chmod', 'ug+x', '{}', '\\;'])
            ->executePassthru();

        // No file should be executable
        Process::new('find')
            ->setExecutionDirectory(DIRECTORY_ROOT)
            ->setSudo(true)
            ->addArguments(['.' , '-type' , 'f', '-exec', 'chmod', 'ug-x', '{}', '\\;'])
            ->executePassthru();

        // ./cli is the only file that can be executed
        Process::new('chmod')
            ->setExecutionDirectory(DIRECTORY_ROOT)
            ->setSudo(true)
            ->addArguments(['ug+w', './pho'])
            ->executePassthru();

        // Writable directories: data/tmp, data/log, data/run, data/cookies, data/content,
        Process::new('chmod')
            ->setExecutionDirectory(DIRECTORY_ROOT)
            ->setSudo(true)
            ->addArguments(['-x,ug+r,g-w,o-rwx', DIRECTORY_DATA . 'tmp', DIRECTORY_DATA . 'log', DIRECTORY_DATA . 'run', DIRECTORY_DATA . 'cookies', DIRECTORY_DATA . 'cookies', '-R'])
            ->executePassthru();

        // Fix file ownership
        Process::new('chown')
            ->setExecutionDirectory(DIRECTORY_ROOT)
            ->setSudo(true)
            ->addArguments(['www-data:www-data', '.', '-R'])
            ->executePassthru();
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
     * @param string|null $branch
     * @param string|null $message
     * @param bool $signed
     * @param string|null $phoundation_path
     * @return static
     */
    public function updateLocalProject(?string $branch, ?string $message = null, bool $signed = false, ?string $phoundation_path = null): static
    {
        if (!$branch) {
            $branch = $this->git->getBranch();

            Log::notice(tr('Trying to pull updates from Phoudation using current project branch ":branch"', [
                ':branch' => $branch
            ]));
        }

        Log::information('Updating your project from a local Phoundation repository');

        // Ensure that the local Phoundation has no changes
        Phoundation::new()->ensureNoChanges();

        try {
            // Add all files to index to ensure everything will be stashed
            if ($this->git->getStatus()->getCount()) {
                $this->git->add(DIRECTORY_ROOT);
                $this->git->getStash()->stash();
                $stash = true;
            }

            // Copy Phoundation core files
            $this->copyPhoundationFilesLocal($phoundation_path, $branch);

            // If there are changes, then add and commit
            if ($this->git->getStatus()->getCount()) {
                if (!$message) {
                    $message = tr('Phoundation update');
                }

                $this->git->add([DIRECTORY_ROOT . 'Phoundation/', DIRECTORY_ROOT . 'scripts/']);
                $this->git->commit($message, $signed);

                Log::warning(tr('Committed local Phoundation update to git'));
            } else {
                Log::warning(tr('No updates found in local Phoundation update'));
            }

            // Stash pop the previous changes and reset HEAD to ensure the index is empty
            if (isset($stash)) {
                $this->git->getStash()->pop();
                $this->git->reset('HEAD');
            }

            return $this;

        } catch (Throwable $e) {
            if (isset($stash)) {
                Log::warning(tr('Moving stashed files back'));
                $this->git->getStash()->pop();
                $this->git->reset('HEAD');
            }

            throw $e;
        }
    }


    /**
     * Updates your Phoundation installation from Phoundation g
     */
    public static function update(): void
    {
        throw new UnderConstructionException();
    }


    /**
     * Saves the project name
     *
     * @return void
     */
    protected static function save(): void
    {
        file_put_contents(DIRECTORY_ROOT . 'config/project', static::$name);
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
     * Copy all files from the local phoundation installation.
     *
     * @note This method will actually delete Phoundation system files! Because of this, it will manually include some
     *       required library files to avoid crashes when these files are needed during this time of deletion
     * @param string|null $directory
     * @param string $branch
     * @return void
     */
    protected function copyPhoundationFilesLocal(?string $directory, string $branch): void
    {
        if (!$branch) {
            throw new OutOfBoundsException(tr('Cannot copy local Phoundation files, no Phoundation branch specified'));
        }

        $rsync       = Rsync::new();
        $phoundation = Phoundation::new($directory)->switchBranch($branch);

        // ATTENTION! Next up, we're going to delete the Phoundation main libraries! To avoid any next commands not
        // finding files they require, include them here so that we have them available in memory
        include_once(DIRECTORY_ROOT . 'Phoundation/Os/Processes/Commands/Rsync.php');
        include_once(DIRECTORY_ROOT . 'Phoundation/Os/Processes/Enum/EnumExecuteMethod.php');

        // Move /Phoundation and /scripts out of the way
        try {
            Directory::new(DIRECTORY_ROOT . 'data/garbage/', Restrictions::new(DIRECTORY_ROOT . 'data/', true, tr('Project management')))->delete();

            $files['scripts']     = Directory::new(DIRECTORY_ROOT . 'scripts/'    , Restrictions::new([DIRECTORY_ROOT . 'scripts/'    , DIRECTORY_DATA], true, tr('Project management')))->move(DIRECTORY_ROOT . 'data/garbage/');
            $files['phoundation'] = Directory::new(DIRECTORY_ROOT . 'Phoundation/', Restrictions::new([DIRECTORY_ROOT . 'Phoundation/', DIRECTORY_DATA], true, tr('Project management')))->move(DIRECTORY_ROOT . 'data/garbage/');
            $files['templates']   = Directory::new(DIRECTORY_ROOT . 'Templates/'  , Restrictions::new([DIRECTORY_ROOT . 'Templates/'  , DIRECTORY_DATA], true, tr('Project management')))->move(DIRECTORY_ROOT . 'data/garbage/');

            // Copy new script versions
            $rsync
                ->setSource($phoundation->getDirectory() . 'scripts/')
                ->setTarget(DIRECTORY_ROOT . 'scripts/')
                ->execute();

            // Copy new core library versions
            $rsync
                ->setSource($phoundation->getDirectory() . 'Phoundation/')
                ->setTarget(DIRECTORY_ROOT . 'Phoundation/')
                ->execute();

            // Copy new core template versions
            $rsync
                ->setSource($phoundation->getDirectory() . 'Templates/')
                ->setTarget(DIRECTORY_ROOT . 'Templates/')
                ->execute();

            // All is well? Get rid of the garbage
            $files['phoundation']->delete();
            $files['templates']->delete();
            $files['scripts']->delete();

            // Switch phoundation back to its previous branch
            $phoundation->switchBranch();

        } catch (Throwable $e) {
            //  Move Phoundation files back again
            if (isset($files['phoundation'])) {
                Log::warning(tr('Moving Phoundation core libraries back from garbage'));
                $files['phoundation']->move(DIRECTORY_ROOT . 'Phoundation/');
            }

            if (isset($files['scripts'])) {
                Log::warning(tr('Moving Phoundation core scripts back from garbage'));
                $files['scripts']->move(DIRECTORY_ROOT . 'scripts/');
            }

            if (isset($files['templates'])) {
                Log::warning(tr('Moving Template core scripts back from garbage'));
                $files['templates']->move(DIRECTORY_ROOT . 'Templates/');
            }

            throw $e;
        }
    }
}
