<?php

namespace Phoundation\Filesystem;

use Phoundation\Core\Config;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Exception\DataEntryNotExistsException;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
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
class Mount extends DataEntry
{
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
            if ($column === 'name') {
                $mount = Config::getArray('filesystem.mounts.' . $identifier);
                $mount = Mount::new()->setSource($mount);

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
        $definitions->
            ->addDefinition(DefinitionFactory::getName($this)
                ->setInputType(InputTypeExtended::name)
                ->setSize(12)
                ->setMaxlength(64)
                ->setHelpText(tr('The name for this role'))
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->isUnique(tr('value ":name" already exists', [':name' => $validator->getSelectedValue()]));
                }))
            ->addDefinition(DefinitionFactory::getSeoName($this))
            ->addDefinition(DefinitionFactory::getDescription($this)
                ->setHelpText(tr('The description for this role')));
    }
}