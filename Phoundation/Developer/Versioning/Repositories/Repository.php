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
use Phoundation\Data\DataEntries\Traits\TraitDataEntryDescription;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryName;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryPathObject;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryPlatform;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryType;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryUrl;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Developer\Phoundation\Enums\EnumPhoundationType;
use Phoundation\Filesystem\Interfaces\PhoDirectoryInterface;
use Phoundation\Filesystem\Interfaces\PhoPathInterface;


class Repository extends DataEntry
{
    use TraitDataEntryType;
    use TraitDataEntryPlatform;
    use TraitDataEntryUrl;
    use TraitDataEntryPathObject {
      setPath as protected __setPath;
    }
    use TraitDataEntryName;
    use TraitDataEntryDescription;


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
     * Returns a new Repository object for the given $o_path object
     *
     * @param PhoPathInterface $o_path
     * @return static
     */
    public static function newFromPathObject(PhoPathInterface $o_path): static
    {
        $o_repository = Repository::new()
                                  ->setName($o_path->getBasename())
                                  ->setPathObject($o_path);

        return $o_repository;
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
        if ($o_directory->addFile('.git')->exists()) {
            if ($o_directory->addFile('.is-phoundation')->exists()) {
                return EnumPhoundationType::system;
            }

            if ($o_directory->addFile('.is-phoundation-plugins')->exists()) {
                return EnumPhoundationType::plugins;
            }

            if ($o_directory->addFile('.is-phoundation-templates')->exists()) {
                return EnumPhoundationType::templates;
            }

            if ($o_directory->addFile('.is-phoundation-data')->exists()) {
                return EnumPhoundationType::data;
            }

            if ($o_directory->addFile('.is-phoundation-cdn')->exists()) {
                return EnumPhoundationType::cdn;
            }

            if ($o_directory->addFile('config/project/phoundation')->exists()) {
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
        if ($o_directory->addFile('.git')->exists()) {
            return 'git';
        }

        if ($o_directory->addFile('.svn')->exists()) {
            return 'subversion';
        }

        return null;
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
        return $this->__setPath($path)
                    ->setPlatform($this->detectPlatform($this->getPathObject()->getDirectoryObject()))
                    ->setType($this->detectPhoundationType($this->getPathObject()->getDirectoryObject())?->value);
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
