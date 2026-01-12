<?php

/**
 * Project class
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   \Phoundation\Developer
 */


declare(strict_types=1);

namespace Phoundation\Developer\Project;

use Phoundation\Accounts\Users\User;
use Phoundation\Core\Core;
use Phoundation\Core\Exception\ProjectException;
use Phoundation\Core\Libraries\Libraries;
use Phoundation\Core\Libraries\Library;
use Phoundation\Core\Libraries\Version;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Filesystem\Traits\TraitDataRestrictions;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Developer\Enums\EnumRepositoryType;
use Phoundation\Developer\Phoundation\Exception\PatchPartiallySuccessfulException;
use Phoundation\Developer\Phoundation\Exception\PhoundationBranchNotExistException;
use Phoundation\Developer\Phoundation\Phoundation;
use Phoundation\Developer\Phoundation\Plugins;
use Phoundation\Developer\Project\Interfaces\DeployInterface;
use Phoundation\Developer\Project\Interfaces\ProjectInterface;
use Phoundation\Developer\Project\Vendors\Interfaces\ProjectVendorsInterface;
use Phoundation\Developer\Project\Vendors\ProjectVendors;
use Phoundation\Developer\Versioning\Git\Exception\GitPatchFailedException;
use Phoundation\Developer\Versioning\Git\Git;
use Phoundation\Developer\Versioning\Git\Interfaces\GitInterface;
use Phoundation\Developer\Versioning\Git\StatusFiles;
use Phoundation\Developer\Versioning\Git\Traits\TraitGit;
use Phoundation\Exception\EnvironmentExistsException;
use Phoundation\Exception\NoLongerSupportedException;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Filesystem\PhoDirectory;
use Phoundation\Filesystem\PhoFile;
use Phoundation\Filesystem\PhoPath;
use Phoundation\Filesystem\PhoRestrictions;
use Phoundation\Filesystem\Interfaces\PhoDirectoryInterface;
use Phoundation\Os\Processes\Commands\Command;
use Phoundation\Os\Processes\Commands\Find;
use Phoundation\Os\Processes\Commands\Rsync;
use Phoundation\Os\Processes\Enum\EnumExecuteMethod;
use Phoundation\Os\Processes\Exception\ProcessFailedException;
use Phoundation\Os\Processes\Process;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Components\Anchor;
use Phoundation\Web\Html\Enums\EnumAnchorTarget;
use Phoundation\Web\Http\Domains;
use Throwable;


class Project implements ProjectInterface
{
    use TraitDataRestrictions;
    use TraitGit {
        __construct as protected ___construct;
    }


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
     * Project constructor
     *
     * @param PhoDirectoryInterface|null $o_directory
     */
    public function __construct(PhoDirectoryInterface|null $o_directory = null)
    {
        if (!$o_directory) {
            // Default to the directory of this project
            $o_directory = new PhoDirectory(DIRECTORY_ROOT, PhoRestrictions::newWritableObject(DIRECTORY_ROOT));
        }

        $this->___construct($o_directory);
    }


    /**
     * Returns a new project with the specified name
     *
     * @param string $project
     * @param bool   $force
     *
     * @return void
     */
    public static function create(string $project, bool $force = false): void
    {
        if (static::projectFileExists()) {
            if (!$force) {
                throw new OutOfBoundsException(tr('Project file "config/project/name" already exist'));
            }

            PhoFile::new(DIRECTORY_ROOT . 'config/project/name', PhoRestrictions::newWritableObject(DIRECTORY_ROOT))
                  ->delete();
        }

        static::$name = $project;
        static::saveName();
    }


    /**
     * Returns if the project file exists
     *
     * @return bool
     */
    public static function projectFileExists(): bool
    {
        return file_exists(DIRECTORY_ROOT . 'config/project/name');
    }


    /**
     * Returns if the version file exists
     *
     * @return bool
     */
    public static function versionFileExists(): bool
    {
        return file_exists(DIRECTORY_ROOT . 'config/file');
    }


    /**
     * Returns if the production configuration file exists
     *
     * @return bool
     */
    public static function productionConfigurationFileExists(): bool
    {
        return file_exists(DIRECTORY_ROOT . 'config/production.yaml');
    }


    /**
     * Returns a new Phoundation object
     *
     * @param string|null $o_directory
     *
     * @return static
     */
    public static function new(?string $o_directory = null): static
    {
        return new static($o_directory);
    }


    /**
     * Saves the project name
     *
     * @return void
     */
    protected static function saveName(): void
    {
        file_put_contents(DIRECTORY_ROOT . 'config/project/name', static::$name);
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

            Log::information(ts('Initializing project ":project", this can take a little while...', [
                ':project' => static::$name,
            ]));

            static::getEnvironment()->setup();

            // Create admin user
            Log::action(ts('Creating administrative user ":email", almost done...', [
                ':email' => $configuration->getEmail(),
            ]));

            $user = User::new()->setEmail($configuration->getEmail())->save();
            $user->changePassword($configuration->getPassword(), $configuration->getPassword());
            $user->getRolesObject()->add('god');

            Log::success(ts('Finished project setup'));

        } catch (Throwable $e) {
            Log::warning('Setup failed with the following exception. Cancelling setup process and removing files.');
            Log::warning($e);

            // Remove the project and continue throwing the exception
            Project::remove();
            Log::warning(ts('Setup process was cancelled'));
            throw $e;
        }
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
     * Returns the configuration for this project
     *
     * @param string $environment
     *
     * @return Environment
     */
    public static function setEnvironment(string $environment): Environment
    {
        $environment = Environment::sanitize($environment);
        if (Environment::exists($environment)) {
            if (!FORCE) {
                throw EnvironmentExistsException::new(tr('Specified environment ":environment" has already been setup', [
                    ':environment' => $environment,
                ]))->makeWarning();
            }

            static::removeEnvironment($environment);
        }

        static::$environment = Environment::new(static::$name, $environment);

        return static::$environment;
    }


