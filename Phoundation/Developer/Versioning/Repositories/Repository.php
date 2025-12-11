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
use Phoundation\Data\DataEntries\Traits\TraitDataEntryPath;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryPlatform;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryType;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryUrl;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Developer\Phoundation\Enums\EnumPhoundationType;
use Phoundation\Filesystem\Interfaces\PhoDirectoryInterface;


class Repository extends DataEntry
{
    use TraitDataEntryType;
    use TraitDataEntryPlatform;
    use TraitDataEntryUrl;
    use TraitDataEntryPath;
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
        return 'seo_name';
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
        return (bool) Repository::getPhoundationType($o_directory);
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
    public static function getPhoundationType(PhoDirectoryInterface $o_directory): ?EnumPhoundationType
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
     * Sets the available data keys for this entry
     *
     * @param DefinitionsInterface $o_definitions
     *
     * @return static
     */
    protected function setDefinitionsObject(DefinitionsInterface $o_definitions): static
    {
        $o_definitions->add(DefinitionFactory::newName('type')
                                             ->setSize(2)
                                             ->setReadonly(true)
                                             ->setSource([
                                                 'git'
                                             ]))

                      ->add(DefinitionFactory::newName('type')
                                             ->setSize(2)
                                             ->setReadonly(true)
                                             ->setSource([
                                                 'system',
                                                 'plugins',
                                                 'templates',
                                                 'data',
                                                 'cdn',
                                                 'project',
                                             ]))

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
