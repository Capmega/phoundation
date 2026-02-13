<?php

/**
 * Class Repositories
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */


declare(strict_types=1);

namespace Phoundation\Developer\Versioning\Repositories;

use Phoundation\Business\Companies\Branches\Interfaces\BranchInterface;
use Phoundation\Cli\Cli;
use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntries\DataIteratorCore;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Data\Traits\TraitDataResultsWithPermissionDenied;
use Phoundation\Developer\Phoundation\Enums\EnumPhoundationClass;
use Phoundation\Developer\Phoundation\Exception\NoRepositoriesAvailableException;
use Phoundation\Developer\Phoundation\Exception\RepositoryNotExistException;
use Phoundation\Developer\Phoundation\Exception\RepositorySynchronizationException;
use Phoundation\Developer\Project\Project;
use Phoundation\Developer\Versioning\Git\Branches\Interfaces\BranchesInterface;
use Phoundation\Developer\Versioning\Git\Interfaces\StatusFilesInterface;
use Phoundation\Developer\Versioning\Git\StatusFiles;
use Phoundation\Developer\Versioning\Git\Traits\TraitGitProcess;
use Phoundation\Developer\Versioning\Repositories\Exception\RepositoriesBranchExistsException;
use Phoundation\Developer\Versioning\Repositories\Exception\RepositoriesDifferentBranchesException;
use Phoundation\Developer\Versioning\Repositories\Exception\RepositoriesException;
use Phoundation\Developer\Versioning\Repositories\Exception\RepositoriesHaveChangesException;
use Phoundation\Developer\Versioning\Repositories\Exception\RepositoriesMissingBranchesException;
use Phoundation\Developer\Versioning\Repositories\Exception\RepositoriesNotAllHaveBranchException;
use Phoundation\Developer\Versioning\Repositories\Exception\RepositoriesNotAllHaveBranchSelectedException;
use Phoundation\Developer\Versioning\Repositories\Exception\RepositoriesNotAllHaveTagException;
use Phoundation\Developer\Versioning\Repositories\Exception\RepositoriesNotAllHaveTagSelectedException;
use Phoundation\Developer\Versioning\Repositories\Exception\RepositoriesNotAllHaveVersionSelectedException;
use Phoundation\Developer\Versioning\Repositories\Exception\RepositoriesChangesException;
use Phoundation\Developer\Versioning\Repositories\Exception\RepositoriesTagExistsException;
use Phoundation\Developer\Versioning\Repositories\Exception\RepositoriesVersionBranchNotExistsException;
use Phoundation\Developer\Versioning\Repositories\Interfaces\RepositoriesInterface;
use Phoundation\Developer\Versioning\Repositories\Interfaces\RepositoryInterface;
use Phoundation\Exception\NotExistsException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Filesystem\Interfaces\PhoDirectoryInterface;
use Phoundation\Filesystem\Interfaces\PhoPathInterface;
use Phoundation\Filesystem\PhoDirectory;
use Phoundation\Os\Processes\Commands\Find;
use Phoundation\Os\Processes\Commands\Interfaces\FindInterface;
use Phoundation\Utils\Strings;
use ReturnTypeWillChange;
use Stringable;


class Repositories extends DataIteratorCore implements RepositoriesInterface
{
    use TraitDataResultsWithPermissionDenied {
        getResultsWithPermissionDenied as protected __getResultsWithPermissionDenied;
    }
    use TraitGitProcess {
        __construct as construct;
    }


    /**
     * Tracks the Find process
     *
     * @var FindInterface
     */
    protected FindInterface $_find;

    /**
     * Tracks the number of new repositories found
     *
     * @var array $new
     */
    protected array $new;

    /**
     * Tracks the number of repositories deleted
     *
     * @var array $deleted
     */
    protected array $deleted;

    /**
     * Tracks the number of repositories that are backup repositories
     *
     * @var array $backups
     */
    protected array $backups;


    /**
     * RemoteRepositories class constructor
     *
     * @param PhoPathInterface|null $_parent_path
     */
    public function __construct(?PhoPathInterface $_parent_path = null)
    {
        parent::__construct();

        $this->construct($_parent_path);

        $this->setKeysAreUniqueColumn(true)
             ->setInjectSourceDirectly(false)
             ->setExceptionOnGet(true);
    }


    /**
     * Returns the table name used by this object
     *
     * @return string|null
     */
    public static function getTable(): ?string
    {
        return 'developer_repositories';
    }


    /**
     * Returns the unique column for this class
     *
     * @return string|null
     */
    public static function getUniqueColumn(): ?string
    {
        return 'name';
    }


    /**
     * Returns the data types that are allowed and accepted for this data iterator
     *
     * @return string|null
     */
    public static function getDefaultContentDataType(): ?string
    {
        return Repository::class;
    }


    /**
     * Returns the amount of 'permission denied' items in the result set
     *
     * @return array
     */
    public function getResultsWithPermissionDenied(): array
    {
        return $this->_find?->getResultsWithPermissionDenied();
    }


    /**
     * Returns an array with the new repositories found after a scan
     *
     * @return array
     */
    public function getNew(): array
    {
        if (empty($this->new)) {
            return [];
        }

        return $this->new;
    }


    /**
     * Returns the specified Repository object
     *
     * @param Stringable|string|float|int $key
     * @param mixed|null                  $default
     * @param bool|null                   $exception
     * @return RepositoryInterface|null
     */
    #[ReturnTypeWillChange] public function get(Stringable|string|float|int $key, mixed $default = null, ?bool $exception = null): ?RepositoryInterface
    {
        return parent::get($key, $default, $exception);
    }


    /**
     * Returns a random Repository object
     *
     * @return RepositoryInterface|null
     */
    #[ReturnTypeWillChange] public function getRandom(): ?RepositoryInterface
    {
        return parent::getRandom();
    }


    /**
     * Returns the current Repository object
     *
     * @note overrides the IteratorCore::current() method which returns mixed
     *
     * @return RepositoryInterface|null
     */
    #[ReturnTypeWillChange] public function current(): ?RepositoryInterface
    {
        if (empty($this->source)) {
            // This method is called when somebody tries a foreach() on this object, but there are no repositories
            throw NoRepositoriesAvailableException::new(ts('Cannot iterate over repositories, no repositories available or loaded'))
                                                  ->addHint(ts('Ensure this Repositories object has loaded the repositories, and ensure repositories are available in the database, run "./pho developer repositories scan" to scan for repositories'));
        }

        return parent::current();
    }


    /**
     * Returns the number of new repositories found after a scan
     *
     * @return int|null
     */
    public function getNewCount(): ?int
    {
        return count($this->getNew());
    }


    /**
     * Returns an array with the repositories that were deleted after a scan
     *
     * @return array
     */
    public function getDeleted(): array
    {
        if (empty($this->deleted)) {
            return [];
        }

        return $this->deleted;
    }


    /**
     * Returns the number of repositories deleted after a scan
     *
     * @return int|null
     */
    public function getDeletedCount(): ?int
    {
        return count($this->getDeleted());
    }


    /**
     * Returns an array with the repositories that were backups after a scan
     *
     * @return array
     */
    public function getBackups(): array
    {
        if (empty($this->backups)) {
            return [];
        }

        return $this->backups;
    }


