<?php

declare(strict_types=1);

namespace Phoundation\Data\Categories;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\DefinitionDefaults;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntry\Traits\DataEntryNameDescription;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Exception\OutOfBoundsException;


/**
 * Category class
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataEntry
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
class Category extends DataEntry
{
    use DataEntryNameDescription;


    /**
     * Category class constructor
     *
     * @param DataEntryInterface|string|int|null $identifier
     * @param bool $init
     */
    public function __construct(DataEntryInterface|string|int|null $identifier = null, bool $init = true)
    {
        $this->table        = 'categories';
        $this->entry_name   = 'category';

        parent::__construct($identifier, $init);
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
     * Sets the available data keys for this entry
     *
     * @param DefinitionsInterface $definitions
     * @return void
     */
    protected function initDefinitions(DefinitionsInterface $definitions): void
    {
        $definitions
            ->addDefinition(Definition::new('parents_id')
                ->setOptional(true)
                ->setContent(function (string $key, array $data, array $source) {
                    return Categories::new()->getHtmlSelect()
                        ->setName($key)
                        ->setSelected(isset_get($source[$key]))
                        ->render();
                })
                ->setSize(6)
                ->setLabel(tr('Parent category'))
                ->addValidationFunction(function ($validator) {
                    // Ensure parents_id exists and that its or parent
                    $validator->or('parent')->isId()->isQueryColumn('SELECT `id` FROM `categories` WHERE `id` = :id AND `status` IS NULL', [':id' => '$parents_id']);
                }))
            ->addDefinition(Definition::new('parent')
                ->setOptional(true)
                ->setVirtual(true)
                ->setCliField('--parent PARENT CATEGORY NAME')
                ->setAutoComplete([
                    'word'   => function($word) { return Categories::new()->filteredList($word); },
                    'noword' => function()      { return Categories::new()->getSource(); },
                ])
                ->addValidationFunction(function ($validator) {
                    // Ensure parent exists and that its or parents_id
                    $validator->or('parents_id')->isName(64)->setColumnFromQuery('parents_id', 'SELECT `id` FROM `categories` WHERE `name` = :name AND `status` IS NULL', [':name' => '$parent']);
                }))
            ->addDefinition(DefinitionDefaults::getName()
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->isFalse(function($value, $source) {
                        Category::exists($value, isset_get($source['id']));
                    }, tr('already exists'));
                }))
            ->addDefinition(DefinitionDefaults::getSeoName())
            ->addDefinition(DefinitionDefaults::getDescription());
    }
}