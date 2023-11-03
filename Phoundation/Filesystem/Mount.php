<?php

namespace Phoundation\Filesystem;

use Phoundation\Core\Config;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Exception\DataEntryNotExistsException;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntry\Traits\DataEntryNameDescription;
use Phoundation\Data\DataEntry\Traits\DataEntryOptions;
use Phoundation\Data\DataEntry\Traits\DataEntrySource;
use Phoundation\Data\DataEntry\Traits\DataEntryTarget;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Filesystem\Interfaces\MountInterface;
use Phoundation\Web\Http\Html\Enums\InputType;
use Phoundation\Web\Http\Html\Enums\InputTypeExtended;


/**
 * Class Mount
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Filesystem
 */
class Mount extends DataEntry implements MountInterface
{
    use DataEntryNameDescription;
    use DataEntrySource;
    use DataEntryTarget;
    use DataEntryOptions;


    /**
     * @inheritDoc
     */
    public static function getTable(): string
    {
        return 'filesystem_mounts';
    }


    /**
     * @inheritDoc
     */
    public static function getDataEntryName(): string
    {
        return tr('Mount');
    }


    /**
     * @inheritDoc
     */
    public static function getUniqueField(): ?string
    {
        return 'name';
    }


    /**
     * @inheritDoc
     */
    public static function get(int|string|DataEntryInterface|null $identifier = null, ?string $column = null): MountInterface
    {
        try {
            return parent::get($identifier, $column);

        } catch (DataEntryNotExistsException $e) {
            // Mount was not found in the database. Get it from configuration instead but that DOES require the name
            // column
            switch ($column) {
                case 'name':
                    $mount = Config::getArray('filesystem.mounts.' . $identifier);
                    $mount = Mount::new()->setSource($mount);
                    return $mount;

                case 'source':
                    // This is a mount that SHOULD already exist on the system
                    $mount = Mounts::getMountSource($identifier);
                    $mount = static::new()->setSource($mount);
                    return $mount;

                case 'target':
                    // This is a mount that SHOULD already exist on the system
                    $mount = Mounts::getMountTarget($identifier);
                    $mount = static::new()->setSource($mount);
                    return $mount;
            }

            throw $e;
        }
    }


    /**
     * @inheritDoc
     */
    protected function setDefinitions(DefinitionsInterface $definitions): void
    {
        $definitions
            ->addDefinition(DefinitionFactory::getName($this)
                ->setInputType(InputTypeExtended::name)
                ->setSize(12)
                ->setMaxlength(64)
                ->setHelpText(tr('The name for this role'))
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->isUnique(tr('value ":name" already exists', [':name' => $validator->getSelectedValue()]));
                }))
            ->addDefinition(DefinitionFactory::getSeoName($this))
            ->addDefinition(Definition::new($this, 'source')
                ->setInputType(InputTypeExtended::name)
                ->setSize(6)
                ->setMaxlength(255)
                ->setHelpText(tr('The source file for this mount'))
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->isFile();
                }))
            ->addDefinition(Definition::new($this, 'target')
                ->setInputType(InputTypeExtended::name)
                ->setSize(6)
                ->setMaxlength(255)
                ->setHelpText(tr('The target file for this mount'))
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->isFile();
                }))
            ->addDefinition(Definition::new($this, 'options')
                ->setInputType(InputTypeExtended::name)
                ->setSize(10)
                ->setMaxlength(508)
                ->setHelpText(tr('The options for this mount'))
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->isFile();
                }))
            ->addDefinition(Definition::new($this, 'auto_mount')
                ->setInputType(InputType::checkbox)
                ->setSize(2)
                ->setHelpText(tr('Auto mount')))
            ->addDefinition(DefinitionFactory::getDescription($this)
                ->setHelpText(tr('The description for this mount')));
    }
}