    /**
     * Returns the number of repositories backups after a scan
     *
     * @return int|null
     */
    public function getBackupsCount(): ?int
    {
        return count($this->getBackups());
    }


    /**
     * Creates and returns a CLI table for the data in this Repositories object
     *
     * @param array|string|null $columns
     * @param array $filters
     * @param string|null $id_column
     * @return $this
     */
    public function displayCliTable(array|string|null $columns = null, array $filters = [], ?string $id_column = 'id'): static
    {
        $source = [];

        foreach ($this as $_repository) {
            $source[] = $_repository->getSource();
        }

        Cli::displayTable($source, $columns, $id_column);
        return $this;
    }


    /**
     * Executes a "git fetch" on all repositories
     *
     * @param string|bool|null $remote [null] The remote to fetch from, null will fetch from the default repository
     * @param bool             $all    [true] Will execute git fetch --all, fetch all remotes, except for the ones that has the remote.
     *
     * @return static
     */
    public function fetch(string|bool|null $remote = null, bool $all = true): static
    {
        foreach ($this as $_repository) {
            $_repository->fetch($remote, $all);
        }

        return $this;
    }


    /**
     * Will push the changes on the specified branch (or all if none specified) to the specified, or default remote repository
     *
     * @param string|bool|null $remote       [null]  The remote to push to, null will push to the default repository
     * @param string|null      $branch       [null]  The specific branch to push to, null will push all branches
     * @param bool             $set_upstream [false]
     *
     * @return static
     */
    public function push(string|bool|null $remote = null, ?string $branch = null, bool $set_upstream = false): static
    {
        $this->checkNoneHaveChanges('push');

        foreach ($this as $_repository) {
            $_repository->push($remote, $branch, $set_upstream);
        }

        return $this;
    }


    /**
     * Executes a "git pull" on all repositories
     *
     * @param string|bool|null $remote [null] The remote to pull from, null will pull from the default repository
     * @param string|null      $branch [null] The specific branch to pull, null will pull the current branch
     *
     * @return static
     */
    public function pull(string|bool|null $remote = null, ?string $branch = null): static
    {
        $this->checkNoneHaveChanges('pull');

        foreach ($this as $_repository) {
            $_repository->pull($remote, $branch);
        }

        return $this;
    }


    /**
     * Scans for repositories on the current machine and registers them in the database
     *
     * @note This method will add repositories with backup paths always at the end so they will typically not show up at the top of any list, if at all.
     *
     * @param PhoPathInterface $_path                       The path from which the scan will start
     * @param bool             $disable_backup_paths        [true] If true, will automatically disable repositories when any
     *                                                      directory (including the basename) in their path is a backup
     *                                                      directory (i.e. a directory name that ends with a ~)
     * @param bool             $delete_gone                 [true] Will delete repositories from the database if they were not
     *                                                      found during this scan
     *
     * @return static
     * @todo Implement $delete_gone support
     */
    public function scan(PhoPathInterface $_path, bool $disable_backup_paths = true, bool $delete_gone = true): static
    {
        Log::action(ts('Scanning path ":path" for repositories, this may take a little while...', [
            ':path' => $_path,
        ]));

        $this->_find = Find::new()
                            ->setIgnorePermissionDeniedInResults(true)
                            ->setPathObject($_path)
                            ->setType('d')
                            ->setName('.git');

        $found         = $this->_find->executeReturnArray();
        $this->backups = [];

        foreach ($found as $repository_path) {
            // We found the .git directory, the actual repository path will be the parent directory of that
            $this->tryAddRepository(PhoDirectory::new($repository_path, $_path->getRestrictionsObject())->getParentDirectoryObject());
        }

        return $this->processScannedBackupPaths($disable_backup_paths)
                    ->deleteScannedGonePaths($delete_gone);
    }


    /**
     * Will try to add the specified repository path to the developer_repositories table, or the backups list if it is a backup repository
     *
     * @note Will only add Phoundation repositories
     * @note Currently only supports GIT repositories
     * @note Any repository with a backup path will be stored in the $this->deleted array instead
     *
     * @param PhoDirectoryInterface $_repository_path The path containing the repository to (maybe) append to the developer_repositories table
     *
     * @return static
     */
    protected function tryAddRepository(PhoDirectoryInterface $_repository_path): static
    {
        Log::notice(ts('Found possible repository ":repository"', [
            ':repository' => $_repository_path,
        ]));

        $_repository_path->makeReal();

        // Only process Phoundation repositories
        if (Repository::repositoryIsPhoundation($_repository_path)) {
            if (!$this->addBackupRepository($_repository_path)) {
                $this->addRepository($_repository_path);
            }
        }

        return $this;
    }


    /**
     * Will process backup paths by adding them at the end of the found repositories and optionally disabling them
     *
     * @param bool $disable_backup_paths If true, will automatically disable all the specified backup paths
     *
     * @return static
     */
    protected function processScannedBackupPaths(bool $disable_backup_paths): static
    {
        // Now process repository paths that have backup paths
        foreach ($this->backups as $_repository_path) {
            $_repository = $this->addRepository($_repository_path);

            if ($_repository) {
                if ($disable_backup_paths) {
                    Log::action(ts('Automatically disabling repository ":repository"', [
                        ':repository' => $_repository->getDisplayName(),
                    ]));

                    $_repository->disable();
                }
            }
        }

        return $this;
    }


    /**
     * Adds a new repository for the specified repository path
     *
     * @param PhoDirectoryInterface $_repository_path
     *
     * @return RepositoryInterface|null
     */
    protected function addRepository(PhoDirectoryInterface $_repository_path): RepositoryInterface|null
    {
        if (Repository::exists($_repository_path->getBasename())) {
            $_repository = Repository::new($_repository_path->getBasename());

            // Do not blindly add this repository to the list. Due to symlinks, we MAY encounter the same repository real path twice!
            if ($this->keyExists($_repository->getDisplayName())) {
                // This repository already exists in the list, return nothing, we do not want this one to be processed at all because its a duplicate
                return null;
            }

            $this->add($_repository, $_repository->getDisplayName());
            $this->new[] = $_repository->getDisplayName();

        } else {
            // The repository path has not yet been registered, make a new Repository object for this path
            $_repository = Repository::newFromPathObject($_repository_path)->save();
            $this->add($_repository, $_repository->getDisplayName());
        }

        return $_repository;
    }


    /**
     * Will add the specified repository path as a backup path IF the path contains a backup indicator
     *
     * @see PhoPath::containsBackupDirectory()
     *
     * @param PhoPathInterface $_repository_path
     *
     * @return bool
     */
    protected function addBackupRepository(PhoPathInterface $_repository_path): bool
    {
        if ($_repository_path->containsBackupDirectory()) {
            // The path for this repository appears to be a backup path. Add those at the end
            $this->backups[] = $_repository_path;
            return true;
        }

        return false;
    }


