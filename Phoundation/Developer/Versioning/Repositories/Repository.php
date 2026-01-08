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

use Phoundation\Data\DataEntries\DataEntry;
use Phoundation\Data\DataEntries\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntries\Interfaces\IdentifierInterface;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryDescription;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryName;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryPathObject;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryPlatform;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryType;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryUrl;
use Phoundation\Data\Enums\EnumLoadParameters;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Developer\Phoundation\Enums\EnumPhoundationType;
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
use Phoundation\Developer\Versioning\Repositories\Exception\RepositoriesException;
use Phoundation\Developer\Versioning\Repositories\Interfaces\RepositoryInterface;
use Phoundation\Filesystem\Interfaces\PhoDirectoryInterface;
use Phoundation\Filesystem\Interfaces\PhoPathInterface;
use Phoundation\Filesystem\PhoRestrictions;


class Repository extends DataEntry implements RepositoryInterface
{
    use TraitDataObjectGit;
    use TraitDataEntryType;
    use TraitDataEntryPlatform;
    use TraitDataEntryUrl;
    use TraitDataEntryPathObject {
      setPath as protected __setPath;
    }
    use TraitDataEntryName;
    use TraitDataEntryDescription;
    use TraitDataEntryBranch;


    /**
     * Repository class constructor
     *
     * @param IdentifierInterface|false|array|int|string|null $identifier
     * @param EnumLoadParameters|null                         $on_null_identifier
     * @param EnumLoadParameters|null                         $on_not_exists
     */
    public function __construct(IdentifierInterface|false|array|int|string|null $identifier = false, ?EnumLoadParameters $on_null_identifier = null, ?EnumLoadParameters $on_not_exists = null)
    {
        parent::__construct($identifier, $on_null_identifier, $on_not_exists);

        $this->setPermittedColumns(['branch']);
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
    public static function isPhoundation(PhoDirectoryInterface $o_directory): bool
    {
        return (bool) Repository::detectPhoundationType($o_directory);
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

        $this->setPlatform($this->detectPlatform($this->getPathObject()->getDirectoryObject()))
             ->setType($this->detectPhoundationType($this->getPathObject()->getDirectoryObject())?->value)
             ->o_git = new Git($this->getPathObject()->getDirectoryObject());

        return $this;
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
     * Returns the Branches object for this Repository
     *
     * @return BranchesInterface
     */
    public function getBranchesObject(): BranchesInterface
    {
        return Branches::new($this);
    }


    /**
     * Returns true if the specified branch exists in this repository
     *
     * @param string $branch
     *
     * @return bool
     */
    public function branchExists(string $branch): bool
    {
        return $this->getBranchesObject()->keyExists($branch);
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
     * @return TagsInterface
     */
    public function getTagsObject(): TagsInterface
    {
        return Tags::new($this);
    }


    /**
     * Returns the size of the repository in bytes
     *
     * @return int
     */
    public function getSize(): int
    {
        return $this->o_git->getDirectoryObject()->getSize();
    }


    /**
     * Returns the size of the repository in bytes
     *
     * @return int
     */
    public function getGitSize(): int
    {
        return $this->o_git->getDirectoryObject()->addDirectory('.git')->getSize();
    }


    /**
     * Returns the current git branch for this repository
     *
     * @return string
     */
    public function getCurrentBranch(): string
    {
        return $this->o_git->getCurrentBranch();
    }


    /**
     * Returns true if the requested branch exists for this repository
     *
     * @param string $branch
     *
     * @return bool
     */
    public function hasBranch(string $branch): bool
    {
        return array_key_exists($branch, $this->o_git->getBranches());
    }


    /**
     * Creates the specified new branch in this repository
     *
     * @param string $branch
     * @param bool   $reset
     *
     * @return static
     */
    public function createBranch(string $branch, bool $reset = false): static
    {
        $this->o_git->createBranch($branch, $reset);
        return $this;
    }


    /**
     * Creates the specified new branch in this repository
     *
     * @param string|null $branch
     * @param string|null $repository
     * @param bool        $reset
     *
     * @return static
     */
    public function push(?string $branch = null, ?string $repository = null, bool $reset = false): static
    {
        $this->o_git->push($this->selectRemoteRepository($repository), $branch, $reset);
        return $this;
    }


    /**
     * Sets the current git branch for this repository
     *
     * @param string $branch
     *
     * @return Repository
     */
    public function setCurrentBranch(string $branch): static
    {
        $this->o_git->setCurrentBranch($branch);
        return $this;
    }


    /**
     * Returns the configured default remote repository name
     *
     * @return string
     */
    public function getDefaultRemoteRepository(): string
    {
        return config()->getString('development.versioning.repositories.remote', 'origin');
    }


    /**
     * Returns the specified repository, or the configured default
     *
     * @param string|null $repository
     *
     * @return string
     */
    public function selectRemoteRepository(?string $repository): string
    {
        $repository = $repository ?? $this->getDefaultRemoteRepository();

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
     * @return static
     */
    public function loadDetails(): static
    {
        return $this->setBranch($this->o_git->getCurrentBranch());
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
                                             ->setHelpText(tr('The name for this repository'))
                                             ->addValidationFunction(function (ValidatorInterface $o_validator) {
                                                 $o_validator->isUnique();
                                             }))

                      ->add(DefinitionFactory::newSeoName())

                      ->add(DefinitionFactory::newPath()
                                             ->setSize(4)
                                             ->setHelpText(tr('The path where this repository is located')))

                      ->add(DefinitionFactory::newUrl())

                      ->add(DefinitionFactory::newDescription()
                                             ->setHelpText(tr('The description for this repository')));

        return $this;
    }
}
