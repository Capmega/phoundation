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

use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntries\DataIteratorCore;
use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Data\DataEntries\Interfaces\IdentifierInterface;
use Phoundation\Data\Traits\TraitDataResultsWithPermissionDenied;
use Phoundation\Developer\Phoundation\Exception\RepositorySynchronizationException;
use Phoundation\Developer\Project\Project;
use Phoundation\Developer\Versioning\Git\Interfaces\StatusFilesInterface;
use Phoundation\Developer\Versioning\Git\StatusFiles;
use Phoundation\Developer\Versioning\Git\Traits\TraitGitProcess;
use Phoundation\Developer\Versioning\Repositories\Exception\RepositoriesBranchExistsException;
use Phoundation\Developer\Versioning\Repositories\Exception\RepositoriesException;
use Phoundation\Developer\Versioning\Repositories\Exception\RepositoriesHaveChangesException;
use Phoundation\Developer\Versioning\Repositories\Exception\RepositoriesNotAllHaveBranchException;
use Phoundation\Developer\Versioning\Repositories\Exception\RepositoriesNotAllHaveTagException;
use Phoundation\Developer\Versioning\Repositories\Exception\RepositoriesTagExistsException;
use Phoundation\Developer\Versioning\Repositories\Exception\RepositoriesVersionBranchNotExistsException;
use Phoundation\Developer\Versioning\Repositories\Interfaces\RepositoriesInterface;
use Phoundation\Developer\Versioning\Repositories\Interfaces\RepositoryInterface;
use Phoundation\Exception\NotExistsException;
use Phoundation\Exception\UnderConstructionException;
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
    protected FindInterface $o_find;

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
     * RemoteRepositories class constructor
     *
     * @param PhoPathInterface|null $o_parent_path
     */
    public function __construct(?PhoPathInterface $o_parent_path = null)
    {
        parent::__construct();
        $this->construct($o_parent_path);

        $this->setKeysAreUniqueColumn(true)
             ->setInjectSourceDirectly(false);

        $this->query = 'SELECT `developer_repositories`.* FROM `developer_repositories` WHERE `status` IS NULL';
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
        return $this->o_find?->getResultsWithPermissionDenied();
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
     * @return mixed
     */
    #[ReturnTypeWillChange] public function get(Stringable|string|float|int $key, mixed $default = null, ?bool $exception = null): RepositoryInterface
    {
        return parent::get($key, $default, $exception);
    }


    /**
     * Returns a random Repository
     *
     * @return mixed
     */
    #[ReturnTypeWillChange] public function getRandom(): RepositoryInterface
    {
        return parent::getRandom();
    }


    /**
     * Returns the current Repository
     *
     * @note overrides the IteratorCore::current() method which returns mixed
     *
     * @return mixed
     */
    #[ReturnTypeWillChange] public function current(): RepositoryInterface
    {
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
     * Executes a "git fetch" on all repositories
     *
     * @param string|bool|null $remote [null] The remote to fetch from, null will fetch from the default repository
     * @param bool             $all    [true] Will execute git fetch --all, fetch all remotes, except for the ones that has the remote.
     *
     * @return static
     */
    public function fetch(string|bool|null $remote = null, bool $all = true): static
    {
        foreach ($this as $o_repository) {
            $o_repository->fetch($remote, $all);
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
        foreach ($this as $o_repository) {
            $o_repository->push($remote, $branch, $set_upstream);
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
        foreach ($this as $o_repository) {
            $o_repository->pull($remote, $branch);
        }

        return $this;
    }


    /**
     * Scans for repositories on the current machine and registers them in the database
     *
     * @param PhoPathInterface $path
     * @param bool             $delete_gone
     *
     * @return static
     * @todo Implement $delete_gone support
     */
    public function scan(PhoPathInterface $path, bool $delete_gone = true): static
    {
        $this->load();

        Log::action(ts('Scanning path ":path" for repositories, this may take a little while...', [
            ':path' => $path,
        ]));

        $this->o_find = Find::new()
                            ->setIgnorePermissionDeniedInResults(true)
                            ->setPathObject($path)
                            ->setType('d')
                            ->setName('.git');

        $found = $this->o_find->executeReturnArray();

        foreach ($found as $repository_path) {
            $o_repository_path = PhoDirectory::new($repository_path, $path->getRestrictionsObject())->getParentDirectoryObject();

            if (Repository::isPhoundation($o_repository_path)) {
                if (!Repository::exists($o_repository_path->getBasename())) {
                    Repository::newFromPathObject($o_repository_path)->save();
                }
            }
        }


        // Remove repositories that were not found from the list?
        if ($delete_gone) {
// TODO Implement auto delete gone repositories
throw new UnderConstructionException();
        }

        return $this;
    }


    /**
     * Returns true when any of the available repositories has changes
     *
     * @return bool
     */
    public function hasChanges(): bool
    {
        foreach ($this as $o_repository) {
            if ($o_repository->getStatusObject()->scanChanges()->getCount()) {
                return true;
            }
        }

        return false;
    }


    /**
     * Returns an array containing the status for all repositories
     *
     * @return StatusFilesInterface
     */
    public function getStatusObject(): StatusFilesInterface
    {
        $o_return = StatusFiles::new();

        foreach ($this as $o_repository) {
            $o_return->getRestrictionsObject()->addRestrictions($o_repository->getRestrictionsObject());
            $o_return->addSource($o_repository->getStatusObject()->scanChanges()->getSource());
//'repository' => $o_repository->getName(),
//'branch'     => $o_repository->getCurrentBranch(),
//'file'       => $file,
//'status'     => $status->getReadableStatus()
        }

        return $o_return;
    }


    /**
     * Gets the project repository object, verifies its on the correct branch, and returns it
     *
     * @param string $action
     * @param bool   $no_suffix
     *
     * @return static
     */
    protected function verifyProjectRepositoryVersion(string $action, bool $no_suffix = false): static
    {
        // Check the current main project repository first
        // The repository version MUST match the configured version
        try {
            $o_repository = $this->get(Project::getDirectoryName());
            $branch       = $o_repository->getSelectedBranch();
            $version      = Project::getVersion();
            $version      = Strings::untilReverse($version, '.');

            if (!preg_match('/^\d{1,3}\.\d{1,3}$/', $branch)) {
                if ($no_suffix) {
                    $e = RepositorySynchronizationException::new(ts('Cannot perform action ":action" on repositories, the currently selected project branch ":version" is not valid', [
                        ':version' => $branch,
                        ':action'  => $action
                    ]))->addHint(ts('In order to perform action ":action" on repositories, the current project branch MUST be either MAJOR.MINOR', [
                        ':action'  => $action
                    ]))->makeWarning();

                } elseif (!preg_match('/^\d{1,3}\.\d{1,3}-[a-z0-9-]$/i', $branch)) {
                    $e = RepositorySynchronizationException::new(ts('Cannot perform action ":action" on repositories, the currently selected project branch ":version" is not valid', [
                        ':version' => $branch,
                        ':action'  => $action
                    ]))->addHint(ts('In order to perform action ":action" on repositories, the current project branch MUST be either MAJOR.MINOR or MAJOR.MINOR-SUFFIX', [
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
                if (!$o_repository->branchExists($version)) {
                    throw $e;
                }

                Log::warning(ts('Project branch ":branch" either has an invalid value or does not match the current project version ":version", selecting correct branch to be able to continue', [
                    ':branch'  => $o_repository->getSelectedBranch(),
                    ':version' => $version
                ]));

                $o_repository->selectBranch($version);
            }

        } catch (NotExistsException) {
            throw RepositorySynchronizationException::new(ts('Cannot perform action ":action" on repositories, could not find the project repository', [
                ':action' => $action
            ]))->addHint(ts('Maybe you need to run "./pho developer repositories scan" first?'));
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
        foreach ($this as $o_repository) {
            if ($o_repository->branchExists($branch, auto_create: $auto_create)) {
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
     * @param bool   $auto_create [false] If true, will automaticanyy create the branch on each repository where it does
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
        foreach ($this as $o_repository) {
            if (!$o_repository->branchExists($branch, auto_create: $auto_create)) {
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
     * Returns true if the current git branch for this repository is equal to the specified branch
     *
     * @param string $branch
     *
     * @return bool
     */
    public function anyIsOnBranch(string $branch): bool
    {
        foreach ($this as $o_repository) {
            if ($o_repository->isOnBranch($branch)) {
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
        foreach ($this as $o_repository) {
            if (!$o_repository->isOnBranch($branch)) {
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
    public function checkNoneIsOnBranch(string $branch, string $action): static
    {
        foreach ($this as $o_repository) {
            $o_repository->checkIsNotOnBranch($branch, $action);
        }

        return $this;
    }


    /**
     * Checks if all repositories have the requested suffix or version branch available, and if not, throws a RepositoriesVersionBranchNotExistsException
     *
     * @param string $phoundation_version
     * @param string $project_version
     * @param string $phoundation_branch
     *
     * @param string $project_branch
     *
     * @return static
     */
    public function checkAllHaveSuffixOrVersionBranch(string $phoundation_version, string $project_version, string $phoundation_branch, string $project_branch): static
    {
        foreach ($this as $o_repository) {
            $branch  = $this->getValueForType($o_repository->getType(), $o_repository->getName(), $phoundation_branch , $project_branch);
            $version = $this->getValueForType($o_repository->getType(), $o_repository->getName(), $phoundation_version, $project_version);

            $o_repository->checkHasSuffixOrVersionBranch($version, $branch);
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

        foreach ($this as $o_repository) {
            $o_repository->createBranch($branch, $reset, $remote, $set_upstream);
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
        foreach ($this as $o_repository) {
            $o_repository->deleteBranch($branch, $remote);
        }

        return $this;
    }


    /**
     * Sets the current git branch for this repository
     *
     * @param string $branch
     * @param bool $auto_create
     * @param bool $upstream
     * @return static
     */
    public function selectBranch(string $branch, bool $auto_create = false, bool $upstream = false): static
    {
        $this->checkAllHaveBranch($branch, ts('select branch'), $auto_create);

        foreach ($this as $o_repository) {
            $o_repository->selectBranch($branch, $auto_create, $upstream);
        }

        return $this;
    }


    /**
     * Synchronizes all selected branch repositories so they are all on the correct branch
     *
     * @param string|null $suffix
     *
     * @return static
     */
    public function selectAutoBranch(?string $suffix): static
    {
        $project_version = Project::getVersion();
        $project_version = Strings::untilReverse($project_version, '.');
        $project_branch  = $project_version . ($suffix ? '-' . $suffix : null);

        $phoundation_version = Project::getPhoundationRequiredVersion();
        $phoundation_version = Strings::untilReverse($phoundation_version, '.');
        $phoundation_branch  = $phoundation_version . ($suffix ? '-' . $suffix : null);

        // Before we start, make sure all target repositories have either the suffix branch already available or if not,
        $this->checkAllHaveSuffixOrVersionBranch($phoundation_version, $project_version, $phoundation_branch, $project_branch);

        if ($this->hasChanges()) {
            if (!FORCE) {
                throw new RepositoriesHaveChangesException(ts('Cannot select branches on repositories, one or more repositories has changes'));
            }
        }

        $this->verifyProjectRepositoryVersion(ts('select branch'), true);

        // Go over each repository, switch each to the correct branch
        foreach ($this as $o_repository) {
            $branch  = $this->getValueForType($o_repository->getType(), $o_repository->getName(), $phoundation_branch , $project_branch);
            $version = $this->getValueForType($o_repository->getType(), $o_repository->getName(), $phoundation_version, $project_version);

            // Can we switch to the branch, or do we have to create and push it first?
            if ($o_repository->branchExists($branch)) {
                Log::action(ts('Selecting auto-branch ":branch" for ":type" repository ":repository"', [
                    ':branch'     => $branch,
                    ':type'       => $o_repository->getType(),
                    ':repository' => $o_repository->getName(),
                ]));

                $o_repository->selectBranch($branch);

            } elseif ($suffix) {
                // Great, we have a suffix, so we COULD switch to the VERSION-SUFFIX branch, IF we have VERSION branch available
                if (!$o_repository->branchExists($version)) {
                    throw new RepositoriesVersionBranchNotExistsException(ts('Cannot select branch ":branch" for repository ":repository" because the repository does not have the required version branch ":version" available', [
                        ':branch'     => $branch,
                        ':repository' => $o_repository->getName(),
                        ':version'    => $version,
                    ]));
                }

                Log::action(ts('Creating and pushing required branch ":branch" from version branch ":version" for ":type" repository ":repository"', [
                    ':branch'     => $branch,
                    ':version'    => $version,
                    ':type'       => $o_repository->getType(),
                    ':repository' => $o_repository->getName(),
                ]));

                $o_repository->selectBranch($version)
                             ->createBranch($branch)
                             ->push($o_repository->selectRemoteRepository(), $branch);

            } else {
                // Problem! The repository does not have the requested branch which is an exact version, without a suffix.
                // We cannot create the branch automatically, because from where?!
                throw new RepositoriesVersionBranchNotExistsException(ts('Cannot select branch ":branch" for repository ":repository" because the repository does not have the required version branch ":version" available', [
                    ':branch'     => $branch,
                    ':repository' => $o_repository->getName(),
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
    public function deleteAutoBranch(string $suffix, string|bool $remote = true): static
    {
        $project_branch     = Project::getVersion();
        $project_branch     = Strings::untilReverse($project_branch, '.') . ($suffix ? '-' . $suffix : null);
        $phoundation_branch = Project::getPhoundationRequiredVersion();
        $phoundation_branch = Strings::untilReverse($phoundation_branch, '.') . ($suffix ? '-' . $suffix : null);

        $this->verifyProjectRepositoryVersion(ts('delete branch'))
             ->checkNoneIsOnBranch($phoundation_branch, ts('delete branch')) // TODO This is not correct, MAYBE a phoundation repository could have the same version branch as the project repository? Improve this
             ->checkNoneIsOnBranch($project_branch    , ts('delete branch'));

        if ($this->hasChanges()) {
            if (!FORCE) {
                throw new RepositoriesHaveChangesException(ts('Cannot branch ":branch" from repositories, one or more repositories has changes', [
                    ':branch' => $suffix
                ]));
            }
        }

        // Go over each repository, switch each to the correct branch
        foreach ($this as $o_repository) {
            $branch = $this->getValueForType($o_repository->getType(), $o_repository->getName(), $phoundation_branch , $project_branch);
            $o_repository->deleteBranch($branch, $remote);
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
        foreach ($this as $o_repository) {
            $o_repository->checkIsOnTag($tag, $action);
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
        foreach ($this as $o_repository) {
            if ($o_repository->tagExists($tag)) {
                return true;
            }
        }

        return false;
    }


    /**
     * Throws a RepositoriesException if not any repositories have the specified tag
     *
     * @param string $tag              The tag that must exist in any repositories
     * @param string $action              The action displayed in the exception, if thrown
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
     * @param string $tag              The tag that any of the repositories must have
     * @param bool   $auto_create [false] If true, will automatically create the tag on each repository where it does
     * *                                  not yet exist
     * @return bool
     */
    public function allHaveTag(string $tag, bool $auto_create = false): bool
    {
        foreach ($this as $o_repository) {
            if (!$o_repository->tagExists($tag)) {
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
        foreach ($this as $o_repository) {
            $tag     = $this->getValueForType($o_repository->getType(), $o_repository->getName(), $phoundation_tag , $project_tag);
            $version = $this->getValueForType($o_repository->getType(), $o_repository->getName(), $phoundation_version, $project_version);

            $o_repository->checkHasSuffixOrVersionTag($version, $tag);
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

        foreach ($this as $o_repository) {
            $o_repository->createTag($tag, $message, $signed);
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
        foreach ($this as $o_repository) {
            $o_repository->createLightweightTag($name);
        }

        return $this;
    }


    /**
     * Sets the current git branch for this repository
     *
     * @param string $branch
     * @param bool $auto_create
     * @param bool $upstream
     * @return static
     */
    public function selectTag(string $branch, bool $auto_create = false, bool $upstream = false): static
    {
        foreach ($this as $o_repository) {
            $o_repository->selectTag($branch);
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

        foreach ($this as $o_repository) {
            $o_repository->deleteTag($tag, $remote);
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
    public function selectAutoTag(?string $suffix): static
    {
        $project_version = Project::getVersion();
        $project_version = Strings::untilReverse($project_version, '.');
        $project_tag  = $project_version . ($suffix ? '-' . $suffix : null);

        $phoundation_version = Project::getPhoundationRequiredVersion();
        $phoundation_version = Strings::untilReverse($phoundation_version, '.');
        $phoundation_tag  = $phoundation_version . ($suffix ? '-' . $suffix : null);

        // Before we start, make sure all target repositories have either the suffix tag already available or if not,
        $this->checkAllHaveSuffixOrVersionTag($phoundation_version, $project_version, $phoundation_tag, $project_tag);

        if ($this->hasChanges()) {
            if (!FORCE) {
                throw new RepositoriesHaveChangesException(ts('Cannot select tages on repositories, one or more repositories has changes'));
            }
        }

        $this->verifyProjectRepositoryVersion(ts('select tag'), true);

        // Go over each repository, switch each to the correct tag
        foreach ($this as $o_repository) {
            $tag  = $this->getValueForType($o_repository->getType(), $o_repository->getName(), $phoundation_tag , $project_tag);
            $version = $this->getValueForType($o_repository->getType(), $o_repository->getName(), $phoundation_version, $project_version);

            // Can we switch to the tag, or do we have to create and push it first?
            if ($o_repository->tagExists($tag)) {
                Log::action(ts('Selecting auto-tag ":tag" for ":type" repository ":repository"', [
                    ':tag'     => $tag,
                    ':type'       => $o_repository->getType(),
                    ':repository' => $o_repository->getName(),
                ]));

                $o_repository->selectTag($tag);

            } elseif ($suffix) {
                // Great, we have a suffix, so we COULD switch to the VERSION-SUFFIX tag, IF we have VERSION tag available
                if (!$o_repository->tagExists($version)) {
                    throw new RepositoriesVersionTagNotExistsException(ts('Cannot select tag ":tag" for repository ":repository" because the repository does not have the required version tag ":version" available', [
                        ':tag'     => $tag,
                        ':repository' => $o_repository->getName(),
                        ':version'    => $version,
                    ]));
                }

                Log::action(ts('Creating and pushing required tag ":tag" from version tag ":version" for ":type" repository ":repository"', [
                    ':tag'     => $tag,
                    ':version'    => $version,
                    ':type'       => $o_repository->getType(),
                    ':repository' => $o_repository->getName(),
                ]));

                $o_repository->selectTag($version)
                             ->createTag($tag)
                             ->push($o_repository->selectRemoteRepository(), $tag);

            } else {
                // Problem! The repository does not have the requested tag which is an exact version, without a suffix.
                // We cannot create the tag automatically, because from where?!
                throw new RepositoriesVersionTagNotExistsException(ts('Cannot select tag ":tag" for repository ":repository" because the repository does not have the required version tag ":version" available', [
                    ':tag'     => $tag,
                    ':repository' => $o_repository->getName(),
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
    public function deleteAutoTag(string $suffix, bool $remote = true): static
    {
        $project_branch     = Project::getVersion();
        $project_branch     = Strings::untilReverse($project_branch, '.') . ($suffix ? '-' . $suffix : null);
        $phoundation_branch = Project::getPhoundationRequiredVersion();
        $phoundation_branch = Strings::untilReverse($phoundation_branch, '.') . ($suffix ? '-' . $suffix : null);

        $this->verifyProjectRepositoryVersion(ts('delete tag'))
             ->checkNoneIsOnBranch($phoundation_branch, ts('delete tag')) // TODO This is not correct, MAYBE a phoundation repository could have the same version branch as the project repository? Improve this
             ->checkNoneIsOnTag($project_branch , ts('delete tag'));

        if ($this->hasChanges()) {
            if (!FORCE) {
                throw new RepositoriesHaveChangesException(ts('Cannot branch ":branch" from repositories, one or more repositories has changes', [
                    ':branch' => $suffix
                ]));
            }
        }

        // Go over each repository, switch each to the correct branch
        foreach ($this as $o_repository) {
            $branch = $this->getValueForType($o_repository->getType(), $o_repository->getName(), $phoundation_branch , $project_branch);
            $o_repository->deleteTag($branch, $remote);
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
    protected function getValueForType(string $type, string $name, string $phoundation, string $project): string
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
}
