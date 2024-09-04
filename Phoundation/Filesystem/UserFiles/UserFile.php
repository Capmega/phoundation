<?php

/**
 * Class UserFile
 *
 * This class represents a single user file
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Filesystem\UserFiles;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryDescription;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Filesystem\FsDirectory;
use Phoundation\Web\Html\Enums\EnumInputType;


class UserFile extends DataEntry
{
    use TraitDataEntryDescription;


    /**
     * Role class constructor
     *
     * @param array|DataEntryInterface|string|int|null $identifier
     * @param bool|null                                $meta_enabled
     * @param bool                                     $init
     */
    public function __construct(array|DataEntryInterface|string|int|null $identifier = null, ?bool $meta_enabled = null, bool $init = true)
    {
        return parent::__construct($identifier, $meta_enabled, $init);
    }


    /**
     * Returns the table name used by this object
     *
     * @return string|null
     */
    public static function getTable(): ?string
    {
        return 'filesystem_user_files';
    }


    /**
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getDataEntryName(): string
    {
        return tr('User file');
    }


    /**
     * Returns the field that is unique for this object
     *
     * @return string|null
     */
    public static function getUniqueColumn(): ?string
    {
        return 'file';
    }


    /**
     * Sets the available data keys for this entry
     *
     * @param DefinitionsInterface $definitions
     */
    protected function setDefinitions(DefinitionsInterface $definitions): void
    {
        $definitions->add(DefinitionFactory::getFile($this, FsDirectory::newUserFilesObject())
                        ->setOptional(false)
                        ->setSize(12)
                        ->setHelpText(tr('Filename'))
                        ->addValidationFunction(function (ValidatorInterface $validator) {
                            $validator->isUnique();
                        }))

                    ->add(DefinitionFactory::getFile($this, FsDirectory::newUserFilesObject(), column: 'seo_file')
                        ->setOptional(false)
                        ->setRender(false)
                        ->setSize(12)
                        ->addValidationFunction(function (ValidatorInterface $validator) {
                            $validator->isUnique();
                        }))

                    ->add(DefinitionFactory::getUsersId($this)
                        ->setOptional(false)
                        ->setSize(4)
                        ->setLabel('Shared from')
                        ->setHelpText(tr('The real owner of this file who shared it')))

                    ->add(DefinitionFactory::getUsersId($this)
                        ->setOptional(false)
                        ->setSize(4)
                        ->setLabel('Owner')
                        ->setHelpText(tr('The owner of this file')))

                    ->add(DefinitionFactory::getDatabaseId($this, 'uploads_id')
                        ->setOptional(true)
                        ->setRender(false)
                        ->setSize(4))

                    ->add(DefinitionFactory::getCode($this, 'extension')
                        ->setOptional(false)
                        ->setInputType(EnumInputType::code)
                        ->setSize(2)
                        ->setMaxlength(16)
                        ->setLabel('Extension')
                        ->setHelpText(tr('The extension for this file')))

                    ->add(DefinitionFactory::getVariable($this, 'primary_part')
                        ->setReadonly(true)
                        ->setOptional(true)
                        ->setSize(4)
                        ->setLabel('Primary mimetype')
                        ->setMaxlength(32))

                    ->add(DefinitionFactory::getVariable($this, 'secondary_part')
                        ->setReadonly(true)
                        ->setOptional(true)
                        ->setSize(4)
                        ->setLabel('Secondary mimetype')
                        ->setMaxlength(96))

                    ->add(DefinitionFactory::getVariable($this, 'mimetype')
                        ->setOptional(false)
                        ->setReadonly(true)
                        ->setSize(4)
                        ->setMaxlength(128)
                        ->setLabel('Mimetype')
                        ->setHelpText(tr('The mimetype for this file'))
                        ->addValidationFunction(function (ValidatorInterface $validator) {
                            $validator->matchesRegex('/\w+\/[a-z0-9-.]+/');
                        }))

                    ->add(DefinitionFactory::getHash($this))

                    ->add(DefinitionFactory::getNumber($this, 'size')
                        ->setOptional(false)
                        ->setReadonly(true)
                        ->setSize(4)
                        ->setMin(0)
                        ->setMax(PHP_INT_MAX)
                        ->setLabel('Size')
                        ->setHelpText(tr('The size of this file in bytes')))

                    ->add(DefinitionFactory::getNumber($this, 'sections')
                        ->setOptional(false)
                        ->setReadonly(true)
                        ->setSize(4)
                        ->setMin(0)
                        ->setMax(10_000)
                        ->setLabel('Sections')
                        ->setHelpText(tr('The amount of sections in the file path')))

                    ->add(DefinitionFactory::getDescription($this)
                                ->setHelpText(tr('The description for this role')));
    }
}
