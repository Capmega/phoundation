<?php

/**
 * Class Repository
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
use Phoundation\Data\DataEntries\DataEntry;
use Phoundation\Data\DataEntries\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntries\Interfaces\IdentifierInterface;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryClass;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryDescription;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryName;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryPathObject;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryPlatform;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryType;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryUrl;
use Phoundation\Data\Enums\EnumLoadParameters;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Developer\Phoundation\Enums\EnumPhoundationClass;
use Phoundation\Developer\Phoundation\Enums\EnumPhoundationType;
use Phoundation\Developer\Phoundation\Exception\NotARepositoryException;
use Phoundation\Developer\Project\Project;
use Phoundation\Developer\Versioning\Git\Branches\Branches;
use Phoundation\Developer\Versioning\Git\Branches\Interfaces\BranchesInterface;
use Phoundation\Developer\Versioning\Git\Git;
use Phoundation\Developer\Versioning\Git\Interfaces\RemotesInterface;
use Phoundation\Developer\Versioning\Git\Interfaces\StatusFilesInterface;
use Phoundation\Developer\Versioning\Git\Remotes;
use Phoundation\Developer\Versioning\Git\StatusFiles;
use Phoundation\Developer\Versioning\Git\Tags\Interfaces\TagsInterface;
use Phoundation\Developer\Versioning\Git\Tags\Tags;
use Phoundation\Developer\Versioning\Git\Traits\TraitDataEntryBranch;
use Phoundation\Developer\Versioning\Git\Traits\TraitDataObjectGit;
use Phoundation\Developer\Versioning\Repositories\Exception\RepositoriesBranchExistsException;
use Phoundation\Developer\Versioning\Repositories\Exception\RepositoriesException;
use Phoundation\Developer\Versioning\Repositories\Exception\RepositoriesChangesException;
use Phoundation\Developer\Versioning\Repositories\Exception\RepositoriesHaveChangesException;
use Phoundation\Developer\Versioning\Repositories\Exception\RepositoriesVersionBranchNotExistsException;
use Phoundation\Developer\Versioning\Repositories\Exception\RepositoriesVersionTagNotExistsException;
use Phoundation\Developer\Versioning\Repositories\Interfaces\RepositoryInterface;
use Phoundation\Filesystem\Exception\DirectoryNotExistsException;
use Phoundation\Filesystem\Interfaces\PhoDirectoryInterface;
use Phoundation\Filesystem\Interfaces\PhoPathInterface;
use Phoundation\Filesystem\PhoRestrictions;
use Phoundation\Utils\Enums\EnumVersionSections;
use Phoundation\Utils\Strings;


class Repository extends DataEntry implements RepositoryInterface
{
    use TraitDataObjectGit;
    use TraitDataEntryType;
    use TraitDataEntryPlatform;
    use TraitDataEntryUrl;
    use TraitDataEntryPathObject {
      setPath as protected __setPath;
    }
    use TraitDataEntryName {
        setName as protected __setName;
    }
    use TraitDataEntryDescription;
    use TraitDataEntryBranch;
    use TraitDataEntryClass;


    /**
     * Repository class constructor
     *
     * @param IdentifierInterface|false|array|int|string|null $identifier
     * @param EnumLoadParameters|null                         $on_null_identifier
     * @param EnumLoadParameters|null                         $on_not_exists
     */
    public function __construct(IdentifierInterface|false|array|int|string|null $identifier = false, ?EnumLoadParameters $on_null_identifier = null, ?EnumLoadParameters $on_not_exists = null)
    {
        $this->setPermittedColumns(['branch', 'class'])
             ->addEventHandler('loaded', function () {
                 try {
                     $this->setBranch($this->getCurrentBranch(true));

                 } catch (NotARepositoryException | DirectoryNotExistsException) {
                     // Whoops, this repository is no longer valid! Continue, but do not read the branch
                 }

                 $this->setClass($this->detectClass()->value)
                      ->is_modified = false;
             });

        parent::__construct($identifier, $on_null_identifier, $on_not_exists);
    }


    /**
     * Returns a new Repository object for the given $o_path object
     *
     * @param PhoPathInterface $o_path
     * @return static
     */
    public static function newFromPathObject(PhoPathInterface $o_path): static
    {
        return Repository::new()
                         ->setName($o_path->getBasename())
                         ->setPathObject($o_path);
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
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getEntryName(): string
    {
        return tr('Repository');
    }


    /**
     * Returns the field that is unique for this object
     *
     * @return string|null
     */
    public static function getUniqueColumn(): ?string
    {
        return 'name';
    }


    /**
     * Returns true if the specified directory is a Phoundation compatible git repository
     *
     * @param PhoDirectoryInterface $o_directory
     *
     * @return bool
     */
    public static function repositoryIsPhoundation(PhoDirectoryInterface $o_directory): bool
    {
        return (bool) Repository::detectPhoundationType($o_directory);
    }


    /**
     * Returns true if this repository is a Phoundation compatible git repository
     *
     * @return bool
     */
    public function isPhoundation(): bool
    {
        return (bool) Repository::detectPhoundationType($this->o_git->getDirectoryObject());
    }


    /**
     * Returns the Phoundation repository type for the specified directory if it is a Phoundation git repository, else will return NULL
     *
     * Possible Phoundation repository types are:
     * system
     * plugins
     * templates
     * data
     * cdn
     * phoundation
     *
     * @param PhoDirectoryInterface $o_directory
     *
     * @return EnumPhoundationType|null
     */
    public static function detectPhoundationType(PhoDirectoryInterface $o_directory): ?EnumPhoundationType
    {
        if ($o_directory->addPath('.git')->exists()) {
            if ($o_directory->addPath('.is-phoundation')->exists()) {
                return EnumPhoundationType::system;
            }

            if ($o_directory->addPath('.is-phoundation-plugins')->exists()) {
                return EnumPhoundationType::plugins;
            }

            if ($o_directory->addPath('.is-phoundation-templates')->exists()) {
                return EnumPhoundationType::templates;
            }

            if ($o_directory->addPath('.is-phoundation-data')->exists()) {
                return EnumPhoundationType::data;
            }

            if ($o_directory->addPath('.is-phoundation-cdn')->exists()) {
                return EnumPhoundationType::cdn;
            }

            if ($o_directory->addPath('config/project/phoundation')->exists()) {
                return EnumPhoundationType::project;
            }
        }

        return null;
    }


    /**
     * Detects and sets the "platform" variable for this class
     *
     * @param PhoDirectoryInterface $o_directory
     * @return string|null
     */
    public static function detectPlatform(PhoDirectoryInterface $o_directory): ?string
    {
        if ($o_directory->addPath('.git')->exists()) {
            return 'git';
        }

        if ($o_directory->addPath('.svn')->exists()) {
            return 'subversion';
        }

        return null;
    }


    /**
     * Returns the "required" property for this object
     *
     * @return string|null
     */
    public function getRequired(): ?string
    {
        return $this->getTypesafe('string', 'required');
    }


    /**
     * Sets the 'required' property for this object
     *
     * @param int|bool $required
     *
     * @return static
     */
    public function setRequired(int|bool $required): static
    {
        return $this->set((bool) $required, 'required');
    }


    /**
     * Sets the path for this object
     *
     * @param string|null  $path
     *
     * @return static
     */
    public function setPath(string|null $path): static
    {
        $this->__setPath($path);

        // Set default restrictions for this Repository object. Repositories can be pretty much anywhere, so we have to assume access to the entire filesystem
        if ($this->getPath()) {
            $this->o_restrictions = PhoRestrictions::new($this->getPath(), true);

        } else {
            $this->o_restrictions = PhoRestrictions::newFilesystemRootObject(true);
        }

        if (!$this->isLoading()) {
            // These are NOT set when loading as when loading, we get these values from the database
            $this->setPlatform($this->detectPlatform($this->getPathObject()->getDirectoryObject()))
                 ->setType($this->detectPhoundationType($this->getPathObject()->getDirectoryObject())?->value);
        }

        $this->o_git = new Git($this->getPathObject()->getDirectoryObject());
        return $this;
    }


    /**
     * Sets the name for this Repository object and ensures it is unique
     *
     * @param string|null $name                The name for this repository
     * @param bool        $set_seo_name [true] If true, will also set the seo_name property
     *
     * @return static
     */
    public function setName(?string $name, bool $set_seo_name = true): static
    {
        // Name might not be be unique, use DataEntry::ensureUnique() to enforce uniqueness
        return $this->__setName($this->ensureUnique($name, 'name'));
    }


    /**
     * Marks this repository as disabled so that it will no longer be used for any action
     *
     * @return static
     */
    public function disable(): static
    {
        return $this->setStatus('disabled');
    }


    /**
     * Marks this repository as enabled so that it can be used again for any action
     *
     * @return static
     */
    public function enable(): static
    {
        return $this->setStatus(null);
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
        $remote = $this->selectRemoteRepository($remote);

        Log::action(ts('Pushing branch ":branch" on ":type" type repository ":repository" to remote ":remote"', [
            ':repository' => $this->getName(),
            ':type'       => $this->getType(),
            ':branch'     => $branch,
            ':remote'     => $remote,
        ]));

        $this->o_git->push($remote, $branch, $set_upstream);
        return $this;
    }


    /**
     * Will pull the changes for the current branch from the specified, or default remote repository
     *
     * @param string|bool|null $remote [null] The remote to pull from, null will pull from the default repository
     * @param string|null      $branch [null] The specific branch to pull, null will pull the current branch
     *
     * @return static
     */
    public function pull(string|bool|null $remote = null, ?string $branch = null): static
    {
        $remote = $this->selectRemoteRepository($remote);

        Log::action(ts('Pulling branch ":branch" on ":type" type repository ":repository" from remote ":remote"', [
            ':repository' => $this->getName(),
            ':type'       => $this->getType(),
            ':branch'     => $branch ?? ($this->getCurrentBranch() . ' (' . ts('current') . ')'),
            ':remote'     => $remote,
        ]));

        $this->o_git->pull($remote, $branch);
        return $this;
    }


    /**
     * Will fetch the changes for the current branch from the specified, or default remote repository
     *
     * @param string|bool|null $remote [null] The remote to fetch from, null will fetch from the default repository
     * @param bool             $all    [true] Will execute git fetch --all, fetch all remotes, except for the ones that has the remote.
     *
     * @return static
     */
    public function fetch(string|bool|null $remote = null, bool $all = true): static
    {
        $remote = $this->selectRemoteRepository($remote);

        if ($remote) {
            Log::action(ts('Executing fetch on repository ":repository" from remote ":remote"', [
                ':repository' => $this->getName(),
                ':remote'     => $remote,
            ]));

        } else {
            Log::action(ts('Executing fetch on repository ":repository" for all remotes', [
                ':repository' => $this->getName(),
            ]));
        }

        $this->o_git->fetch($remote, $all);
        return $this;
    }


    /**
     * Returns the size of the repository working tree and database in bytes
     *
     * @return int
     */
    public function getSize(): int
    {
        return $this->o_git->getDirectoryObject()->getSize();
    }


    /**
     * Returns the size of the repository database in bytes
     *
     * @return int
     */
    public function getGitSize(): int
    {
        return $this->o_git->getDirectoryObject()->addDirectory('.git')->getSize();
    }


    /**
     * Returns the size of the repository working tree in bytes
     *
     * @return int
     */
    public function getWorkingTreeSize(): int
    {
        return $this->getSize() - $this->getGitSize();
    }


    /**
     * Returns the Status object for this Repository
     *
     * @return StatusFilesInterface
     */
    public function getStatusObject(): StatusFilesInterface
    {
        return StatusFiles::new($this);
    }


    /**
     * Returns the Remotes class object for this Repository
     *
     * @return RemotesInterface
     */
    public function getRemotesObject(): RemotesInterface
    {
        return Remotes::new($this);
    }


    /**
     * Returns the configured default remote repository name
     *
     * @return string
     */
    public function getConfigRemoteRepository(): string
    {
        return config()->getString('developer.versioning.repositories.remote', 'origin');
    }


    /**
     * Returns the specified repository, or the configured default
     *
     * @param string|bool|null $repository
     *
     * @return string|null
     */
    public function selectRemoteRepository(string|bool|null $repository = null): ?string
    {
        if (is_bool($repository)) {
            if ($repository === false) {
                return null;
            }

            $repository = null;
        }

        $repository = $repository ?? $this->getConfigRemoteRepository();

        if (array_key_exists($repository, $this->getRemoteRepositories())) {
            return $repository;
        }

        throw new RepositoriesException(ts('Cannot select remote ":remote" for repository ":repository", the remote does not exist', [
            ':repository' => $this->getDisplayName(),
            ':remote'     => $repository
        ]));
    }


    /**
     * Returns the available remote repositories for this path
     *
     * @return array
     */
    public function getRemoteRepositories(): array
    {
        return $this->o_git->getRemotes();
    }


    /**
     * Returns the Branches object for this Repository
     *
     * @return BranchesInterface
     */
    public function getBranchObject(): BranchesInterface
    {
        return Branches::new($this);
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
        Log::action(ts('Selecting branch ":branch" for ":type" type repository ":repository"', [
            ':branch'     => $branch,
            ':type'       => $this->getType(),
            ':repository' => $this->getName()
        ]));

        $this->o_git->selectBranch($branch, $auto_create, $upstream);
        return $this;
    }


    /**
     * Returns the current git branch for this repository
     *
     * @param bool $return_if_detached [false] If true, will return the selected branch, even if it is not a branch
     *
     * @return string|null
     */
    public function getCurrentBranch(bool $return_if_detached = false): ?string
    {
        return $this->o_git->getSelectedBranch($return_if_detached);
    }


    /**
     * Returns the current git branch for this repository
     *
     * @return string|null
     */
    public function getSelectedTag(): ?string
    {
        return $this->o_git->getSelectedTag();
    }


    /**
     * Returns true if the current git branch for this repository is equal to the specified branch
     *
     * @param string $branch
     *
     * @return bool
     */
    public function isOnBranch(string $branch): bool
    {
        return $this->o_git->getSelectedBranch() === $branch;
    }


    /**
     * Throws a RepositoriesException if the repository is using the specified branch
     *
     * @param string $branch
     * @param string $action
     *
     * @return static
     */
    public function checkIsNotOnBranch(string $branch, string $action): static
    {
        if ($this->isOnBranch($branch)) {
            throw RepositoriesException::new(ts('Cannot perform action ":action" on branch ":branch" of repository ":repository", the repository is using the branch right now', [
                ':action'     => $action,
                ':branch'     => $branch,
                ':repository' => $this->getDisplayName()
            ]))->makeWarning();
        }

        return $this;
    }


    /**
     * Returns true if the requested branch exists for this repository
     *
     * @param string $branch                 The branch to search for
     * @param bool   $check_tags_too [true]  If true will search for the branch name in the tags list as well
     * @param bool   $auto_create    [false] If true, will automatically create the branch on each repository where it
     *                                       does not yet exist
     *
     * @return bool
     */
    public function branchExists(string $branch, bool $check_tags_too = true, bool $auto_create = false): bool
    {
        $exists = array_key_exists($branch, $this->o_git->getBranches()) or ($check_tags_too and array_key_exists($branch, $this->o_git->getTags()));

        if (!$exists) {
            // Branch does not yet exist for this repository, create it automatically?
            if ($auto_create) {
                $this->createBranch($branch);
                return true;
            }
        }

        return $exists;
    }


    /**
     * Returns true if this repository has the requested suffix or version branch available
     *
     * @param string|null $version                The version branch that will be checked if it exists. If NULL, will
     *                                            not check for this version
     * @param string      $branch                 The branch that will be checked if it exists.
     * @param bool        $check_tags_too [false] If true will also check in the tags list
     * @param bool        $check_all      [false] If true will also check remote repositories
     *
     * @return bool
     */
    public function hasBranchOrVersionBranch(?string $version, string $branch, bool $check_tags_too = false, bool $check_all = false): bool
    {
        if ($version === null) {
            return $this->branchExists($branch, $check_tags_too, $check_all);
        }

        return $this->branchExists($version, $check_tags_too, $check_all) or $this->branchExists($branch, $check_tags_too, $check_all);
    }


    /**
     * Creates the specified new branch in this repository
     *
     * @param string           $branch               The branch to create from the currently selected branch
     * @param bool             $reset        [false] If true, will first reset the repository before creating the new branch
     * @param string|true|null $remote       [null]  If true or string value, will push the new branch to the default (for true) or specified remote
     * @param bool             $set_upstream [false] If true, will set the remote as the default upstream repository
     *
     * @return static
     */
    public function createBranch(string $branch, bool $reset = false, string|true|null $remote = null, bool $set_upstream = false): static
    {
        if ($this->branchExists($branch)) {
            throw new RepositoriesBranchExistsException(ts('Cannot create branch ":branch" on repository ":repository", the branch already exists', [
                ':branch'     => $branch,
                ':repository' => $this->getName()
            ]));
        }

        $this->o_git->createBranch($branch, $reset);

        if ($remote or $set_upstream) {
            $this->push($this->selectRemoteRepository($remote), $branch, $set_upstream);
        }

        return $this;
    }


    /**
     * Deletes the specified branch from this repository (and optionally the selected remote as well)
     *
     * @param string      $branch        The auto-branch suffix to delete
     * @param string|bool $remote [true] If string value or true, will delete the branch from the default (for true) or specified remote repository
     *
     * @return static
     */
    public function deleteBranch(string $branch, string|bool $remote = false): static
    {
        // Select what remote to use, if any
        $remote = $this->selectRemoteRepository($remote);

        // Only delete the branch if the repository has it
        if ($this->branchExists($branch)) {
            // Only delete the branch if it is not selected
            if ($this->isOnBranch($branch) and !FORCE) {
                throw new RepositoriesException(ts('Cannot delete branch ":branch" from repository ":repository", it has the branch selected', [
                    ':branch'     => $branch,
                    ':repository' => $this->getName(),
                ]));
            }

            // Delete the branch locally
            Log::action(ts('Deleting branch ":branch" from ":type" type repository ":repository"', [
                ':branch'     => $branch,
                ':type'       => $this->getType(),
                ':repository' => $this->getName()
            ]));

            $this->o_git->deleteBranch($branch);

        } else {
            Log::warning(ts('Not deleting branch ":branch" from repository ":repository", the branch does not exist', [
                ':branch'     => $branch,
                ':repository' => $this->getName()
            ]), 4);
        }

        if ($remote) {
            // Delete the branch from the remote repository as well
            Log::action(ts('Deleting branch ":branch" from repository ":repository" remote ":remote"', [
                ':branch'     => $branch,
                ':remote'     => $remote,
                ':repository' => $this->getName()
            ]));

            $this->o_git->deleteBranchRemote($branch, $remote);
        }

        return $this;
    }


    /**
     * Checks if this repository has the requested suffix or version branch available, and if not, throws a
     * RepositoriesHaveChangesException
     *
     * @param string|null $version
     * @param string      $branch
     * @param bool        $check_tags_too [false]
     * @param bool        $check_all      [false] If true will also check remote repositories
     * @return static
     * @throws RepositoriesHaveChangesException
     */
    public function checkHasBranchOrVersionBranch(?string $version, string $branch, bool $check_tags_too = true, bool $check_all = false): static
    {
        if (!$this->hasBranchOrVersionBranch($version, $branch, $check_tags_too, $check_all)) {
            if ($branch and ($version !== $branch)) {
                throw RepositoriesVersionBranchNotExistsException::new(ts('The repository ":repository" does not have the required suffix branch ":suffix" nor version branch ":version"', [
                    ':repository' => $this->getName(),
                    ':suffix'     => $branch,
                    ':version'    => $version
                ]))->addData([
                    'repository' => $this->getDisplayName()
                ]);
            }

            throw RepositoriesVersionBranchNotExistsException::new(ts('The repository ":repository" does not have the required version branch ":version"', [
                ':repository' => $this->getName(),
                ':version'    => $version
            ]))->addData([
                'repository' => $this->getDisplayName()
            ]);
        }

        return $this;
    }


    /**
     * Returns the Tags object for this Repository
     *
     * @return TagsInterface
     */
    public function getTagsObject(): TagsInterface
    {
        return Tags::new($this);
    }


    /**
     * Returns true if the specified tag exists in this repository
     *
     * @param string $tag                        The tag to test for existence
     * @param bool   $check_branches_too [false] If true will check if the tag exists as a branch name as well
     *
     * @return bool
     */
    public function tagExists(string $tag, bool $check_branches_too = false): bool
    {
        return $this->getTagsObject()->keyExists($tag) or ($check_branches_too and array_key_exists($tag, $this->o_git->getTags()));
    }


    /**
     * Returns true if this repository has the requested suffix or version tag available
     *
     * @param string $version                    The tag version to check
     * @param string $tag                        The tag label to check
     * @param bool   $check_branches_too [false] If true will also check in the branches list
     *
     * @return bool
     */
    public function hasTagOrVersionTag(string $version, string $tag, bool $check_branches_too = false): bool
    {
        return $this->tagExists($version, $check_branches_too) or $this->tagExists($tag, $check_branches_too);
    }


    /**
     * Creates the specified tag for this repository
     *
     * @param string      $tag             The name for the tag
     * @param string|null $message [NULL]  The optional message for the tag. If specified, will create an annotated tag
     *                                     automatically
     * @param bool|null   $signed  [FALSE] If true
     * @return static
     */
    public function createTag(string $tag, ?string $message = null, ?bool $signed = false): static
    {
        if ($this->tagExists($tag)) {
            throw new RepositoriesBranchExistsException(ts('Cannot create tag ":tag" on repository ":repository", the tag already exists', [
                ':branch'     => $tag,
                ':repository' => $this->getName()
            ]));
        }

        $this->o_git->createTag($tag, $message, $signed);
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
        $this->o_git->createLightweightTag($name);
        return $this;
    }


    /**
     * Returns true if this repository has a branch selected
     *
     * @return bool
     */
    public function hasTypeBranchSelected(): bool
    {
        return $this->o_git->hasTypeBranchSelected();
    }


    /**
     * Returns true if this repository has a tag selected
     *
     * @return bool
     */
    public function hasTypeTagSelected(): bool
    {
        return $this->o_git->hasTypeTagSelected();
    }


    /**
     * Returns true if this repository has the specified branch selected
     *
     * @param string $branch The branch that should be selected for this repository
     *
     * @return bool
     */
    public function hasBranchSelected(string $branch): bool
    {
        return $this->o_git->hasBranchSelected($branch);
    }


    /**
     * Returns true if this repository has the specified tag selected
     *
     * @param string $tag The tag that should be selected for this repository
     *
     * @return bool
     */
    public function hasTagSelected(string $tag): bool
    {
        return $this->o_git->hasTagSelected($tag);
    }


    /**
     * Returns true if this repository has changes on the working tree
     *
     * @return bool
     */
    public function hasChanges(): bool
    {
        return (bool) $this->getStatusObject()->scanChanges()->getCount();
    }


    /**
     * Throws a RepositoriesSomeHaveChangesException if not all repositories have the specified branch
     *
     * @param string $action
     *
     * @return static
     */
    public function checkHasNoChanges(string $action): static
    {
        if (!$this->hasChanges()) {
            throw RepositoriesChangesException::new(ts('Cannot perform action ":action" on repository ":repository", the repository has changes', [
                ':action'     => $action,
                ':repository' => $this->getName(),
            ]))->addHint(ts('To fix this issue, please first commit the changes, and try again'));
        }

        return $this;
    }


    /**
     * Returns true if the current git tag for this repository is equal to the specified tag
     *
     * @param string $tag
     *
     * @return bool
     */
    public function isOnTag(string $tag): bool
    {
        return $this->o_git->getSelectedTag() === $tag;
    }


    /**
     * Throws a RepositoriesException if the repository is using the specified tag
     *
     * @param string $tag
     * @param string $action
     *
     * @return Repository
     */
    public function checkIsOnTag(string $tag, string $action): static
    {
        if ($this->isOnBranch($tag)) {
            throw RepositoriesException::new(ts('Cannot perform action ":action" on tag ":tag" of repository ":repository", the repository is using the tag right now', [
                ':action'     => $action,
                ':tag'        => $tag,
                ':repository' => $this->getDisplayName()
            ]))->makeWarning();
        }

        return $this;
    }


    /**
     * Creates the specified new tag in this repository
     *
     * @param string $tag
     *
     * @return static
     */
    public function selectTag(string $tag): static
    {
        Log::action(ts('Selecting tag ":tag" for ":type" type repository ":repository"', [
            ':tag'     => $tag,
            ':type'       => $this->getType(),
            ':repository' => $this->getName()
        ]));

        $this->o_git->selectTag($tag);
        return $this;
    }


    /**
     * Deletes the specified tag from this repository (and optionally the selected remote as well)
     *
     * @param string      $tag
     * @param string|bool $remote_repository
     *
     * @return static
     */
    public function deleteTag(string $tag, string|bool $remote_repository = false): static
    {
        // Select what remote to use, if any
        $remote_repository = $this->selectRemoteRepository($remote_repository);

        // Only delete the tag if the repository has it
        if ($this->tagExists($tag)) {
            // Only delete the tag if it is not selected
            if ($this->isOnTag($tag) and !FORCE) {
                throw new RepositoriesException(ts('Cannot delete tag ":tag" from repository ":repository", it has the tag selected', [
                    ':tag'     => $tag,
                    ':repository' => $this->getName(),
                ]));
            }

            // Delete the tag locally
            Log::action(ts('Deleting tag ":tag" from ":type" type repository ":repository"', [
                ':tag'     => $tag,
                ':type'       => $this->getType(),
                ':repository' => $this->getName()
            ]));

            $this->o_git->deleteTag($tag);

        } else {
            Log::warning(ts('Not deleting tag ":tag" from repository ":repository", the tag does not exist', [
                ':tag'        => $tag,
                ':repository' => $this->getName()
            ]), 4);
        }

        if ($remote_repository) {
            // Delete the tag from the remote repository as well
            Log::action(ts('Deleting tag ":tag" from repository ":repository" remote ":remote"', [
                ':tag'     => $tag,
                ':remote'     => $remote_repository,
                ':repository' => $this->getName()
            ]));

            $this->o_git->deleteTagRemote($tag, $remote_repository);
        }

        return $this;
    }


    /**
     * Upgrades the revision version section of this repository
     *
     * @param int|null $increase [1] The number to increase the release part of the version
     *
     * @return static
     */
    public function upgradeRevision(?int $increase = 1): static
    {
        return $this->upgrade(EnumVersionSections::revision, $increase ?? 1);
    }


    /**
     * Upgrade this repository
     *
     * @param EnumVersionSections $section      The section of the version to upgrade
     * @param int|null            $increase [1] The number to increase the release part of the version
     *
     * @return static
     */
    public function upgrade(EnumVersionSections $section, ?int $increase = 1): static
    {
showdie();
        $this->hasBranchOrVersionBranch();

        switch ($this->detectClass()) {
            case EnumPhoundationClass::phoundation:


            case EnumPhoundationClass::project:
            case EnumPhoundationClass::cdn:
        }

        return $this;
    }


    /**
     * Returns true if this repository is currently on a version branch
     *
     * @return bool
     */
    public function isOnVersionBranch(): bool
    {
        return Strings::isVersion(Strings::until($this->getBranch(), '-'), short_version: true);
    }


    /**
     * Returns true if this repository is currently on a version branch that has a suffix
     *
     * @return bool
     */
    public function isOnVersionSuffixBranch(): bool
    {
        return Strings::isVersion(Strings::until($this->getBranch(), '-')) and Strings::from($this->getBranch(), '-');
    }


    /**
     * Returns true if this repository is currently on a version branch
     *
     * @return static
     * @throws RepositoriesException
     */
    public function checkIsOnVersionBranch(): static
    {
        if (!$this->isOnVersionBranch()) {
            throw new RepositoriesException(ts('Cannot get suffix for repository ":repository" branch ":branch", the branch is not on a version branch', [
                ':repository' => $this->getName(),
                ':branch'     => $this->getBranch(),
            ]));
        }

        return $this;
    }


    /**
     * Returns true if this repository is currently on a version branch
     *
     * @return bool
     */
    public function isOnCorrectVersionBranch(): bool
    {
        return $this->isOnVersionBranch() and $this->hasBranchSelected(Project::getVersion());
    }


    /**
     * Returns true if this repository is currently on a version branch
     *
     * @return static
     * @throws RepositoriesException
     */
    public function checkIsOnCorrectVersionBranch(): static
    {
        if (!$this->isOnVersionBranch()) {
            throw new RepositoriesException(ts('Cannot get suffix for repository ":repository" branch ":branch", the branch is not on the (required) correct version branch ":version"', [
                ':repository' => $this->getName(),
                ':branch'     => $this->getBranch(),
                ':version'    => Project::getVersion(),
            ]));
        }

        return $this;
    }


    /**
     * Returns the version (without the suffix) for this repository version branch, if any.
     *
     * If the current branch is not a version branch, NULL will be returned
     *
     * @return string|null
     */
    public function getCurrentVersion(): ?string
    {
        if ($this->isOnVersionBranch()) {
            return $this->getBranch();
        }

        return null;
    }


    /**
     * Returns the suffix for this repository version branch, if any. Will return NULL if on a suffix less branch
     *
     * If the current branch is not a version branch, a RepositoriesException will be thrown
     *
     * @param bool $require_correct_version
     * @return string|null
     * @throws RepositoriesException
     */
    public function getCurrentSuffix(bool $require_correct_version = false): ?string
    {
        if ($require_correct_version) {
            $this->checkIsOnCorrectVersionBranch();

        } else {
            $this->checkIsOnVersionBranch();
        }

        return get_null(Strings::from($this->getBranch(), '-', needle_required: true));
    }


    /**
     * Returns the platform for this repository
     *
     * @return EnumPhoundationClass
     */
    public function detectClass(): EnumPhoundationClass
    {
        switch ($this->getName()) {
            case 'phoundation':
                // no break;
            case 'phoundation-data':
                // no break;
            case 'phoundation-plugins':
                // no break;
            case 'phoundation-templates':
                return EnumPhoundationClass::phoundation;
        }

        if ($this->isType('cdn')) {
            return EnumPhoundationClass::cdn;
        }

        return EnumPhoundationClass::project;
    }


    /**
     * Returns true if the type for this object is the same as the specified type
     *
     * @param EnumPhoundationType|string $type
     *
     * @return bool
     */
    public function isType(EnumPhoundationType|string $type): bool
    {
        if ($type instanceof EnumPhoundationType) {
            $type = $type->value;
        }

        return $this->getType() === $type;
    }


    /**
     * Returns true if the platform for this object is the same as the specified platform
     *
     * @param EnumPhoundationClass|string $class
     *
     * @return bool
     */
    public function isClass(EnumPhoundationClass|string $class): bool
    {
        if ($class instanceof EnumPhoundationClass) {
            $class = $class->value;
        }

        return $this->detectClass() === $class;
    }


    /**
     * Checks if this repository has the requested suffix or version tag available, and if not, throws a RepositoriesHaveChangesException
     *
     * @param string $version
     * @param string $tag
     * @param bool   $check_tags_too [false] If true will also check in the tags list
     * @return static
     */
    public function checkHasSuffixOrVersionTag(string $version, string $tag, bool $check_tags_too = true): static
    {
        if (!$this->hasTagOrVersionTag($version, $tag, $check_tags_too)) {
            if ($tag and ($version !== $tag)) {
                throw RepositoriesVersionTagNotExistsException::new(ts('The repository ":repository" does not have the required suffix tag ":suffix" nor version tag ":version"', [
                    ':repository' => $this->getName(),
                    ':suffix'     => $tag,
                    ':version'    => $version
                ]))->addData([
                    'repository' => $this->getDisplayName()
                ]);
            }

            throw RepositoriesVersionTagNotExistsException::new(ts('The repository ":repository" does not have the required version tag ":version"', [
                ':repository' => $this->getName(),
                ':version'    => $version
            ]))->addData([
                'repository' => $this->getDisplayName()
            ]);
        }

        return $this;
    }


    /**
     * Update the version suffix branch from its version base branch
     *
     * @return static
     */
    public function updateVersionBranch(): static
    {
        Log::action(ts('Updating repository ":repository" branch ":branch" from version ":version"', [
            ':repository' => $this->getName(),
            ':branch'     => $this->getCurrentBranch(),
            ':version'    => $this->getCurrentVersion(),
        ]));
show($this->getCurrentBranch());
show($this->getCurrentVersion());
showdie();
        $this->o_git->merge($this->getCurrentVersion());
        return $this;
    }


    /**
     * Sets the available data keys for this entry
     *
     * @param DefinitionsInterface $o_definitions
     *
     * @return static
     */
    protected function setDefinitionsObject(DefinitionsInterface $o_definitions): static
    {
        $o_definitions->add(DefinitionFactory::newName('platform')
                                             ->setSize(2)
                                             ->setLabel(tr('Platform'))
                                             ->setReadonly(true)
                                             ->setSource([
                                                 'git'        => tr('Git'),
                                                 'subversion' => tr('Subversion'),
                                             ]))

                      ->add(DefinitionFactory::newName('type')
                                             ->setSize(2)
                                             ->setLabel(tr('Type'))
                                             ->setReadonly(true)
                                             ->setSource([
                                                 'system'    => tr('System'),
                                                 'plugins'   => tr('Plugins'),
                                                 'templates' => tr('Templates'),
                                                 'data'      => tr('Data'),
                                                 'cdn'       => tr('CDN'),
                                                 'project'   => tr('Project'),
                                             ]))

                      ->add(DefinitionFactory::newName('required')
                                             ->setSize(1)
                                             ->setLabel(tr('Required')))

                      ->add(DefinitionFactory::newName()
                                             ->setSize(4)
                                             ->setHelpText(tr('The name for this repository')))

                      ->add(DefinitionFactory::newSeoName())

                      ->add(DefinitionFactory::newPath()
                                             ->setSize(4)
                                             ->setHelpText(tr('The path where this repository is located'))
                                             ->addValidationFunction(function (ValidatorInterface $o_validator) {
                                                 $o_validator->isUnique();
                                             }))

                      ->add(DefinitionFactory::newUrl())

                      ->add(DefinitionFactory::newDescription()
                                             ->setHelpText(tr('The description for this repository')));

        return $this;
    }
}