    /**
     * Remove this project
     *
     * This will remove all databases and configuration files for this project
     *
     * @return void
     */
    public static function remove(): void
    {
        Log::action(ts('Removing project'));

        foreach (static::getEnvironments() as $environment) {
            // Delete this environment
            static::removeEnvironment($environment);
        }

        // Remove the project file
        Log::warning(ts('Removing project file "config/project/name"'));
        PhoFile::new(DIRECTORY_ROOT . 'config/project/name', PhoRestrictions::new(DIRECTORY_ROOT . 'config/project/name', true))
            ->delete();
    }


    /**
     * Returns all available environments
     *
     * @return IteratorInterface
     */
    public static function getEnvironments(): IteratorInterface
    {
        $return = [];
        $files  = glob(DIRECTORY_ROOT . 'config/environments/*');

        foreach ($files as $file) {
            if ($file[0] === '.') {
                // No hidden files no "." and no ".."
                continue;
            }

            $return[] = Strings::untilReverse(basename($file), '.');
        }

        return new Iterator($return);
    }


    /**
     * Remove the specified environment
     *
     * @param string $environment
     *
     * @return bool
     */
    protected static function removeEnvironment(string $environment): bool
    {
        if (!Environment::exists($environment)) {
            return false;
        }

        Log::warning(ts('Removing environment ":environment"', [
            ':environment' => $environment,
        ]));

        // If we're removing the environment that is currently used then remove it from memory too
        if (static::$environment->getName() === $environment) {
            static::$environment = null;
        }

        // Get the environment and remove all environment specific data
        return Environment::get(static::$name, $environment)->remove();
    }


    /**
     * Returns if the specified project name is valid or not
     *
     * @param string $project
     *
     * @return string
     */
    protected static function sanitize(string $project): string
    {
        if (!$project) {
            throw OutOfBoundsException::new(tr('No project name specified in the project file ":file"', [
                ':file' => 'config/project/name'
            ]))->makeWarning();
        }

        if (strlen($project) > 32) {
            throw OutOfBoundsException::new(tr('Specified project name is ":size" characters long, please specify a project name equal or less than 32 characters', [
                ':size' => strlen($project),
            ]))->makeWarning();
        }

        $project = strtoupper($project);

        if (!preg_match('/[A-Z0-9_]+/', $project)) {
            throw OutOfBoundsException::new(tr('Specified project ":project" contains invalid characters, please ensure it has only A-Z, 0-9 or _', [
                ':project' => $project,
            ]))->makeWarning();
        }

        return $project;
    }


