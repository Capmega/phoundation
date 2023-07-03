<?php

namespace Phoundation\Data\DataEntry\Definitions;

use Phoundation\Accounts\Users\Users;
use Phoundation\Business\Companies\Companies;
use Phoundation\Business\Customers\Customers;
use Phoundation\Business\Providers\Providers;
use Phoundation\Core\Locale\Language\Languages;
use Phoundation\Data\Categories\Categories;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Geo\Cities\Cities;
use Phoundation\Geo\Countries\Countries;
use Phoundation\Geo\Countries\Country;
use Phoundation\Geo\States\State;
use Phoundation\Geo\States\States;
use Phoundation\Geo\Timezones\Timezone;
use Phoundation\Geo\Timezones\Timezones;
use Phoundation\Web\Http\Html\Enums\InputType;
use Phoundation\Web\Http\Html\Enums\InputTypeExtended;


/**
 * Class DefinitionFactory
 *
 * Definition class factory that contains predefined field definitions
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
class DefinitionFactory
{
    /**
     * Returns Definition object for column categories_id
     *
     * @param DataEntryInterface $data_entry
     * @param string|null $field
     * @param array|null $filters
     * @return DefinitionInterface
     */
    public static function getCategoriesId(DataEntryInterface $data_entry, ?string $field = 'categories_id', array $filters = null): DefinitionInterface
    {
        return Definition::new($data_entry, $field)
            ->setOptional(true)
            ->setContent(function (DefinitionInterface $definition, string $key, string $field_name, array $source) use ($filters) {
                return Categories::new()->getHtmlSelect()
                    ->setName($key)
                    ->setSelected(isset_get($source[$key]))
                    ->render();
            })
            ->setSize(6)
            ->setLabel(tr('Category'))
            ->addValidationFunction(function (ValidatorInterface $validator) {
                // Ensure categories id exists and that its or category
                $validator->or('category')->isDbId()->isQueryColumn('SELECT `id` FROM `categories` WHERE `id` = :id AND `status` IS NULL', [':id' => '$categories_id']);
            });
    }


    /**
     * Returns Definition object for column category
     *
     * @param DataEntryInterface $data_entry
     * @param string|null $field
     * @return DefinitionInterface
     */
    public static function getCategory(DataEntryInterface $data_entry, ?string $field = 'category'): DefinitionInterface
    {
        return Definition::new($data_entry, $field)
            ->setOptional(true)
            ->setVirtual(true)
            ->setCliField('-t,--category CATEGORY-NAME')
            ->setLabel(tr('Category'))
            ->setAutoComplete([
                'word' => function ($word) {
                    return Categories::new()->filteredList($word);
                },
                'noword' => function () {
                    return Categories::new()->getSource();
                },
            ])
            ->addValidationFunction(function (ValidatorInterface $validator) {
                // Ensure category exists and that its or category
                $validator->or('categories_id')->isCategory();
            });
    }


    /**
     * Returns Definition object for column companies_id
     *
     * @param DataEntryInterface $data_entry
     * @param string|null $field
     * @param array|null $filters
     * @return DefinitionInterface
     */
    public static function getCompaniesId(DataEntryInterface $data_entry, ?string $field = 'companies_id', array $filters = null): DefinitionInterface
    {
        return Definition::new($data_entry, $field)
            ->setOptional(true)
            ->setContent(function (DefinitionInterface $definition, string $key, string $field_name, array $source) use ($filters) {
                return Companies::new()->getHtmlSelect()
                    ->setName($key)
                    ->setSelected(isset_get($source[$key]))
                    ->render();
            })
            ->setSize(6)
            ->setLabel(tr('Company'))
            ->addValidationFunction(function (ValidatorInterface $validator) {
                // Ensure companies id exists and that its or company
                $validator->or('company')->isDbId()->isQueryColumn('SELECT `id` FROM `business_companies` WHERE `id` = :id AND `status` IS NULL', [':id' => '$companies_id']);
            });
    }


    /**
     * Returns Definition object for column company
     *
     * @param DataEntryInterface $data_entry
     * @param string|null $field
     * @return DefinitionInterface
     */
    public static function getCompany(DataEntryInterface $data_entry, ?string $field = 'company'): DefinitionInterface
    {
        return Definition::new($data_entry, $field)
            ->setOptional(true)
            ->setVirtual(true)
            ->setCliField('--company COMPANY-NAME')
            ->setLabel(tr('Company'))
            ->setAutoComplete([
                'word' => function ($word) {
                    return Companies::new()->filteredList($word);
                },
                'noword' => function () {
                    return Companies::new()->getSource();
                },
            ])
            ->addValidationFunction(function (ValidatorInterface $validator) {
                // Ensure company exists and that its or company
                $validator->or('companies_id')->isCompany();
            });
    }


    /**
     * Returns Definition object for column languages_id
     *
     * @param DataEntryInterface $data_entry
     * @param string|null $field
     * @param array|null $filters
     * @return DefinitionInterface
     */
    public static function getLanguagesId(DataEntryInterface $data_entry, ?string $field = 'languages_id', array $filters = null): DefinitionInterface
    {
        return Definition::new($data_entry, $field)
            ->setOptional(true)
            ->setInputType(InputType::number)
            ->setContent(function (DefinitionInterface $definition, string $key, string $field_name, array $source) use ($filters) {
                return Languages::new()->getHtmlSelect()
                    ->setName($key)
                    ->setSelected(isset_get($source[$key]))
                    ->render();
            })
            ->setSize(3)
            ->setCliField('--languages-id')
            ->setAutoComplete(true)
            ->setLabel(tr('Language'))
            ->setHelpGroup(tr('Location information'))
            ->setHelpText(tr('The language in which the site will be displayed to the user'))
            ->addValidationFunction(function (ValidatorInterface $validator) {
                $validator->or('language')->isDbId()->isQueryColumn('SELECT `id` FROM `core_languages` WHERE `id` = :id AND `status` IS NULL', [':id' => '$languages_id']);
            });
    }


    /**
     * Returns Definition object for column language
     *
     * @param DataEntryInterface $data_entry
     * @param string|null $field
     * @return DefinitionInterface
     */
    public static function getLanguage(DataEntryInterface $data_entry, ?string $field = 'language'): DefinitionInterface
    {
        return Definition::new($data_entry, $field)
            ->setOptional(true)
            ->setVirtual(true)
            ->setVisible(false)
            ->setMaxlength(32)
            ->setCliField('-l,--language LANGUAGE-CODE')
            ->setLabel(tr('Language'))
            ->setAutoComplete([
                'word' => function ($word) {
                    return Languages::new()->filteredList($word);
                },
                'noword' => function () {
                    return Languages::new()->getSource();
                },
            ])
            ->addValidationFunction(function (ValidatorInterface $validator) {
                // Ensure language exists and that its or language
                $validator->or('languages_id')->isName()->setColumnFromQuery('languages_id', 'SELECT `id` FROM `core_languages` WHERE `code_639_1` = :code AND `status` IS NULL', [':code' => '$language']);
            });
    }


    /**
     * Returns Definition object for column providers_id
     *
     * @param DataEntryInterface $data_entry
     * @param string|null $field
     * @param array|null $filters
     * @return DefinitionInterface
     */
    public static function getProvidersId(DataEntryInterface $data_entry, ?string $field = 'providers_id', array $filters = null): DefinitionInterface
    {
        return Definition::new($data_entry, $field)
            ->setOptional(true)
            ->setContent(function (DefinitionInterface $definition, string $key, string $field_name, array $source) use ($filters) {
                return Providers::new()->getHtmlSelect()
                    ->setName($key)
                    ->setSelected(isset_get($source[$key]))
                    ->render();
            })
            ->setSize(6)
            ->setLabel(tr('Provider'))
            ->addValidationFunction(function (ValidatorInterface $validator) {
                // Ensure providers id exists and that its or provider
                $validator->or('provider')->isDbId()->isQueryColumn('SELECT `id` FROM `business_providers` WHERE `id` = :id AND `status` IS NULL', [':id' => '$providers_id']);
            });
    }


    /**
     * Returns Definition object for column provider
     *
     * @param DataEntryInterface $data_entry
     * @param string|null $field
     * @return DefinitionInterface
     */
    public static function getProvider(DataEntryInterface $data_entry, ?string $field = 'provider'): DefinitionInterface
    {
        return Definition::new($data_entry, $field)
            ->setOptional(true)
            ->setSize(6)
            ->setCliField('--provider PROVIDER-NAME')
            ->setLabel(tr('Provider'))
            ->setAutoComplete([
                'word' => function ($word) {
                    return Providers::new()->filteredList($word);
                },
                'noword' => function () {
                    return Providers::new()->getSource();
                },
            ])
            ->addValidationFunction(function (ValidatorInterface $validator) {
                // Ensure provider exists and that its or provider
                $validator->or('providers_id')->isProvider();
            });
    }


    /**
     * Returns Definition object for column customers_id
     *
     * @param DataEntryInterface $data_entry
     * @param string|null $field
     * @param array|null $filters
     * @return DefinitionInterface
     */
    public static function getCustomersId(DataEntryInterface $data_entry, ?string $field = 'customers_id', array $filters = null): DefinitionInterface
    {
        return Definition::new($data_entry, $field)
            ->setOptional(true)
            ->setContent(function (DefinitionInterface $definition, string $key, string $field_name, array $source) use ($filters) {
                return Customers::new()->getHtmlSelect()
                    ->setName($key)
                    ->setSelected(isset_get($source[$key]))
                    ->render();
            })
            ->setSize(6)
            ->setLabel(tr('Customer'))
            ->addValidationFunction(function (ValidatorInterface $validator) {
                // Ensure customers id exists and that its or customer
                $validator->or('customer')->isDbId()->isQueryColumn('SELECT `id` FROM `business_customers` WHERE `id` = :id AND `status` IS NULL', [':id' => '$customers_id']);
            });
    }


    /**
     * Returns Definition object for column customer
     *
     * @param DataEntryInterface $data_entry
     * @param string|null $field
     * @return DefinitionInterface
     */
    public static function getCustomer(DataEntryInterface $data_entry, ?string $field = 'customer'): DefinitionInterface
    {
        return Definition::new($data_entry, $field)
            ->setOptional(true)
            ->setSize(6)
            ->setCliField('--customer CUSTOMER-NAME')
            ->setLabel(tr('Customer'))
            ->setAutoComplete([
                'word' => function ($word) {
                    return Customers::new()->filteredList($word);
                },
                'noword' => function () {
                    return Customers::new()->getSource();
                },
            ])
            ->addValidationFunction(function (ValidatorInterface $validator) {
                // Ensure customer exists and that its or customer
                $validator->or('customers_id')->isCustomer();
            });
    }


    /**
     * Returns Definition object for column timezones_id
     *
     * @param DataEntryInterface $data_entry
     * @param string|null $field
     * @param array|null $filters
     * @return DefinitionInterface
     */
    public static function getTimezonesId(DataEntryInterface $data_entry, ?string $field = 'timezones_id', array $filters = null): DefinitionInterface
    {
        return Definition::new($data_entry, $field)
            ->setOptional(true)
            ->setInputType(InputType::number)
            ->setContent(function (DefinitionInterface $definition, string $key, string $field_name, array $source) use ($filters) {
                return Timezones::new()->getHtmlSelect()
                    ->setSelected(isset_get($source['timezones_id']))
                    ->render();
            })
            ->setCliField('--timezones-id TIMEZONE-DATABASE-ID')
            ->setAutoComplete(true)
            ->setSize(3)
            ->setLabel(tr('Timezone'))
            ->addValidationFunction(function (ValidatorInterface $validator) {
                $validator->or('timezone')->isDbId()->isTrue(function ($value) {
                    // This timezone must exist.
                    return Timezone::exists('name', $value);
                }, tr('The specified timezone does not exist'));
            });
    }


    /**
     * Returns Definition object for column timezone
     *
     * @param DataEntryInterface $data_entry
     * @param string|null $field
     * @return DefinitionInterface
     */
    public static function getTimezone(DataEntryInterface $data_entry, ?string $field = 'timezone'): DefinitionInterface
    {
        return Definition::new($data_entry, $field)
            ->setOptional(true)
            ->setVirtual(true)
            ->setVisible(false)
            ->setCliField('-t,--timezone TIMEZONE-NAME')
            ->setLabel(tr('Timezone'))
            ->setAutoComplete([
                'word' => function ($word) {
                    return Timezones::new()->filteredList($word);
                },
                'noword' => function () {
                    return Timezones::new()->getSource();
                },
            ])
            ->addValidationFunction(function (ValidatorInterface $validator) {
                // Ensure timezone exists and that its or timezone
                $validator->or('timezones_id')->isName()->setColumnFromQuery('timezones_id', 'SELECT `id` FROM `geo_timezones` WHERE `name` = :name AND `status` IS NULL', [':name' => '$timezone']);
            });
    }


    /**
     * Returns Definition object for column countries_id
     *
     * @param DataEntryInterface $data_entry
     * @param string|null $field
     * @param array|null $filters
     * @return DefinitionInterface
     */
    public static function getCountriesId(DataEntryInterface $data_entry, ?string $field = 'countries_id', array $filters = null): DefinitionInterface
    {
        return Definition::new($data_entry, $field)
            ->setOptional(true)
            ->setInputType(InputType::number)
            ->setContent(function (DefinitionInterface $definition, string $key, string $field_name, array $source) use ($filters) {
                return Countries::getHtmlCountriesSelect()
                    ->setName($key)
                    ->setSelected(isset_get($source[$key]))
                    ->render();
            })
            ->setSize(3)
            ->setCliField('--countries-id COUNTRY-DATABASE-ID')
            ->setAutoComplete(true)
            ->setLabel(tr('Country'))
            ->addValidationFunction(function (ValidatorInterface $validator) {
                $validator->or('country')->isDbId()->isQueryColumn('SELECT `id` FROM `geo_countries` WHERE `id` = :id AND `status` IS NULL', [':id' => '$countries_id']);
            });
    }


    /**
     * Returns Definition object for column timezone
     *
     * @param DataEntryInterface $data_entry
     * @param string|null $field
     * @return DefinitionInterface
     */
    public static function getCountry(DataEntryInterface $data_entry, ?string $field = 'country'): DefinitionInterface
    {
        return Definition::new($data_entry, $field)
            ->setOptional(true)
            ->setVisible(false)
            ->setVirtual(true)
            ->setCliField('--country COUNTRY-NAME')
            ->setLabel(tr('Country'))
            ->setAutoComplete([
                'word' => function ($word) {
                    return Countries::new()->filteredList($word);
                },
                'noword' => function () {
                    return Countries::new()->getSource();
                },
            ])
            ->addValidationFunction(function (ValidatorInterface $validator) {
                // Ensure country exists and that its or countries_id
                $validator->or('countries_id')->isName(200)->setColumnFromQuery('countries_id', 'SELECT `id` FROM `geo_countries` WHERE `name` = :name AND `status` IS NULL', [':name' => '$country']);
            });
    }


    /**
     * Returns Definition object for column states_id
     *
     * @param DataEntryInterface $data_entry
     * @param string|null $field
     * @param array|null $filters
     * @return DefinitionInterface
     */
    public static function getStatesId(DataEntryInterface $data_entry, ?string $field = 'states_id', array $filters = null): DefinitionInterface
    {
        return Definition::new($data_entry, $field)
            ->setOptional(true)
            ->setInputType(InputType::number)
            ->setContent(function (DefinitionInterface $definition, string $key, string $field_name, array $source) use ($filters) {
                return Country::get($source['countries_id'])->getHtmlStatesSelect($key)
                    ->setName($key)
                    ->setSelected(isset_get($source[$key]))
                    ->render();
            })
            ->setSize(3)
            ->setCliField('--states-id STATE-DATABASE-ID')
            ->setAutoComplete(true)
            ->setLabel(tr('State'))
            ->addValidationFunction(function (ValidatorInterface $validator) {
                $validator->or('state')->isDbId()->isQueryColumn('SELECT `id` FROM `geo_states` WHERE `id` = :id AND `countries_id` = :countries_id AND `status` IS NULL', [':id' => '$states_id', ':countries_id' => '$countries_id']);
            });
    }


    /**
     * Returns Definition object for column timezone
     *
     * @param DataEntryInterface $data_entry
     * @param string|null $field
     * @return DefinitionInterface
     */
    public static function getState(DataEntryInterface $data_entry, ?string $field = 'state'): DefinitionInterface
    {
        return Definition::new($data_entry, $field)
            ->setOptional(true)
            ->setVisible(false)
            ->setVirtual(true)
            ->setCliField('--state STATE-NAME')
            ->setLabel(tr('State'))
            ->setAutoComplete([
                'word' => function ($word) {
                    return States::new()->filteredList($word);
                },
                'noword' => function () {
                    return States::new()->getSource();
                },
            ])
            ->addValidationFunction(function (ValidatorInterface $validator) {
                // Ensure state exists and that its or states_id
                $validator->or('states_id')->isName()->setColumnFromQuery('states_id', 'SELECT `name` FROM `geo_states` WHERE `name` = :name AND `countries_id` = :countries_id AND `status` IS NULL', [':name' => '$state', ':countries_id' => '$countries_id']);
            });
    }


    /**
     * Returns Definition object for column cities_id
     *
     * @param DataEntryInterface $data_entry
     * @param string|null $field
     * @param array|null $filters
     * @return DefinitionInterface
     */
    public static function getCitiesId(DataEntryInterface $data_entry, ?string $field = 'cities_id', array $filters = null): DefinitionInterface
    {
        return Definition::new($data_entry, $field)
            ->setOptional(true)
            ->setInputType(InputType::number)
            ->setContent(function (DefinitionInterface $definition, string $key, string $field_name, array $source) use ($filters) {
                return State::get($source['states_id'])->getHtmlCitiesSelect($key)
                    ->setName($key)
                    ->setSelected(isset_get($source[$key]))
                    ->render();
            })
            ->setSize(3)
            ->setCliField('--cities-id CITY-DATABASE-ID')
            ->setAutoComplete(true)
            ->setLabel(tr('City'))
            ->addValidationFunction(function (ValidatorInterface $validator) {
                $validator->or('city')->isDbId()->isQueryColumn('SELECT `id` FROM `geo_cities` WHERE `id` = :id AND `states_name`  = :states_id    AND `status` IS NULL', [':id' => '$cities_id', ':states_id' => '$states_id']);
            });
    }


    /**
     * Returns Definition object for column timezone
     *
     * @param DataEntryInterface $data_entry
     * @param string|null $field
     * @return DefinitionInterface
     */
    public static function getCity(DataEntryInterface $data_entry, ?string $field = 'city'): DefinitionInterface
    {
        return Definition::new($data_entry, $field)
            ->setOptional(true)
            ->setVisible(false)
            ->setVirtual(true)
            ->setCliField('--city CITY-NAME')
            ->setLabel(tr('City'))
            ->setAutoComplete([
                'word' => function ($word) {
                    return Cities::new()->filteredList($word);
                },
                'noword' => function () {
                    return Cities::new()->getSource();
                },
            ])
            ->addValidationFunction(function (ValidatorInterface $validator) {
                // Ensure city exists and that its or cities_id
                $validator->or('cities_id')->isName()->setColumnFromQuery('cities_id', 'SELECT `name` FROM `geo_cities` WHERE `name` = :name AND `states_name`  = :states_id    AND `status` IS NULL', [':name' => '$city', ':states_id' => '$states_id']);
            });
    }


    /**
     * Returns Definition object for column users_id
     *
     * @param DataEntryInterface $data_entry
     * @param string|null $field
     * @param array|null $filters
     * @return DefinitionInterface
     */
    public static function getUsersId(DataEntryInterface $data_entry, ?string $field = 'users_id', array $filters = null): DefinitionInterface
    {
        return Definition::new($data_entry, $field)
            ->setOptional(true)
            ->setInputType(InputTypeExtended::dbid)
            ->setSize(3)
            ->setAutoComplete(true)
            ->setContent(function (DefinitionInterface $definition, string $key, string $field_name, array $source) use ($filters) {
                return Users::new()->getHtmlSelect()
                    ->setSelected(isset_get($source[$key]))
                    ->render();
            })
            ->addValidationFunction(function (ValidatorInterface $validator) {
                $validator->isDbId()->isQueryColumn('SELECT `id` FROM `accounts_users` WHERE `id` = :id AND `status` IS NULL', [':id' => '$leaders_id']);
            });
    }


    /**
     * Returns Definition object for column users_id
     *
     * @param DataEntryInterface $data_entry
     * @param string|null $field
     * @return DefinitionInterface
     */
    public static function getUser(DataEntryInterface $data_entry, ?string $field = 'email'): DefinitionInterface
    {
        return Definition::new($data_entry, $field)
            ->setOptional(true)
            ->setVirtual(true)
            ->setInputType(InputType::email)
            ->setCliField('-u,--user EMAIL')
            ->setLabel(tr('User'))
            ->setAutoComplete([
                'word' => function ($word) {
                    return Users::new()->filteredList($word);
                },
                'noword' => function () {
                    return Users::new()->getSource();
                },
            ])
            ->addValidationFunction(function (ValidatorInterface $validator) {
                $validator->or('users_id')->setColumnFromQuery('users_id', 'SELECT `id` FROM `accounts_users` WHERE `email` = :email', [':email' => '$email']);
            });
    }


    /**
     * Returns Definition object for column code
     *
     * @param DataEntryInterface $data_entry
     * @param string|null $field
     * @return DefinitionInterface
     */
    public static function getCode(DataEntryInterface $data_entry, ?string $field = 'code'): DefinitionInterface
    {
        return Definition::new($data_entry, $field)
            ->setOptional(true)
            ->setSize(3)
            ->setMaxlength(16)
            ->setCliField('-c,--code CODE')
            ->setAutoComplete(true)
            ->setLabel(tr('Code'))
            ->addValidationFunction(function (ValidatorInterface $validator) {
                $validator->isCode();
            });
    }


    /**
     * Returns Definition object for column datetime
     *
     * @param DataEntryInterface $data_entry
     * @param string|null $field
     * @return DefinitionInterface
     */
    public static function getDateTime(DataEntryInterface $data_entry, ?string $field = 'datetime'): DefinitionInterface
    {
        return Definition::new($data_entry, $field)
            ->setOptional(true)
            ->setInputType(InputType::datetime_local)
            ->setSize(3)
            ->setLabel(tr('Date time'));
    }


    /**
     * Returns Definition object for column date
     *
     * @param DataEntryInterface $data_entry
     * @param string|null $field
     * @return DefinitionInterface
     */
    public static function getDate(DataEntryInterface $data_entry, ?string $field = 'date'): DefinitionInterface
    {
        return Definition::new($data_entry, $field)
            ->setOptional(true)
            ->setInputType(InputType::date)
            ->setSize(3)
            ->setAutoComplete(true)
            ->setLabel(tr('Date'));
    }


    /**
     * Returns Definition object for column date
     *
     * @param DataEntryInterface $data_entry
     * @param string|null $field
     * @return DefinitionInterface
     */
    public static function getTime(DataEntryInterface $data_entry, ?string $field = 'time'): DefinitionInterface
    {
        return Definition::new($data_entry, $field)
            ->setOptional(true)
            ->setInputType(InputType::time)
            ->setSize(3)
            ->setAutoComplete(true)
            ->setLabel(tr('Time'));
    }


    /**
     * Returns Definition object for column title
     *
     * @param DataEntryInterface $data_entry
     * @param string|null $field
     * @return DefinitionInterface
     */
    public static function getTitle(DataEntryInterface $data_entry, ?string $field = 'title'): DefinitionInterface
    {
        return Definition::new($data_entry, $field)
            ->setOptional(true)
            ->setMaxLength(24)
            ->setSize(3)
            ->setCliField('-t,--title TITLE')
            ->setAutoComplete(true)
            ->setLabel(tr('Title'))
            ->addValidationFunction(function (ValidatorInterface $validator) {
                $validator->isName();
            });
    }


    /**
     * Returns Definition object for column name
     *
     * @param DataEntryInterface $data_entry
     * @param string|null $field
     * @return DefinitionInterface
     */
    public static function getName(DataEntryInterface $data_entry, ?string $field = 'name'): DefinitionInterface
    {
        return Definition::new($data_entry, $field)
            ->setMaxLength(64)
            ->setSize(3)
            ->setLabel(tr('Name'))
            ->setCliField(tr('-n,--name NAME'))
            ->setInputType(InputTypeExtended::name)
            ->setAutoComplete(true)
            ->addValidationFunction(function (ValidatorInterface $validator) {
                $validator->isName();
            });
    }


    /**
     * Returns Definition object for column email
     *
     * @param DataEntryInterface $data_entry
     * @param string|null $field
     * @return DefinitionInterface
     */
    public static function getEmail(DataEntryInterface $data_entry, ?string $field = 'email'): DefinitionInterface
    {
        return Definition::new($data_entry, $field)
            ->setOptional(true)
            ->setInputType(InputType::email)
            ->setMaxlength(128)
            ->setCliField('-e,--email EMAIL')
            ->setAutoComplete(true)
            ->setLabel(tr('Email address'));
    }


    /**
     * Returns Definition object for column url
     *
     * @param DataEntryInterface $data_entry
     * @param string|null $field
     * @return DefinitionInterface
     */
    public static function getUrl(DataEntryInterface $data_entry, ?string $field = 'url'): DefinitionInterface
    {
        return Definition::new($data_entry, $field)
            ->setOptional(true)
            ->setInputType(InputType::url)
            ->setMaxlength(2048)
            ->setAutoComplete(true)
            ->setCliField('--w,--website WEBSITE-URL')
            ->setLabel(tr('Website URL'))
            ->addValidationFunction(function (ValidatorInterface $validator) {
                $validator->isOptional()->isUrl();
            });
    }


    /**
     * Returns Definition object for column phone
     *
     * @param DataEntryInterface $data_entry
     * @param string|null $field
     * @return DefinitionInterface
     */
    public static function getPhone(DataEntryInterface $data_entry, ?string $field = 'phone'): DefinitionInterface
    {
        return Definition::new($data_entry, $field)
            ->setOptional(true)
            ->setInputType(InputTypeExtended::phone)
            ->setLabel(tr('Phone number'))
            ->setCliField(tr('-p,--phone-number PHONE-NUMBER'))
            ->setMaxlength(16);
    }


    /**
     * Returns Definition object for column phones
     *
     * @param DataEntryInterface $data_entry
     * @param string|null $field
     * @return DefinitionInterface
     */
    public static function getPhones(DataEntryInterface $data_entry, ?string $field = 'phones'): DefinitionInterface
    {
        return Definition::new($data_entry, $field)
            ->setOptional(true)
            ->setMinlength(10)
            ->setMaxLength(64)
            ->setSize(3)
            ->setCliField(tr('-p,--phone-numbers "PHONE-NUMBER,PHONE-NUMBER,..."'))
            ->setAutoComplete(true)
            ->setLabel(tr('Phone numbers'))
            ->setHelpGroup(tr('Personal information'))
            ->setHelpText(tr('Phone numbers where this user can be reached'))
            ->addValidationFunction(function (ValidatorInterface $validator) {
                $validator->isPhoneNumbers();
                // $validator->sanitizeForceArray(',')->each()->isPhoneNumber()->sanitizeForceString()
            });
    }


    /**
     * Returns Definition object for column seo_name
     *
     * @param DataEntryInterface $data_entry
     * @param string|null $field
     * @return DefinitionInterface
     */
    public static function getSeoName(DataEntryInterface $data_entry, ?string $field = 'seo_name'): DefinitionInterface
    {
        return Definition::new($data_entry, $field)
            ->setOptional(true)
            ->setVisible(false)
            ->setReadonly(true);
    }


    /**
     * Returns Definition object for column description
     *
     * @param DataEntryInterface $data_entry
     * @param string|null $field
     * @return DefinitionInterface
     */
    public static function getDescription(DataEntryInterface $data_entry, ?string $field = 'description'): DefinitionInterface
    {
        return Definition::new($data_entry, $field)
            ->setOptional(true)
            ->setInputType(InputTypeExtended::description)
            ->setSize(12)
            ->setMaxlength(65_535)
            ->setCliField('-d,--description "DESCRIPTION"')
            ->setAutoComplete(true)
            ->setLabel(tr('Description'));
    }


    /**
     * Returns Definition object for column comments
     *
     * @param DataEntryInterface $data_entry
     * @param string|null $field
     * @return DefinitionInterface
     */
    public static function getComments(DataEntryInterface $data_entry, ?string $field = 'comments'): DefinitionInterface
    {
        return Definition::new($data_entry, $field)
            ->setOptional(true)
            ->setInputType(InputTypeExtended::description)
            ->setSize(12)
            ->setMaxlength(65_535)
            ->setCliField('--comments "COMMENTS"')
            ->setAutoComplete(true)
            ->setLabel(tr('Comments'));
    }
}