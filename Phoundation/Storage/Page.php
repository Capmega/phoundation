<?php

declare(strict_types=1);

namespace Phoundation\Storage;

use Phoundation\Data\Categories\Categories;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryCategory;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryCode;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryContent;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryDescription;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryName;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryParent;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Storage\Interfaces\PageInterface;
use Phoundation\Web\Html\Enums\EnumElement;

/**
 * Class Page
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Pages
 */
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
     * @return string
     */
    public static function getTable(): string
    {
        return 'storage_pages';
    }


    /**
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getDataEntryName(): string
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
     * Format:
     *
     * [
     *   field => [key => value],
     *   field => [key => value],
     *   field => [key => value],
     * ]
     *
     * "field" should be the database table column name
     *
     * Field keys:
     *
     * FIELD          DATATYPE           DEFAULT VALUE  DESCRIPTION
     * value          mixed              null           The value for this entry
     * visible        boolean            true           If false, this key will not be shown on web, and be readonly
     * virtual        boolean            false          If true, this key will be visible and can be modified but it
     *                                                  won't exist in database. It instead will be used to generate
     *                                                  a different field
     * element        string|null        "input"        Type of element, input, select, or text or callable function
     * type           string|null        "text"         Type of input element, if element is "input"
     * readonly       boolean            false          If true, will make the input element readonly
     * disabled       boolean            false          If true, the field will be displayed as disabled
     * label          string|null        null           If specified, will show a description label in HTML
     * size           int [1-12]         12             The HTML boilerplate column size, 1 - 12 (12 being the whole
     *                                                  row)
     * source         array|string|null  null           Array or query source to get contents for select, or single
     *                                                  value for text inputs
     * execute        array|null         null           Bound execution variables if specified "source" is a query
     *                                                  string
     * complete       array|bool|null    null           If defined must be bool or contain array with key "noword"
     *                                                  and "word". each key must contain a callable function that
     *                                                  returns an array with possible words for shell auto
     *                                                  completion. If bool, the system will generate this array
     *                                                  automatically from the rows for this field
     * cli            string|null        null           If set, defines the alternative column name definitions for
     *                                                  use with CLI. For example, the column may be name, whilst
     *                                                  the cli column name may be "-n,--name"
     * optional       boolean            false          If true, the field is optional and may be left empty
     * title          string|null        null           The title attribute which may be used for tooltips
     * placeholder    string|null        null           The placeholder attribute which typically shows an example
     * maxlength      string|null        null           The maxlength attribute which typically shows an example
     * pattern        string|null        null           The pattern the value content should match in browser client
     * min            string|null        null           The minimum amount for numeric inputs
     * max            string|null        null           The maximum amount for numeric inputs
     * step           string|null        null           The up / down step for numeric inputs
     * default        mixed              null           If "value" for entry is null, then default will be used
     * null_disabled  boolean            false          If "value" for entry is null, then use this for "disabled"
     * null_readonly  boolean            false          If "value" for entry is null, then use this for "readonly"
     * null_type      boolean            false          If "value" for entry is null, then use this for "type"
     *
     * @param DefinitionsInterface $definitions
     */
    protected function setDefinitions(DefinitionsInterface $definitions): void
    {
//`view_rights_id` bigint DEFAULT NULL,
//`collections_id` bigint NOT NULL,
//`books_id` bigint NOT NULL,
//`chapters_id` bigint NOT NULL,
//`parents_id` bigint DEFAULT NULL,
//`categories_id` bigint DEFAULT NULL,
//`templates_id` bigint DEFAULT NULL,
//`is_template` tinyint DEFAULT NULL,
        $definitions->add(DefinitionFactory::getParentsId($this)
                                           ->setElement(EnumElement::select)
                                           ->setContent(function (DefinitionInterface $definition, string $key, string $field_name, array $source) {
                                               return Pages::new()
                                                           ->getHtmlSelect()
                                                           ->setName($key)
                                                           ->setSelected(isset_get($source[$key]));
                                           })
                                           ->addValidationFunction(function (ValidatorInterface $validator) {
                                               // Ensure categories id exists and that its or category
                                               $validator->orColumn('parents_name')
                                                         ->isDbId()
                                                         ->isQueryResult('SELECT `id` FROM `pages` WHERE `id` = :id AND `status` IS NULL', [':id' => '$parents_id']);
                                           }))
                    ->add(DefinitionFactory::getParent($this)
                                           ->setCliAutoComplete([
                                               'word'   => function ($word) {
                                                   return Categories::new()
                                                                    ->keepMatchingKeys($word);
                                               },
                                               'noword' => function () {
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
                    ->add(DefinitionFactory::getCategoriesId($this))
                    ->add(DefinitionFactory::getCategory($this))
                    ->add(DefinitionFactory::getCode($this)
                                           ->setDefault(tr('-')))
                    ->add(DefinitionFactory::getName($this)
                                           ->addValidationFunction(function (ValidatorInterface $validator) {
                                               $validator->isFalse(function ($value, $source) {
                                                   static::exists($value, 'name', isset_get($source['id']));
                                               }, tr('already exists'));
                                           }))
                    ->add(DefinitionFactory::getSeoName($this))
                    ->add(DefinitionFactory::getDescription($this)
                                           ->setHelpText(tr('The description for this page')))
                    ->add(DefinitionFactory::getContent($this)
                                           ->setHelpText(tr('The content for this page')));
    }
}
