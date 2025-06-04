<?php

/**
 * Class DefinitionFactory
 *
 * Definition class factory that contains predefined column definitions
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Definitions;

use Phoundation\Accounts\Roles\Roles;
use Phoundation\Accounts\Users\Locale\Language\Languages;
use Phoundation\Accounts\Users\Locale\PhoLocale;
use Phoundation\Accounts\Users\Sessions\Session;
use Phoundation\Accounts\Users\User;
use Phoundation\Accounts\Users\Users;
use Phoundation\Business\Companies\Companies;
use Phoundation\Business\Customers\Customers;
use Phoundation\Business\Providers\Providers;
use Phoundation\Core\Locale;
use Phoundation\Data\Categories\Categories;
use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Filesystem\Interfaces\PhoDirectoryInterface;
use Phoundation\Geo\Cities\Cities;
use Phoundation\Geo\Countries\Countries;
use Phoundation\Geo\Countries\Country;
use Phoundation\Geo\States\State;
use Phoundation\Geo\States\States;
use Phoundation\Geo\Timezones\Timezone;
use Phoundation\Geo\Timezones\Timezones;
use Phoundation\Servers\Servers;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Components\Input\InputText;
use Phoundation\Web\Html\Enums\EnumElement;
use Phoundation\Web\Html\Enums\EnumInputType;

class DefinitionFactory
{
    /**
     * Returns a Definition object for any database id
     *
     * @param string|null $column
     *
     * @return DefinitionInterface
     */
    public static function newDatabaseId(?string $column = 'id'): DefinitionInterface
    {
        return Definition::new($column)
                         ->setOptional(true)
                         ->setInputType(EnumInputType::dbid)
                         ->setSize(3);
    }


    /**
     * Returns a Definition object for the column "categories_id"
     *
     * @param string|null $column
     * @param array|null  $filters
     *
     * @return DefinitionInterface
     */
    public static function newCategoriesId(?string $column = 'categories_id', ?array $filters = null): DefinitionInterface
    {
        return DefinitionFactory::newDatabaseId($column)
                                ->setContent(function (DefinitionInterface $definition, string $key, string $column_name, array $source) use ($filters) {
                                    return Categories::new()
                                                     ->getHtmlSelectOld()
                                                     ->setName($key)
                                                     ->setReadonly($definition->getReadonly())
                                                     ->setDisabled($definition->getDisabled())
                                                     ->setSelected(isset_get($source[$key]));
                                })
                                ->setSize(6)
                                ->setLabel(tr('Category'))
                                ->addValidationFunction(function (ValidatorInterface $validator) {
                                    // Ensure categories id exists and that it's or category
                                    $validator->orColumn('categories_name')
                                              ->isDbId()
                                              ->isQueryResult('SELECT `id` 
                                                               FROM   `categories` 
                                                               WHERE  `id` = :id 
                                                                 AND  `status` IS NULL', [
                                                                     ':id' => '$categories_id'
                                              ]);
                                });
    }


    /**
     * Returns a Definition object for the column "categories_name"
     *
     * @param string|null $column
     *
     * @return DefinitionInterface
     */
    public static function newCategory(?string $column = 'categories_name'): DefinitionInterface
    {
        return Definition::new($column)
                         ->setOptional(true)
                         ->setRender(false)
                         ->setVirtual(true)
                         ->setCliColumn('-c,--category CATEGORY-NAME')
                         ->setLabel(tr('Category'))
                         ->setCliAutoComplete([
                             'word'   => function ($word) {
                                 return Categories::new()->keepMatchingKeys($word);
                             },
                             'noword' => function ($word) {
                                 return Categories::new()->getSource();
                             },
                         ])
                         ->addValidationFunction(function (ValidatorInterface $validator) {
                             // Ensure category exists and that it's a category id or category name
                             $validator->orColumn('categories_id')
                                       ->isName()
                                       ->setColumnFromQuery('categories_id', 'SELECT `id` 
                                                                              FROM   `categories` 
                                                                              WHERE  `name` = :name 
                                                                                AND  `status` IS NULL', [
                                                                                    ':name' => '$categories_name'
                                       ]);
                         });
    }


    /**
     * Returns a Definition object for the column "servers_id"
     *
     * @param string|null $column
     * @param array|null  $filters
     *
     * @return DefinitionInterface
     */
    public static function newServersId(?string $column = 'servers_id', ?array $filters = null): DefinitionInterface
    {
        return DefinitionFactory::newDatabaseId($column)
                                ->setContent(function (DefinitionInterface $definition, string $key, string $column_name, array $source) use ($filters) {
                                    return Servers::new()
                                                  ->getHtmlSelectOld()
                                                  ->setName($key)
                                                  ->setReadonly($definition->getReadonly())
                                                  ->setDisabled($definition->getDisabled())
                                                  ->setSelected(isset_get($source[$key]));
                                })
                                ->setSize(6)
                                ->setLabel(tr('Server'))
                                ->addValidationFunction(function (ValidatorInterface $validator) {
                                    // Ensure servers id exists, either
                                    $validator->orColumn('servers_name')
                                              ->isDbId()
                                              ->isQueryResult('SELECT `id` 
                                                               FROM   `servers` 
                                                               WHERE  `id` = :id
                                                                 AND  `status` IS NULL', [
                                                                     ':id' => '$servers_id'
                                              ]);
                                });
    }


    /**
     * Returns a Definition object for the column "servers_name"
     *
     * @param string|null $column
     *
     * @return DefinitionInterface
     */
    public static function newServer(?string $column = 'servers_name'): DefinitionInterface
    {
        return Definition::new($column)
                         ->setOptional(true)
                         ->setRender(false)
                         ->setVirtual(true)
                         ->setCliColumn('-c,--server CATEGORY-NAME')
                         ->setLabel(tr('Server'))
                         ->setCliAutoComplete([
                             'word'   => function ($word) {
                                 return Servers::new()->keepMatchingKeys($word);
                             },
                             'noword' => function ($word) {
                                 return Servers::new()->getSource();
                             },
                         ])
                         ->addValidationFunction(function (ValidatorInterface $validator) {
                             // Ensure server exists and that it's a server id or server name
                             $validator->orColumn('servers_id')
                                       ->isName()
                                       ->setColumnFromQuery('servers_id', 'SELECT `id` 
                                                                           FROM   `servers` 
                                                                           WHERE  `name` = :name 
                                                                             AND  `status` IS NULL', [
                                                                                 ':id' => '$servers_name'
                                       ]);
                         });
    }


    /**
     * Returns a Definition object for the column "parents_id"
     *
     * @param string|null $column
     *
     * @return DefinitionInterface
     */
    public static function newParentsId(?string $column = 'parents_id'): DefinitionInterface
    {
        return DefinitionFactory::newDatabaseId($column)
                                ->setSize(6)
                                ->setLabel(tr('Parent'));
    }


    /**
     * Returns a Definition object for the column "parents_name"
     *
     * @param string|null $column
     *
     * @return DefinitionInterface
     */
    public static function newParent(?string $column = 'parents_name'): DefinitionInterface
    {
        return Definition::new($column)
                         ->setOptional(true)
                         ->setRender(false)
                         ->setVirtual(true)
                         ->setCliColumn('-p,--parent PARENT-NAME')
                         ->setLabel(tr('Parent'));
    }


    /**
     * Returns a Definition object for the column "companies_id"
     *
     * @param string|null $column
     * @param array|null  $filters
     *
     * @return DefinitionInterface
     */
    public static function newCompaniesId(?string $column = 'companies_id', ?array $filters = null): DefinitionInterface
    {
        return DefinitionFactory::newDatabaseId($column)
                                ->setOptional(true)
                                ->setContent(function (DefinitionInterface $definition, string $key, string $column_name, array $source) use ($filters) {
                                    return Companies::new()
                                                    ->getHtmlSelectOld()
                                                    ->setName($key)
                                                    ->setReadonly($definition->getReadonly())
                                                    ->setDisabled($definition->getDisabled())
                                                    ->setSelected(isset_get($source[$key]));
                                })
                                ->setSize(6)
                                ->setLabel(tr('Company'))
                                ->addValidationFunction(function (ValidatorInterface $validator) {
                                    // Ensure companies id exists and that it's or company
                                    $validator->orColumn('companies_name')
                                              ->isDbId()
                                              ->isQueryResult('SELECT `id` 
                                                               FROM   `business_companies` 
                                                               WHERE  `id` = :id 
                                                                 AND  `status` IS NULL', [
                                                                     ':id' => '$companies_id'
                                              ]);
                                });
    }


    /**
     * Returns a Definition object for the column ""company""
     *
     * @param string|null $column
     *
     * @return DefinitionInterface
     */
    public static function newCompany(?string $column = 'companies_name'): DefinitionInterface
    {
        return Definition::new($column)
                         ->setOptional(true)
                         ->setRender(false)
                         ->setVirtual(true)
                         ->setCliColumn('--company COMPANY-NAME')
                         ->setLabel(tr('Company'))
                         ->setCliAutoComplete([
                             'word'   => function ($word) {
                                 return Companies::new()->keepMatchingKeys($word);
                             },
                             'noword' => function ($word) {
                                 return Companies::new()->getSource();
                             },
                         ])
                         ->addValidationFunction(function (ValidatorInterface $validator) {
                             // Ensure the company exists and that it's or company
                             $validator->orColumn('companies_id')
                                       ->isName()
                                       ->setColumnFromQuery('companies_id', 'SELECT `id` 
                                                                             FROM   `business_companies` 
                                                                             WHERE  `name` = :name 
                                                                               AND  `status` IS NULL', [
                                                                                   ':name' => '$companies_name'
                                       ]);
                         });
    }


    /**
     * Returns a Definition object for the column "languages_id"
     *
     * @param string|null $column
     * @param array|null  $filters
     *
     * @return DefinitionInterface
     */
    public static function newLanguagesId(?string $column = 'languages_id', ?array $filters = null): DefinitionInterface
    {
        return DefinitionFactory::newDatabaseId($column)
                                ->setInputType(EnumInputType::number)
                                ->setContent(function (DefinitionInterface $definition, string $key, string $column_name, array $source) use ($filters) {
                                    return Languages::new()
                                                    ->getHtmlSelectOld(key_column: 'id')
                                                    ->setName($key)
                                                    ->setReadonly($definition->getReadonly())
                                                    ->setDisabled($definition->getDisabled())
                                                    ->setSelected(isset_get($source[$key]));
                                })
                                ->setSize(3)
                                ->setCliColumn('--languages-id')
                                ->setCliAutoComplete(true)
                                ->setLabel(tr('Language'))
                                ->setHelpGroup(tr('Location information'))
                                ->setHelpText(tr('The language in which the site will be displayed to the user'))
                                ->addValidationFunction(function (ValidatorInterface $validator) {
                                    $validator->orColumn('languages_name')
                                              ->isDbId()
                                              ->isQueryResult('SELECT `id` FROM `core_languages` WHERE `id` = :id AND `status` IS NULL', [':id' => '$languages_id']);
                                });
    }


    /**
     * Returns a Definition object for the column "language"
     *
     * @param string|null $column
     *
     * @return DefinitionInterface
     */
    public static function newLanguagesName(?string $column = 'languages_name'): DefinitionInterface
    {
        return Definition::new($column)
                         ->setOptional(true)
                         ->setRender(false)
                         ->setVirtual(true)
                         ->setMaxLength(32)
                         ->setCliColumn('--language-name LANGUAGE-NAME')
                         ->setLabel(tr('Language'))
                         ->setCliAutoComplete([
                             'word'   => function ($word) {
                                 return Languages::new()->keepMatchingKeys($word);
                             },
                             'noword' => function ($word) {
                                 return Languages::new()->getSource();
                             },
                         ])
                         ->addValidationFunction(function (ValidatorInterface $validator) {
                             // Ensure language exists and that it's or language
                             $validator->orColumn('languages_id')
                                       ->orColumn('languages_code')
                                       ->isName()
                                       ->setColumnFromQuery('languages_id', 'SELECT `id` 
                                                                             FROM   `core_languages` 
                                                                             WHERE  `name` = :name 
                                                                               AND  `status` IS NULL', [
                                           ':name' => '$languages_name'
                                       ]);
                         });
    }


    /**
     * Returns a Definition object for the column "language"
     *
     * @param string|null $column
     *
     * @return DefinitionInterface
     */
    public static function newLanguagesCode(?string $column = 'languages_code'): DefinitionInterface
    {
        return Definition::new($column)
                         ->setOptional(true)
                         ->setRender(false)
                         ->setVirtual(true)
                         ->setMaxLength(32)
                         ->setCliColumn('-l,--language-code LANGUAGE-CODE')
                         ->setLabel(tr('Language'))
                         ->setCliAutoComplete([
                             'word'   => function ($word) {
                                 return Languages::new()->keepMatchingKeys($word);
                             },
                             'noword' => function ($word) {
                                 return Languages::new()->getSource();
                             },
                         ])
                         ->addValidationFunction(function (ValidatorInterface $validator) {
                             // Ensure language exists and that it's or language
                             $validator->orColumn('languages_id')
                                       ->orColumn('languages_name')
                                       ->isName()
                                       ->setColumnFromQuery('languages_id', 'SELECT `id` 
                                                                             FROM   `core_languages` 
                                                                             WHERE  `code_639_1` = :code 
                                                                               AND  `status` IS NULL', [
                                           ':code' => '$languages_code'
                                       ]);
                         });
    }


    /**
     * Returns a Definition object for the column "providers_id"
     *
     * @param string|null $column
     * @param array|null  $filters
     *
     * @return DefinitionInterface
     */
    public static function newProvidersId(?string $column = 'providers_id', ?array $filters = null): DefinitionInterface
    {
        return DefinitionFactory::newDatabaseId($column)
                                ->setOptional(true)
                                ->setContent(function (DefinitionInterface $definition, string $key, string $column_name, array $source) use ($filters) {
                                    return Providers::new()
                                                    ->getHtmlSelectOld()
                                                    ->setName($key)
                                                    ->setReadonly($definition->getReadonly())
                                                    ->setDisabled($definition->getDisabled())
                                                    ->setSelected(isset_get($source[$key]));
                                })
                                ->setSize(6)
                                ->setLabel(tr('Provider'))
                                ->addValidationFunction(function (ValidatorInterface $validator) {
                                    // Ensure providers id exists and that it's or provider
                                    $validator->orColumn('providers_name')
                                              ->isDbId()
                                              ->isQueryResult('SELECT `id` 
                                                               FROM   `business_providers` 
                                                               WHERE  `id` = :id 
                                                                 AND  `status` IS NULL', [
                                                                     ':id' => '$providers_id'
                                              ]);
                                });
    }


    /**
     * Returns a Definition object for the column "provider"
     *
     * @param string|null $column
     *
     * @return DefinitionInterface
     */
    public static function newProvider(?string $column = 'providers_name'): DefinitionInterface
    {
        return Definition::new($column)
                         ->setOptional(true)
                         ->setSize(6)
                         ->setCliColumn('--provider PROVIDER-NAME')
                         ->setLabel(tr('Provider'))
                         ->setCliAutoComplete([
                             'word'   => function ($word) {
                                 return Providers::new()->keepMatchingKeys($word);
                             },
                             'noword' => function ($word) {
                                 return Providers::new()->getSource();
                             },
                         ])
                         ->addValidationFunction(function (ValidatorInterface $validator) {
                             // Ensure provider exists and that it's providers id or providers name
                             $validator->orColumn('providers_id')
                                       ->isName()
                                       ->setColumnFromQuery('providers_id', 'SELECT `id` 
                                                                             FROM   `business_providers` 
                                                                             WHERE  `name` = :name 
                                                                               AND  `status` IS NULL', [
                                                                                   ':code' => '$providers_name'
                                       ]);
                         });
    }


    /**
     * Returns a Definition object for the column "customers_id"
     *
     * @param string|null $column
     * @param array|null  $filters
     *
     * @return DefinitionInterface
     */
    public static function newCustomersId(?string $column = 'customers_id', ?array $filters = null): DefinitionInterface
    {
        return DefinitionFactory::newDatabaseId($column)
                                ->setOptional(true)
                                ->setContent(function (DefinitionInterface $definition, string $key, string $column_name, array $source) use ($filters) {
                                    return Customers::new()
                                                    ->getHtmlSelectOld()
                                                    ->setName($key)
                                                    ->setReadonly($definition->getReadonly())
                                                    ->setDisabled($definition->getDisabled())
                                                    ->setSelected(isset_get($source[$key]));
                                })
                                ->setSize(6)
                                ->setLabel(tr('Customer'))
                                ->addValidationFunction(function (ValidatorInterface $validator) {
                                    // Ensure customers id exists and that it's or customer
                                    $validator->orColumn('customers_name')
                                              ->isDbId()
                                              ->isQueryResult('SELECT `id` 
                                                               FROM   `business_customers` 
                                                               WHERE  `id` = :id 
                                                                 AND  `status` IS NULL', [
                                                                     ':id' => '$customers_id'
                                              ]);
                                });
    }


    /**
     * Returns a Definition object for the column "customer"
     *
     * @param string|null $column
     *
     * @return DefinitionInterface
     */
    public static function newCustomer(?string $column = 'customers_name'): DefinitionInterface
    {
        return Definition::new($column)
                         ->setOptional(true)
                         ->setSize(6)
                         ->setCliColumn('--customer CUSTOMER-NAME')
                         ->setLabel(tr('Customer'))
                         ->setCliAutoComplete([
                             'word'   => function ($word) {
                                 return Customers::new()->keepMatchingKeys($word);
                             },
                             'noword' => function ($word) {
                                 return Customers::new()->getSource();
                             },
                         ])
                         ->addValidationFunction(function (ValidatorInterface $validator) {
                             // Ensure customer exists and that it's or customer
                             $validator->orColumn('customers_id')
                                       ->isName()
                                       ->setColumnFromQuery('customers_id', 'SELECT `id` 
                                                                             FROM   `business_customers` 
                                                                             WHERE  `name` = :name 
                                                                               AND  `status` IS NULL', [
                                                                                   ':id' => '$customers_name'
                                       ]);
                         });
    }


    /**
     * Returns a Definition object for the column "timezones_id"
     *
     * @param string|null $column
     * @param array|null  $filters
     *
     * @return DefinitionInterface
     */
    public static function newTimezonesId(?string $column = 'timezones_id', ?array $filters = null): DefinitionInterface
    {
        return DefinitionFactory::newDatabaseId($column)
                                ->setInputType(EnumInputType::number)
                                ->setContent(function (DefinitionInterface $definition, string $key, string $column_name, array $source) use ($filters) {
                                    return Timezones::new()->getHtmlSelectObject()
                                                           ->setName($key)
                                                           ->setReadonly($definition->getReadonly())
                                                           ->setDisabled($definition->getDisabled())
                                                           ->setSelected(isset_get($source[$key]));
                                })
                                ->setCliColumn('--timezones-id TIMEZONE-DATABASE-ID')
                                ->setCliAutoComplete(true)
                                ->setSize(3)
                                ->setLabel(tr('Timezone'))
                                ->addValidationFunction(function (ValidatorInterface $validator) {
                                    $validator->orColumn('timezones_name')
                                              ->orColumn('timezones_code')
                                              ->isDbId()
                                              ->isTrue(function ($value) {
                                                  // This timezone must exist.
                                                  return Timezone::exists(['name' => $value]);
                                              }, tr('The specified timezone does not exist'));
                                });
    }


    /**
     * Returns a Definition object for the column "timezones_name"
     *
     * @param string|null $column
     *
     * @return DefinitionInterface
     */
    public static function newTimezonesName(?string $column = 'timezones_name'): DefinitionInterface
    {
        return Definition::new($column)
                         ->setOptional(true)
                         ->setRender(false)
                         ->setVirtual(true)
                         ->setCliColumn('-t,--timezone TIMEZONE-NAME')
                         ->setLabel(tr('Timezone'))
                         ->setMaxLength(64)
                         ->setCliAutoComplete([
                             'word'   => function ($word) {
                                 return Timezones::new()->keepMatchingKeys($word);
                             },
                             'noword' => function ($word) {
                                 return Timezones::new()->getSource();
                             },
                         ])
                         ->addValidationFunction(function (ValidatorInterface $validator) {
                             // Ensure timezone exists and that it's or timezone
                             $validator->orColumn('timezones_id')
                                       ->orColumn('timezones_code')
                                       ->isName()
                                       ->setColumnFromQuery('timezones_id', 'SELECT `id` 
                                                                             FROM   `geo_timezones` 
                                                                             WHERE  `name` = :name 
                                                                               AND  `status` IS NULL', [
                                                                                   ':name' => '$timezones_name'
                                       ]);
                         });
    }


    /**
     * Returns a Definition object for the column "timezones_code"
     *
     * @param string|null $column
     *
     * @return DefinitionInterface
     */
    public static function newTimezonesCode(?string $column = 'timezones_code'): DefinitionInterface
    {
        return Definition::new($column)
                         ->setOptional(true)
                         ->setRender(false)
                         ->setVirtual(true)
                         ->setCliColumn('-t,--timezone TIMEZONE-CODE')
                         ->setLabel(tr('Timezone'))
                         ->setCliAutoComplete([
                             'word'   => function ($word) {
                                 return Timezones::new()->keepMatchingKeys($word);
                             },
                             'noword' => function ($word) {
                                 return Timezones::new()->getSource();
                             },
                         ])
                         ->addValidationFunction(function (ValidatorInterface $validator) {
                             // Ensure timezone exists and that it's or timezone
                             $validator->orColumn('timezones_id')
                                       ->orColumn('timezones_name')
                                       ->isCode()
                                       ->setColumnFromQuery('timezones_id', 'SELECT `id` 
                                                                             FROM   `geo_timezones` 
                                                                             WHERE  `code` = :code 
                                                                               AND  `status` IS NULL', [
                                           ':code' => '$timezones_name'
                                       ]);
                         });
    }


    /**
     * Returns a Definition object for the column "countries_id"
     *
     * @param string|null $column
     * @param array|null  $filters
     *
     * @return DefinitionInterface
     */
    public static function newCountriesId(?string $column = 'countries_id', ?array $filters = null): DefinitionInterface
    {
        return DefinitionFactory::newDatabaseId($column)
                                ->setOptional(true)
                                ->setElement(EnumElement::select)
                                ->setInputType(EnumInputType::number)
                                ->setContent(function (DefinitionInterface $definition, string $key, string $column_name, array $source) use ($filters) {
                                    return Countries::new()->getHtmlSelectObject()
                                                           ->setName($key)
                                                           ->setReadonly($definition->getReadonly())
                                                           ->setDisabled($definition->getDisabled())
                                                           ->setSelected(isset_get($source[$key]));
                                })
                                ->setSize(3)
                                ->setCliColumn('--countries-id COUNTRY-DATABASE-ID')
                                ->setCliAutoComplete(true)
                                ->setLabel(tr('Country'))
                                ->addValidationFunction(function (ValidatorInterface $validator) {
                                    $validator->orColumn('countries_name')
                                              ->orColumn('countries_code')
                                              ->isDbId()
                                              ->isQueryResult('SELECT `id` 
                                                               FROM   `geo_countries` 
                                                               WHERE  `id` = :id 
                                                                 AND  `status` IS NULL', [
                                                                     ':id' => '$countries_id'
                                              ]);
                                });
    }


    /**
     * Returns a Definition object for the column "countries_name"
     *
     * @param string|null $column
     *
     * @return DefinitionInterface
     */
    public static function newCountriesName(?string $column = 'countries_name'): DefinitionInterface
    {
        return Definition::new($column)
                         ->setOptional(true)
                         ->setRender(false)
                         ->setVirtual(true)
                         ->setCliColumn('--country-name COUNTRY-NAME')
                         ->setLabel(tr('Country'))
                         ->setCliAutoComplete([
                             'word'   => function ($word) {
                                 return Countries::new()->keepMatchingKeys($word);
                             },
                             'noword' => function ($word) {
                                 return Countries::new()->getSource();
                             },
                         ])
                         ->addValidationFunction(function (ValidatorInterface $validator) {
                             // Ensure country exists and that it's or countries_id
                             $validator->orColumn('countries_id')
                                       ->orColumn('countries_code')
                                       ->isName()
                                       ->setColumnFromQuery('countries_id', 'SELECT `id` 
                                                                             FROM   `geo_countries` 
                                                                             WHERE  `name` = :name 
                                                                               AND  `status` IS NULL', [
                                                                                ':name' => '$countries_name'
                                       ]);
                         });
    }


    /**
     * Returns a Definition object for the column "countries_code"
     *
     * @param string|null $column
     *
     * @return DefinitionInterface
     */
    public static function newCountriesCode(?string $column = 'countries_code'): DefinitionInterface
    {
        return Definition::new($column)
                         ->setOptional(true)
                         ->setRender(false)
                         ->setVirtual(true)
                         ->setCliColumn('--country-code COUNTRY-CODE')
                         ->setLabel(tr('Country'))
                         ->setCliAutoComplete([
                             'word'   => function ($word) {
                                 return Countries::new()->keepMatchingKeys($word);
                             },
                             'noword' => function ($word) {
                                 return Countries::new()->getSource();
                             },
                         ])
                         ->addValidationFunction(function (ValidatorInterface $validator) {
                             // Ensure country exists and that it's or countries_id
                             $validator->orColumn('countries_id')
                                       ->orColumn('countries_name')
                                       ->isCode()
                                       ->setColumnFromQuery('countries_id', 'SELECT `id` 
                                                                             FROM   `geo_countries` 
                                                                             WHERE  `code` = :code 
                                                                               AND  `status` IS NULL', [
                                                                                ':code' => '$countries_code'
                                       ]);
                         });
    }


    /**
     * Returns a Definition object for the column "states_id"
     *
     * @param string|null $column
     * @param array|null  $filters
     *
     * @return DefinitionInterface
     */
    public static function newStatesId(?string $column = 'states_id', ?array $filters = null): DefinitionInterface
    {
        return DefinitionFactory::newDatabaseId($column)
                                ->setInputType(EnumInputType::number)
                                ->setContent(function (DefinitionInterface $definition, string $key, string $column_name, array $source) use ($filters) {
                                    return Country::new($source['countries_id'])
                                                  ->getHtmlStatesSelect($key)
                                                  ->setName($key)
                                                  ->setReadonly($definition->getReadonly())
                                                  ->setDisabled($definition->getDisabled())
                                                  ->setSelected(isset_get($source[$key]));
                                })
                                ->setSize(3)
                                ->setCliColumn('--states-id STATE-DATABASE-ID')
                                ->setCliAutoComplete(true)
                                ->setLabel(tr('State'))
                                ->addValidationFunction(function (ValidatorInterface $validator) {
                                    $validator->orColumn('states_name')
                                              ->orColumn('states_code')
                                              ->isDbId()
                                              ->isQueryResult('SELECT `id` 
                                                               FROM   `geo_states` 
                                                               WHERE  `id`           = :id 
                                                                 AND  `countries_id` = :countries_id 
                                                                 AND  `status` IS NULL', [
                                                                     ':id'           => '$states_id',
                                                                     ':countries_id' => '$countries_id',
                                              ]);
                                });
    }


    /**
     * Returns a Definition object for the column "states_name"
     *
     * @param string|null $column
     *
     * @return DefinitionInterface
     */
    public static function newStatesName(?string $column = 'states_name'): DefinitionInterface
    {
        return Definition::new($column)
                         ->setOptional(true)
                         ->setRender(false)
                         ->setVirtual(true)
                         ->setCliColumn('--states-name STATE-NAME')
                         ->setLabel(tr('State'))
                         ->setCliAutoComplete([
                             'word'   => function ($word) {
                                 return States::new()->keepMatchingKeys($word);
                             },
                             'noword' => function ($word) {
                                 return States::new()->getSource();
                             },
                         ])
                         ->addValidationFunction(function (ValidatorInterface $validator) {
                             // Ensure state exists and that it's or states_id
                             $validator->orColumn('states_id')
                                       ->orColumn('states_code')
                                       ->isName()
                                       ->setColumnFromQuery('states_id', 'SELECT `name` 
                                                                          FROM   `geo_states` 
                                                                          WHERE  `name`         = :name 
                                                                            AND  `countries_id` = :countries_id 
                                                                            AND  `status` IS NULL', [
                                                                           ':name'         => '$states_name',
                                                                           ':countries_id' => '$countries_id',
                                       ]);
                         });
    }


    /**
     * Returns a Definition object for the column "states_code"
     *
     * @param string|null $column
     *
     * @return DefinitionInterface
     */
    public static function newStatesCode(?string $column = 'states_code'): DefinitionInterface
    {
        return Definition::new($column)
                         ->setOptional(true)
                         ->setRender(false)
                         ->setVirtual(true)
                         ->setCliColumn('--states-coode STATE-CODE')
                         ->setLabel(tr('State'))
                         ->setCliAutoComplete([
                             'word'   => function ($word) {
                                 return States::new()->keepMatchingKeys($word);
                             },
                             'noword' => function ($word) {
                                 return States::new()->getSource();
                             },
                         ])
                         ->addValidationFunction(function (ValidatorInterface $validator) {
                             // Ensure state exists and that it's or states_id
                             $validator->orColumn('states_id')
                                       ->orColumn('states_name')
                                       ->isCode()
                                       ->setColumnFromQuery('states_id', 'SELECT `code` 
                                                                          FROM   `geo_states` 
                                                                          WHERE  `code`         = :code 
                                                                            AND  `countries_id` = :countries_id 
                                                                            AND  `status` IS NULL', [
                                                                               ':code'         => '$states_code',
                                                                               ':countries_id' => '$countries_id',
                                       ]);
                         });
    }


    /**
     * Returns a Definition object for the column "cities_id"
     *
     * @param string|null $column
     * @param array|null  $filters
     *
     * @return DefinitionInterface
     */
    public static function newCitiesId(?string $column = 'cities_id', ?array $filters = null): DefinitionInterface
    {
        return DefinitionFactory::newDatabaseId($column)
                                ->setInputType(EnumInputType::number)
                                ->setContent(function (DefinitionInterface $definition, string $key, string $column_name, array $source) use ($filters) {
                                    return State::new($source['states_id'])
                                                ->getHtmlCitiesSelect($key)
                                                ->setName($key)
                                                ->setReadonly($definition->getReadonly())
                                                ->setDisabled($definition->getDisabled())
                                                ->setSelected(isset_get($source[$key]));
                                })
                                ->setSize(3)
                                ->setCliColumn('--cities-id CITY-DATABASE-ID')
                                ->setCliAutoComplete(true)
                                ->setLabel(tr('City'))
                                ->addValidationFunction(function (ValidatorInterface $validator) {
                                    $validator->orColumn('cities_name')
                                              ->orColumn('cities_code')
                                              ->isDbId()
                                              ->isQueryResult('SELECT `id` 
                                                               FROM   `geo_cities` 
                                                               WHERE  `id` = :id 
                                                                 AND  `states_id`  = :states_id    
                                                                 AND  `status` IS NULL', [
                                                                     ':id'        => '$cities_id',
                                                                     ':states_id' => '$states_id',
                                              ]);
                                });
    }


    /**
     * Returns a Definition object for a city name
     * @param string|null $column
     *
     * @return DefinitionInterface
     */
    public static function newCitiesName(?string $column = 'cities_name'): DefinitionInterface
    {
        return Definition::new($column)
                         ->setOptional(true)
                         ->setRender(false)
                         ->setVirtual(true)
                         ->setCliColumn('--cities-name CITY-NAME')
                         ->setLabel(tr('City'))
                         ->setCliAutoComplete([
                             'word'   => function ($word) {
                                 return Cities::new()->keepMatchingKeys($word);
                             },
                             'noword' => function ($word) {
                                 return Cities::new()->getSource();
                             },
                         ])
                         ->addValidationFunction(function (ValidatorInterface $validator) {
                             // Ensure city exists and that it's or cities_id
                             $validator->orColumn('cities_id')
                                       ->orColumn('cities_code')
                                       ->isName()
                                       ->setColumnFromQuery('cities_id', 'SELECT `name` 
                                                                          FROM   `geo_cities` 
                                                                          WHERE  `name`      = :name 
                                                                            AND  `states_id` = :states_id    
                                                                            AND  `status` IS NULL', [
                                                                                ':name'      => '$cities_name',
                                                                                ':states_id' => '$states_id',
                                       ]);
                         });
    }


    /**
     * Returns a Definition object for a city code
     * @param string|null $column
     *
     * @return DefinitionInterface
     */
    public static function newCitiesCode(?string $column = 'cities_code'): DefinitionInterface
    {
        return Definition::new($column)
                         ->setOptional(true)
                         ->setRender(false)
                         ->setVirtual(true)
                         ->setCliColumn('--cities-code CITY-CODE')
                         ->setLabel(tr('City'))
                         ->setCliAutoComplete([
                             'word'   => function ($word) {
                                 return Cities::new()->keepMatchingKeys($word);
                             },
                             'noword' => function ($word) {
                                 return Cities::new()->getSource();
                             },
                         ])
                         ->addValidationFunction(function (ValidatorInterface $validator) {
                             // Ensure city exists and that it's or cities_id
                             $validator->orColumn('cities_id')
                                       ->orColumn('cities_name')
                                       ->isCode()
                                       ->setColumnFromQuery('cities_id', 'SELECT `code` 
                                                                          FROM   `geo_cities` 
                                                                          WHERE  `code`      = :code 
                                                                            AND  `states_id` = :states_id    
                                                                            AND  `status` IS NULL', [
                                                                           ':code'      => '$cities_code',
                                                                           ':states_id' => '$states_id',
                                       ]);
                         });
    }


    /**
     * Returns a Definition object for the column "users_id"
     *
     * @param string|null $column
     * @param array|null  $filters
     *
     * @return DefinitionInterface
     */
    public static function newUsersId(?string $column = 'users_id', ?array $filters = null): DefinitionInterface
    {
        return DefinitionFactory::newDatabaseId($column)
                                ->setRender(false)
                                ->setInputType(EnumInputType::dbid)
                                ->setSize(3)
                                ->setCliAutoComplete(true)
                                ->setContent(function (DefinitionInterface $definition, string $key, string $column_name, array $source) use ($filters, $column) {
                                    return Users::new()
                                                ->getHtmlSelectOld()
                                                ->setId($column)
                                                ->setName($column)
                                                ->setReadonly($definition->getReadonly())
                                                ->setDisabled($definition->getDisabled())
                                                ->setSelected(isset_get($source[$key]));
                                })
                                ->addValidationFunction(function (ValidatorInterface $validator) use ($column) {
                                    $validator->isDbId()
                                              ->isQueryResult('SELECT `id` 
                                                               FROM   `accounts_users` 
                                                               WHERE  `id` = :id 
                                                                 AND  ((`status` IS NULL) or (`status` != "deleted"))', [
                                                                     ':id' => '$' . $column
                                              ]);
                                });
    }


    /**
     * Returns a Definition object for the column "id"
     *
     * @param string $column
     *
     * @return DefinitionInterface
     */
    public static function newId(string $column): DefinitionInterface
    {
        return DefinitionFactory::newDatabaseId($column)
                                ->setRender(false)
                                ->setSize(3)
                                ->setCliAutoComplete(true);
    }


    /**
     * Returns a Definition object for the column "users_id"
     *
     * @param string|null $column
     *
     * @return DefinitionInterface
     */
    public static function newUsersEmail(?string $column = 'email'): DefinitionInterface
    {
        return Definition::new($column)
                         ->setOptional(true)
                         ->setRender(false)
                         ->setVirtual(true)
                         ->setInputType(EnumInputType::email)
                         ->setCliColumn('-u,--user EMAIL')
                         ->setLabel(tr('User'))
                         ->setCliAutoComplete([
                             'word'   => function ($word) {
                                 return Users::new()->keepMatchingKeys($word);
                             },
                             'noword' => function ($word) {
                                 return Users::new()->getSource();
                             },
                         ])
                         ->addValidationFunction(function (ValidatorInterface $validator) {
                             $validator->orColumn('users_id')
                                       ->setColumnFromQuery('users_id', 'SELECT `id` 
                                                                         FROM   `accounts_users` 
                                                                         WHERE  `email` = :email', [
                                                                             ':email' => '$email'
                                       ]);
                         });
    }


    /**
     * Returns a Definition object for the column "users_id"
     *
     * @param string|null $column
     *
     * @return DefinitionInterface
     */
    public static function newUsername(?string $column = 'username'): DefinitionInterface
    {
        return Definition::new($column)
                         ->setOptional(true)
                         ->setRender(true)
                         ->setInputType(EnumInputType::name)
                         ->setCliColumn('-u,--username NAME')
                         ->setLabel(tr('Username'))
                         ->setCliAutoComplete(true);
    }


    /**
     * Returns a Definition object for the column "roles_id"
     *
     * @param string|null $column
     * @param array|null  $filters
     *
     * @return DefinitionInterface
     */
    public static function newRolesId(?string $column = 'roles_id', ?array $filters = null): DefinitionInterface
    {
        return DefinitionFactory::newDatabaseId($column)
                                ->setInputType(EnumInputType::dbid)
                                ->setSize(3)
                                ->setCliAutoComplete(true)
                                ->setContent(function (DefinitionInterface $definition, string $key, string $column_name, array $source) use ($filters, $column) {
                                    return Roles::new()
                                                ->getHtmlSelectOld()
                                                ->setId($column)
                                                ->setName($column)
                                                ->setReadonly($definition->getReadonly())
                                                ->setDisabled($definition->getDisabled())
                                                ->setSelected(isset_get($source[$key]));
                                })
                                ->addValidationFunction(function (ValidatorInterface $validator) use ($column) {
                                    $validator->isDbId()
                                              ->isQueryResult('SELECT `id` 
                                                               FROM   `accounts_roles` 
                                                               WHERE  `id` = :id 
                                                                 AND `status` IS NULL', [
                                                                     ':id' => '$' . $column
                                              ]);
                                });
    }


    /**
     * Returns a Definition object for the column "roles_id"
     *
     * @param string|null $column
     *
     * @return DefinitionInterface
     */
    public static function newRolesName(?string $column = 'name'): DefinitionInterface
    {
        return Definition::new($column)
                         ->setOptional(true)
                         ->setRender(false)
                         ->setVirtual(true)
                         ->setInputType(EnumInputType::name)
                         ->setCliColumn('-r,--role EMAIL')
                         ->setLabel(tr('Role'))
                         ->setCliAutoComplete([
                             'word'   => function ($word) {
                                 return Roles::new()->keepMatchingKeys($word);
                             },
                             'noword' => function ($word) {
                                 return Roles::new()->getSource();
                             },
                         ])
                         ->addValidationFunction(function (ValidatorInterface $validator) {
                             $validator->orColumn('roles_id')
                                       ->setColumnFromQuery('roles_id', 'SELECT `id`  
                                                                         FROM   `accounts_roles` 
                                                                         WHERE  `name` = :name', [
                                                                             ':name' => '$name'
                                       ]);
                         });
    }


    /**
     * Returns a Definition object for the column "code"
     *
     * @param string|null $column
     *
     * @return DefinitionInterface
     */
    public static function newCode(?string $column = 'code'): DefinitionInterface
    {
        return Definition::new($column)
                         ->setOptional(true)
                         ->setInputType(EnumInputType::code)
                         ->setSize(3)
                         ->setMaxLength(64)
                         ->setMinLength(1)
                         ->setCliColumn('-c,--code CODE')
                         ->setCliAutoComplete(true)
                         ->setLabel(tr('Code'));
    }


    /**
     * Returns a Definition object for the column "hash"
     *
     * @param string|null $column
     *
     * @return DefinitionInterface
     */
    public static function newHash(?string $column = 'hash'): DefinitionInterface
    {
        return Definition::new($column)
                         ->setOptional(true)
                         ->setReadonly(true)
                         ->setInputType(EnumInputType::code)
                         ->setSize(3)
                         ->setMaxLength(128)
                         ->setMinLength(1)
                         ->setCliAutoComplete(true)
                         ->setLabel(tr('Hash'));
    }


    /**
     * Returns a Definition object for the column "datetime"
     *
     * @param string|null $column
     *
     * @return DefinitionInterface
     */
    public static function newDateTime(?string $column = 'datetime'): DefinitionInterface
    {
        return Definition::new($column)
                         ->setOptional(true)
                         ->setInputType(EnumInputType::datetime_local)
                         ->setSize(3)
                         ->setMaxLength(20)
                         ->setLabel(tr('Date time'));
    }


    /**
     * Returns a Definition object for the column "date"
     *
     * @param string|null $column
     *
     * @return DefinitionInterface
     */
    public static function newDate(?string $column = 'date'): DefinitionInterface
    {
        return Definition::new($column)
                         ->setOptional(true)
                         ->setInputType(EnumInputType::date)
                         ->setSize(3)
                         ->setMaxLength(10)
                         ->setCliAutoComplete(true)
                         ->setLabel(tr('Date'));
    }


    /**
     * Returns a Definition object for a column containing a variable
     *
     * @param string|null $column
     *
     * @return DefinitionInterface
     */
    public static function newHostname(?string $column = 'name'): DefinitionInterface
    {
        return Definition::new($column)
                         ->setMaxLength(255)
                         ->setOptional(true)
                         ->setSize(6)
                         ->setInputType(EnumInputType::text)
                         ->setCliAutoComplete(true)
                         ->addValidationFunction(function (ValidatorInterface $validator) {
                             $validator->isDomainOrIp();
                         });
    }


    /**
     * Returns a Definition object for a column containing a number
     *
     * @param string|null $column
     * @param int|null                $default
     *
     * @return DefinitionInterface
     */
    public static function newPort(?string $column = 'number', ?int $default = null): DefinitionInterface
    {
        return static::newNumber($column, $default)
                     ->setInputType(EnumInputType::positiveInteger)
                     ->setMin(1)
                     ->setMax(65535);
    }


    /**
     * Returns a Definition object for a column containing a variable
     *
     * @param string|null $column
     *
     * @return DefinitionInterface
     */
    public static function newVariable(?string $column = 'name'): DefinitionInterface
    {
        return Definition::new($column)
                         ->setMaxLength(255)
                         ->setOptional(true)
                         ->setSize(6)
                         ->setInputType(EnumInputType::variable)
                         ->setCliAutoComplete(true)
                         ->addValidationFunction(function (ValidatorInterface $validator) {
                             $validator->isVariable();
                         });
    }


    /**
     * Returns a Definition object for a column containing a number
     *
     * @param string|null $column
     * @param int|null                $default
     *
     * @return DefinitionInterface
     */
    public static function newNumber(?string $column = 'number', ?int $default = null): DefinitionInterface
    {
        return Definition::new($column)
                         ->setOptional(true, $default)
                         ->setInputType(EnumInputType::number)
                         ->setSize(4)
                         ->setMin(0)
                         ->setCliAutoComplete(true)
                         ->addValidationFunction(function (ValidatorInterface $validator) {
                             $validator->isNumeric();
                         });
    }


    /**
     * Returns a Definition object for the column "password"
     *
     * @param string|null $column
     *
     * @return DefinitionInterface
     */
    public static function newPassword(?string $column = 'password'): DefinitionInterface
    {
        return Definition::new($column)
                         ->setMaxLength(255)
                         ->setOptional(true)
                         ->setSize(6)
                         ->setInputType(EnumInputType::password)
                         ->setCliAutoComplete(true)
                         ->addValidationFunction(function (ValidatorInterface $validator) {
                             $validator->isPassword();
                         });
    }


    /**
     * Returns a Definition object for the column "date"
     *
     * @param string|null $column
     *
     * @return DefinitionInterface
     */
    public static function newTime(?string $column = 'time'): DefinitionInterface
    {
        return Definition::new($column)
                         ->setOptional(true)
                         ->setInputType(EnumInputType::time)
                         ->setSize(3)
                         ->setCliAutoComplete(true)
                         ->setLabel(tr('Time'));
    }


    /**
     * Returns a Definition object for the column "title"
     *
     * @param string|null $column
     *
     * @return DefinitionInterface
     */
    public static function newTitle(?string $column = 'title'): DefinitionInterface
    {
        return Definition::new($column)
                         ->setOptional(true)
                         ->setMaxLength(24)
                         ->setInputType(EnumInputType::name)
                         ->setSize(3)
                         ->setCliColumn('-t,--title TITLE')
                         ->setCliAutoComplete(true)
                         ->setLabel(tr('Title'))
                         ->addValidationFunction(function (ValidatorInterface $validator) {
                             $validator->isName();
                         });
    }


    /**
     * Returns a Definition object for the column "name"
     *
     * @param string|null $column
     *
     * @return DefinitionInterface
     */
    public static function newName(?string $column = 'name'): DefinitionInterface
    {
        return Definition::new($column)
                         ->setMaxLength(128)
                         ->setOptional(true)
                         ->setSize(3)
                         ->setLabel(tr('Name'))
                         ->setCliColumn(tr('[-n,--name NAME]'))
                         ->setInputType(EnumInputType::name)
                         ->setCliAutoComplete(true);
    }


    /**
     * Returns a Definition object for the column "file"
     *
     * @param PhoDirectoryInterface|null $exists_in_directory
     * @param string|null                $column
     *
     * @return DefinitionInterface
     */
    public static function newFile(?PhoDirectoryInterface $exists_in_directory = null, ?string $column = 'file'): DefinitionInterface
    {
        return Definition::new($column)
                         ->setMaxLength(2048)
                         ->setOptional(true)
                         ->setSize(3)
                         ->setLabel(tr('File'))
                         ->setCliColumn(tr('-f,--file PATH'))
                         ->setInputType(EnumInputType::text)
                         ->setCliAutoComplete(true)
                         ->addValidationFunction(function (ValidatorInterface $validator) use ($exists_in_directory) {
                             if ($exists_in_directory) {
                                 $validator->isFile($exists_in_directory);
                             }
                         });
    }


    /**
     * Returns a Definition object for the column "filename"
     *
     * @param string|null $column
     *
     * @return DefinitionInterface
     */
    public static function newFilename(?string $column = 'filename'): DefinitionInterface
    {
        return Definition::new($column)
                         ->setMaxLength(2048)
                         ->setOptional(true)
                         ->setSize(3)
                         ->setLabel(tr('Filename'))
                         ->setCliColumn(tr('-f,--filename NAME'))
                         ->setInputType(EnumInputType::text)
                         ->setCliAutoComplete(true)
                         ->addValidationFunction(function (ValidatorInterface $validator) {
                             $validator->matchesNotRegex('/\//');
                         });
    }


    /**
     * Returns a Definition object for the column "email"
     *
     * @param string|null $column
     *
     * @return DefinitionInterface
     */
    public static function newEmail(?string $column = 'email'): DefinitionInterface
    {
        return Definition::new($column)
                         ->setOptional(true)
                         ->setInputType(EnumInputType::email)
                         ->setCliColumn('-e,--email EMAIL')
                         ->setCliAutoComplete(true)
                         ->setLabel(tr('Email address'));
    }


    /**
     * Returns a Definition object for the column "url"
     *
     * @param string|null $column
     *
     * @return DefinitionInterface
     */
    public static function newUrl(?string $column = 'url'): DefinitionInterface
    {
        return Definition::new($column)
                         ->setOptional(true)
                         ->setInputType(EnumInputType::url)
                         ->setCliAutoComplete(true)
                         ->setCliColumn('-w,--website WEBSITE-URL')
                         ->setLabel(tr('Website URL'))
                         ->addValidationFunction(function (ValidatorInterface $validator) {
                             $validator->isOptional()
                                       ->isUrl();
                         });
    }


    /**
     * Returns a Definition object for the column "ip_address"
     *
     * @param string|null $column
     *
     * @return DefinitionInterface
     */
    public static function newIpAddress(?string $column = 'ip_address'): DefinitionInterface
    {
        return Definition::new($column)
                         ->setReadonly(true)
                         ->setInputType(EnumInputType::text)
                         ->setSize(6)
                         ->setMaxLength(48)
                         ->setCliAutoComplete(true)
                         ->setLabel(tr('IP Address'))
                         ->addValidationFunction(function (ValidatorInterface $validator) {
                             $validator->isIpAddress();
                         });
    }


    /**
     * Returns a Definition object for the column "domain"
     *
     * @param string|null $column
     *
     * @return DefinitionInterface
     */
    public static function newDomain(?string $column = 'domain'): DefinitionInterface
    {
        return Definition::new($column)
                         ->setReadonly(true)
                         ->setInputType(EnumInputType::text)
                         ->setSize(6)
                         ->setMaxLength(255)
                         ->setCliAutoComplete(true)
                         ->setLabel(tr('Domain name'))
                         ->addValidationFunction(function (ValidatorInterface $validator) {
                             $validator->isDomain();
                         });
    }


    /**
     * Returns a Definition object for the column "phone"
     *
     * @param string|null $column
     *
     * @return DefinitionInterface
     */
    public static function newPhone(?string $column = 'phone'): DefinitionInterface
    {
        return Definition::new($column)
                         ->setOptional(true)
                         ->setInputType(EnumInputType::phone)
                         ->setLabel(tr('Phone number'))
                         ->setCliColumn(tr('-p,--phone-number PHONE-NUMBER'))
                         ->addValidationFunction(function (ValidatorInterface $validator) {
                             $validator->isPhoneNumber();
                         })
                         ->setDisplayCallback(function (mixed $value, array $source) {
                             return Session::getUserObject()->getLocaleObject()->formatPhoneNumber($value);
                         });
    }


    /**
     * Returns a Definition object for the column "phones"
     *
     * @param string|null $column
     *
     * @return DefinitionInterface
     */
    public static function newPhones(?string $column = 'phones'): DefinitionInterface
    {
        return Definition::new($column)
                         ->setOptional(true)
                         ->setMinLength(10)
                         ->setMaxLength(64)
                         ->setSize(3)
                         ->setCliColumn(tr('-p,--phone-numbers "PHONE-NUMBER,PHONE-NUMBER,..."'))
                         ->setCliAutoComplete(true)
                         ->setLabel(tr('Phone numbers'))
                         ->setHelpGroup(tr('Personal information'))
                         ->setHelpText(tr('Phone numbers where this user can be reached'))
                         ->addValidationFunction(function (ValidatorInterface $validator) {
                             $validator->isPhoneNumbers();
                             // $validator->sanitizeForceArray(',')->forEachField()->isPhoneNumber()->sanitizeForceString()
                         });
    }


    /**
     * Returns a Definition object for the column "seo_name"
     *
     * @param string|null $column
     *
     * @return DefinitionInterface
     */
    public static function newSeoName(?string $column = 'seo_name'): DefinitionInterface
    {
        return DefinitionFactory::newName($column)
                                ->setOptional(true)
                                ->setRender(false)
                                ->setReadonly(true);
    }


    /**
     * Returns a Definition object for the column "description"
     *
     * @param string|null $column
     *
     * @return DefinitionInterface
     */
    public static function newUuid(?string $column = 'uuid'): DefinitionInterface
    {
        return Definition::new($column)
                         ->setReadonly(true)
                         ->setInputType(EnumInputType::text)
                         ->setSize(6)
                         ->setMaxLength(36)
                         ->setCliAutoComplete(true)
                         ->setLabel(tr('UUID'));
    }


    /**
     * Returns a Definition object for a boolean column (checkbox)
     *
     * @param string|null $column
     *
     * @return DefinitionInterface
     */
    public static function newBoolean(?string $column): DefinitionInterface
    {
        return Definition::new($column)
                         ->setOptional(true)
                         ->setDefault(false)
                         ->setInputType(EnumInputType::checkbox)
                         ->setSize(2);
    }


    /**
     * Returns a Definition object for an array column (select list, for example)
     *
     * @param string|null $column
     *
     * @return DefinitionInterface
     */
    public static function newArray(?string $column): DefinitionInterface
    {
        return Definition::new($column)
                         ->setOptional(true)
                         ->setDefault([])
                         ->setInputType(EnumInputType::select)
                         ->setSize(2)
                         ->addValidationFunction(function (ValidatorInterface $validator) {
                             $validator->isArray();
                         });
    }


    /**
     * Returns a Definition object for generic data column
     *
     * @param string             $column
     *
     * @return DefinitionInterface
     */
    public static function newData(string $column = 'data'): DefinitionInterface
    {
        return Definition::new($column)
                         ->setOptional(true)
                         ->setElement(EnumElement::textarea, false)
                         ->setInputType(EnumInputType::array_json)
                         ->setSize(12)
                         ->setRows(5)
                         ->setMaxLength(16_777_200)
                         ->setLabel(tr('Data'))
                         ->setCliAutoComplete(true);
    }


    /**
     * Returns a Definition object for the column "description"
     *
     * @param string|null $column
     *
     * @return DefinitionInterface
     */
    public static function newDescription(?string $column = 'description'): DefinitionInterface
    {
        return Definition::new($column)
                         ->setOptional(true)
                         ->setInputType(EnumInputType::description)
                         ->setSize(12)
                         ->setRows(5)
                         ->setMaxLength(65_535)
                         ->setCliColumn('-d,--description "DESCRIPTION"')
                         ->setCliAutoComplete(true)
                         ->setLabel(tr('Description'));
    }


    /**
     * Returns a Definition object for the column "body"
     *
     * @param string|null $column
     *
     * @return DefinitionInterface
     */
    public static function newBody(?string $column = 'body'): DefinitionInterface
    {
        return Definition::new($column)
                         ->setOptional(true)
                         ->setInputType(EnumInputType::description)
                         ->setSize(12)
                         ->setRows(5)
                         ->setMaxLength(16_777_215)
                         ->setCliColumn('-b,--body "BODY"')
                         ->setCliAutoComplete(true)
                         ->setLabel(tr('Body'));
    }


    /**
     * Returns a Definition object for the column "content"
     *
     * @param string|null $column
     *
     * @return DefinitionInterface
     */
    public static function newContent(?string $column = 'content'): DefinitionInterface
    {
        return Definition::new($column)
                         ->setOptional(true)
                         ->setInputType(EnumInputType::text)
                         ->setSize(12)
                         ->setMaxLength(16_777_215)
                         ->setCliColumn('--content "CONTENT"')
                         ->setCliAutoComplete(true)
                         ->setLabel(tr('Content'));
    }


    /**
     * Returns a Definition object for the column "comments"
     *
     * @param string|null $column
     *
     * @return DefinitionInterface
     */
    public static function newComments(?string $column = 'comments'): DefinitionInterface
    {
        return Definition::new($column)
                         ->setOptional(true)
                         ->setInputType(EnumInputType::description)
                         ->setSize(12)
                         ->setRows(5)
                         ->setMaxLength(65_535)
                         ->setCliColumn('--comments "COMMENTS"')
                         ->setCliAutoComplete(true)
                         ->setLabel(tr('Comments'));
    }


    /**
     * Returns a Definition object for buttons
     *
     * @param string|null $column
     *
     * @return DefinitionInterface
     */
    public static function newButton(?string $column): DefinitionInterface
    {
        return Definition::new($column)
                         ->setOptional(true)
                         ->addClasses('btn-primary')
                         ->setRender(true)
                         ->setVirtual(true)
                         ->setContainsData(false)
                         ->setElement(EnumElement::input)
                         ->setInputType(EnumInputType::button)
                         ->setLabel(tr(' '))
                         ->setSize(1);
    }


    /**
     * Returns a Definition object for buttons
     *
     * @param string|null $column
     *
     * @return DefinitionInterface
     */
    public static function newSubmit(?string $column): DefinitionInterface
    {
        return Definition::new($column)
                         ->setOptional(true)
                         ->addClasses('btn-primary')
                         ->setRender(true)
                         ->setVirtual(true)
                         ->setElement(EnumElement::input)
                         ->setInputType(EnumInputType::submit)
                         ->setLabel(tr(' '))
                         ->setSize(1);
    }


    /**
     * Returns a Definition object for created_by
     *
     * @param string|null $column
     *
     * @return DefinitionInterface
     */
    public static function newCreatedBy(?string $column = 'created_by'): DefinitionInterface
    {
        return DefinitionFactory::newDatabaseId($column)
                                ->setDisabled(true)
                                ->setSize(3)
                                ->setLabel(tr('Created by'))
                                ->setTooltip(tr('This column contains the user who created this object. Other users may have made further edits to this object, that information may be found in the object\'s meta data'))
                                ->setInputType(EnumInputType::dbid)
                                ->addValidationFunction(function (ValidatorInterface $validator) {
                                    $validator->columnExists(tr('must be an existing user'), table: 'accounts_users');
                                })
                                ->setContent(function (DefinitionInterface $definition, string $key, string $column_name, array $source) {
                                    if ($definition->getDataEntryObject()->isNew()) {
                                        // This is a new DataEntry object, so the creator is.. Well, you!
                                        return InputText::new()
                                                        ->setDisabled(true)
                                                        ->addClasses('text-center')
                                                        ->setValue(Session::getUserObject()->getDisplayName());
                                    }

                                    // This is created by a user or by the system user
                                    if ($source[$key]) {
                                        return InputText::new()
                                                        ->setDisabled(true)
                                                        ->addClasses('text-center')
                                                        ->setValue(User::new()->load($source[$key])->getDisplayName());
                                    }

                                    return InputText::new()
                                                    ->setDisabled(true)
                                                    ->addClasses('text-center')
                                                    ->setContent(tr('System'));
                                });
    }


    /**
     * Returns a Definition object for created_on
     *
     * @param string|null $column
     *
     * @return DefinitionInterface
     */
    public static function newCreatedOn(?string $column = 'created_on'): DefinitionInterface
    {
        return Definition::new($column)
                         ->setDisabled(true)
                         ->setInputType(EnumInputType::datetime_local)
                         ->setDbNullInputType(EnumInputType::text)
                         ->addClasses('text-center')
                         ->setSize(3)
                         ->setMaxLength(20)
                         ->setTooltip(tr('This column contains the exact date / time when this object was created'))
                         ->setLabel(tr('Created on'));
    }


    /**
     * Returns a Definition object for meta_id
     *
     * @param string|null $column
     *
     * @return DefinitionInterface
     */
    public static function newMetaId(?string $column = 'meta_id'): DefinitionInterface
    {
        return DefinitionFactory::newDatabaseId($column)
                                ->setDisabled(true)
                                ->setRender(false)
                                ->setInputType(EnumInputType::dbid)
                                ->setDbNullInputType(EnumInputType::text)
                                ->setTooltip(tr('This column contains the identifier for this object\'s audit history'))
                                ->setLabel(tr('Meta ID'));
    }


    /**
     * Returns a Definition object for status
     *
     * @param string|null $column
     *
     * @return DefinitionInterface
     */
    public static function newStatus(?string $column = 'status'): DefinitionInterface
    {
        return Definition::new($column)
                         ->setOptional(true)
                         ->setDisabled(true)
                         ->setInputType(EnumInputType::text)
                         ->setTooltip(tr('This column contains the current status of this object. A typical status is "Ok", but objects may also be "Deleted" or "In process", for example. Depending on their status, objects may be visible in tables, or not'))
                         ->addClasses('text-center')
                         ->setSize(3)
                         ->setMaxLength(32)
                         ->setLabel(tr('Status'));
    }


    /**
     * Returns a Definition object for meta_state
     *
     * @param string|null $column
     *
     * @return DefinitionInterface
     */
    public static function newMetaState(?string $column = 'meta_state'): DefinitionInterface
    {
        return Definition::new($column)
                         ->setDisabled(true)
                         ->setRender(false)
                         ->setInputType(EnumInputType::text)
                         ->setTooltip(tr('This column contains a cache identifier value for this object. This information usually is of no importance to normal users'))
                         ->setLabel(tr('Meta state'));
    }


    /**
     * Returns a Definition object that will display an <hr> divider
     *
     * @param string|null $column
     *
     * @return DefinitionInterface
     */
    public static function newDivider(?string $column = null): DefinitionInterface
    {
        if (!$column) {
            $column = 'divider-' . Strings::getUuid();
        }

        return Definition::new($column)
                         ->setVirtual(true)
                         ->setContainsData(false)
                         ->setElement(EnumElement::hr)
                         ->addValidationFunction(function (ValidatorInterface $validator) {
                             // Nothing to validate, this is not an input
                             $validator->doNotValidate();
                         });
    }
}