    /**
     * Validate the specified project information
     *
     * @param ValidatorInterface $o_validator
     *
     * @return array
     */
    public static function validate(ValidatorInterface $o_validator): array
    {
        return $o_validator->select('admin_email')->isEmail()
                         ->select('admin_pass1')->isPassword()
                         ->select('admin_pass2')->isPassword()
                         ->isEqualTo('admin_pass1')->select('domain')->isDomain()
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
    public function load(): ?string
    {
        $project      = file_get_contents(DIRECTORY_ROOT . 'config/project/name');
        $project      = static::sanitize($project);
        static::$name = $project;

        return $project;
    }


    /**
     * Executes data import for all libraries that support it
     *
     * @param bool              $demo
     * @param int               $min
     * @param int               $max
     * @param array|string|null $libraries
     *
     * @todo Find a better solution for this
     *
     * @return void
     */
    public static function import(bool $demo, int $min, int $max, array|string|null $libraries = null): void
    {
        Log::information(ts('Starting import for all libraries that support it'));
return;
throw new NoLongerSupportedException('Project::import() is no longer supported as it was mostly a bad idea. Find a better solution');
        $libraries = Arrays::force(strtolower(Strings::force($libraries)));
        $sections  = [
            'Phoundation/' => tr('Phoundation'),
            'Plugins/'     => tr('Plugin'),
        ];

        foreach ($sections as $directory => $section) {
            // Find all import commands and execute them
            $files = Process::new('find')
                            ->addArgument(DIRECTORY_ROOT . $directory)
                            ->addArgument('-name')
                            ->addArgument('Import.php')
                            ->executeReturnArray();

            Log::notice(ts('Found ":count" import classes for section ":section"', [
                ':count'   => count($files),
                ':section' => $section,
            ]), 5);

            // Execute all Import objects if they are valid
            foreach ($files as $file) {
                $library = null;

                try {
                    include_once($file);

                    $class   = Library::getClassPath($file);
                    $library = Strings::until(Strings::from($file, $directory), '/');

                    if ($libraries and !in_array(strtolower($library), $libraries)) {
                        Log::warning(ts('Not executing import for library ":library" as it is filtered', [
                            ':library' => $library,
                        ]));
                        continue;
                    }

                    if (is_subclass_of($class, Import::class)) {
                        Log::action(ts('Importing data for ":section" library ":library" from file ":file"', [
                            ':section' => $section,
                            ':library' => $library,
                            ':file'    => Strings::from($file, DIRECTORY_ROOT . $directory),
                        ]), 5);

                        $count = $class::new($demo, $min, $max)->execute();

                        Log::success(ts('Imported ":count" records for ":section" library ":library"', [
                            ':section' => $section,
                            ':library' => $library,
                            ':count'   => $count,
                        ]), 6);
                    }

                } catch (Throwable $e) {
                    Log::action(ts('Failed to import data for ":section" library ":library" with the following exception', [
                        ':section' => $section,
                        ':library' => $library,
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
        $directory = PhoDirectory::newRootObject(true, 'Project::fixFileModes');

        // Don't check for root user, check sudo access to these commands individually, perhaps the user has it?
        Command::checkSudoAvailable('chown,chmod,mkdir,touch,rm', PhoRestrictions::new('/bin,/usr/bin'), true);

        // Fix file modes, first make everything readonly
        Process::new('chmod')
               ->setExecutionDirectory($directory)
               ->setSudo(true)
               ->addArguments(['-x,ug+r,g-w,o-rwx', '.', '-R'])
               ->executePassthru();

        // TODO Use the Find command that has all these parameters implemented as clear methods
        // All directories must have the "execute" bit for users and groups
        Process::new('find')
               ->setExecutionDirectory($directory)
               ->setSudo(true)
               ->addArguments(['.', '-type', 'd', '-exec', 'chmod', 'ug+x', '{}','\\;',])
               ->executePassthru();

        // No file should be executable
        Process::new('find')
               ->setExecutionDirectory($directory)
               ->setSudo(true)
               ->addArguments(['.', '-type', 'f', '-exec', 'chmod', 'ug-x', '{}', '\\;', ])
               ->executePassthru();

        // ./cli is the only file that can be executed
        Process::new('chmod')
               ->setExecutionDirectory($directory)
               ->setSudo(true)
               ->addArguments(['ug+w', './pho', ])
               ->executePassthru();

        // Readable directories: data/,
        Process::new('chmod')
               ->setExecutionDirectory($directory)
               ->setSudo(true)
               ->addArguments([
                   '-x,ug+r,g-w,o-rwx',
                   DIRECTORY_DATA,
                   '-R',
               ])
               ->executePassthru();

        // Writable directories: data/system, data/log
        Process::new('chmod')
               ->setExecutionDirectory($directory)
               ->setSudo(true)
               ->addArguments([
                   '-ug+w',
                   DIRECTORY_SYSTEM,
                   DIRECTORY_DATA . 'log',
                   '-R',
               ])
               ->executePassthru();


        // Fix file ownership
        Process::new('chown')
               ->setExecutionDirectory($directory)
               ->setSudo(true)
               ->addArguments(['www-data:www-data', '.', '-R', ])
               ->executePassthru();
    }


    /**
     * Checks if there are updates available for Phoundation
     */
    public static function checkUpdates(): void
    {
        throw new UnderConstructionException();
    }


    /**
     * Updates your Phoundation installation from Phoundation
     */
    public static function update(): void
    {
        throw new UnderConstructionException();
    }


    /**
     * Returns the git object for this project
     *
     * @return GitInterface
     */
    public function getGitObject(): GitInterface
    {
        return $this->o_git;
    }


    /**
     * Returns the deploy object for this project
     *
     * @param array|null $target_environments
     *
     * @return DeployInterface
     */
    public function getDeploy(array|null $target_environments): DeployInterface
    {
        return new Deploy($this, $target_environments);
    }


    /**
     * Returns true if the specified filesystem location contains a valid Phoundation project installation
     *
     * @param string $directory
     *
     * @return bool
     */
    public function isPhoundationProject(string $directory): bool
    {
        // Is the path readable?
        $directory = PhoDirectory::new($directory, $this->o_restrictions)
                                 ->checkReadable()
                                 ->getSource();

        // All these files and directories must be available.
        $files = [
            'config',
            'data',
            'Phoundation',
            'Plugins',
            'Tests',
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
     * Resets the git pointer to HEAD for this project
     *
     * @return static
     */
    public function resetHead(): static
    {
        $this->o_git->reset('HEAD');

        return $this;
    }


    /**
     * Updates your Phoundation installation
     *
     * @param string|null $branch
     * @param string|null $message
     * @param bool        $signed
     * @param string|null $phoundation_path
     * @param bool        $skip_caching
     * @param bool        $commit
     *
     * @return static
     */
    public function updateLocalProject(?string $branch, ?string $message = null, bool $signed = false, ?string $phoundation_path = null, bool $skip_caching = false, bool $commit = true): static
    {
        $branch = $this->getBranch($branch);

        Log::notice(ts('Trying to pull updates from Phoundation using current project branch ":branch"', [
            ':branch' => $branch,
        ]));

        Log::information('Updating your project from a local Phoundation repository');

        // Ensure that the local Phoundation has no changes
        Phoundation::new($phoundation_path)
                   ->ensureNoChanges();
        try {
            // Add all files to index to ensure everything will be stashed
            if ($this->o_git->getStatusFilesObject()->getCount()) {
                $this->o_git->add(DIRECTORY_ROOT);
                $this->o_git->getStashObject()->stash();

                $stash = true;
            }

            // Cache ALL Phoundation files to avoid code incompatibility after update, then copy Phoundation core files
            $this->cacheLibraries($skip_caching)->copyPhoundationFilesLocal($phoundation_path, $branch);

            // If there are changes, then add and commit
            if ($this->o_git->getStatusFilesObject()->getCount()) {
                if (!$message) {
                    $message = tr('Phoundation update');
                }

                $this->o_git->add([DIRECTORY_ROOT]);

                if ($commit) {
                    $this->o_git->commit($message, $signed);
                }

                Log::warning(ts('Committed local Phoundation update to git'));

            } else {
                Log::warning(ts('No updates found in local Phoundation update'));
            }

            // Stash pop the previous changes and reset HEAD to ensure the index is empty
            if (isset($stash)) {
                $this->o_git->getStashObject()->pop();
                $this->o_git->reset('HEAD');
            }

            return $this;

        } catch (Throwable $e) {
            if (isset($stash)) {
                Log::warning(ts('Moving stashed files back'));
                $this->o_git->getStashObject()
                          ->pop();
                $this->o_git->reset('HEAD');
            }

            throw $e;
        }
    }


    /**
     * Returns either the specified branch or the current project branch as default
     *
     * @param string $branch
     *
     * @return bool
     */
    public function hasBranch(string $branch): bool
    {
        if (!$branch) {
            throw new OutOfBoundsException(tr('No branch specified'));
        }

        // Select the current branch
        return $this->o_git->branchExists($branch);
    }


    /**
     * Returns either the specified branch or the current project branch as default
     *
     * @param string|null $default
     *
     * @return string
     */
    public function getBranch(?string $default = null): string
    {
        if (!$default) {
            // Select the current branch
            $default = $this->o_git->getCurrentBranch();

            Log::notice(ts('Using project branch ":branch"', [
                ':branch' => $default,
            ]));
        }

        return $default;
    }


    /**
     * Sets the current project git branch to the specified branch
     *
     * @param string $branch
     *
     * @return static
     */
    public function setBranch(string $branch): static
    {
        if (!$branch) {
            throw new OutOfBoundsException(tr('No branch specified'));
        }

        // Select the current branch
        $this->o_git->selectBranch($branch);

        Log::notice(ts('Set project branch to ":branch"', [
            ':branch' => $branch,
        ]));

        return $this;
    }


    /**
     * Returns a list with Phoundation core files that (according to git) were modified
     *
     * @return IteratorInterface
     */
    public function getCoreChanges(): IteratorInterface
    {
        return $this->o_git->getStatusFilesObject($this->o_directory->addDirectory('Phoundation'));
    }


    /**
     * Returns true if the project has changes in the Phoundation core files
     *
     * @return bool
     */
    public function hasCoreChanges(): bool
    {
        return $this->getCoreChanges()->isNotEmpty();
    }


    /**
     * Returns a list with Phoundation plugins files that (according to git) were modified
     *
     * @return IteratorInterface
     */
    public function getPluginsChanges(): IteratorInterface
    {
        // Ensure that all the plugin directories exist
        foreach (['Plugins', 'Templates', 'data/vendors'] as $directory) {
            $this->o_directory->addDirectory($directory)->ensure();
        }

        // Get and return all changes
        return $this->o_git
                    ->getStatusFilesObject($this->o_directory->addDirectory('Plugins'))
                        ->addSource($this->o_git->getStatusFilesObject(
                            $this->o_directory->addDirectory('Templates')
                        ))
                        ->addSource($this->o_git->getStatusFilesObject(
                            $this->o_directory->addDirectory('data/vendors')
                        ));
    }


    /**
     * Returns a list of vendors that have changes, with each vendor key containing an StatusFiles object as value
     * containing the files that have changes
     *
     * @param bool $changed If true will only return vendors that have changed files
     *
     * @return ProjectVendorsInterface
     */
    public function getVendors(bool $changed = false): ProjectVendorsInterface
    {
        return ProjectVendors::new($this, EnumRepositoryType::data, $changed)
                             ->addSource(ProjectVendors::new($this, EnumRepositoryType::plugins, $changed))
                             ->addSource(ProjectVendors::new($this, EnumRepositoryType::templates, $changed));
    }


    /**
     * Returns true if the project has changes in the Phoundation plugins files
     *
     * @return bool
     */
    public function hasPluginsChanges(): bool
    {
        return $this->getPluginsChanges()->isNotEmpty();
    }


    /**
     * Returns a list with Phoundation templates files that (according to git) were modified
     *
     * @return IteratorInterface
     */
    public function getTemplatesChanges(): IteratorInterface
    {
        return $this->o_git->getStatusFilesObject($this->o_directory->addDirectory('data/templates'));
    }


    /**
     * Returns a list of vendors that have changes, with each vendor key containing an StatusFiles object as value
     * containing the files that have changes
     *
     * @return ProjectVendorsInterface
     */
    public function getChangedTemplatesVendors(): ProjectVendorsInterface
    {
        return ProjectVendors::new($this, EnumRepositoryType::templates, true);
    }


    /**
     * Returns true if the project has changes in the Phoundation templates files
     *
     * @return bool
     */
    public function hasTemplatesChanges(): bool
    {
        return $this->getTemplatesChanges()->isNotEmpty();
    }


    /**
     * Returns a list with Phoundation templates files that (according to git) were modified
     *
     * @return IteratorInterface
     */
    public function getDataChanges(): IteratorInterface
    {
        return $this->o_git->getStatusFilesObject($this->o_directory->addDirectory('data/vendors'));
    }


    /**
     * Returns a list of vendors that have changes, with each vendor key containing an StatusFiles object as value
     * containing the files that have changes
     *
     * @return ProjectVendorsInterface
     */
    public function getChangedDataVendors(): ProjectVendorsInterface
    {
        return ProjectVendors::new($this, EnumRepositoryType::data, true);
    }


    /**
     * Returns true if the project has changes in the Phoundation data vendor files
     *
     * @return bool
     */
    public function hasDataChanges(): bool
    {
        return $this->getDataChanges()->isNotEmpty();
    }


    /**
     * Copy all files from the local phoundation installation.
     *
     * @note This method will actually delete Phoundation system files! Because of this, it will manually include some
     *       required library files to avoid crashes when these files are needed during this time of deletion
     *
     * @param string|null $directory
     * @param string      $branch
     *
     * @return void
     * @throws PhoundationBranchNotExistException|OutOfBoundsException|Throwable
     */
    protected function copyPhoundationFilesLocal(?string $directory, string $branch): void
    {
        if (!$branch) {
            throw new OutOfBoundsException(tr('Cannot copy local Phoundation files, no Phoundation branch specified'));
        }

        try {
            $phoundation = Phoundation::new($directory)->switchBranch($branch);

        } catch (ProcessFailedException $e) {
            // TODO Check if it actually does not exist or if there is another problem!
            throw new PhoundationBranchNotExistException(tr('Cannot switch to Phoundation branch ":branch", it does not exist', [
                ':branch' => $branch,
            ]), $e);
        }

        // ATTENTION! Next up, we're going to delete the Phoundation main libraries! To avoid any next commands not
        // finding files they require, include them here so that we have them available in memory
        include_once(DIRECTORY_ROOT . 'Phoundation/Os/Processes/Commands/Rsync.php');
        include_once(DIRECTORY_ROOT . 'Phoundation/Os/Processes/Enum/EnumExecuteMethod.php');

        // Move /Phoundation and /scripts out of the way
        PhoDirectory::new(DIRECTORY_ROOT . 'data/garbage/', PhoRestrictions::new(DIRECTORY_ROOT . 'data/', true, tr('Project management')))
                 ->delete();
        // Copy new core library versions
        Log::action('Updating Phoundation core libraries');

        Rsync::new()
             ->setSource($phoundation->getDirectoryObject() . 'Phoundation/')
             ->setTarget(DIRECTORY_ROOT . 'Phoundation')
             ->setExclude([
                 '.idea',
                 '.git',
                 '.gitignore',
                 '/Templates',
                 '/Plugins',
             ])
             ->setDelete(true)
             ->execute();
        // Copy Phoundation plugin
        Log::action('Updating Phoundation Plugin');
        Rsync::new()
             ->setSource($phoundation->getDirectoryObject() . 'Plugins/Phoundation/Phoundation/')
             ->setTarget(DIRECTORY_ROOT . 'Plugins/Phoundation/Phoundation')
             ->setExclude([
                 '.idea',
                 '.git',
                 '.gitignore',
             ])
             ->setDelete(true)
             ->execute();
        // Copy Phoundation PHO command
        Log::action('Updating "pho" command');
        Rsync::new()
             ->setSource($phoundation->getDirectoryObject() . 'pho')
             ->setTarget(DIRECTORY_ROOT . 'pho')
             ->setExclude([
                 '.idea',
                 '.git',
                 '.gitignore',
             ])
             ->setDelete(true)
             ->execute();
//            // All is well? Get rid of the garbage
//            $files['phoundation']->delete();
//            $files['templates']->delete();
        // Switch phoundation back to its previous branch
        $phoundation->switchBranch();
    }


    /**
     * Pre-reads ALL Phoundation library files into memory
     *
     * This is done to avoid certain files having newer and incompatible versions that might be included and used AFTER
     * the update, causing crashes because of the update.
     *
     * @param bool $skip
     *
     * @return static
     */
    protected function cacheLibraries(bool $skip): static
    {
// TODO Implement
        $skip = false;

        if ($skip) {
            Log::action(ts('Caching all Phoundation libraries'));
            Find::new()
                ->setPathObject(PhoPath::new(DIRECTORY_ROOT . 'Phoundation/'))
                ->setName('*.php')
                ->setCallback(function (string $file) {
                    Log::dot(25);
                    @include($file);
                })
                ->execute(EnumExecuteMethod::noReturn);
            Log::dot(true);

        } else {
            Log::warning(ts('Not caching Phoundation libraries'));
        }

        return $this;
    }


    /**
     * Updates your Phoundation Plugins
     *
     * @param string|null $branch
     * @param string|null $message
     * @param bool        $signed
     * @param string|null $phoundation_path
     * @param bool        $skip_caching
     * @param bool        $commit
     *
     * @return static
     */
    public function updateLocalProjectPlugins(?string $branch, ?string $message = null, bool $signed = false, ?string $phoundation_path = null, bool $skip_caching = false, bool $commit = true): static
    {
        if (!$branch) {
            $branch = $this->o_git->getCurrentBranch();
            Log::notice(ts('Trying to pull plugin updates from Phoundation using current project branch ":branch"', [
                ':branch' => $branch,
            ]));
        }
        Log::information('Updating your project plugins from a local Phoundation repository');

        // Ensure that the local Phoundation has no changes
        Plugins::new()
               ->ensureNoChanges();

        try {
            // Add all files to index to ensure everything will be stashed
            if ($this->o_git->getStatusFilesObject()->getCount()) {
                $this->o_git->add(DIRECTORY_ROOT);
                $this->o_git->getStashObject()
                          ->stash();
                $stash = true;
            }

            // Cache ALL Phoundation files to avoid code incompatibility after update, then copy Phoundation core files
            $this->cacheLibraries($skip_caching)
                 ->copyPluginsFilesLocal($phoundation_path, $branch);

            // If there are changes, then add and commit
            if (
                $this->o_git->getStatusFilesObject()
                          ->getCount()
            ) {
                if (!$message) {
                    $message = tr('Phoundation plugins update');
                }

                $this->o_git->add([DIRECTORY_ROOT]);

                if ($commit) {
                    $this->o_git->commit($message, $signed);
                }

                Log::warning(ts('Committed local Phoundation update to git'));

            } else {
                Log::warning(ts('No updates found in local Phoundation plugins update'));
            }

            // Stash pop the previous changes and reset HEAD to ensure the index is empty
            if (isset($stash)) {
                $this->o_git->getStashObject()
                          ->pop();
                $this->o_git->reset('HEAD');
            }

            return $this;

        } catch (Throwable $e) {
            if (isset($stash)) {
                Log::warning(ts('Moving stashed files back'));
                $this->o_git->getStashObject()
                          ->pop();
                $this->o_git->reset('HEAD');
            }

            throw $e;
        }
    }


    /**
     * Copy all files from the local phoundation installation.
     *
     * @note This method will actually delete Phoundation system files! Because of this, it will manually include some
     *       required library files to avoid crashes when these files are needed during this time of deletion
     *
     * @param string|null $directory
     * @param string      $branch
     *
     * @return void
     * @throws PhoundationBranchNotExistException|OutOfBoundsException|Throwable
     */
    protected function copyPluginsFilesLocal(?string $directory, string $branch): void
    {
        if (!$branch) {
            throw new OutOfBoundsException(tr('Cannot copy local plugin files, no Phoundation branch specified'));
        }

        try {
            $plugins = Plugins::new($directory)->switchBranch($branch);

        } catch (ProcessFailedException $e) {
            // TODO Check if it actually does not exist or if there is another problem!
            throw new PhoundationBranchNotExistException(tr('Cannot switch to Phoundation plugins branch ":branch", it does not exist', [
                ':branch' => $branch,
            ]), $e);
        }

        // ATTENTION! Next up, we're going to delete the Phoundation main libraries! To avoid any next commands not
        // finding files they require, include them here so that we have them available in memory
        include_once(DIRECTORY_ROOT . 'Phoundation/Os/Processes/Commands/Rsync.php');
        include_once(DIRECTORY_ROOT . 'Phoundation/Os/Processes/Enum/EnumExecuteMethod.php');

        // Copy new plugin libraries
        Log::action('Updating Phoundation plugins');

        Rsync::new()
             ->setSource($plugins->getDirectoryObject())
             ->setTarget(DIRECTORY_ROOT)
             ->setExclude([
                 '.idea',
                 '.git',
                 '.gitignore',
                 '/Phoundation',
                 '/Plugins/Phoundation/Phoundation',
             ])
             ->execute();

        // Switch phoundation back to its previous branch
        $plugins->switchBranch();
    }


    /**
     * Will patch the specified sections
     *
     * @param array             $sections
     * @param IteratorInterface $stash
     * @param bool              $checkout
     *
     * @return void
     */
    protected function patchSections(array $sections, IteratorInterface $stash, bool $checkout): void
    {
        $failed = [];

        foreach ($sections as $section) {
            $failed = array_merge($failed, static::patchSection($section, $stash));
        }

        if ($checkout) {
            // Checkout files locally in the specified sections so that these changes are removed from the project
            // Clean files locally in the specified sections so that new files are removed from the project
            Git::new(DIRECTORY_ROOT)
               ->checkout($sections)
               ->clean($sections, true, true);
        }

        if ($stash->getCount()) {
            $bad_files = clone $stash;
            // Whoopsie, we have shirts in the stash, meaning some file was naughty.
            Log::warning(ts('Returning problematic files ":files" from stash', [':files' => $failed]));

            Git::new(DIRECTORY_ROOT)
               ->getStashObject()
               ->pop();

            throw PatchPartiallySuccessfulException::new(tr('Phoundation plugins patch was partially successful, some files failed'))
                                                   ->addData([
                                                       'files' => $bad_files,
                                                   ]);
        }
    }


    /**
     * Patches the specified section and returns the files that were stashed
     *
     * @param string            $section
     * @param IteratorInterface $stash
     *
     * @return array|null
     */
    protected function patchSection(string $section, IteratorInterface $stash): ?array
    {
        $files = [];

        // Patch phoundation target section and remove the changes locally
        while (true) {
            try {
                StatusFiles::new()
                           ->setParentDirectoryObject(PhoDirectory::newRootObject(false, $section))
                           ->patch($this->getDirectoryObject($section));

                // All okay!
                return $files;

            } catch (GitPatchFailedException $e) {
                // Fork me, the patch failed on one or multiple files. Stash those files and try again to patch
                // the rest of the files that do apply
                $files = $e->getDataKey('files');
                $git   = Git::new(PhoDirectory::newRootObject());

                if ($files) {
                    Log::warning(ts('Trying to fix by stashing ":count" problematic file(s) ":files"', [
                        ':count' => count($files),
                        ':files' => $files,
                    ]));

                    // Add all files to index before stashing, except deleted files.
                    foreach ($files as $file) {
                        $stash->add($file);

                        // Deleted files cannot be stashed after being added, un-add, and then stash
                        if (PhoFile::new($file)->exists()) {
                            $git->add($file);

                        } else {
                            // Ensure it's not added yet
                            $git->reset('HEAD', $file);
                        }
                    }

                    // Stash all problematic files (auto un-stash later)
                    $git->getStashObject()->stash($files);
                }
            }
        }
    }


    /**
     * Returns the version for this project
     *
     * @return string
     */
    public static function getVersion(): string
    {
        static $return;

        if (empty($return)) {
            // Get the project version
            try {
                $return = strtolower(trim(file_get_contents(DIRECTORY_ROOT . 'config/project/version')));

                if (!strlen($return)) {
                    throw new OutOfBoundsException(tr('No version defined in DIRECTORY_ROOT/project/version file'));
                }

                if (!is_version($return)) {
                    throw new OutOfBoundsException(tr('Invalid version ":version" defined in DIRECTORY_ROOT/config/project/version file', [
                        ':version' => $return,
                    ]));
                }

                return $return;

            } catch (Throwable $e) {
                if ($e instanceof OutOfBoundsException) {
                    throw $e;
                }

                // Project file is not readable
                if (!is_readable(DIRECTORY_ROOT . 'config/project/version')) {
                    if (file_exists(DIRECTORY_ROOT . 'config/project/version')) {
                        // Okay, we have a problem here! The project file DOES exist but is not readable. This is either
                        // (likely) a security file owner / group / mode issue, or a filesystem problem. Either way, we
                        // won't be able to work our way around this.
                        throw new ProjectException(tr('Project version file "config/project/version" does exist but is not readable. Please check the owner, group and mode for this file'));
                    }

                    // The file doesn't exist, that is good. Go to setup mode
                    Log::toAlternateLog('Project version file "config/project/version" does not exist, entering setup mode');

                    throw new ProjectException(tr('Project version file ":path" cannot be read. Please ensure it exists', [
                        ':path' => DIRECTORY_ROOT . 'config/project/version',
                    ]));
                }
            }
        }

        return $return;
    }


    /**
     * Returns the Phoundation version required for this project
     *
     * @return string
     */
    public static function getPhoundationRequiredVersion(): string
    {
        static $return;

        if (empty($return)) {
            // Get the Phoundation project version
            try {
                $return = strtolower(trim(file_get_contents(DIRECTORY_ROOT . 'config/project/phoundation')));

                if (!strlen($return)) {
                    throw new OutOfBoundsException(tr('No version defined in DIRECTORY_ROOT/project/phoundation file'));
                }

                if (!is_version($return)) {
                    throw new OutOfBoundsException(tr('Invalid version ":version" defined in DIRECTORY_ROOT/config/project/phoundation file', [
                        ':version' => $return,
                    ]));
                }

                return $return;

            } catch (Throwable $e) {
                if ($e instanceof OutOfBoundsException) {
                    throw $e;
                }

                // Project file is not readable
                if (!is_readable(DIRECTORY_ROOT . 'config/project/phoundation')) {
                    if (file_exists(DIRECTORY_ROOT . 'config/project/phoundation')) {
                        // Okay, we have a problem here! The project file DOES exist but is not readable. This is either
                        // (likely) a security file owner / group / mode issue, or a filesystem problem. Either way, we
                        // won't be able to work our way around this.
                        throw new ProjectException(tr('Project version file "config/project/phoundation" does exist but is not readable. Please check the owner, group and mode for this file'));
                    }

                    // The file doesn't exist, that is good. Go to setup mode
                    Log::toAlternateLog('Project Phoundation version file "config/project/phoundation" does not exist, entering setup mode');

                    throw new ProjectException(tr('Project Phoundation version file ":path" cannot be read. Please ensure it exists', [
                        ':path' => DIRECTORY_ROOT . 'config/project/phoundation',
                    ]));
                }
            }
        }

        return $return;
    }


    /**
     * Returns the current version of the Phoundation system
     *
     * @return string
     */
    public static function getPhoundationVersion(): string
    {
        return Core::PHOUNDATION_VERSION;
    }


    /**
     * Returns an associative array (or formatted string) with project version information
     *
     * @param bool $string If true, will return the versions list in a formatted string instead of an associative array
     *
     * @return array|string
     */
    public static function getVersions(bool $string = false): array|string
    {
        $return = [
            'project name'                  => Project::getFullName(),
            'project version'               => Project::getVersion(),
            'phoundation framework version' => Project::getPhoundationVersion(),
            'phoundation database version'  => Version::getString(Libraries::getMaximumVersion()),
            'phoundation required version'  => Project::getPhoundationRequiredVersion(),
            'PHP minimal version'           => Core::PHP_MINIMUM_VERSION,
        ];

        if ($string) {
            $return = Arrays::equalizeKeySizes($return);
            $return = Arrays::capitalizeKeys($return);
            $return = Arrays::implodeWithKeys($return, PHP_EOL, ': ');
        }

        return $return;
    }


    /**
     * Returns the name for this project
     *
     * @return string
     */
    public static function getFullName(): string
    {
        static $return;

        if (empty($return)) {
            $return = strtoupper(Project::getOwnerName() . '_' . Project::getName());
        }

        return $return;
    }


    /**
     * Returns the name for this project
     *
     * @return string
     */
    public static function getHumanReadableFullName(): string
    {
        static $return;

        if (empty($return)) {
            $return = Strings::capitalize(Project::getOwnerName()) . ' ' . Strings::capitalize(Project::getName());
        }

        return $return;
    }


    /**
     * Returns the name for this project
     *
     * @return string
     */
    public static function getSeoFullName(): string
    {
        static $return;

        if (empty($return)) {
            $return = str_replace('_', '-', strtolower(Project::getFullName()));
        }

        return $return;
    }


    /**
     * Returns the name for this project
     *
     * @return string
     */
    public static function getName(): string
    {
        static $return;

        if (empty($return)) {
            $return = config()->getString('project.name', PROJECT);
        }

        return $return;
    }


    /**
     * Returns the directory name for this project
     *
     * @return string
     */
    public static function getDirectoryName(): string
    {
        return Strings::fromReverse(substr(DIRECTORY_ROOT, 0, -1), '/');
    }


    /**
     * Returns the name for this project
     *
     * @return string
     */
    public static function getSeoName(): string
    {
        static $return;

        if (empty($return)) {
            $return = str_replace('_', '-', strtolower(Project::getName()));
        }

        return $return;
    }


    /**
     * Returns the general email for this project
     *
     * @return string
     */
    public static function getEmail(): string
    {
        static $return;

        if (empty($return)) {
            $return = config()->getString('project.email', 'webmaster@' . Domains::getPrimaryWeb());
        }

        return $return;
    }


    /**
     * Returns the URL for the owner of this project
     *
     * @return string
     */
    public static function getOwnerUrl(): string
    {
        static $return;

        if (empty($return)) {
            $return = config()->getString('project.owner.url', 'https://phoundation.org');
        }

        return $return;
    }


    /**
     * Returns the name for the owner of this project
     *
     * @return string
     */
    public static function getOwnerName(): string
    {
        static $return;

        if (empty($return)) {
            $return = config()->getString('project.owner.name', 'Phoundation');
        }

        return $return;
    }


    /**
     * Returns the name for the owner of this project
     *
     * @return string
     */
    public static function getSeoOwnerName(): string
    {
        static $return;

        if (empty($return)) {
            $return = str_replace('_', '-', strtolower(Project::getOwnerName()));
        }

        return $return;
    }


    /**
     * Returns the name for the owner of this project
     *
     * @return string
     */
    public static function getOwnerLabel(): string
    {
        static $return;

        if (empty($return)) {
            $return = config()->getString('project.owner.name.label', '<span>Phoun</span>dation');
        }

        return $return;
    }


    /**
     * Returns the copyright year for this project
     *
     * @return int
     */
    public static function getCopyrightYear(): int
    {
        static $return;

        if (empty($return)) {
            $return = config()->getPositiveInteger('project.copyright.year', 2025);
        }

        return $return;
    }


    /**
     * Returns a basic copyright message
     *
     * @param bool      $multi_line
     * @param bool|null $html
     *
     * @return string
     */
    public static function getCopyrightString(bool $multi_line = false, ?bool $html = null): string
    {
        if ($html === null) {
            $html = PLATFORM_WEB;
        }

        if ($html) {
            return tr('Copyright © :year <b>:url</b> ' . ($multi_line ? '<br/>' : null) . '<small>:reserved</small>', [
                ':reserved' => tr('All rights reserved'),
                ':year'     => Project::getCopyrightYear(),
                ':url'      => Anchor::new(Project::getOwnerUrl())
                                     ->setTargetObject(EnumAnchorTarget::blank)
                                     ->setContent(Project::getOwnerName())
            ]);
        }

        return tr('Copyright © :year :url ' . ($multi_line ? PHP_EOL : null) . ':reserved', [
            ':reserved' => tr('All rights reserved'),
            ':year'     => Project::getCopyrightYear(),
            ':url'      => Anchor::new(Project::getOwnerUrl())
                                 ->setTargetObject(EnumAnchorTarget::blank)
                                 ->setContent(Project::getOwnerName())
        ]);
    }
}
