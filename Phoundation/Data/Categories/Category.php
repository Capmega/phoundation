<?php

/**
 * Category class
 *
 *
 *
 * @see       \Phoundation\Data\DataEntries\DataEntry
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Categories;

use Phoundation\Data\Categories\Interfaces\CategoryInterface;
use Phoundation\Data\DataEntries\DataEntry;
use Phoundation\Data\DataEntries\Definitions\Definition;
use Phoundation\Data\DataEntries\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryNameDescription;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Web\Html\Enums\EnumElement;


class Category extends DataEntry implements CategoryInterface
{
    use TraitDataEntryNameDescription;

    /**
     * Returns the table name used by this object
     *
     * @return string|null
     */
    public static function getTable(): ?string
    {
        return 'categories';
    }


    /**
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getEntryName(): string
    {
        return tr('Category');
    }


    /**
     * Returns the field that is unique for this object
     *
     * @return string|null
     */
    public static function getUniqueColumn(): ?string
    {
        return 'seo_name';
    }


    /**
     * Returns the parents_id for this object
     *
     * @return int|null
     */
    public function getParentsId(): ?int
    {
        return $this->getTypesafe('int', 'parents_id');
    }


    /**
     * Sets the parents_id for this object
     *
     * @param int|null $parents_id
     *
     * @return static
     */
    public function setParentsId(?int $parents_id): static
    {
        return $this->set($parents_id, 'parents_id');
    }


    /**
     * Returns the parents_id for this user
     *
     * @return Category|null
     */
    public function getParent(): ?Category
    {
        $parents_id = $this->getTypesafe('int', 'parents_id');
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
        return $this->getTypesafe('string', 'parents_name');
    }


    /**
     * Sets the parents_id for this user
     *
     * @param string|null $parents_name
     *
     * @return static
     */
    public function setParentsName(?string $parents_name): static
    {
        return $this->set($parents_name, 'parents_name');
    }


    /**
     * Sets the available data keys for this entry
     *
     * @param DefinitionsInterface $o_definitions
     *
     * @return static
     */
    protected function setDefinitionsObject(DefinitionsInterface $o_definitions): static
    {
        $o_definitions->add(Definition::new('parents_id')
                                      ->setOptional(true)
                                      ->setElement(EnumElement::select)
                                      ->setContent(function (DefinitionInterface $o_definition, string $key, string $field_name, array $source) {
                                        return Categories::new()
                                                         ->getHtmlSelectOld()
                                                         ->setName($field_name)
                                                         ->setSelected(isset_get($source[$key]));
                                    })
                                      ->setSize(6)
                                      ->setLabel(tr('Parent category'))
                                    ->addValidationFunction(function (ValidatorInterface $o_validator) {
                                        // Ensure parents_id exists and that its or parent
                                        $o_validator->orColumn('parent')
                                                  ->isDbId()
                                                  ->isQueryResult('SELECT `id` 
                                                                   FROM   `categories` 
                                                                   WHERE  `id` = :id 
                                                                   AND   (`status` IS NULL OR `status` != "deleted")', [
                                                                       ':id' => '$parents_id'
                                                  ]);
                                    }))

                    ->add(Definition::new('parent')
                                    ->setOptional(true)
                                    ->setVirtual(true)
                                    ->setCliColumn('--parent PARENT CATEGORY NAME')
                                    ->setCliAutoComplete([
                                        'word' => function ($word) {
                                            return Categories::new()
                                                             ->keepMatchingKeys($word);
                                        },
                                        'noword' => function ($word) {
                                            return Categories::new()
                                                             ->getSource();
                                        },
                                    ])
                                    ->addValidationFunction(function (ValidatorInterface $o_validator) {
                                        // Ensure parent exists and that its or parents_id
                                        $o_validator->orColumn('parents_id')
                                                  ->isName(64)
                                                  ->setColumnFromQuery('parents_id', 'SELECT `id` 
                                                                                      FROM   `categories` 
                                                                                      WHERE  `name` = :name 
                                                                                      AND   (`status` IS NULL OR `status` != "deleted")', [
                                                                                          ':name' => '$parent'
                                                  ]);
                                    }))

                    ->add(DefinitionFactory::newName()
                                           ->addValidationFunction(function (ValidatorInterface $o_validator) {
                                               $o_validator->isFalse(function ($value, $source) {
                                                   Category::exists(['name' => $value], isset_get($source['id']));
                                               }, tr('already exists'));
                                           }))

                    ->add(DefinitionFactory::newSeoName())

                    ->add(DefinitionFactory::newDescription());

        return $this;
    }
}
