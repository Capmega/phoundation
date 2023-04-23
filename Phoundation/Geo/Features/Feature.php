<?php

namespace Phoundation\Geo\Features;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Traits\DataEntryNameDescription;
use Phoundation\Data\Interfaces\InterfaceDataEntry;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Exception\UnderConstructionException;

/**
 * Class Feature
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataEntry
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Geo
 */
class Feature extends DataEntry
{
    use DataEntryNameDescription;

    /**
     * Features class constructor
     *
     * @param InterfaceDataEntry|string|int|null $identifier
     */
    public function __construct(InterfaceDataEntry|string|int|null $identifier = null)
    {
        static::$entry_name = 'geo feature';
        $this->table        = 'geo_features';
        $this->unique_field = 'seo_name';

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
        throw new UnderConstructionException();

        $data = $validator
            ->select($this->getAlternateValidationField('code'), true)->hasMaxCharacters()->isName()->isQueryColumn('SELECT `name` FROM `geo_continents` WHERE `name` = :name AND `status` IS NULL', [':name' => '$continent'])
            ->select($this->getAlternateValidationField('continent'), true)->or('continents_id')->isName()->isQueryColumn('SELECT `name` FROM `geo_continents` WHERE `name` = :name AND `status` IS NULL', [':name' => '$continent'])
            ->select($this->getAlternateValidationField('continents_id'), true)->or('continent')->isId()->isQueryColumn  ('SELECT `id`   FROM `geo_continents` WHERE `id`   = :id   AND `status` IS NULL', [':id'   => '$continents_id'])
            ->select($this->getAlternateValidationField('timezone'), true)->or('timezones_id')->isName()->isQueryColumn  ('SELECT `name` FROM `geo_timezone`   WHERE `name` = :name AND `status` IS NULL', [':name' => '$timezone'])
            ->select($this->getAlternateValidationField('timezones_id'), true)->or('timezone')->isId()->isQueryColumn    ('SELECT `id`   FROM `geo_timezone`   WHERE `id`   = :id   AND `status` IS NULL', [':id'   => '$timezones_id'])
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
        // TODO: Implement getFieldDefinitions() method.
    }
}