    /**
     * Will delete all repositories that were gone, if specified to do so
     *
     * @param bool $delete_gone [true] If true, will delete all repositories that have been registered as gone
     *
     * @return static
     */
    protected function deleteScannedGonePaths(bool $delete_gone = true): static
    {
        // Remove repositories that were not found from the list?
        if ($delete_gone) {
            $db_repositories = sql()->listKeyValue('SELECT `name` 
                                                    FROM   `developer_repositories` 
                                                    WHERE  `status` IS NULL OR `status` != "deleted"');

            $this->deleted = array_diff($db_repositories, $this->getSourceKeys());

            foreach ($this->deleted as $repository) {
                Repository::new($repository)->delete();
            }
        }

        return $this;
    }


    /**
     * Returns an array containing the status for all repositories
     *
     * @return StatusFilesInterface
     */
    public function getStatusObject(): StatusFilesInterface
    {
        $_status_files = StatusFiles::new();

        foreach ($this as $_repository) {
            $_status_files->getRestrictionsObject()->addRestrictions($_repository->getRestrictionsObject());
            $_status_files->addSource($_repository->getStatusObject()->scanChanges()->getSource());
        }

        return $_status_files;
    }


    /**
     * Returns an array containing the status for all repositories
     *
     * @return StatusFilesInterface
     */
    public function getDiffObject(): StatusFilesInterface
    {
        $_status_files = StatusFiles::new();

        foreach ($this as $_repository) {
            $_status_files->getRestrictionsObject()->addRestrictions($_repository->getRestrictionsObject());
            $_status_files->addSource($_repository->getStatusObject()->scanChanges()->getSource());
        }

        return $_status_files;
    }


    /**
     * Checks that the project repository has the correct version (with or without suffix) specified
     *
     * @param string $action
     * @param bool   $no_suffix
     *
     * @return static
     */
    protected function checkProjectRepositoryVersion(string $action, bool $no_suffix = false): static
    {
        // Check the selected main project repository first
        // The repository version MUST match the configured version
        try {
            $_repository = $this->get(Project::getDirectoryName());
            $branch       = $_repository->getSelectedBranch(true);
            $version      = Project::getVersion();
            $version      = Strings::untilReverse($version, '.');

            if (!preg_match('/^\d{1,3}\.\d{1,3}$/', $branch)) {
                if ($no_suffix) {
                    $e = RepositorySynchronizationException::new(ts('Cannot perform action ":action" on repositories, the currently selected project branch ":version" is not valid', [
                        ':version' => $branch,
                        ':action'  => $action
                    ]))->addHint(ts('In order to perform action ":action" on repositories, the selected project branch MUST be either MAJOR.MINOR', [
                        ':action'  => $action
                    ]))->makeWarning();

                } elseif (!preg_match('/^\d{1,3}\.\d{1,3}-[a-z0-9-]$/i', $branch)) {
                    $e = RepositorySynchronizationException::new(ts('Cannot perform action ":action" on repositories, the currently selected project branch ":version" is not valid', [
                        ':version' => $branch,
                        ':action'  => $action
                    ]))->addHint(ts('In order to perform action ":action" on repositories, the selected project branch MUST be either MAJOR.MINOR or MAJOR.MINOR-SUFFIX', [
                        ':action'  => $action
                    ]))->makeWarning();
                }
            }

            if (empty($e)) {
                if (!str_starts_with($branch, $version)) {
                    $e = RepositorySynchronizationException::new(ts('Cannot perform action ":action" on repositories, the project version ":version" does not match the project repository branch ":branch"', [
                        ':action'  => $action,
                        ':branch'  => $branch,
                        ':version' => Project::getVersion(),
                    ]))->addHint(ts('In order to perform action ":action" on repositories, please select the branch ":version" or ":version-SUFFIX"', [
                        ':version' => $branch,
                        ':action'  => $action
                    ]))->makeWarning();
                }
            }

            if (isset($e)) {
                if (!$_repository->branchExists($version)) {
                    throw $e;
                }

                Log::warning(ts('Project branch ":branch" either has an invalid value or does not match the selected project version ":version", selecting correct branch to be able to continue', [
                    ':branch'  => $_repository->getSelectedBranch(),
                    ':version' => $version
                ]));

                $_repository->selectBranch($version);
            }

        } catch (NotExistsException) {
            if (sql()->getColumn('SELECT COUNT(`id`) `count` FROM `' . static::getTable() . '` WHERE `status` IS NULL')) {
                throw RepositorySynchronizationException::new(ts('Cannot perform action ":action" on project repositories, could not find the project main repository', [
                    ':action' => $action
                ]))->addHint(ts('Maybe you need to run "./pho developer repositories scan" first?'));
            }

            // There are no repositories!
            throw NoRepositoriesAvailableException::new(ts('Cannot perform action ":action" on project repositories, there are no repositories available', [
                ':action' => $action
            ]))->addHint(ts('First run "./pho developer repositories scan" to load up repositories to work with'));
        }

        return $this;
    }


    /**
     * Returns true if any repository is on the specified branch
     *
     * @param string $branch              The branch that any of the repositories must have
     * @param bool   $auto_create [false] If true, will automatically create the branch on each repository where it does
     * *                                  not yet exist
     * @return bool
     */
    public function anyHaveBranch(string $branch, bool $auto_create = false): bool
    {
        foreach ($this as $_repository) {
            if ($_repository->branchExists($branch, auto_create: $auto_create)) {
                return true;
            }
        }

        return false;
    }


    /**
     * Throws a RepositoriesBranchExistsException if not any repositories have the specified branch
     *
     * @param string $branch              The branch that must exist in any repositories
     * @param string $action              The action displayed in the exception, if thrown
     * @param bool   $auto_create [false] If true, will automatically create the branch on each repository where it does
     *                                    not yet exist
     * @return static
     * @throws RepositoriesBranchExistsException
     */
    public function checkNoneHaveBranch(string $branch, string $action, bool $auto_create = false): static
    {
        if ($this->anyHaveBranch($branch, $auto_create)) {
            throw new RepositoriesBranchExistsException(ts('Cannot perform action ":action", one or more repositories already have the specified branch ":branch"', [
                ':action' => $action,
                ':branch' => $branch
            ]));
        }

        return $this;
    }


    /**
     * Returns true if any repository is on the specified branch
     *
     * @param string $branch              The branch that any of the repositories must have
     * @param bool   $auto_create [false] If true, will automatically create the branch on each repository where it does
     * *                                  not yet exist
     * @return bool
     */
    public function allHaveBranch(string $branch, bool $auto_create = false): bool
    {
        foreach ($this as $_repository) {
            if (!$_repository->branchExists($branch, auto_create: $auto_create)) {
                return false;
            }
        }

        return true;
    }


    /**
     * Throws a RepositoriesNotAllHaveBranchException if not all repositories have the specified branch
     *
     * @param string $branch              The branch that must exist in all repositories
     * @param string $action              The action displayed in the exception, if thrown
     * @param bool   $auto_create [false] If true, will automatically create the branch on each repository where it does
     *                                    not yet exist
     * @return static
     * @throws RepositoriesNotAllHaveBranchException
     */
    public function checkAllHaveBranch(string $branch, string $action, bool $auto_create = false): static
    {
        if (!$this->anyHaveBranch($branch, $auto_create)) {
            throw new RepositoriesNotAllHaveBranchException(ts('Cannot perform action ":action", one or more repositories do not have the required branch ":branch"', [
                ':action' => $action,
                ':branch' => $branch
            ]));
        }

        return $this;
    }


    /**
     * Returns true if the selected branch for this repository is equal to the specified branch
     *
     * @param string $branch
     *
     * @return bool
     */
    public function anyIsOnBranch(string $branch): bool
    {
        foreach ($this as $_repository) {
            if ($_repository->isOnBranch($branch)) {
                return true;
            }
        }

        return false;
    }


    /**
     * Returns true if all repository is on the specified branch
     *
     * @param string $branch
     *
     * @return bool
     */
    public function allAreOnBranch(string $branch): bool
    {
        foreach ($this as $_repository) {
            if (!$_repository->isOnBranch($branch)) {
                return false;
            }
        }

        return true;
    }


    /**
     * Throws a RepositoriesException if any of the available repositories currently has the specified branch selected
     *
     * NOTE: Will NOT throw the exception when running in FORCE mode
     *
     * @param string $branch
     * @param string $action
     *
     * @return static
     * @throws RepositoriesException
     */
    public function checkNoneAreOnBranch(string $branch, string $action): static
    {
        foreach ($this as $_repository) {
            $_repository->checkIsNotOnBranch($branch, $action);
        }

        return $this;
    }


    /**
     * Returns a Repositories object with all the repositories that have changes
     *
     * @return RepositoriesInterface
     */
    public function getRepositoriesWithChanges(): RepositoriesInterface
    {
        $return = [];

        foreach ($this as $_repository) {
            if ($_repository->hasChanges()) {
                $return[$_repository->getName()] = $_repository;
            }
        }

        return Repositories::newFromSource($return);
    }


    /**
     * Returns true when any of the available repositories has changes
     *
     * @return bool
     */
    public function anyHaveChanges(): bool
    {
        foreach ($this as $_repository) {
            if ($_repository->hasChanges()) {
                return true;
            }
        }

        return false;
    }


    /**
     * Throws a RepositoriesSomeHaveChangesException if not all repositories have the specified branch
     *
     * @param string $action
     *
     * @return static
     * @throws RepositoriesChangesException
     */
    public function checkNoneHaveChanges(string $action): static
    {
        if ($this->anyHaveChanges()) {
            if (!FORCE) {
                throw RepositoriesChangesException::new(ts('Cannot perform action ":action", one or more repositories have changes', [
                    ':action' => $action,
                ]))->addHint(ts('To fix this issue, please first check what repositories have changes, commit them, and try again'))
                   ->setData([
                   'repositories' => $this->getRepositoriesWithChanges()->getSourceKeys(),
                   'files'        => $this->getChangedFiles(),
               ]);
;
            }
        }

        return $this;
    }


    /**
     * Returns true if all repositories have the requested project or phoundation branch selected
     *
     * @param string $phoundation_branch
     * @param string $project_branch
     *
     * @return bool
     */
    public function allHaveBranchSelected(string $phoundation_branch, string $project_branch): bool
    {
        foreach ($this as $_repository) {
            $branch = $this->getPhoundationOrProjectForType($_repository->getType(), $_repository->getName(), $phoundation_branch , $project_branch);

            if (!$_repository->hasBranchSelected($branch)) {
                return false;
            }
        }

        return true;
    }


    /**
     * Returns an array with all the repositories that do not have the requested project or phoundation branch selected
     *
     * @param string $phoundation_branch
     * @param string $project_branch
     *
     * @return array
     */
    public function getWithWrongBranchSelected(string $phoundation_branch, string $project_branch): array
    {
        $return = [];

        foreach ($this as $_repository) {
            $branch = $this->getPhoundationOrProjectForType($_repository->getType(), $_repository->getName(), $phoundation_branch , $project_branch);

            if (!$_repository->hasBranchSelected($branch)) {
                $return[$_repository->getName()] = $_repository->getName();
            }
        }

        return $return;
    }


    /**
     * Checks if all repositories have the requested project or phoundation branch selected, and if not, throws a RepositoriesNotAllHaveBranchSelectedException
     *
     * @param string $action
     * @param string $phoundation_branch
     * @param string $project_branch
     *
     * @return static
     * @throws RepositoriesNotAllHaveBranchSelectedException
     */
    public function checkAllHaveBranchSelected(string $action, string $phoundation_branch, string $project_branch): static
    {
        if ($this->allHaveBranchSelected($phoundation_branch, $project_branch)) {
            return $this;
        }

        throw RepositoriesNotAllHaveBranchSelectedException::new(ts('Cannot perform action ":action", one or more repositories have the wrong branch selected', [
            ':action' => $action,
        ]))->setData([
            'repositories' => $this->getWithWrongBranchSelected($phoundation_branch, $project_branch)
        ])->addHint(ts('To perform this action, please ensure all repositories have the correct branch, then try again. You can try ./pho developers repositories branches select-auto to automatically have all repositories on the right branch'));
    }


    /**
     * Checks if all repositories have the requested suffix or version branch available, and if not, throws a RepositoriesVersionBranchNotExistsException
     *
     * @param string $phoundation_version
     * @param string $project_version
     * @param string $phoundation_branch
     * @param string $project_branch
     *
     * @return bool
     */
    public function allHaveSuffixOrVersionBranch(string $phoundation_version, string $project_version, string $phoundation_branch, string $project_branch): bool
    {
        foreach ($this as $_repository) {
            $branch  = $this->getPhoundationOrProjectForType($_repository->getType(), $_repository->getName(), $phoundation_branch , $project_branch);
            $version = $this->getPhoundationOrProjectForType($_repository->getType(), $_repository->getName(), $phoundation_version, $project_version);

            if (!$_repository->hasBranchOrVersionBranch($version, $branch)) {
                return false;
            }
        }

        return true;
    }


    /**
     * Checks if all repositories have the requested suffix or version branch available, and if not, throws a RepositoriesVersionBranchNotExistsException
     *
     * @param string|null $suffix                     The optional suffix to use
     * @param string|null $phoundation_version        The version that should exist if this repository is a Phoundation repository
     * @param string|null $project_version            The version that should exist if this repository is a project repository
     * @param string|null $phoundation_branch         The branch that should exist if this repository is a Phoundation repository
     * @param string|null $project_branch             The branch that should exist if this repository is a project repository
     * @param bool        $check_versions      [true] If true will check version and branch. If false, will only check branch
     * @return static
     */
    public function checkAllHaveSuffixOrVersionBranch(?string $suffix, ?string &$phoundation_version = null, ?string &$project_version = null, ?string &$phoundation_branch = null, ?string &$project_branch = null, bool $check_versions = true): static
    {
        $project_version = Strings::untilReverse(Project::getVersion(), '.');
        $project_branch  = $project_version . ($suffix ? '-' . $suffix : null);

        $phoundation_version = Project::getPhoundationRequiredVersion();
        $phoundation_version = Strings::untilReverse($phoundation_version, '.');
        $phoundation_branch  = $phoundation_version . ($suffix ? '-' . $suffix : null);

        foreach ($this as $_repository) {
            // Only work on Phoundation type repositories
            if ($_repository->isPhoundation()) {
                $branch  = $this->getPhoundationOrProjectForType($_repository->getType(), $_repository->getName(), $phoundation_branch , $project_branch);
                $version = null;

                if ($check_versions) {
                    $version = $this->getPhoundationOrProjectForType($_repository->getType(), $_repository->getName(), $phoundation_version, $project_version);
                }

                $_repository->checkHasBranchOrVersionBranch($version, $branch);
            }
        }

        return $this;
    }


    /**
     * Returns true if all repositories have a branch selected
     *
     * @return bool
     */
    public function allHaveTypeBranchSelected(): bool
    {
        foreach ($this as $_repository) {
            if (!$_repository->hasTypeBranchSelected()) {
                return false;
            }
        }

        return true;
    }


    /**
     * Throws a RepositoriesException if any of the available repositories currently has the specified branch selected
     *
     * @param string $action The action that will be executed that requires all repositories to have a branch selected
     *
     * @return static
     */
    public function checkAllHaveTypeBranchSelected(string $action): static
    {
        if (!$this->allHaveTypeBranchSelected()) {
            throw new RepositoriesNotAllHaveBranchSelectedException(ts('Cannot execute action ":action", not all repositories have a branch selected', [
                ':action' => $action
            ]));
        }

        return $this;
    }


    /**
     * Creates the specified new branch in this repository
     *
     * @param string      $branch
     * @param bool        $reset
     * @param string|null $remote
     * @param bool        $set_upstream
     *
     * @return static
     */
    public function createBranch(string $branch, bool $reset = false, ?string $remote = null, bool $set_upstream = false): static
    {
        $this->checkNoneHaveBranch($branch, ts('create branch'));

        foreach ($this as $_repository) {
            $_repository->createBranch($branch, $reset, $remote, $set_upstream);
        }

        return $this;
    }


    /**
     * Creates the specified branch for all repositories
     *
     * @param string      $branch        The name for the branch to delete
     * @param string|bool $remote [true] If true or string with value, will delete the branch on the default (for true) or specified remote
     *
     * @return static
     */
    public function deleteBranch(string $branch, string|bool $remote = true): static
    {
        foreach ($this as $_repository) {
            $_repository->deleteBranch($branch, $remote);
        }

        return $this;
    }


    /**
     * Sets the selected branch for this repository
     *
     * @param string $branch
     * @param bool $auto_create
     * @param bool $upstream
     * @return static
     */
    public function selectBranch(string $branch, bool $auto_create = false, bool $upstream = false): static
    {
        $this->checkAllHaveBranch($branch, ts('select branch'), $auto_create);

        foreach ($this as $_repository) {
            $_repository->selectBranch($branch, $auto_create, $upstream);
        }

        return $this;
    }


    /**
     * Selects the correct version branch for all repositories so they are all on the correct branch
     *
     * @param string|null $suffix        If specified, will select VERSIONBRANCH-SUFFIX instead of VERSIONBRANCH
     * @param bool $auto_create   [true] If true, will automatically create the branch if it does not exist for
     *                                   each repository
     * @param bool $auto_pull     [true]
     * @return static
     */
    public function selectVersionBranch(?string $suffix, bool $auto_create = true, bool $auto_pull = true): static
    {
        // Before we start, make sure all target repositories have either the suffix branch already available or if not
        // Make sure none of the repositories have changes
        // Make sure the project repository is on the right version
        $this->checkAllHaveSuffixOrVersionBranch($suffix, $phoundation_version, $project_version, $phoundation_branch, $project_branch, $auto_create)
             ->checkNoneHaveChanges(ts('select version branch'))
             ->checkProjectRepositoryVersion(ts('select version branch'), true);

        // Go over each repository, switch each to the correct branch
        foreach ($this as $_repository) {
            $branch  = $this->getPhoundationOrProjectForType($_repository->getType(), $_repository->getName(), $phoundation_branch , $project_branch);
            $version = $this->getPhoundationOrProjectForType($_repository->getType(), $_repository->getName(), $phoundation_version, $project_version);

            // Can we switch to the branch, or do we have to create and push it first?
            if ($_repository->branchExists($branch)) {
                Log::action(ts('Selecting version branch ":branch" for ":type" repository ":repository"', [
                    ':branch'     => $branch,
                    ':type'       => $_repository->getType(),
                    ':repository' => $_repository->getName(),
                ]));

                $_repository->selectBranch($branch)
                             ->pull();

            } elseif ($suffix) {
                // Great, we have a suffix, so we COULD switch to the VERSION-SUFFIX branch, IF we have VERSION branch available
                if (!$_repository->branchExists($version)) {
                    throw new RepositoriesVersionBranchNotExistsException(ts('Cannot select branch ":branch" for repository ":repository" because the repository does not have the required version branch ":version" available', [
                        ':branch'     => $branch,
                        ':repository' => $_repository->getName(),
                        ':version'    => $version,
                    ]));
                }

                Log::action(ts('Creating and pushing required branch ":branch" from version branch ":version" for ":type" repository ":repository"', [
                    ':branch'     => $branch,
                    ':version'    => $version,
                    ':type'       => $_repository->getType(),
                    ':repository' => $_repository->getName(),
                ]));

                $_repository->selectBranch($version)
                             ->createBranch($branch)
                             ->push($_repository->selectRemoteRepository(), $branch)
                             ->selectBranch($branch);

            } else {
                // Problem! The repository does not have the requested branch which is an exact version, without a suffix.
                // We cannot create the branch automatically, because from where?!
                throw new RepositoriesVersionBranchNotExistsException(ts('Cannot select branch ":branch" for repository ":repository" because the repository does not have the required version branch ":version" available', [
                    ':branch'     => $branch,
                    ':repository' => $_repository->getName(),
                    ':version'    => $version,
                ]));
            }
        }

        return $this;
    }


    /**
     * Deletes the specified branch from all known repositories
     *
     * @param string      $suffix
     * @param string|bool $remote [true] If true or string with value, will delete the branch on the default (for true) or specified remote
     *
     * @return static
     */
    public function deleteVersionBranch(string $suffix, string|bool $remote = true): static
    {
        $project_branch     = Project::getVersion();
        $project_branch     = Strings::untilReverse($project_branch, '.') . ($suffix ? '-' . $suffix : null);
        $phoundation_branch = Project::getPhoundationRequiredVersion();
        $phoundation_branch = Strings::untilReverse($phoundation_branch, '.') . ($suffix ? '-' . $suffix : null);

        $this->checkProjectRepositoryVersion(ts('delete branch'))
             ->checkNoneAreOnBranch($phoundation_branch, ts('delete branch')) // TODO This is not correct, MAYBE a phoundation repository could have the same version branch as the project repository? Improve this
             ->checkNoneAreOnBranch($project_branch    , ts('delete branch'));

        if ($this->anyHaveChanges()) {
            if (!FORCE) {
                throw new RepositoriesHaveChangesException(ts('Cannot branch ":branch" from repositories, one or more repositories has changes', [
                    ':branch' => $suffix
                ]));
            }
        }

        // Go over each repository, switch each to the correct branch
        foreach ($this as $_repository) {
            $branch = $this->getPhoundationOrProjectForType($_repository->getType(), $_repository->getName(), $phoundation_branch , $project_branch);
            $_repository->deleteBranch($branch, $remote);
        }

        return $this;
    }


    /**
     * Throws a RepositoriesException if any of the known repositories currently has the specified tag selected
     *
     * NOTE: Will NOT throw the exception when running in FORCE mode
     *
     * @param string $tag
     * @param string $action
     *
     * @return static
     * @throws RepositoriesException
     */
    public function checkNoneIsOnTag(string $tag, string $action): static
    {
        foreach ($this as $_repository) {
            $_repository->checkIsOnTag($tag, $action);
        }

        return $this;
    }


    /**
     * Returns true if any repository is on the specified tag
     *
     * @param string $tag The tag that any of the repositories must have
     *
     * @return bool
     */
    public function anyHaveTag(string $tag): bool
    {
        foreach ($this as $_repository) {
            if ($_repository->tagExists($tag)) {
                return true;
            }
        }

        return false;
    }


    /**
     * Throws a RepositoriesException if not any repositories have the specified tag
     *
     * @param string $tag    The tag that must exist in any repositories
     * @param string $action The action displayed in the exception, if thrown
     *
     * @return static
     * @throws RepositoriesTagExistsException
     */
    public function checkNoneHaveTag(string $tag, string $action): static
    {
        if ($this->anyHaveTag($tag)) {
            throw new RepositoriesTagExistsException(ts('Cannot perform action ":action", one or more repositories already have the specified tag ":tag"', [
                ':action' => $action,
                ':tag' => $tag
            ]));
        }

        return $this;
    }


    /**
     * Returns true if any repository is on the specified tag
     *
     * @param string $tag                 The tag that any of the repositories must have
     * @param bool   $auto_create [false] If true, will automatically create the tag on each repository where it does
     *                                    not yet exist
     * @return bool
     */
    public function allHaveTag(string $tag, bool $auto_create = false): bool
    {
        foreach ($this as $_repository) {
            if (!$_repository->tagExists($tag)) {
                return false;
            }
        }

        return true;
    }


    /**
     * Throws a RepositoriesNotAllHaveTagException if not all repositories have the specified tag
     *
     * @param string $tag    The tag that must exist in all repositories
     * @param string $action The action displayed in the exception, if thrown
     *
     * @return static
     * @throws RepositoriesNotAllHaveTagException
     */
    public function checkAllHaveTag(string $tag, string $action): static
    {
        if (!$this->anyHaveTag($tag)) {
            throw new RepositoriesNotAllHaveTagException(ts('Cannot perform action ":action", one or more repositories do not have the required tag ":tag"', [
                ':action' => $action,
                ':tag' => $tag
            ]));
        }

        return $this;
    }


    /**
     * Checks if all repositories have the requested suffix or version tag available, and if not, throws a RepositoriesVersionTagNotExistsException
     *
     * @param string $phoundation_version
     * @param string $project_version
     * @param string $phoundation_tag
     *
     * @param string $project_tag
     *
     * @return static
     */
    public function checkAllHaveSuffixOrVersionTag(string $phoundation_version, string $project_version, string $phoundation_tag, string $project_tag): static
    {
        foreach ($this as $_repository) {
            $tag     = $this->getPhoundationOrProjectForType($_repository->getType(), $_repository->getName(), $phoundation_tag , $project_tag);
            $version = $this->getPhoundationOrProjectForType($_repository->getType(), $_repository->getName(), $phoundation_version, $project_version);

            $_repository->checkHasSuffixOrVersionTag($version, $tag);
        }

        return $this;
    }


    /**
     * Creates the specified tag for all repositories
     *
     * @param string      $tag             The name for the tag
     * @param string|null $message [NULL]  The optional message for the tag. If specified, will create an annotated tag
     *                                     automatically
     * @param bool        $signed  [FALSE] If true
     * @return static
     */
    public function createTag(string $tag, ?string $message = null, ?bool $signed = false): static
    {
        $this->checkNoneHaveTag($tag, ts('create tag'));
showdie();

        foreach ($this as $_repository) {
            $_repository->createTag($tag, $message, $signed);
        }

        return $this;
    }


    /**
     * Creates the specified lightweight tag for all repositories
     *
     * @param string $name The name for the tag
     * @return static
     */
    public function createLightweightTag(string $name): static
    {
        foreach ($this as $_repository) {
            $_repository->createLightweightTag($name);
        }

        return $this;
    }


    /**
     * Sets the selected branch for this repository
     *
     * @param string $branch
     * @param bool $auto_create
     * @param bool $upstream
     * @return static
     */
    public function selectTag(string $branch, bool $auto_create = false, bool $upstream = false): static
    {
        foreach ($this as $_repository) {
            $_repository->selectTag($branch);
        }

        return $this;
    }


    /**
     * Creates the specified tag for all repositories
     *
     * @param string      $tag           The name for the tag to delete
     * @param string|bool $remote [true] If true or string with value, will delete the branch on the default (for true) or specified remote
     *
     * @return static
     */
    public function deleteTag(string $tag, string|bool $remote = true): static
    {
        $this->checkNoneIsOnTag($tag, ts('delete tag'));

        foreach ($this as $_repository) {
            $_repository->deleteTag($tag, $remote);
        }

        return $this;
    }


    /**
     * Synchronizes all selected tag repositories so they are all on the correct tag
     *
     * @param string|null $suffix
     *
     * @return static
     */
    public function selectVersionTag(?string $suffix): static
    {
        $project_version = Project::getVersion();
        $project_version = Strings::untilReverse($project_version, '.');
        $project_tag  = $project_version . ($suffix ? '-' . $suffix : null);

        $phoundation_version = Project::getPhoundationRequiredVersion();
        $phoundation_version = Strings::untilReverse($phoundation_version, '.');
        $phoundation_tag  = $phoundation_version . ($suffix ? '-' . $suffix : null);

        // Before we start, make sure all target repositories have either the suffix tag already available or if not,
        $this->checkAllHaveSuffixOrVersionTag($phoundation_version, $project_version, $phoundation_tag, $project_tag);

        if ($this->anyHaveChanges()) {
            if (!FORCE) {
                throw new RepositoriesHaveChangesException(ts('Cannot select tages on repositories, one or more repositories has changes'));
            }
        }

        $this->checkProjectRepositoryVersion(ts('select tag'), true);

        // Go over each repository, switch each to the correct tag
        foreach ($this as $_repository) {
            $tag  = $this->getPhoundationOrProjectForType($_repository->getType(), $_repository->getName(), $phoundation_tag , $project_tag);
            $version = $this->getPhoundationOrProjectForType($_repository->getType(), $_repository->getName(), $phoundation_version, $project_version);

            // Can we switch to the tag, or do we have to create and push it first?
            if ($_repository->tagExists($tag)) {
                Log::action(ts('Selecting auto-tag ":tag" for ":type" repository ":repository"', [
                    ':tag'     => $tag,
                    ':type'       => $_repository->getType(),
                    ':repository' => $_repository->getName(),
                ]));

                $_repository->selectTag($tag);

            } elseif ($suffix) {
                // Great, we have a suffix, so we COULD switch to the VERSION-SUFFIX tag, IF we have VERSION tag available
                if (!$_repository->tagExists($version)) {
                    throw new RepositoriesVersionTagNotExistsException(ts('Cannot select tag ":tag" for repository ":repository" because the repository does not have the required version tag ":version" available', [
                        ':tag'     => $tag,
                        ':repository' => $_repository->getName(),
                        ':version'    => $version,
                    ]));
                }

                Log::action(ts('Creating and pushing required tag ":tag" from version tag ":version" for ":type" repository ":repository"', [
                    ':tag'     => $tag,
                    ':version'    => $version,
                    ':type'       => $_repository->getType(),
                    ':repository' => $_repository->getName(),
                ]));

                $_repository->selectTag($version)
                             ->createTag($tag)
                             ->push($_repository->selectRemoteRepository(), $tag);

            } else {
                // Problem! The repository does not have the requested tag which is an exact version, without a suffix.
                // We cannot create the tag automatically, because from where?!
                throw new RepositoriesVersionTagNotExistsException(ts('Cannot select tag ":tag" for repository ":repository" because the repository does not have the required version tag ":version" available', [
                    ':tag'     => $tag,
                    ':repository' => $_repository->getName(),
                    ':version'    => $version,
                ]));
            }
        }

        return $this;
    }


    /**
     * Deletes the specified tag from all repositories
     *
     * @param string $suffix
     * @param bool $remote
     * @return static
     */
    public function deleteVersionTag(string $suffix, bool $remote = true): static
    {
        $project_branch     = Project::getVersion();
        $project_branch     = Strings::untilReverse($project_branch, '.') . ($suffix ? '-' . $suffix : null);
        $phoundation_branch = Project::getPhoundationRequiredVersion();
        $phoundation_branch = Strings::untilReverse($phoundation_branch, '.') . ($suffix ? '-' . $suffix : null);

        $this->checkProjectRepositoryVersion(ts('delete tag'))
             ->checkNoneAreOnBranch($phoundation_branch, ts('delete tag')) // TODO This is not correct, MAYBE a phoundation repository could have the same version branch as the project repository? Improve this
             ->checkNoneIsOnTag($project_branch , ts('delete tag'));

        if ($this->anyHaveChanges()) {
            if (!FORCE) {
                throw new RepositoriesHaveChangesException(ts('Cannot branch ":branch" from repositories, one or more repositories has changes', [
                    ':branch' => $suffix
                ]));
            }
        }

        // Go over each repository, switch each to the correct branch
        foreach ($this as $_repository) {
            $branch = $this->getPhoundationOrProjectForType($_repository->getType(), $_repository->getName(), $phoundation_branch , $project_branch);
            $_repository->deleteTag($branch, $remote);
        }

        return $this;
    }


    /**
     * Returns true if all repositories have a tag selected
     *
     * @return bool
     */
    public function allHaveTypeTagSelected(): bool
    {
        foreach ($this as $_repository) {
            if (!$_repository->hasTypeTagSelected()) {
                return false;
            }
        }

        return true;
    }


    /**
     * Throws a RepositoriesException if any of the available repositories currently has the specified tag selected
     *
     * @param string $action The action that will be executed that requires all repositories to have a tag selected
     *
     * @return static
     */
    public function checkAllHaveTypeTagSelected(string $action): static
    {
        if (!$this->allHaveTypeTagSelected()) {
            throw new RepositoriesNotAllHaveTagSelectedException(ts('Cannot execute action ":action", not all repositories have a tag selected', [
                ':action' => $action
            ]));
        }

        return $this;
    }


    /**
     * Returns the correct branch for the specified type and name
     *
     * If the type is data and starts not with "phoundation-", the $project value will be returned, else the $phoundation value will be returned
     *
     * @param string $type
     * @param string $name
     * @param string $phoundation
     * @param string $project
     *
     * @return string
     */
    protected function getPhoundationOrProjectForType(string $type, string $name, string $phoundation, string $project): string
    {
        switch ($type) {
            case 'project':
                // no break

            case 'data':
                if (!str_starts_with($name, 'phoundation-')) {
                    return $project;
                }
        }

        return $phoundation;
    }


    /**
     * Will upgrade the revision part of the version of class repositories by the specified number
     *
     * @param EnumPhoundationClass $class    The class of repository to upgrade, either "phoundation" or "project" or "cdn"
     * @param int|null             $increase [1] The amount to increase the release part of the version by
     *
     * @return $this
     */
    public function releaseRevision(EnumPhoundationClass $class, ?int $increase = 1): static
    {
        $this->checkAllHaveCorrectVersionSelected(ts('release ' . $class->value));
showdie('YAY!');
        foreach ($this as $_repository) {
            if ($_repository->isClass($class)) {
                $_repository->upgradeRevision($increase ?? 1);
            }
        }

        return $this;
    }


    /**
     * Returns true if all repositories have the correct version branch or tag selected
     *
     * @param string|null $phoundation_version Will contain the Phoundation version
     * @param string|null $project_version     Will contain the project version
     * @param string|null $phoundation_branch  Will contain the project version
     * @param string|null $project_branch
     * @return bool
     */
    public function allHaveCorrectVersionSelected(?string &$phoundation_version = null, ?string &$project_version = null, ?string &$phoundation_branch = null, ?string &$project_branch = null): bool
    {
        $project_version = Project::getVersion();
        $project_version = Strings::untilReverse($project_version, '.');
        $project_branch  = $project_version;

        $phoundation_version = Project::getPhoundationRequiredVersion();
        $phoundation_version = Strings::untilReverse($phoundation_version, '.');
        $phoundation_branch  = $phoundation_version;

        // Before we start, make sure all target repositories have either the suffix branch already available or if not,
        return $this->allHaveSuffixOrVersionBranch($phoundation_version, $project_version, $phoundation_branch, $project_branch);
    }


    /**
     * Throws a RepositoriesNotAllHaveVersionSelectedException not if all repositories have the correct version branch or tag selected
     *
     * @param string $action The action displayed in the exception, if thrown
     *
     * @return static
     * @throws RepositoriesNotAllHaveVersionSelectedException
     */
    public function checkAllHaveCorrectVersionSelected(string $action): static
    {
        if (!$this->allHaveCorrectVersionSelected()) {
            throw new RepositoriesNotAllHaveVersionSelectedException(ts('Cannot execute action ":action", not all repositories have a version selected', [
                ':action' => $action
            ]));
        }

        return $this;
    }


    /**
     * Returns the master project repository, or NULL if it does not exist
     *
     * @note If the project repository does not exist, and $exception is true, a RepositoryNotExistException will be thrown
     *
     * @param bool $exception [true] If true, will throw a RepositoryNotExistException when the project repository does not exist, else will return NULL instead
     *
     * @return RepositoryInterface|null
     * @throws RepositoryNotExistException
     */
    public function getProjectRepositoryObject(bool $exception = true): ?RepositoryInterface
    {
        foreach ($this as $_repository) {
            if ($_repository->hasType('project')) {
                return $_repository;
            }
        }

        if ($exception) {
            throw RepositoryNotExistException::new(ts('The project repository does not exist'))
                                             ->addHint(ts('Try running "./pho developer repositories scan" to find the missing repository'));
        }

        return null;
    }


    /**
     * Returns the version from the currently selected branch for the project main repository, or NULL if the project branch is not on a version branch
     *
     * @return string|null
     */
    public function getProjectSelectedBranchVersion(): ?string
    {
        try {
            return $this->getProjectRepositoryObject()->getSelectedBranchVersion();

        } catch (RepositoryNotExistException $e) {
            throw RepositoryNotExistException::new(ts('Could not detect project selected branch version, the project repository does not exist'), $e);
        }
    }


    /**
     * Returns the version from the currently selected branch for the project main repository, or NULL if the project branch is not on a version-suffix branch
     *
     * @return string|null
     */
    public function getProjectSelectedBranchSuffix(): ?string
    {
        try {
            return $this->getProjectRepositoryObject()->getSelectedBranchSuffix();

        } catch (RepositoryNotExistException $e) {
            throw RepositoryNotExistException::new(ts('Could not detect project selected branch suffix, the project repository does not exist'), $e);
        }
    }


    /**
     * Returns the currently selected for the project main repository, or NULL if no suffix has been selected
     *
     * @return string
     */
    public function detectProjectBranch(): string
    {
        foreach ($this as $_repository) {
            if ($_repository->hasType('project')) {
                return $_repository->getBranch();
            }
        }

        throw RepositoryNotExistException::new(ts('Could not detect project branch, could not find the project repository'))
                                         ->addHint(ts('Try running "./pho developer repositories scan" to find the missing repository'));
    }


    /**
     * Returns true if the project is on a version branch with suffix
     *
     * @return bool
     */
    public function hasProjectVersionSuffixSelected(): bool
    {
        return (bool) $this->getProjectSelectedBranchSuffix();
    }


    /**
     * Throws a RepositoriesException if the selected project version has no suffix
     *
     * @param string $action the action that is to be taken if this test passes
     * @return static
     * @throws RepositoriesException
     */
    public function checkHasProjectVersionSuffixSelected(string $action): static
    {
        if ($this->hasProjectVersionSuffixSelected()) {
            return $this;
        }

        throw RepositoriesException::new(ts('Cannot execute action ":action", the project repositories are on a version branch ":branch" that has no suffix', [
            ':action' => $action,
            ':branch' => $this->detectProjectBranch()
        ]))->makeWarning();
    }


    /**
     * Returns a full branch name for the specified suffix
     *
     * @param string $version
     * @param string|null $suffix
     *
     * @return string
     */
    protected function getBranchForVersionAndSuffix(string $version, ?string $suffix): string
    {
        return $version . ($suffix ? '-' . $suffix : null);
    }


    /**
     * Returns true if all repositories have the same branch version / suffix selected
     *
     * @param string|null $suffix      [null]  The suffix to use. If not specified, will default to the currently selected project repository suffix
     * @param bool        $auto_create [false] If true, will automatically generate the branches when missing
     *
     * @return bool
     */
    protected function allOnSameVersionSuffix(?string $suffix = null, bool $auto_create = false): bool
    {
        $suffix = $suffix ?? $this->getProjectRepositoryObject()->getSelectedBranchSuffix();

        $this->checkAllHaveSuffixOrVersionBranch($suffix, $phoundation_version, $project_version, $phoundation_branch, $project_branch, $auto_create);

        foreach ($this as $_repository) {
            $branch = $this->getPhoundationOrProjectForType($_repository->getType(), $_repository->getName(), $phoundation_branch, $project_branch);

            if ($_repository->hasBranchSelected($branch)) {
                continue;
            }

            return false;
        }

        return true;
    }


    /**
     * Throws a RepositoriesDifferentBranchesException if not all repositories have the same version / suffix branch selected
     *
     * @param string      $action              The action that would be executed if all repositories are on the same version / suffix
     * @param string|null $suffix      [null]  The suffix to use. If not specified, will default to the currently selected project repository suffix
     * @param bool        $auto_create [false] If true, will automatically generate the branches when missing
     *
     * @return static
     */
    protected function checkAllOnSameVersionSuffix(string $action, ?string $suffix = null, bool $auto_create = false): static
    {
        if ($this->allOnSameVersionSuffix($suffix, $auto_create)) {
            return $this;
        }

        throw RepositoriesDifferentBranchesException::new(ts('Cannot execute action ":action", not all repositories are on the same suffix version branch', [
            ':action' => $action,
        ]))->addHint(ts('Check the selected branch for all registered repositories with "./pho developer repositories branch" or "./pho dv rp br"'));
    }


    /**
     * Updates the selected suffixed version branches, and updates it from the base version in all repositories
     *
     * @param bool $all_version_branches [false] If true, will not only update the current suffix branch, but will update all branches for the same version
     * @return static
     */
    public function updateVersionBranches(bool $all_version_branches = false): static
    {
        $this->checkNoneHaveChanges(ts('select auto-branch'))
             ->checkHasProjectVersionSuffixSelected(ts('update suffix branches'))
             ->checkAllHaveSuffixOrVersionBranch($this->getProjectSelectedBranchSuffix());

        foreach ($this as $_repository) {
            $_repository->updateVersionBranch($all_version_branches);
        }

        return $this;
    }


    /**
     * Merges the specified version suffix branches into the current version suffix branch
     *
     * @param array|string $suffixes a (space separated, if string) list of version suffix branches that will be merged into the current version suffix branch
     *                               for each repository
     *
     * @return static
     */
    public function mergeVersionSuffixes(array|string $suffixes): static
    {
        $this->checkNoneHaveChanges(ts(ts('select auto-branch')))
             ->checkAllOnSameVersionSuffix(ts('select auto-branch'))
             ->checkAllHaveVersionSuffixBranches(ts('select auto-branch'), $suffixes);

        foreach ($this as $_repository) {
            $_repository->mergeVersionSuffixes($suffixes);
        }

        return $this;
    }


    /**
     * Returns all branches in all repositories where the specified revision exists
     *
     * @param string $revision The revision to filter on
     *
     * @return BranchesInterface
     */
    public function getBranchesContainingRevision(string $revision): IteratorInterface
    {
        $return = [];

        foreach ($this as $_repository) {
            $results = $_repository->getBranchesContainingRevision($revision);

            if ($results) {
                $return[$_repository->getDisplayName()] = $results;
            }
        }

        return Iterator::new($return);
    }


    /**
     * Executes a grep on all revisions of this repository for the specified word, and returns all revisions where that word was found
     *
     * @param string $keyword        The keyword to search for
     * @param bool   $grouped [true] If true, will return the results grouped by revision and file. If false, will return the results directly from GIT
     *
     * @return IteratorInterface
     */
    public function grep(string $keyword, bool $grouped = true): IteratorInterface
    {
        $return = new Iterator();

        foreach ($this as $_repository) {
            // When adding the Repository sources to the Repositories source, be sure to clear the keys as all keys will start with 0
            $return->addSource($_repository->grep($keyword, $grouped), true);
        }

        return $return;
    }


    /**
     * Updates all suffixed version branches for the specified version, and update them from the base version, in all repositories
     *
     * @param string $version
     * @return static
     */
    public function updateAllSuffixedVersionBranches(string $version): static
    {
        return $this;
    }


    /**
     * Returns an array containing all files that have changes in all repositories
     *
     * @return array
     */
    public function getChangedFiles(): array
    {
        $return = [];

        foreach ($this as $_repository) {
            $return = array_merge($return, $_repository->getChangedFiles());
        }

        return $return;
    }
}
