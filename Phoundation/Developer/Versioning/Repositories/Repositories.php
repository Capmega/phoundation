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
use Phoundation\Data\DataEntries\Interfaces\IdentifierInterface;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Data\Traits\TraitDataResultsWithPermissionDenied;
use Phoundation\Developer\Phoundation\Exception\NotARepositoryException;
use Phoundation\Developer\Phoundation\Exception\RepositorySynchronizationException;
use Phoundation\Developer\Project\Project;
use Phoundation\Developer\Versioning\Git\Traits\TraitGitProcess;
use Phoundation\Developer\Versioning\Repositories\Exception\RepositoriesException;
use Phoundation\Developer\Versioning\Repositories\Exception\RepositoriesHaveChangesException;
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
     * Load the Repositories list data from the database, and optionally adds detail directly from the repositories
     *
     * @param IdentifierInterface|int|array|string|null $identifiers
     * @param bool                                      $like
     * @param bool                                      $details
     *
     * @return static
     */
    public function load(IdentifierInterface|int|array|string|null $identifiers = null, bool $like = false, bool $details = false): static
    {
        parent::load($identifiers, $like);

        // Load detail information directly from the repositories themselves?
        if ($details) {
            foreach ($this as $o_repository) {
                $o_repository->loadDetails();
            }
        }

        return $this;
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
     * Returns the entry with the specified identifier
     *
     * @param Stringable|string|float|int $key
     * @param mixed                       $default
     * @param bool|null                   $exception
     *
     * @return RepositoryInterface|null
     */
    #[ReturnTypeWillChange] public function get(Stringable|string|float|int $key, mixed $default = null, ?bool $exception = null): ?RepositoryInterface
    {
        return parent::get($key, $default, $exception);
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
throw new UnderConstructionException();
        }

        return $this;
    }


    /**
     * Returns true when any of the known repositories has changes
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
     * @param bool $readable
     *
     * @return IteratorInterface
     */
    public function getStatusObject(bool $readable = false): IteratorInterface
    {
        $return = [];

        foreach (Repositories::new()->load() as $o_repository) {
            $o_status = $o_repository->getStatusObject()->scanChanges()->getSource();

            foreach ($o_status as $file => $status) {
                if ($readable) {
                    $return[$file] = [
                        'repository' => $o_repository->getName(),
                        'branch'     => $o_repository->getCurrentBranch(),
                        'file'       => $file,
                        'status'     => $status->getReadableStatus()
                    ];

                } else {
                    $return[$file] = [
                        'repository' => $o_repository->getName(),
                        'branch'     => $o_repository->getCurrentBranch(),
                        'file'       => $file,
                        'status'     => $status->getStatus()
                    ];
                }
            }
        }

        return Iterator::new($return);
    }


    /**
     * Gets the project repository object, verifies its on the correct branch, and returns it
     *
     * @param string $action
     *
     * @return static
     */
    protected function verifyProjectRepositoryVersion(string $action): static
    {
        // Check the current main project repository first
        // The repository version MUST match the configured version
        try {
            $o_repository = $this->get(Project::getDirectoryName());
            $branch       = $o_repository->getCurrentBranch();

            if (!preg_match('/^\d{1,3}\.\d{1,3}$/', $branch)) {
                if (!preg_match('/^\d{1,3}\.\d{1,3}-[a-z0-9-]$/i', $branch)) {
                    $e = RepositorySynchronizationException::new(ts('Cannot perform action ":action" on repositories, the currently selected project branch ":version" is not valid', [
                        ':version' => $branch,
                        ':action'  => $action
                    ]))->addHint(ts('In order to synchronize branches amongst all project repositories, the current project branch MUST be either MAJOR.MINOR or MAJOR.MINOR-SUFFIX'))
                       ->makeWarning();
                }
            }

            $version = Project::getVersion();
            $version = Strings::untilReverse($version, '.');

            if (!str_starts_with($branch, $version)) {
                $e = RepositorySynchronizationException::new(ts('Cannot perform action ":action" on repositories, the project version ":version" does not match the project repository branch ":branch"', [
                    ':action'  => $action,
                    ':branch'  => $branch,
                    ':version' => Project::getVersion(),
                ]))->addHint(ts('In order to synchronize branches amongst all project repositories, please select the branch ":version" or ":version-SUFFIX"', [
                    ':version' => $branch,
                ]))->makeWarning();
            }

            if (isset($e)) {
                if (!$o_repository->hasBranch($version)) {
                    throw $e;
                }

                Log::warning(ts('Project branch ":branch" either has an invalid value or does not match the current project version ":version", selecting correct branch to be able to continue', [
                    ':branch'  => $o_repository->getCurrentBranch(),
                    ':version' => $version
                ]));

                $o_repository->setCurrentBranch($version);
            }

        } catch (NotExistsException) {
            throw RepositorySynchronizationException::new(ts('Cannot perform action ":action" on repositories, could not find the project repository', [
                ':action' => $action
            ]))->addHint(ts('Maybe you need to run "./pho developer repositories scan" first?'));
        }

        return $this;
    }


    /**
     * Deletes the specified branch from all known repositories
     *
     * @param string $suffix
     * @param bool   $remote
     *
     * @return static
     */
    public function deleteBranch(string $suffix, bool $remote = true): static
    {
        $project_branch     = Project::getVersion();
        $project_branch     = Strings::untilReverse($project_branch, '.') . ($suffix ? '-' . $suffix : null);
        $phoundation_branch = Project::getPhoundationRequiredVersion();
        $phoundation_branch = Strings::untilReverse($phoundation_branch, '.') . ($suffix ? '-' . $suffix : null);

        $this->verifyProjectRepositoryVersion()
             ->checkAnyIsOnBranch($phoundation_branch, ts('delete branch')) // TODO This is not correct, MAYBE a phoundation repository could have the same version branch as the project repository? Improve this
             ->checkAnyIsOnBranch($project_branch    , ts('delete branch'));

        if ($this->hasChanges()) {
            if (!FORCE) {
                throw new RepositoriesHaveChangesException(ts('Cannot branch ":branch" from repositories, one or more repositories has changes', [
                    ':branch' => $suffix
                ]));
            }
        }

        // Go over each repository, switch each to the correct branch
        foreach ($this as $o_repository) {
            switch ($o_repository->getType()) {
                case 'project':
                    // no break

                case 'data':
                    $branch = $project_branch;
                    break;

                default:
                    $branch = $phoundation_branch;
            }

            // Delete the branch, if exists
            if ($o_repository->hasBranch($branch)) {
                Log::warning(ts('Deleting branch ":branch" for ":type" repository ":repository"', [
                    ':branch'     => $branch,
                    ':type'       => $o_repository->getType(),
                    ':repository' => $o_repository->getName(),
                ]));

                $o_repository->deleteBranch($branch);

                if ($remote) {
                    // Delete the branch from the default remote repository as well

                }
            }
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
     * Throws a RepositoriesException if any of the known repositories currently has the specified branch selected
     *
     * NOTE: Will NOT throw the exception when running in FORCE mode
     *
     * @param string $branch
     * @param string $action
     *
     * @return static
     * @throws RepositoriesException
     */
    public function checkAnyIsOnBranch(string $branch, string $action): static
    {
        foreach ($this as $o_repository) {
            $o_repository->checkIsOnBranch($branch, $action);
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
    public function selectBranch(?string $suffix): static
    {
        $project_branch     = Project::getVersion();
        $project_branch     = Strings::untilReverse($project_branch, '.') . ($suffix ? '-' . $suffix : null);
        $phoundation_branch = Project::getPhoundationRequiredVersion();
        $phoundation_branch = Strings::untilReverse($phoundation_branch, '.') . ($suffix ? '-' . $suffix : null);

        if ($this->hasChanges()) {
            if (!FORCE) {
                throw new RepositoriesHaveChangesException(ts('Cannot select branches on repositories, one or more repositories has changes'));
            }
        }

        $this->verifyProjectRepositoryVersion(ts('select branch'));

        // Go over each repository, switch each to the correct branch
        foreach ($this as $o_repository) {
            switch ($o_repository->getType()) {
                case 'project':
                    // no break

                case 'data':
                    $branch = $project_branch;
                    break;

                default:
                    $branch = $phoundation_branch;
            }

            // Can we switch to the branch, or do we have to create and push it first?
            if ($o_repository->hasBranch($branch)) {
                Log::action(ts('Selecting branch ":branch" for ":type" repository ":repository"', [
                    ':branch'     => $branch,
                    ':type'       => $o_repository->getType(),
                    ':repository' => $o_repository->getName(),
                ]));

                $o_repository->setCurrentBranch($branch);

            } else {
                Log::action(ts('Creating and pushing required branch ":branch" for ":type" repository ":repository"', [
                    ':branch'     => $branch,
                    ':type'       => $o_repository->getType(),
                    ':repository' => $o_repository->getName(),
                ]));

                $o_repository->createBranch($branch)
                             ->push($branch);
            }
        }

        return $this;
    }
}
