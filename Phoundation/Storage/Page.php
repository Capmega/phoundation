<?php

/**
 * Class Page
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Storage
 */


declare(strict_types=1);

namespace Phoundation\Storage;

use Phoundation\Data\Categories\Categories;
use Phoundation\Data\DataEntries\DataEntry;
use Phoundation\Data\DataEntries\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryCategory;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryCode;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryContent;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryDescription;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryName;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryParent;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Storage\Interfaces\PageInterface;
use Phoundation\Web\Html\Enums\EnumElement;


class Page extends DataEntry implements PageInterface
{
    use TraitDataEntryName;
    use TraitDataEntryCode;
    use TraitDataEntryParent;
    use TraitDataEntryCategory;
    use TraitDataEntryContent;
    use TraitDataEntryDescription;

    /**
     * Returns the table name used by this object
     *
     * @return string|null
     */
    public static function getTable(): ?string
    {
        return 'storage_pages';
    }


    /**
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getEntryName(): string
    {
        return tr('Page');
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
     * Sets and returns the field definitions for the data fields in this DataEntry object
     *
     * @param DefinitionsInterface $definitions
     *
     * @return static
     */
    protected function setDefinitions(DefinitionsInterface $definitions): static
    {
//`view_rights_id` bigint DEFAULT NULL,
//`collections_id` bigint NOT NULL,
//`books_id` bigint NOT NULL,
//`chapters_id` bigint NOT NULL,
//`parents_id` bigint DEFAULT NULL,
//`categories_id` bigint DEFAULT NULL,
//`templates_id` bigint DEFAULT NULL,
//`is_template` tinyint DEFAULT NULL,
        $definitions->add(DefinitionFactory::newParentsId()
                                           ->setElement(EnumElement::select)
                                           ->setContent(function (DefinitionInterface $definition, string $key, string $field_name, array $source) {
                                               return Pages::new()
                                                           ->getHtmlSelectOld()
                                                           ->setName($key)
                                                           ->setSelected(isset_get($source[$key]));
                                           })
                                           ->addValidationFunction(function (ValidatorInterface $validator) {
                                               // Ensure categories id exists and that its or category
                                               $validator->orColumn('parents_name')
                                                         ->isDbId()
                                                         ->isQueryResult('SELECT `id` FROM `pages` WHERE `id` = :id AND `status` IS NULL', [':id' => '$parents_id']);
                                           }))

                    ->add(DefinitionFactory::newParent()
                                           ->setCliAutoComplete([
                                               'word'   => function ($word) {
                                                   return Categories::new()
                                                                    ->keepMatchingKeys($word);
                                               },
                                               'noword' => function ($word) {
                                                   return Categories::new()
                                                                    ->getSource();
                                               },
                                           ])
                                           ->addValidationFunction(function (ValidatorInterface $validator) {
                                               // Ensure category exists and that it's a category id or category name
                                               $validator->orColumn('parents_id')
                                                         ->isName()
                                                         ->setColumnFromQuery('parents_id', 'SELECT `id` FROM `pages` WHERE `name` = :name AND `status` IS NULL', [':id' => '$parents_name']);
                                           }))

                    ->add(DefinitionFactory::newCategoriesId())

                    ->add(DefinitionFactory::newCategory())

                    ->add(DefinitionFactory::newCode()
                                           ->setDefault(tr('-')))

                    ->add(DefinitionFactory::newName()
                                           ->addValidationFunction(function (ValidatorInterface $validator) {
                                               $validator->isFalse(function ($value, $source) {
                                                   static::exists(['name' => $value], isset_get($source['id']));
                                               }, tr('already exists'));
                                           }))

                    ->add(DefinitionFactory::newSeoName())

                    ->add(DefinitionFactory::newDescription()
                                           ->setHelpText(tr('The description for this page')))

                    ->add(DefinitionFactory::newContent()
                                           ->setHelpText(tr('The content for this page')));

        return $this;
    }
}
