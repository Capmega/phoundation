<?php

namespace Phoundation\Data\Categories;

use Phoundation\Core\Locale\Language\Languages;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Traits\DataEntryCategory;
use Phoundation\Data\DataEntry\Traits\DataEntryNameDescription;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\PostValidator;
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
     * @param int|string|null $identifier
     */
    public function __construct(int|string|null $identifier = null)
    {
        static::$entry_name = 'category';
        $this->table        = 'categories';

        parent::__construct($identifier);
    }



    /**
     * Returns the parents_id for this object
     *
     * @return string|null
     */
    public function getParentsId(): ?string
    {
        return $this->getDataValue('parents_id');
    }



    /**
     * Sets the parents_id for this object
     *
     * @param string|null $parents_id
     * @return static
     */
    public function setParentsId(?string $parents_id): static
    {
        return $this->setDataValue('parents_id', $parents_id);
    }



    /**
     * Returns the parents_id for this user
     *
     * @return Parent|null
     */
    public function getParent(): ?Parent
    {
        $parents_id = $this->getDataValue('parents_id');

        if ($parents_id) {
            return new static($parents_id);
        }

        return null;
    }



    /**
     * Sets the parents_id for this user
     *
     * @param Category|string|int|null $parents_id
     * @return static
     */
    public function setParent(Category|string|int|null $parents_id): static
    {
        if (!is_numeric($parents_id)) {
            $parents_id = static::get($parents_id);
        }

        if (is_object($parents_id)) {
            $parents_id = $parents_id->getId();
        }

        return $this->setDataValue('parents_id', $parents_id);
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
     * @inheritDoc
     */
    public static function getFieldDefinitions(): array
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