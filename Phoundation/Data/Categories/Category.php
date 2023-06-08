<?php

declare(strict_types=1);

namespace Phoundation\Data\Categories;

use Phoundation\Core\Locale\Language\Languages;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Interfaces\DataEntryFieldDefinitionsInterface;
use Phoundation\Data\DataEntry\Traits\DataEntryCategory;
use Phoundation\Data\DataEntry\Traits\DataEntryNameDescription;
use Phoundation\Data\Interfaces\InterfaceDataEntry;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\Interfaces\DataValidator;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\UnderConstructionException;

/**
 * Category class
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataEntry
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
class Category extends DataEntry
{
    use DataEntryNameDescription;


    /**
     * Category class constructor
     *
     * @param InterfaceDataEntry|string|int|null $identifier
     */
    public function __construct(InterfaceDataEntry|string|int|null $identifier = null)
    {
        static::$entry_name = 'category';

        parent::__construct($identifier);
    }


    /**
     * Returns the table name used by this object
     *
     * @return string
     */
    public static function getTable(): string
    {
        return 'categories';
    }


    /**
     * Returns the parents_id for this object
     *
     * @return int|null
     */
    public function getParentsId(): ?int
    {
        return $this->getDataValue('int', 'parents_id');
    }


    /**
     * Sets the parents_id for this object
     *
     * @param string|int|null $parents_id
     * @return static
     */
    public function setParentsId(string|int|null $parents_id): static
    {
        if ($parents_id and !is_natural($parents_id)) {
            throw new OutOfBoundsException(tr('Specified parents_id ":id" is not numeric', [
                ':id' => $parents_id
            ]));
        }

        return $this->setDataValue('parents_id', get_null(isset_get_typed('integer', $parents_id)));
    }


    /**
     * Returns the parents_id for this user
     *
     * @return Parent|null
     */
    public function getParent(): ?Parent
    {
        $parents_id = $this->getDataValue('int', 'parents_id');

        if ($parents_id) {
            return new static($parents_id);
        }

        return null;
    }


    /**
     * Sets the parents_id for this user
     *
     * @param Category|string|int|null $parent
     * @return static
     */
    public function setParent(Category|string|int|null $parent): static
    {
        if ($parent) {
            if (!is_numeric($parent)) {
                $parent = static::get($parent);
            }

            if (is_object($parent)) {
                $parent = $parent->getId();
            }
        }

        return $this->setParentsId(get_null($parent));
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
        return [
            'parents_id' => [
                'element'  => function (string $key, array $data, array $source) {
                    return Categories::getHtmlSelect($key)
                        ->setParentsId(null)
                        ->setSelected(isset_get($source['parents_id']))
                        ->render();
                },
                'source' => [],
                'label'  => tr('Parent'),
                'size'   => 4,
                'help'   => tr('The parent category for this category'),
            ],
            'name' => [
                'label'     => tr('Name'),
                'maxlength' => 64,
                'size'      => 12,
                'help'      => tr('The name for this category'),
            ],
            'seo_name' => [
                'visible'  => false,
                'readonly' => true,
            ],
            'description' => [
                'element'   => 'text',
                'label'     => tr('Description'),
                'maxlength' => 65535,
                'size'      => 12,
                'help'      => tr('The description for this category'),
            ],
        ];
    }
}