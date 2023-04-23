<?php

namespace Phoundation\Accounts\Rights;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Traits\DataEntryNameDescription;
use Phoundation\Data\Interfaces\InterfaceDataEntry;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\PostValidator;

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
class Right extends DataEntry
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
        $this->table        = 'accounts_rights';

        parent::__construct($identifier);
    }

    /**
     * Validates the provider record with the specified validator object
     *
     * @param ArgvValidator|PostValidator|GetValidator $validator
     * @param bool $no_arguments_left
     * @param bool $modify
     * @return array
     */
    protected function validate(ArgvValidator|PostValidator|GetValidator $validator, bool $no_arguments_left = false, bool $modify = false): array
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
     * @return array
     */
    protected static function getFieldDefinitions(): array
    {
        return [
            'name' => [
                'label'     => tr('Name'),
                'size'      => 12,
                'maxlength' => 64,
                'help'      => tr('The name for this right'),
            ],
            'seo_name' => [
                'visible'  => false,
                'readonly' => true,
            ],
            'description' => [
                'element'   => 'text',
                'label'     => tr('Description'),
                'size'      => 12,
                'maxlength' => 65535,
                'help'      => tr('The description for this right'),
            ]
        ];
    }
}