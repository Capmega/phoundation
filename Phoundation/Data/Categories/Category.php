<?php

declare(strict_types=1);

namespace Phoundation\Data\Categories;

use Phoundation\Data\Categories\Interfaces\CategoryInterface;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionInterface;
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
class Category extends DataEntry implements CategoryInterface
{
    use DataEntryNameDescription;


    /**
     * Category class constructor
     *
     * @param DataEntryInterface|string|int|null $identifier
     * @param string|null $column
     */
    public function __construct(DataEntryInterface|string|int|null $identifier = null, ?string $column = null)
    {
        $this->table      = 'categories';
        $this->entry_name = 'category';

        parent::__construct($identifier, $column);
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
     * @param int|null $parents_id
     * @return static
     */
    public function setParentsId(int|null $parents_id): static
    {
        return $this->setDataValue('parents_id', $parents_id);
    }


    /**
     * Returns the parents_id for this user
     *
     * @return Category|null
     */
    public function getParent(): ?Category
    {
        $parents_id = $this->getDataValue('int', 'parents_id');

        if ($parents_id) {
            return new static($parents_id);
        }

        return null;
    }


    /**
     * Returns the parents_id for this user
     *
     * @return string|null
     */
    public function getParentsName(): ?string
    {
        return $this->getDataValue('string', 'parents_name');
    }


    /**
     * Sets the parents_id for this user
     *
     * @param string|null $parents_name
     * @return static
     */
    public function setParentsName(string|null $parents_name): static
    {
        return $this->setDataValue('parents_name', $parents_name);
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
            ->addDefinition(Definition::new($this, 'parents_id')
                ->setOptional(true)
                ->setContent(function (DefinitionInterface $definition, string $key, string $field_name, array $source) {
                    return Categories::new()->getHtmlSelect()
                        ->setName($field_name)
                        ->setSelected(isset_get($source[$key]))
                        ->render();
                })
                ->setSize(6)
                ->setLabel(tr('Parent category'))
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    // Ensure parents_id exists and that its or parent
                    $validator->or('parent')->isDbId()->isQueryColumn('SELECT `id` FROM `categories` WHERE `id` = :id AND `status` IS NULL', [':id' => '$parents_id']);
                }))
            ->addDefinition(Definition::new($this, 'parent')
                ->setOptional(true)
                ->setVirtual(true)
                ->setCliField('--parent PARENT CATEGORY NAME')
                ->setAutoComplete([
                    'word'   => function($word) { return Categories::new()->filteredList($word); },
                    'noword' => function()      { return Categories::new()->getSource(); },
                ])
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    // Ensure parent exists and that its or parents_id
                    $validator->or('parents_id')->isName(64)->setColumnFromQuery('parents_id', 'SELECT `id` FROM `categories` WHERE `name` = :name AND `status` IS NULL', [':name' => '$parent']);
                }))
            ->addDefinition(DefinitionFactory::getName($this)
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->isFalse(function($value, $source) {
                        Category::exists('name', $value, isset_get($source['id']));
                    }, tr('already exists'));
                }))
            ->addDefinition(DefinitionFactory::getSeoName($this))
            ->addDefinition(DefinitionFactory::getDescription($this));
    }
}