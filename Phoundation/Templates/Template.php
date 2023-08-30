<?php

declare(strict_types=1);

namespace Phoundation\Templates;

use Phoundation\Core\Session;
use Phoundation\Data\Categories\Categories;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Traits\DataEntryCategory;
use Phoundation\Data\DataEntry\Traits\DataEntryCode;
use Phoundation\Data\DataEntry\Traits\DataEntryContent;
use Phoundation\Data\DataEntry\Traits\DataEntryDescription;
use Phoundation\Data\DataEntry\Traits\DataEntryName;
use Phoundation\Data\DataEntry\Traits\DataEntryParent;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Web\Http\UrlBuilder;


/**
 * Class Template
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Templates
 */
class Template extends DataEntry
{
    use DataEntryName;
    use DataEntryCode;
    use DataEntryParent;
    use DataEntryCategory;
    use DataEntryContent;
    use DataEntryDescription;


    /**
     * Returns the table name used by this object
     *
     * @return string
     */
    public static function getTable(): string
    {
        return 'templates_pages';
    }

    /**
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getDataEntryName(): string
    {
        return tr('Template');
    }

    /**
     * Returns the field that is unique for this object
     *
     * @return string|null
     */
    public static function getUniqueField(): ?string
    {
        return 'seo_name';
    }

    /**
     * The template text
     *
     * @var string|null $text
     */
    protected ?string $text = null;


    /**
     * Returns a new Template object
     */
    public static function page(string $page_name = null): Template
    {
        $text = static::getPage($page_name);
        return static::new()->setText($text);
    }


    /**
     * Returns the template text
     *
     * @return string|null
     */
    public function getText(): ?string
    {
        return $this->text;
    }


    /**
     * Set the template text
     *
     * @param string|null $text
     * @return static
     */
    public function setText(?string $text): static
    {
        $this->text = $text;
        return $this;
    }


    /**
     * @param array $source
     * @return string
     */
    public function render(array $source): string
    {
        $text = $this->text;

        foreach ($source as $search => $replace) {
            $text = str_replace($search, (string) $replace, $text);
        }

        return $text;
    }


    /**
     * Returns the text for the specified page
     *
     * @todo Implement! For now this just returns hard coded texts
     * @param string $page
     * @return string|null
     */
    protected static function getPage(string $page): ?string
    {
        switch ($page) {
            case 'system/error':
                return '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
                        <html><head>
                            <title>:title</title>
                        </head><body>
                            <h1>:h1</h1>
                            <p>:p</p>
                            <hr>
                            :body
                        </body></html>';

            case 'system/detail-error':
                return '<div class="container">
                            <div class="d-flex justify-content-center align-items-center" style="height: 100vh">
                                <div class="text-center">
                                    <h1>:h1</h1>
                                </div>
                            </div>
                            <p>:p</p>
                        </div>';

            case 'admin/system/detail-error':
                $html =  '  <body class="hold-transition login-page">
                                <div class="login-box">
                                    <div class="error-page">
                                        <h2 class="headline text-warning"> :h2</h2>
                                    
                                        <div class="error-content">
                                            <h3><i class="fas fa-exclamation-triangle text-:type"></i> :h3</h3>
                                    
                                            <p>:p</p>
                                            <p>' . tr('Click :here to go to the index page', [':here' => '<a href="' . UrlBuilder::getCurrentDomainRootUrl() . '">here</a>']) . '</p>
                                            <p>' . tr('Click :here to sign out', [':here' => '<a href="' . UrlBuilder::getWww('sign-out.html') . '">here</a>']) . '</p>';

                if (!Session::getUser()->isGuest()) {
                    $html .= '              <form class="search-form" method="post" action=":action">
                                                <div class="input-group">
                                                    <input type="text" name="search" class="form-control" placeholder=":search">                        
                                                    <div class="input-group-append">
                                                        <button type="submit" name="submit" class="btn btn-warning"><i class="fas fa-search"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>';
                }

                $html .= '          </div>
                                    <!-- /.error-content -->
                                </div>
                            </div>
                        </body>';

                return $html;

        }

        return tr('TEMPLATE PAGE ":page" NOT FOUND', [':page' => $page]);
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
    protected function initDefinitions(DefinitionsInterface $definitions): void
    {
        $definitions
            ->addDefinition(DefinitionFactory::getParentsId($this)
                ->setContent(function (DefinitionInterface $definition, string $key, string $field_name, array $source) {
                    return Templates::new()->getHtmlSelect()
                        ->setName($key)
                        ->setSelected(isset_get($source[$key]))
                        ->render();
                })
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    // Ensure categories id exists and that its or category
                    $validator->or('parents_name')->isDbId()->isQueryResult('SELECT `id` FROM `templates` WHERE `id` = :id AND `status` IS NULL', [':id' => '$parents_id']);
                }))
            ->addDefinition(DefinitionFactory::getParent($this)
                ->setCliAutoComplete([
                    'word' => function ($word) {
                        return Categories::new()->getMatchingKeys($word);
                    },
                    'noword' => function () {
                        return Categories::new()->getSource();
                    },
                ])
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    // Ensure category exists and that it's a category id or category name
                    $validator->or('parents_id')->isName()->setColumnFromQuery('parents_id', 'SELECT `id` FROM `templates` WHERE `name` = :name AND `status` IS NULL', [':id' => '$parents_name']);
                }))
            ->addDefinition(DefinitionFactory::getCategoriesId($this))
            ->addDefinition(DefinitionFactory::getCategory($this))
            ->addDefinition(DefinitionFactory::getCode($this)
                ->setDefault(tr('-')))
            ->addDefinition(DefinitionFactory::getName($this)
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->isFalse(function($value, $source) {
                        static::exists($value, 'name', isset_get($source['id']));
                    }, tr('already exists'));
                }))
            ->addDefinition(DefinitionFactory::getSeoName($this))
            ->addDefinition(DefinitionFactory::getDescription($this)
                ->setHelpText(tr('The description for this template')))
            ->addDefinition(DefinitionFactory::getContent($this)
                ->setHelpText(tr('The content for this template')));
    }
}
