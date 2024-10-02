<?php

/**
 * Class DefinitionFactory
 *
 * Definition class factory that contains predefined column definitions
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Definitions;

use Phoundation\Accounts\Roles\Roles;
use Phoundation\Accounts\Users\User;
use Phoundation\Accounts\Users\Users;
use Phoundation\Business\Companies\Companies;
use Phoundation\Business\Customers\Customers;
use Phoundation\Business\Providers\Providers;
use Phoundation\Core\CoreLocale;
use Phoundation\Core\Locale\Language\Languages;
use Phoundation\Core\Sessions\Session;
use Phoundation\Data\Categories\Categories;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Filesystem\Interfaces\FsDirectoryInterface;
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
     * @param DataEntryInterface|null $data_entry
     * @param string|null             $column
     *
     * @return DefinitionInterface
     */
    public static function getDatabaseId(?DataEntryInterface $data_entry, ?string $column = 'id'): DefinitionInterface
    {
        return Definition::new($data_entry, $column)
                         ->setOptional(true)
                         ->setInputType(EnumInputType::dbid)
                         ->setSize(3);
    }


    /**
     * Returns a Definition object for column categories_id
     *
     * @param DataEntryInterface|null $data_entry
     * @param string|null             $column
     * @param array|null              $filters
     *
     * @return DefinitionInterface
     */
    public static function getCategoriesId(?DataEntryInterface $data_entry, ?string $column = 'categories_id', array $filters = null): DefinitionInterface
    {
        return Definition::new($data_entry, $column)
                         ->setOptional(true)
                         ->setContent(function (DefinitionInterface $definition, string $key, string $column_name, array $source) use ($filters) {
                             return Categories::new()
                                              ->getHtmlSelect()
                                              ->setName($key)
                                              ->setReadonly($definition->getReadonly())
                                              ->setDisabled($definition->getDisabled())
                                              ->setSelected(isset_get($source[$key]));
                         })
                         ->setSize(6)
                         ->setLabel(tr('Category'))
                         ->addValidationFunction(function (ValidatorInterface $validator) {
                             // Ensure categories id exists and that its or category
                             $validator->orColumn('categories_name')
                                       ->isDbId()
                                       ->isQueryResult('SELECT `id` FROM `categories` WHERE `id` = :id AND `status` IS NULL', [':id' => '$categories_id']);
                         });
    }


    /**
     * Returns a Definition object for column categories_name
     *
     * @param DataEntryInterface|null $data_entry
     * @param string|null             $column
     *
     * @return DefinitionInterface
     */
    public static function getCategory(?DataEntryInterface $data_entry, ?string $column = 'categories_name'): DefinitionInterface
    {
        return Definition::new($data_entry, $column)
                         ->setOptional(true)
                         ->setRender(false)
                         ->setVirtual(true)
                         ->setCliColumn('-c,--category CATEGORY-NAME')
                         ->setLabel(tr('Category'))
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
                             // Ensure category exists and that its a category id or category name
                             $validator->orColumn('categories_id')
                                       ->isName()
                                       ->setColumnFromQuery('categories_id', 'SELECT `id` FROM `categories` WHERE `name` = :name AND `status` IS NULL', [':id' => '$categories_name']);
                         });
    }


    /**
     * Returns a Definition object for column servers_id
     *
     * @param DataEntryInterface|null $data_entry
     * @param string|null             $column
     * @param array|null              $filters
     *
     * @return DefinitionInterface
     */
    public static function getServersId(?DataEntryInterface $data_entry, ?string $column = 'servers_id', array $filters = null): DefinitionInterface
    {
        return Definition::new($data_entry, $column)
                         ->setOptional(true)
                         ->setContent(function (DefinitionInterface $definition, string $key, string $column_name, array $source) use ($filters) {
                             return Servers::new()
                                           ->getHtmlSelect()
                                           ->setName($key)
                                           ->setReadonly($definition->getReadonly())
                                           ->setDisabled($definition->getDisabled())
                                           ->setSelected(isset_get($source[$key]));
                         })
                         ->setSize(6)
                         ->setLabel(tr('Server'))
                         ->addValidationFunction(function (ValidatorInterface $validator) {
                             // Ensure servers id exists and that its or server
                             $validator->orColumn('servers_name')
                                       ->isDbId()
                                       ->isQueryResult('SELECT `id` FROM `servers` WHERE `id` = :id AND `status` IS NULL', [':id' => '$servers_id']);
                         });
    }


    /**
     * Returns a Definition object for column servers_name
     *
     * @param DataEntryInterface|null $data_entry
     * @param string|null             $column
     *
     * @return DefinitionInterface
     */
    public static function getServer(?DataEntryInterface $data_entry, ?string $column = 'servers_name'): DefinitionInterface
    {
        return Definition::new($data_entry, $column)
                         ->setOptional(true)
                         ->setRender(false)
                         ->setVirtual(true)
                         ->setCliColumn('-c,--server CATEGORY-NAME')
                         ->setLabel(tr('Server'))
                         ->setCliAutoComplete([
                             'word'   => function ($word) {
                                 return Servers::new()
                                               ->keepMatchingKeys($word);
                             },
                             'noword' => function () {
                                 return Servers::new()
                                               ->getSource();
                             },
                         ])
                         ->addValidationFunction(function (ValidatorInterface $validator) {
                             // Ensure server exists and that its a server id or server name
                             $validator->orColumn('servers_id')
                                       ->isName()
                                       ->setColumnFromQuery('servers_id', 'SELECT `id` FROM `servers` WHERE `name` = :name AND `status` IS NULL', [':id' => '$servers_name']);
                         });
    }


    /**
     * Returns a Definition object for column parents_id
     *
     * @param DataEntryInterface|null $data_entry
     * @param string|null             $column
     *
     * @return DefinitionInterface
     */
    public static function getParentsId(?DataEntryInterface $data_entry, ?string $column = 'parents_id'): DefinitionInterface
    {
        return Definition::new($data_entry, $column)
                         ->setOptional(true)
                         ->setSize(6)
                         ->setLabel(tr('Parent'));
    }


    /**
     * Returns a Definition object for column parents_name
     *
     * @param DataEntryInterface|null $data_entry
     * @param string|null             $column
     *
     * @return DefinitionInterface
     */
    public static function getParent(?DataEntryInterface $data_entry, ?string $column = 'parents_name'): DefinitionInterface
    {
        return Definition::new($data_entry, $column)
                         ->setOptional(true)
                         ->setRender(false)
                         ->setVirtual(true)
                         ->setCliColumn('-p,--parent PARENT-NAME')
                         ->setLabel(tr('Parent'));
    }


    /**
     * Returns a Definition object for column companies_id
     *
     * @param DataEntryInterface|null $data_entry
     * @param string|null             $column
     * @param array|null              $filters
     *
     * @return DefinitionInterface
     */
    public static function getCompaniesId(?DataEntryInterface $data_entry, ?string $column = 'companies_id', array $filters = null): DefinitionInterface
    {
        return Definition::new($data_entry, $column)
                         ->setOptional(true)
                         ->setContent(function (DefinitionInterface $definition, string $key, string $column_name, array $source) use ($filters) {
                             return Companies::new()
                                             ->getHtmlSelect()
                                             ->setName($key)
                                             ->setReadonly($definition->getReadonly())
                                             ->setDisabled($definition->getDisabled())
                                             ->setSelected(isset_get($source[$key]));
                         })
                         ->setSize(6)
                         ->setLabel(tr('Company'))
                         ->addValidationFunction(function (ValidatorInterface $validator) {
                             // Ensure companies id exists and that its or company
                             $validator->orColumn('companies_name')
                                       ->isDbId()
                                       ->isQueryResult('SELECT `id` FROM `business_companies` WHERE `id` = :id AND `status` IS NULL', [':id' => '$companies_id']);
                         });
    }


    /**
     * Returns a Definition object for column company
     *
     * @param DataEntryInterface|null $data_entry
     * @param string|null             $column
     *
     * @return DefinitionInterface
     */
    public static function getCompany(?DataEntryInterface $data_entry, ?string $column = 'companies_name'): DefinitionInterface
    {
        return Definition::new($data_entry, $column)
                         ->setOptional(true)
                         ->setRender(false)
                         ->setVirtual(true)
                         ->setCliColumn('--company COMPANY-NAME')
                         ->setLabel(tr('Company'))
                         ->setCliAutoComplete([
                             'word'   => function ($word) {
                                 return Companies::new()
                                                 ->keepMatchingKeys($word);
                             },
                             'noword' => function () {
                                 return Companies::new()
                                                 ->getSource();
                             },
                         ])
                         ->addValidationFunction(function (ValidatorInterface $validator) {
                             // Ensure company exists and that its or company
                             $validator->orColumn('companies_id')
                                       ->isName()
                                       ->setColumnFromQuery('companies_id', 'SELECT `id` FROM `business_companies` WHERE `name` = :name AND `status` IS NULL', [':name' => '$companies_name']);
                         });
    }


    /**
     * Returns a Definition object for column languages_id
     *
     * @param DataEntryInterface|null $data_entry
     * @param string|null             $column
     * @param array|null              $filters
     *
     * @return DefinitionInterface
     */
    public static function getLanguagesId(?DataEntryInterface $data_entry, ?string $column = 'languages_id', array $filters = null): DefinitionInterface
    {
        return Definition::new($data_entry, $column)
                         ->setOptional(true)
                         ->setInputType(EnumInputType::number)
                         ->setContent(function (DefinitionInterface $definition, string $key, string $column_name, array $source) use ($filters) {
                             return Languages::new()
                                             ->getHtmlSelect()
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
     * Returns a Definition object for column language
     *
     * @param DataEntryInterface|null $data_entry
     * @param string|null             $column
     *
     * @return DefinitionInterface
     */
    public static function getLanguage(?DataEntryInterface $data_entry, ?string $column = 'languages_name'): DefinitionInterface
    {
        return Definition::new($data_entry, $column)
                         ->setOptional(true)
                         ->setRender(false)
                         ->setVirtual(true)
                         ->setMaxlength(32)
                         ->setCliColumn('-l,--language LANGUAGE-CODE')
                         ->setLabel(tr('Language'))
                         ->setCliAutoComplete([
                             'word'   => function ($word) {
                                 return Languages::new()
                                                 ->keepMatchingKeys($word);
                             },
                             'noword' => function () {
                                 return Languages::new()
                                                 ->getSource();
                             },
                         ])
                         ->addValidationFunction(function (ValidatorInterface $validator) {
                             // Ensure language exists and that its or language
                             $validator->orColumn('languages_id')
                                       ->isName()
                                       ->setColumnFromQuery('languages_id', 'SELECT `id` FROM `core_languages` WHERE `code_639_1` = :code AND `status` IS NULL', [':code' => '$language']);
                         });
    }


    /**
     * Returns a Definition object for column providers_id
     *
     * @param DataEntryInterface|null $data_entry
     * @param string|null             $column
     * @param array|null              $filters
     *
     * @return DefinitionInterface
     */
    public static function getProvidersId(?DataEntryInterface $data_entry, ?string $column = 'providers_id', array $filters = null): DefinitionInterface
    {
        return Definition::new($data_entry, $column)
                         ->setOptional(true)
                         ->setContent(function (DefinitionInterface $definition, string $key, string $column_name, array $source) use ($filters) {
                             return Providers::new()
                                             ->getHtmlSelect()
                                             ->setName($key)
                                             ->setReadonly($definition->getReadonly())
                                             ->setDisabled($definition->getDisabled())
                                             ->setSelected(isset_get($source[$key]));
                         })
                         ->setSize(6)
                         ->setLabel(tr('Provider'))
                         ->addValidationFunction(function (ValidatorInterface $validator) {
                             // Ensure providers id exists and that its or provider
                             $validator->orColumn('providers_name')
                                       ->isDbId()
                                       ->isQueryResult('SELECT `id` FROM `business_providers` WHERE `id` = :id AND `status` IS NULL', [':id' => '$providers_id']);
                         });
    }


    /**
     * Returns a Definition object for column provider
     *
     * @param DataEntryInterface|null $data_entry
     * @param string|null             $column
     *
     * @return DefinitionInterface
     */
    public static function getProvider(?DataEntryInterface $data_entry, ?string $column = 'providers_name'): DefinitionInterface
    {
        return Definition::new($data_entry, $column)
                         ->setOptional(true)
                         ->setSize(6)
                         ->setCliColumn('--provider PROVIDER-NAME')
                         ->setLabel(tr('Provider'))
                         ->setCliAutoComplete([
                             'word'   => function ($word) {
                                 return Providers::new()
                                                 ->keepMatchingKeys($word);
                             },
                             'noword' => function () {
                                 return Providers::new()
                                                 ->getSource();
                             },
                         ])
                         ->addValidationFunction(function (ValidatorInterface $validator) {
                             // Ensure provider exists and that its providers id or providers name
                             $validator->orColumn('providers_id')
                                       ->isName()
                                       ->setColumnFromQuery('providers_id', 'SELECT `id` FROM `business_providers` WHERE `name` = :name AND `status` IS NULL', [':code' => '$providers_name']);
                         });
    }


    /**
     * Returns a Definition object for column customers_id
     *
     * @param DataEntryInterface|null $data_entry
     * @param string|null             $column
     * @param array|null              $filters
     *
     * @return DefinitionInterface
     */
    public static function getCustomersId(?DataEntryInterface $data_entry, ?string $column = 'customers_id', array $filters = null): DefinitionInterface
    {
        return Definition::new($data_entry, $column)
                         ->setOptional(true)
                         ->setContent(function (DefinitionInterface $definition, string $key, string $column_name, array $source) use ($filters) {
                             return Customers::new()
                                             ->getHtmlSelect()
                                             ->setName($key)
                                             ->setReadonly($definition->getReadonly())
                                             ->setDisabled($definition->getDisabled())
                                             ->setSelected(isset_get($source[$key]));
                         })
                         ->setSize(6)
                         ->setLabel(tr('Customer'))
                         ->addValidationFunction(function (ValidatorInterface $validator) {
                             // Ensure customers id exists and that its or customer
                             $validator->orColumn('customers_name')
                                       ->isDbId()
                                       ->isQueryResult('SELECT `id` FROM `business_customers` WHERE `id` = :id AND `status` IS NULL', [':id' => '$customers_id']);
                         });
    }


    /**
     * Returns a Definition object for column customer
     *
     * @param DataEntryInterface|null $data_entry
     * @param string|null             $column
     *
     * @return DefinitionInterface
     */
    public static function getCustomer(?DataEntryInterface $data_entry, ?string $column = 'customers_name'): DefinitionInterface
    {
        return Definition::new($data_entry, $column)
                         ->setOptional(true)
                         ->setSize(6)
                         ->setCliColumn('--customer CUSTOMER-NAME')
                         ->setLabel(tr('Customer'))
                         ->setCliAutoComplete([
                             'word'   => function ($word) {
                                 return Customers::new()
                                                 ->keepMatchingKeys($word);
                             },
                             'noword' => function () {
                                 return Customers::new()
                                                 ->getSource();
                             },
                         ])
                         ->addValidationFunction(function (ValidatorInterface $validator) {
                             // Ensure customer exists and that its or customer
                             $validator->orColumn('customers_id')
                                       ->isName()
                                       ->setColumnFromQuery('customers_id', 'SELECT `id` FROM `business_customers` WHERE `name` = :name AND `status` IS NULL', [':id' => '$customers_name']);
                         });
    }


    /**
     * Returns a Definition object for column timezones_id
     *
     * @param DataEntryInterface|null $data_entry
     * @param string|null             $column
     * @param array|null              $filters
     *
     * @return DefinitionInterface
     */
    public static function getTimezonesId(?DataEntryInterface $data_entry, ?string $column = 'timezones_id', array $filters = null): DefinitionInterface
    {
        return Definition::new($data_entry, $column)
                         ->setOptional(true)
                         ->setInputType(EnumInputType::number)
                         ->setContent(function (DefinitionInterface $definition, string $key, string $column_name, array $source) use ($filters) {
                             return Timezones::new()
                                             ->getHtmlSelect()
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
                                       ->isDbId()
                                       ->isTrue(function ($value) {
                                           // This timezone must exist.
                                           return Timezone::exists(['name' => $value]);
                                       }, tr('The specified timezone does not exist'));
                         });
    }


    /**
     * Returns a Definition object for column timezone
     *
     * @param DataEntryInterface|null $data_entry
     * @param string|null             $column
     *
     * @return DefinitionInterface
     */
    public static function getTimezone(?DataEntryInterface $data_entry, ?string $column = 'timezones_name'): DefinitionInterface
    {
        return Definition::new($data_entry, $column)
                         ->setOptional(true)
                         ->setRender(false)
                         ->setVirtual(true)
                         ->setCliColumn('-t,--timezone TIMEZONE-NAME')
                         ->setLabel(tr('Timezone'))
                         ->setCliAutoComplete([
                             'word'   => function ($word) {
                                 return Timezones::new()
                                                 ->keepMatchingKeys($word);
                             },
                             'noword' => function () {
                                 return Timezones::new()
                                                 ->getSource();
                             },
                         ])
                         ->addValidationFunction(function (ValidatorInterface $validator) {
                             // Ensure timezone exists and that its or timezone
                             $validator->orColumn('timezones_id')
                                       ->isName()
                                       ->setColumnFromQuery('timezones_id', 'SELECT `id` FROM `geo_timezones` WHERE `name` = :name AND `status` IS NULL', [':name' => '$timezone']);
                         });
    }


    /**
     * Returns a Definition object for column countries_id
     *
     * @param DataEntryInterface|null $data_entry
     * @param string|null             $column
     * @param array|null              $filters
     *
     * @return DefinitionInterface
     */
    public static function getCountriesId(?DataEntryInterface $data_entry, ?string $column = 'countries_id', array $filters = null): DefinitionInterface
    {
        return Definition::new($data_entry, $column)
                         ->setOptional(true)
                         ->setElement(EnumElement::select)
                         ->setInputType(EnumInputType::number)
                         ->setContent(function (DefinitionInterface $definition, string $key, string $column_name, array $source) use ($filters) {
                             return Countries::getHtmlCountriesSelect()
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
                                       ->isDbId()
                                       ->isQueryResult('SELECT `id` FROM `geo_countries` WHERE `id` = :id AND `status` IS NULL', [':id' => '$countries_id']);
                         });
    }


    /**
     * Returns a Definition object for column timezone
     *
     * @param DataEntryInterface|null $data_entry
     * @param string|null             $column
     *
     * @return DefinitionInterface
     */
    public static function getCountry(?DataEntryInterface $data_entry, ?string $column = 'countries_name'): DefinitionInterface
    {
        return Definition::new($data_entry, $column)
                         ->setOptional(true)
                         ->setRender(false)
                         ->setVirtual(true)
                         ->setCliColumn('--country COUNTRY-NAME')
                         ->setLabel(tr('Country'))
                         ->setCliAutoComplete([
                             'word'   => function ($word) {
                                 return Countries::new()
                                                 ->keepMatchingKeys($word);
                             },
                             'noword' => function () {
                                 return Countries::new()
                                                 ->getSource();
                             },
                         ])
                         ->addValidationFunction(function (ValidatorInterface $validator) {
                             // Ensure country exists and that its or countries_id
                             $validator->orColumn('countries_id')
                                       ->isName(200)
                                       ->setColumnFromQuery('countries_id', 'SELECT `id` FROM `geo_countries` WHERE `name` = :name AND `status` IS NULL', [':name' => '$country']);
                         });
    }


    /**
     * Returns a Definition object for column states_id
     *
     * @param DataEntryInterface|null $data_entry
     * @param string|null             $column
     * @param array|null              $filters
     *
     * @return DefinitionInterface
     */
    public static function getStatesId(?DataEntryInterface $data_entry, ?string $column = 'states_id', array $filters = null): DefinitionInterface
    {
        return Definition::new($data_entry, $column)
                         ->setOptional(true)
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
                                       ->isDbId()
                                       ->isQueryResult('SELECT `id` FROM `geo_states` WHERE `id` = :id AND `countries_id` = :countries_id AND `status` IS NULL', [
                                           ':id'           => '$states_id',
                                           ':countries_id' => '$countries_id',
                                       ]);
                         });
    }


    /**
     * Returns a Definition object for column timezone
     *
     * @param DataEntryInterface|null $data_entry
     * @param string|null             $column
     *
     * @return DefinitionInterface
     */
    public static function getState(?DataEntryInterface $data_entry, ?string $column = 'states_name'): DefinitionInterface
    {
        return Definition::new($data_entry, $column)
                         ->setOptional(true)
                         ->setRender(false)
                         ->setVirtual(true)
                         ->setCliColumn('--state STATE-NAME')
                         ->setLabel(tr('State'))
                         ->setCliAutoComplete([
                             'word'   => function ($word) {
                                 return States::new()
                                              ->keepMatchingKeys($word);
                             },
                             'noword' => function () {
                                 return States::new()
                                              ->getSource();
                             },
                         ])
                         ->addValidationFunction(function (ValidatorInterface $validator) {
                             // Ensure state exists and that its or states_id
                             $validator->orColumn('states_id')
                                       ->isName()
                                       ->setColumnFromQuery('states_id', 'SELECT `name` FROM `geo_states` WHERE `name` = :name AND `countries_id` = :countries_id AND `status` IS NULL', [
                                           ':name'         => '$state',
                                           ':countries_id' => '$countries_id',
                                       ]);
                         });
    }


    /**
     * Returns a Definition object for column cities_id
     *
     * @param DataEntryInterface|null $data_entry
     * @param string|null             $column
     * @param array|null              $filters
     *
     * @return DefinitionInterface
     */
    public static function getCitiesId(?DataEntryInterface $data_entry, ?string $column = 'cities_id', array $filters = null): DefinitionInterface
    {
        return Definition::new($data_entry, $column)
                         ->setOptional(true)
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
                                       ->isDbId()
                                       ->isQueryResult('SELECT `id` FROM `geo_cities` WHERE `id` = :id AND `states_name`  = :states_id    AND `status` IS NULL', [
                                           ':id'        => '$cities_id',
                                           ':states_id' => '$states_id',
                                       ]);
                         });
    }


    /**
     * Returns a Definition object for column timezone
     *
     * @param DataEntryInterface|null $data_entry
     * @param string|null             $column
     *
     * @return DefinitionInterface
     */
    public static function getCity(?DataEntryInterface $data_entry, ?string $column = 'cities_name'): DefinitionInterface
    {
        return Definition::new($data_entry, $column)
                         ->setOptional(true)
                         ->setRender(false)
                         ->setVirtual(true)
                         ->setCliColumn('--city CITY-NAME')
                         ->setLabel(tr('City'))
                         ->setCliAutoComplete([
                             'word'   => function ($word) {
                                 return Cities::new()
                                              ->keepMatchingKeys($word);
                             },
                             'noword' => function () {
                                 return Cities::new()
                                              ->getSource();
                             },
                         ])
                         ->addValidationFunction(function (ValidatorInterface $validator) {
                             // Ensure city exists and that its or cities_id
                             $validator->orColumn('cities_id')
                                       ->isName()
                                       ->setColumnFromQuery('cities_id', 'SELECT `name` FROM `geo_cities` WHERE `name` = :name AND `states_name`  = :states_id    AND `status` IS NULL', [
                                           ':name'      => '$city',
                                           ':states_id' => '$states_id',
                                       ]);
                         });
    }


    /**
     * Returns a Definition object for column users_id
     *
     * @param DataEntryInterface|null $data_entry
     * @param string|null             $column
     * @param array|null              $filters
     *
     * @return DefinitionInterface
     */
    public static function getUsersId(?DataEntryInterface $data_entry, ?string $column = 'users_id', array $filters = null): DefinitionInterface
    {
        return Definition::new($data_entry, $column)
                         ->setOptional(true)
                         ->setRender(false)
                         ->setInputType(EnumInputType::dbid)
                         ->setSize(3)
                         ->setCliAutoComplete(true)
                         ->setContent(function (DefinitionInterface $definition, string $key, string $column_name, array $source) use ($filters, $column) {
                             return Users::new()
                                         ->getHtmlSelect()
                                         ->setId($column)
                                         ->setName($column)
                                         ->setReadonly($definition->getReadonly())
                                         ->setDisabled($definition->getDisabled())
                                         ->setSelected(isset_get($source[$key]));
                         })
                         ->addValidationFunction(function (ValidatorInterface $validator) use ($column) {
                             $validator->isDbId()
                                       ->isQueryResult('SELECT `id` FROM `accounts_users` WHERE `id` = :id AND `status` IS NULL', [
                                           ':id' => '$' . $column
                                       ]);
                         });
    }


    /**
     * Returns a Definition object for column id
     *
     * @param DataEntryInterface|null $data_entry
     * @param string                  $column
     *
     * @return DefinitionInterface
     */
    public static function getId(?DataEntryInterface $data_entry, string $column): DefinitionInterface
    {
        return Definition::new($data_entry, $column)
                         ->setOptional(true)
                         ->setRender(false)
                         ->setInputType(EnumInputType::dbid)
                         ->setSize(3)
                         ->setCliAutoComplete(true);
    }


    /**
     * Returns a Definition object for column users_id
     *
     * @param DataEntryInterface|null $data_entry
     * @param string|null             $column
     *
     * @return DefinitionInterface
     */
    public static function getUsersEmail(?DataEntryInterface $data_entry, ?string $column = 'email'): DefinitionInterface
    {
        return Definition::new($data_entry, $column)
                         ->setOptional(true)
                         ->setRender(false)
                         ->setVirtual(true)
                         ->setInputType(EnumInputType::email)
                         ->setCliColumn('-u,--user EMAIL')
                         ->setLabel(tr('User'))
                         ->setCliAutoComplete([
                             'word'   => function ($word) {
                                 return Users::new()
                                             ->keepMatchingKeys($word);
                             },
                             'noword' => function () {
                                 return Users::new()
                                             ->getSource();
                             },
                         ])
                         ->addValidationFunction(function (ValidatorInterface $validator) {
                             $validator->orColumn('users_id')
                                       ->setColumnFromQuery('users_id', 'SELECT `id` FROM `accounts_users` WHERE `email` = :email', [':email' => '$email']);
                         });
    }


    /**
     * Returns a Definition object for column users_id
     *
     * @param DataEntryInterface|null $data_entry
     * @param string|null             $column
     *
     * @return DefinitionInterface
     */
    public static function getUsername(?DataEntryInterface $data_entry, ?string $column = 'username'): DefinitionInterface
    {
        return Definition::new($data_entry, $column)
                         ->setOptional(true)
                         ->setRender(true)
                         ->setInputType(EnumInputType::name)
                         ->setCliColumn('-u,--username NAME')
                         ->setLabel(tr('Username'))
                         ->setCliAutoComplete(true);
    }


    /**
     * Returns a Definition object for column roles_id
     *
     * @param DataEntryInterface|null $data_entry
     * @param string|null             $column
     * @param array|null              $filters
     *
     * @return DefinitionInterface
     */
    public static function getRolesId(?DataEntryInterface $data_entry, ?string $column = 'roles_id', array $filters = null): DefinitionInterface
    {
        return Definition::new($data_entry, $column)
                         ->setOptional(true)
                         ->setInputType(EnumInputType::dbid)
                         ->setSize(3)
                         ->setCliAutoComplete(true)
                         ->setContent(function (DefinitionInterface $definition, string $key, string $column_name, array $source) use ($filters, $column) {
                             return Roles::new()
                                         ->getHtmlSelect()
                                         ->setId($column)
                                         ->setName($column)
                                         ->setReadonly($definition->getReadonly())
                                         ->setDisabled($definition->getDisabled())
                                         ->setSelected(isset_get($source[$key]));
                         })
                         ->addValidationFunction(function (ValidatorInterface $validator) use ($column) {
                             $validator->isDbId()
                                       ->isQueryResult('SELECT `id` FROM `accounts_roles` WHERE `id` = :id AND `status` IS NULL', [':id' => '$' . $column]);
                         });
    }


    /**
     * Returns a Definition object for column roles_id
     *
     * @param DataEntryInterface|null $data_entry
     * @param string|null             $column
     *
     * @return DefinitionInterface
     */
    public static function getRolesName(?DataEntryInterface $data_entry, ?string $column = 'name'): DefinitionInterface
    {
        return Definition::new($data_entry, $column)
                         ->setOptional(true)
                         ->setRender(false)
                         ->setVirtual(true)
                         ->setInputType(EnumInputType::name)
                         ->setCliColumn('-r,--role EMAIL')
                         ->setLabel(tr('Role'))
                         ->setCliAutoComplete([
                             'word'   => function ($word) {
                                 return Roles::new()
                                             ->keepMatchingKeys($word);
                             },
                             'noword' => function () {
                                 return Roles::new()
                                             ->getSource();
                             },
                         ])
                         ->addValidationFunction(function (ValidatorInterface $validator) {
                             $validator->orColumn('roles_id')
                                       ->setColumnFromQuery('roles_id', 'SELECT `id` FROM `accounts_roles` WHERE `name` = :name', [':name' => '$name']);
                         });
    }


    /**
     * Returns a Definition object for column code
     *
     * @param DataEntryInterface|null $data_entry
     * @param string|null             $column
     *
     * @return DefinitionInterface
     */
    public static function getCode(?DataEntryInterface $data_entry, ?string $column = 'code'): DefinitionInterface
    {
        return Definition::new($data_entry, $column)
                         ->setOptional(true)
                         ->setInputType(EnumInputType::code)
                         ->setSize(3)
                         ->setMaxlength(64)
                         ->setMinlength(1)
                         ->setCliColumn('-c,--code CODE')
                         ->setCliAutoComplete(true)
                         ->setLabel(tr('Code'));
    }


    /**
     * Returns a Definition object for column hash
     *
     * @param DataEntryInterface|null $data_entry
     * @param string|null             $column
     *
     * @return DefinitionInterface
     */
    public static function getHash(?DataEntryInterface $data_entry, ?string $column = 'hash'): DefinitionInterface
    {
        return Definition::new($data_entry, $column)
                         ->setOptional(true)
                         ->setReadonly(true)
                         ->setInputType(EnumInputType::code)
                         ->setSize(3)
                         ->setMaxlength(128)
                         ->setMinlength(1)
                         ->setCliAutoComplete(true)
                         ->setLabel(tr('Hash'));
    }


    /**
     * Returns a Definition object for column datetime
     *
     * @param DataEntryInterface|null $data_entry
     * @param string|null             $column
     *
     * @return DefinitionInterface
     */
    public static function getDateTime(?DataEntryInterface $data_entry, ?string $column = 'datetime'): DefinitionInterface
    {
        return Definition::new($data_entry, $column)
                         ->setOptional(true)
                         ->setInputType(EnumInputType::datetime_local)
                         ->setSize(3)
                         ->setLabel(tr('Date time'));
    }


    /**
     * Returns a Definition object for column date
     *
     * @param DataEntryInterface|null $data_entry
     * @param string|null             $column
     *
     * @return DefinitionInterface
     */
    public static function getDate(?DataEntryInterface $data_entry, ?string $column = 'date'): DefinitionInterface
    {
        return Definition::new($data_entry, $column)
                         ->setOptional(true)
                         ->setInputType(EnumInputType::date)
                         ->setSize(3)
                         ->setCliAutoComplete(true)
                         ->setLabel(tr('Date'));
    }


    /**
     * Returns a Definition object for a column containing a variable
     *
     * @param DataEntryInterface|null $data_entry
     * @param string|null             $column
     *
     * @return DefinitionInterface
     */
    public static function getHostname(?DataEntryInterface $data_entry, ?string $column = 'name'): DefinitionInterface
    {
        return Definition::new($data_entry, $column)
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
     * Returns a Definition object for a column containing a variable
     *
     * @param DataEntryInterface|null $data_entry
     * @param string|null             $column
     *
     * @return DefinitionInterface
     */
    public static function getVariable(?DataEntryInterface $data_entry, ?string $column = 'name'): DefinitionInterface
    {
        return Definition::new($data_entry, $column)
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
     * @param DataEntryInterface|null $data_entry
     * @param string|null             $column
     * @param int|null                $default
     *
     * @return DefinitionInterface
     */
    public static function getNumber(?DataEntryInterface $data_entry, ?string $column = 'number', ?int $default = null): DefinitionInterface
    {
        return Definition::new($data_entry, $column)
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
     * Returns a Definition object for column password
     *
     * @param DataEntryInterface|null $data_entry
     * @param string|null             $column
     *
     * @return DefinitionInterface
     */
    public static function getPassword(?DataEntryInterface $data_entry, ?string $column = 'password'): DefinitionInterface
    {
        return Definition::new($data_entry, $column)
                         ->setMaxLength(255)
                         ->setOptional(true)
                         ->setSize(6)
                         ->setInputType(EnumInputType::password)
                         ->setCliAutoComplete(true)
                         ->addValidationFunction(function (ValidatorInterface $validator) {
                             $validator->isVariable();
                         });
    }


    /**
     * Returns a Definition object for column date
     *
     * @param DataEntryInterface|null $data_entry
     * @param string|null             $column
     *
     * @return DefinitionInterface
     */
    public static function getTime(?DataEntryInterface $data_entry, ?string $column = 'time'): DefinitionInterface
    {
        return Definition::new($data_entry, $column)
                         ->setOptional(true)
                         ->setInputType(EnumInputType::time)
                         ->setSize(3)
                         ->setCliAutoComplete(true)
                         ->setLabel(tr('Time'));
    }


    /**
     * Returns a Definition object for column title
     *
     * @param DataEntryInterface|null $data_entry
     * @param string|null             $column
     *
     * @return DefinitionInterface
     */
    public static function getTitle(?DataEntryInterface $data_entry, ?string $column = 'title'): DefinitionInterface
    {
        return Definition::new($data_entry, $column)
                         ->setOptional(true)
                         ->setMaxLength(24)
                         ->setSize(3)
                         ->setCliColumn('-t,--title TITLE')
                         ->setCliAutoComplete(true)
                         ->setLabel(tr('Title'))
                         ->addValidationFunction(function (ValidatorInterface $validator) {
                             $validator->isName();
                         });
    }


    /**
     * Returns a Definition object for column name
     *
     * @param DataEntryInterface|null $data_entry
     * @param string|null             $column
     *
     * @return DefinitionInterface
     */
    public static function getName(?DataEntryInterface $data_entry, ?string $column = 'name'): DefinitionInterface
    {
        return Definition::new($data_entry, $column)
                         ->setMaxLength(128)
                         ->setOptional(true)
                         ->setSize(3)
                         ->setLabel(tr('Name'))
                         ->setCliColumn(tr('[-n,--name NAME]'))
                         ->setInputType(EnumInputType::name)
                         ->setCliAutoComplete(true)
                         ->addValidationFunction(function (ValidatorInterface $validator) {
                             $validator->isName();
                         });
    }


    /**
     * Returns a Definition object for column file
     *
     * @param DataEntryInterface|null   $data_entry
     * @param FsDirectoryInterface|null $exists_in_directory
     * @param FsDirectoryInterface|null $prefix
     * @param string|null               $column
     *
     * @return DefinitionInterface
     */
    public static function getFile(?DataEntryInterface $data_entry, ?FsDirectoryInterface $exists_in_directory = null, ?FsDirectoryInterface $prefix = null, ?string $column = 'file'): DefinitionInterface
    {
        return Definition::new($data_entry, $column)
            ->setMaxLength(2048)
            ->setOptional(true)
            ->setSize(3)
            ->setLabel(tr('File'))
            ->setCliColumn(tr('-f,--file PATH'))
            ->setInputType(EnumInputType::text)
            ->setCliAutoComplete(true)
            ->addValidationFunction(function (ValidatorInterface $validator) use ($exists_in_directory, $prefix) {
                if ($exists_in_directory) {
                    $validator->isFile($exists_in_directory, prefix: $prefix);
                }
            });
    }


    /**
     * Returns a Definition object for column filename
     *
     * @param DataEntryInterface|null   $data_entry
     * @param string|null               $column
     *
     * @return DefinitionInterface
     */
    public static function getFilename(?DataEntryInterface $data_entry, ?string $column = 'filename'): DefinitionInterface
    {
        return Definition::new($data_entry, $column)
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
     * Returns a Definition object for column email
     *
     * @param DataEntryInterface|null $data_entry
     * @param string|null             $column
     *
     * @return DefinitionInterface
     */
    public static function getEmail(?DataEntryInterface $data_entry, ?string $column = 'email'): DefinitionInterface
    {
        return Definition::new($data_entry, $column)
                         ->setOptional(true)
                         ->setInputType(EnumInputType::email)
                         ->setMaxlength(128)
                         ->setCliColumn('-e,--email EMAIL')
                         ->setCliAutoComplete(true)
                         ->setLabel(tr('Email address'));
    }


    /**
     * Returns a Definition object for column url
     *
     * @param DataEntryInterface|null $data_entry
     * @param string|null             $column
     *
     * @return DefinitionInterface
     */
    public static function getUrl(?DataEntryInterface $data_entry, ?string $column = 'url'): DefinitionInterface
    {
        return Definition::new($data_entry, $column)
                         ->setOptional(true)
                         ->setInputType(EnumInputType::url)
                         ->setMaxlength(2048)
                         ->setCliAutoComplete(true)
                         ->setCliColumn('-w,--website WEBSITE-URL')
                         ->setLabel(tr('Website URL'))
                         ->addValidationFunction(function (ValidatorInterface $validator) {
                             $validator->isOptional()
                                       ->isUrl();
                         });
    }


    /**
     * Returns a Definition object for column ip_address
     *
     * @param DataEntryInterface|null $data_entry
     * @param string|null             $column
     *
     * @return DefinitionInterface
     */
    public static function getIpAddress(?DataEntryInterface $data_entry, ?string $column = 'ip_address'): DefinitionInterface
    {
        return Definition::new($data_entry, $column)
                         ->setReadonly(true)
                         ->setInputType(EnumInputType::text)
                         ->setSize(6)
                         ->setMaxlength(48)
                         ->setCliAutoComplete(true)
                         ->setLabel(tr('IP Address'));
    }


    /**
     * Returns a Definition object for column domain
     *
     * @param DataEntryInterface|null $data_entry
     * @param string|null             $column
     *
     * @return DefinitionInterface
     */
    public static function getDomain(?DataEntryInterface $data_entry, ?string $column = 'domain'): DefinitionInterface
    {
        return Definition::new($data_entry, $column)
                         ->setReadonly(true)
                         ->setInputType(EnumInputType::text)
                         ->setSize(6)
                         ->setMaxlength(255)
                         ->setCliAutoComplete(true)
                         ->setLabel(tr('Domain name'));
    }


    /**
     * Returns a Definition object for column phone
     *
     * @param DataEntryInterface|null $data_entry
     * @param string|null             $column
     *
     * @return DefinitionInterface
     */
    public static function getPhone(?DataEntryInterface $data_entry, ?string $column = 'phone'): DefinitionInterface
    {
        return Definition::new($data_entry, $column)
                         ->setOptional(true)
                         ->setInputType(EnumInputType::phone)
                         ->setLabel(tr('Phone number'))
                         ->setCliColumn(tr('-p,--phone-number PHONE-NUMBER'))
                         ->setMaxlength(22)
                         ->setDisplayCallback(function (mixed $value, array $source) {
                             return CoreLocale::formatPhoneNumber($value);
                         });
    }


    /**
     * Returns a Definition object for column phones
     *
     * @param DataEntryInterface|null $data_entry
     * @param string|null             $column
     *
     * @return DefinitionInterface
     */
    public static function getPhones(?DataEntryInterface $data_entry, ?string $column = 'phones'): DefinitionInterface
    {
        return Definition::new($data_entry, $column)
                         ->setOptional(true)
                         ->setMinlength(10)
                         ->setMaxLength(64)
                         ->setSize(3)
                         ->setCliColumn(tr('-p,--phone-numbers "PHONE-NUMBER,PHONE-NUMBER,..."'))
                         ->setCliAutoComplete(true)
                         ->setLabel(tr('Phone numbers'))
                         ->setHelpGroup(tr('Personal information'))
                         ->setHelpText(tr('Phone numbers where this user can be reached'))
                         ->addValidationFunction(function (ValidatorInterface $validator) {
                             $validator->isPhoneNumbers();
                             // $validator->sanitizeForceArray(',')->eachField()->isPhoneNumber()->sanitizeForceString()
                         });
    }


    /**
     * Returns a Definition object for column seo_name
     *
     * @param DataEntryInterface|null $data_entry
     * @param string|null             $column
     *
     * @return DefinitionInterface
     */
    public static function getSeoName(?DataEntryInterface $data_entry, ?string $column = 'seo_name'): DefinitionInterface
    {
        return Definition::new($data_entry, $column)
                         ->setMaxlength(128)
                         ->setOptional(true)
                         ->setRender(false)
                         ->setReadonly(true);
    }


    /**
     * Returns a Definition object for column description
     *
     * @param DataEntryInterface|null $data_entry
     * @param string|null             $column
     *
     * @return DefinitionInterface
     */
    public static function getUuid(?DataEntryInterface $data_entry, ?string $column = 'uuid'): DefinitionInterface
    {
        return Definition::new($data_entry, $column)
                         ->setReadonly(true)
                         ->setInputType(EnumInputType::text)
                         ->setSize(6)
                         ->setMaxlength(36)
                         ->setCliAutoComplete(true)
                         ->setLabel(tr('UUID'));
    }


    /**
     * Returns a Definition object for a boolean column (checkbox)
     *
     * @param DataEntryInterface|null $data_entry
     * @param string|null             $column
     *
     * @return DefinitionInterface
     */
    public static function getBoolean(?DataEntryInterface $data_entry, ?string $column): DefinitionInterface
    {
        return Definition::new($data_entry, $column)
                         ->setOptional(true)
                         ->setDefault(false)
                         ->setInputType(EnumInputType::checkbox)
                         ->setSize(2)
                         ->addValidationFunction(function (ValidatorInterface $validator) {
                             $validator->isBoolean();
                         });
    }


    /**
     * Returns a Definition object for generic data column
     *
     * @param DataEntryInterface|null $data_entry
     * @param string             $column
     *
     * @return DefinitionInterface
     */
    public static function getData(?DataEntryInterface $data_entry, string $column): DefinitionInterface
    {
        return Definition::new($data_entry, $column)
                         ->setOptional(true)
                         ->setElement(EnumElement::textarea)
                         ->setInputType(EnumInputType::array_json)
                         ->setSize(12)
                         ->setMaxlength(16_777_200)
                         ->setLabel(tr('Data'))
                         ->setCliAutoComplete(true);
    }


    /**
     * Returns a Definition object for column description
     *
     * @param DataEntryInterface|null $data_entry
     * @param string|null             $column
     *
     * @return DefinitionInterface
     */
    public static function getDescription(?DataEntryInterface $data_entry, ?string $column = 'description'): DefinitionInterface
    {
        return Definition::new($data_entry, $column)
                         ->setOptional(true)
                         ->setInputType(EnumInputType::description)
                         ->setSize(12)
                         ->setMaxlength(65_535)
                         ->setCliColumn('-d,--description "DESCRIPTION"')
                         ->setCliAutoComplete(true)
                         ->setLabel(tr('Description'));
    }


    /**
     * Returns a Definition object for column content
     *
     * @param DataEntryInterface|null $data_entry
     * @param string|null             $column
     *
     * @return DefinitionInterface
     */
    public static function getContent(?DataEntryInterface $data_entry, ?string $column = 'content'): DefinitionInterface
    {
        return Definition::new($data_entry, $column)
                         ->setOptional(true)
                         ->setInputType(EnumInputType::text)
                         ->setSize(12)
                         ->setMaxlength(16_777_215)
                         ->setCliColumn('--content "CONTENT"')
                         ->setCliAutoComplete(true)
                         ->setLabel(tr('Content'));
    }


    /**
     * Returns a Definition object for column comments
     *
     * @param DataEntryInterface|null $data_entry
     * @param string|null             $column
     *
     * @return DefinitionInterface
     */
    public static function getComments(?DataEntryInterface $data_entry, ?string $column = 'comments'): DefinitionInterface
    {
        return Definition::new($data_entry, $column)
                         ->setOptional(true)
                         ->setInputType(EnumInputType::description)
                         ->setSize(12)
                         ->setMaxlength(65_535)
                         ->setCliColumn('--comments "COMMENTS"')
                         ->setCliAutoComplete(true)
                         ->setLabel(tr('Comments'));
    }


    /**
     * Returns a Definition object for buttons
     *
     * @param DataEntryInterface|null $data_entry
     * @param string|null             $column
     *
     * @return DefinitionInterface
     */
    public static function getButton(?DataEntryInterface $data_entry, ?string $column): DefinitionInterface
    {
        return Definition::new($data_entry, $column)
                         ->setOptional(true)
                         ->addClasses('btn-primary')
                         ->setRender(true)
                         ->setVirtual(true)
                         ->setElement(EnumElement::input)
                         ->setInputType(EnumInputType::button)
                         ->setLabel(tr(' '))
                         ->setSize(1);
    }


    /**
     * Returns a Definition object for buttons
     *
     * @param DataEntryInterface|null $data_entry
     * @param string|null             $column
     *
     * @return DefinitionInterface
     */
    public static function getSubmit(?DataEntryInterface $data_entry, ?string $column): DefinitionInterface
    {
        return Definition::new($data_entry, $column)
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
     * @param DataEntryInterface|null $data_entry
     * @param string|null             $column
     *
     * @return DefinitionInterface
     */
    public static function getCreatedBy(?DataEntryInterface $data_entry, ?string $column = 'created_by'): DefinitionInterface
    {
        return Definition::new($data_entry, $column)
                         ->setDisabled(true)
                         ->setSize(3)
                         ->setLabel(tr('Created by'))
                         ->setTooltip(tr('This column contains the user who created this object. Other users may have made further edits to this object, that information may be found in the object\'s meta data'))
                         ->setContent(function (DefinitionInterface $definition, string $key, string $column_name, array $source) use ($data_entry) {
                             if ($data_entry->isNew()) {
                                 // This is a new DataEntry object, so the creator is.. Well, you!
                                 return InputText::new()
                                                 ->setDisabled(true)
                                                 ->addClasses('text-center')
                                                 ->setValue(Session::getUserObject()
                                                     ->getDisplayName());
                             } else {
                                 // This is created by a user or by the system user
                                 if ($source[$key]) {
                                     return InputText::new()
                                                     ->setDisabled(true)
                                                     ->addClasses('text-center')
                                                     ->setValue(User::load($source[$key])
                                                         ->getDisplayName());
                                 } else {
                                     return InputText::new()
                                                     ->setDisabled(true)
                                                     ->addClasses('text-center')
                                                     ->setValue(tr('System'));
                                 }
                             }
                         });
    }


    /**
     * Returns a Definition object for created_on
     *
     * @param DataEntryInterface|null $data_entry
     * @param string|null             $column
     *
     * @return DefinitionInterface
     */
    public static function getCreatedOn(?DataEntryInterface $data_entry, ?string $column = 'created_on'): DefinitionInterface
    {
        return Definition::new($data_entry, $column)
                         ->setDisabled(true)
                         ->setInputType(EnumInputType::datetime_local)
                         ->setDbNullInputType(EnumInputType::text)
                         ->addClasses('text-center')
                         ->setSize(3)
                         ->setTooltip(tr('This column contains the exact date / time when this object was created'))
                         ->setLabel(tr('Created on'));
    }


    /**
     * Returns a Definition object for meta_id
     *
     * @param DataEntryInterface|null $data_entry
     * @param string|null             $column
     *
     * @return DefinitionInterface
     */
    public static function getMetaId(?DataEntryInterface $data_entry, ?string $column = 'meta_id'): DefinitionInterface
    {
        return Definition::new($data_entry, $column)
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
     * @param DataEntryInterface|null $data_entry
     * @param string|null             $column
     *
     * @return DefinitionInterface
     */
    public static function getStatus(?DataEntryInterface $data_entry, ?string $column = 'status'): DefinitionInterface
    {
        return Definition::new($data_entry, $column)
                         ->setOptional(true)
                         ->setDisabled(true)
                         ->setInputType(EnumInputType::text)
                         ->setTooltip(tr('This column contains the current status of this object. A typical status is "Ok", but objects may also be "Deleted" or "In process", for example. Depending on their status, objects may be visible in tables, or not'))
                         ->addClasses('text-center')
                         ->setSize(3)
                         ->setMaxlength(32)
                         ->setLabel(tr('Status'));
    }


    /**
     * Returns a Definition object for meta_state
     *
     * @param DataEntryInterface|null $data_entry
     * @param string|null             $column
     *
     * @return DefinitionInterface
     */
    public static function getMetaState(?DataEntryInterface $data_entry, ?string $column = 'meta_state'): DefinitionInterface
    {
        return Definition::new($data_entry, $column)
                         ->setDisabled(true)
                         ->setRender(false)
                         ->setInputType(EnumInputType::text)
                         ->setTooltip(tr('This column contains a cache identifier value for this object. This information usually is of no importance to normal users'))
                         ->setLabel(tr('Meta state'));
    }



    /**
     * Returns a Definition object for meta_state
     *
     * @param DataEntryInterface|null $data_entry
     * @param string|null             $column
     *
     * @return DefinitionInterface
     */
    public static function getDivider(?DataEntryInterface $data_entry, ?string $column = null): DefinitionInterface
    {
        if (!$column) {
            $column = 'divider' . Strings::getUuid();
        }

        return Definition::new($data_entry, $column)
                         ->setVirtual(true)
                         ->setElement(EnumElement::hr)
                         ->addValidationFunction(function (ValidatorInterface $validator) {
                             // Nothing to validate, this is not an input
                             $validator->doNotValidate();
                         });
    }
}
