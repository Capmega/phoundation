<?php

declare(strict_types=1);

namespace Phoundation\Accounts\Rights;

use Phoundation\Accounts\Interfaces\InterfaceRight;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\DataEntryFieldDefinition;
use Phoundation\Data\DataEntry\DataEntryFieldDefinitions;
use Phoundation\Data\DataEntry\Interfaces\DataEntryFieldDefinitionsInterface;
use Phoundation\Data\DataEntry\Traits\DataEntryNameDescription;
use Phoundation\Data\Interfaces\InterfaceDataEntry;
use Phoundation\Data\Validator\Interfaces\DataValidator;


/**
 * Class Right
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataEntry
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */
class Right extends DataEntry implements InterfaceRight
{
    use DataEntryNameDescription;


    /**
     * Right class constructor
     *
     * @param InterfaceDataEntry|string|int|null $identifier
     */
    public function __construct(InterfaceDataEntry|string|int|null $identifier = null)
    {
        static::$entry_name = 'right';

        parent::__construct($identifier);
    }


    /**
     * Returns the table name used by this object
     *
     * @return string
     */
    public static function getTable(): string
    {
        return 'accounts_rights';
    }


    /**
     * Validates the provider record with the specified validator object
     *
     * @param DataValidator $validator
     * @param bool $no_arguments_left
     * @param bool $modify
     * @return array
     */
    protected function validate(DataValidator $validator, bool $no_arguments_left, bool $modify): array
    {
        $data = $validator
            ->select($this->getAlternateValidationField('name'), true)->hasMaxCharacters(64)->isName()
            ->select($this->getAlternateValidationField('description'), true)->isOptional()->hasMaxCharacters(65_530)->isPrintable()
            ->select($this->getAlternateValidationField('parent'), true)->or('parents_id')->isName()->isQueryColumn('SELECT `name` FROM `categories` WHERE `name` = :name AND `status` IS NULL', [':name' => '$parent'])
            ->select($this->getAlternateValidationField('parents_id'), true)->or('parent')->isId()->isQueryColumn  ('SELECT `id`   FROM `categories` WHERE `id`   = :id   AND `status` IS NULL', [':id'   => '$parents_id'])
            ->noArgumentsLeft($no_arguments_left)
            ->validate();

        // Ensure the name doesn't exist yet as it is a unique identifier
        if ($data['name']) {
            static::notExists($data['name'], $this->getId(), true);
        }

        return $data;
    }


    /**
     * Sets the available data keys for this entry
     *
     * @return DataEntryFieldDefinitionsInterface
     */
    protected static function setFieldDefinitions(): DataEntryFieldDefinitionsInterface
    {
        return DataEntryFieldDefinitions::new(self::getTable())
            ->add(DataEntryFieldDefinition::new('name')
                ->setLabel(tr('Name'))
                ->setSize(12)
                ->setMaxlength(64)
                ->setHelpText(tr('The name for this right')))
            ->add(DataEntryFieldDefinition::new('seo_name')
                ->setVisible(true)
                ->setReadonly(true))
            ->add(DataEntryFieldDefinition::new('description')
                ->setLabel(tr('Description'))
                ->setSize(12)
                ->setMaxlength(65_535)
                ->setHelpText(tr('The description for this right')));
    }
}