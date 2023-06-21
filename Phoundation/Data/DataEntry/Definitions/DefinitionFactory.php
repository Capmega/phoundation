<?php

namespace Phoundation\Data\DataEntry\Definitions;

use Phoundation\Accounts\Users\User;
use Phoundation\Accounts\Users\Users;
use Phoundation\Business\Companies\Companies;
use Phoundation\Business\Customers\Customers;
use Phoundation\Business\Providers\Providers;
use Phoundation\Core\Locale\Language\Languages;
use Phoundation\Data\Categories\Categories;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionInterface;
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
     * @param string $column_name
     * @param array|null $filters
     * @return DefinitionInterface
     */
    public static function getCategoriesId(string $column_name = 'categories_id', array $filters = null): DefinitionInterface
    {
        return Definition::new($column_name)
            ->setOptional(true)
            ->setContent(function (string $key, array $data, array $source) use ($filters) {
                return Categories::new()->getHtmlSelect()
                    ->setName($key)
                    ->setSelected(isset_get($source[$key]))
                    ->render();
            })
            ->setSize(6)
            ->setLabel(tr('Category'))
            ->addValidationFunction(function ($validator) {
                // Ensure categories id exists and that its or category
                $validator->or('category')->isId()->isQueryColumn('SELECT `id` FROM `categories` WHERE `id` = :id AND `status` IS NULL', [':id' => '$categories_id']);
            });
    }


    /**
     * Returns Definition object for column category
     *
     * @param string $column_name
     * @return DefinitionInterface
     */
    public static function getCategory(string $column_name = 'category'): DefinitionInterface
    {
        return Definition::new($column_name)
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
            ->addValidationFunction(function ($validator) {
                // Ensure category exists and that its or category
                $validator->or('categories_id')->isCategory();
            });
    }


    /**
     * Returns Definition object for column companies_id
     *
     * @param string $column_name
     * @param array|null $filters
     * @return DefinitionInterface
     */
    public static function getCompaniesId(string $column_name = 'companies_id', array $filters = null): DefinitionInterface
    {
        return Definition::new($column_name)
            ->setOptional(true)
            ->setContent(function (string $key, array $data, array $source) use ($filters) {
                return Companies::new()->getHtmlSelect()
                    ->setName($key)
                    ->setSelected(isset_get($source[$key]))
                    ->render();
            })
            ->setSize(6)
            ->setLabel(tr('Company'))
            ->addValidationFunction(function ($validator) {
                // Ensure companies id exists and that its or company
                $validator->or('company')->isId()->isQueryColumn('SELECT `id` FROM `business_companies` WHERE `id` = :id AND `status` IS NULL', [':id' => '$companies_id']);
            });
    }


    /**
     * Returns Definition object for column company
     *
     * @param string $column_name
     * @return DefinitionInterface
     */
    public static function getCompany(string $column_name = 'company'): DefinitionInterface
    {
        return Definition::new($column_name)
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
            ->addValidationFunction(function ($validator) {
                // Ensure company exists and that its or company
                $validator->or('companies_id')->isCompany();
            });
    }


    /**
     * Returns Definition object for column languages_id
     *
     * @param string $column_name
     * @param array|null $filters
     * @return DefinitionInterface
     */
    public static function getLanguagesId(string $column_name = 'languages_id', array $filters = null): DefinitionInterface
    {
        return Definition::new($column_name)
            ->setOptional(true)
            ->setInputType(InputType::number)
            ->setContent(function (string $key, array $data, array $source) use ($filters) {
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
            ->addValidationFunction(function ($validator) {
                $validator->or('language')->isId()->isQueryColumn('SELECT `id` FROM `core_languages` WHERE `id` = :id AND `status` IS NULL', [':id' => '$languages_id']);
            });
    }


    /**
     * Returns Definition object for column language
     *
     * @param string $column_name
     * @return DefinitionInterface
     */
    public static function getLanguage(string $column_name = 'language'): DefinitionInterface
    {
        return Definition::new($column_name)
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
            ->addValidationFunction(function ($validator) {
                // Ensure language exists and that its or language
                $validator->or('languages_id')->isName()->setColumnFromQuery('languages_id', 'SELECT `id` FROM `core_languages` WHERE `code_639_1` = :code AND `status` IS NULL', [':code' => '$language']);
            });
    }


    /**
     * Returns Definition object for column providers_id
     *
     * @param string $column_name
     * @param array|null $filters
     * @return DefinitionInterface
     */
    public static function getProvidersId(string $column_name = 'providers_id', array $filters = null): DefinitionInterface
    {
        return Definition::new($column_name)
            ->setOptional(true)
            ->setContent(function (string $key, array $data, array $source) use ($filters) {
                return Providers::new()->getHtmlSelect()
                    ->setName($key)
                    ->setSelected(isset_get($source[$key]))
                    ->render();
            })
            ->setSize(6)
            ->setLabel(tr('Provider'))
            ->addValidationFunction(function ($validator) {
                // Ensure providers id exists and that its or provider
                $validator->or('provider')->isId()->isQueryColumn('SELECT `id` FROM `business_providers` WHERE `id` = :id AND `status` IS NULL', [':id' => '$providers_id']);
            });
    }


    /**
     * Returns Definition object for column provider
     *
     * @param string $column_name
     * @return DefinitionInterface
     */
    public static function getProvider(string $column_name = 'provider'): DefinitionInterface
    {
        return Definition::new($column_name)
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
            ->addValidationFunction(function ($validator) {
                // Ensure provider exists and that its or provider
                $validator->or('providers_id')->isProvider();
            });
    }


    /**
     * Returns Definition object for column customers_id
     *
     * @param string $column_name
     * @param array|null $filters
     * @return DefinitionInterface
     */
    public static function getCustomersId(string $column_name = 'customers_id', array $filters = null): DefinitionInterface
    {
        return Definition::new($column_name)
            ->setOptional(true)
            ->setContent(function (string $key, array $data, array $source) use ($filters) {
                return Customers::new()->getHtmlSelect()
                    ->setName($key)
                    ->setSelected(isset_get($source[$key]))
                    ->render();
            })
            ->setSize(6)
            ->setLabel(tr('Customer'))
            ->addValidationFunction(function ($validator) {
                // Ensure customers id exists and that its or customer
                $validator->or('customer')->isId()->isQueryColumn('SELECT `id` FROM `business_customers` WHERE `id` = :id AND `status` IS NULL', [':id' => '$customers_id']);
            });
    }


    /**
     * Returns Definition object for column customer
     *
     * @param string $column_name
     * @return DefinitionInterface
     */
    public static function getCustomer(string $column_name = 'customer'): DefinitionInterface
    {
        return Definition::new($column_name)
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
            ->addValidationFunction(function ($validator) {
                // Ensure customer exists and that its or customer
                $validator->or('customers_id')->isCustomer();
            });
    }


    /**
     * Returns Definition object for column timezones_id
     *
     * @param string $column_name
     * @param array|null $filters
     * @return DefinitionInterface
     */
    public static function getTimezonesId(string $column_name = 'timezones_id', array $filters = null): DefinitionInterface
    {
        return Definition::new($column_name)
            ->setOptional(true)
            ->setInputType(InputType::number)
            ->setContent(function (string $key, array $data, array $source) use ($filters) {
                return Timezones::getHtmlSelect($key)
                    ->setSelected(isset_get($source['timezones_id']))
                    ->render();
            })
            ->setCliField('--timezones-id TIMEZONE-DATABASE-ID')
            ->setAutoComplete(true)
            ->setSize(3)
            ->setLabel(tr('Timezone'))
            ->addValidationFunction(function ($validator) {
                $validator->or('timezone')->isId()->isTrue(function ($value) {
                    // This timezone must exist.
                    return Timezone::exists($value);
                }, tr('The specified timezone does not exist'));
            });
    }


    /**
     * Returns Definition object for column timezone
     *
     * @param string $column_name
     * @return DefinitionInterface
     */
    public static function getTimezone(string $column_name = 'timezone'): DefinitionInterface
    {
        return Definition::new($column_name)
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
            ->addValidationFunction(function ($validator) {
                // Ensure timezone exists and that its or timezone
                $validator->or('timezones_id')->isName()->setColumnFromQuery('timezones_id', 'SELECT `id` FROM `geo_timezones` WHERE `name` = :name AND `status` IS NULL', [':name' => '$timezone']);
            });
    }


    /**
     * Returns Definition object for column countries_id
     *
     * @param string $column_name
     * @param array|null $filters
     * @return DefinitionInterface
     */
    public static function getCountriesId(string $column_name = 'countries_id', array $filters = null): DefinitionInterface
    {
        return Definition::new($column_name)
            ->setOptional(true)
            ->setInputType(InputType::number)
            ->setContent(function (string $key, array $data, array $source) use ($filters) {
                return Countries::getHtmlCountriesSelect()
                    ->setName($key)
                    ->setSelected(isset_get($source[$key]))
                    ->render();
            })
            ->setSize(3)
            ->setCliField('--countries-id COUNTRY-DATABASE-ID')
            ->setAutoComplete(true)
            ->setLabel(tr('Country'))
            ->addValidationFunction(function ($validator) {
                $validator->or('country')->isId()->isQueryColumn('SELECT `id` FROM `geo_countries` WHERE `id` = :id AND `status` IS NULL', [':id' => '$countries_id']);
            });
    }


    /**
     * Returns Definition object for column timezone
     *
     * @param string $column_name
     * @return DefinitionInterface
     */
    public static function getCountry(string $column_name = 'country'): DefinitionInterface
    {
        return Definition::new($column_name)
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
            ->addValidationFunction(function ($validator) {
                // Ensure country exists and that its or countries_id
                $validator->or('countries_id')->isName(200)->setColumnFromQuery('countries_id', 'SELECT `id` FROM `geo_countries` WHERE `name` = :name AND `status` IS NULL', [':name' => '$country']);
            });
    }


    /**
     * Returns Definition object for column states_id
     *
     * @param string $column_name
     * @param array|null $filters
     * @return DefinitionInterface
     */
    public static function getStatesId(string $column_name = 'states_id', array $filters = null): DefinitionInterface
    {
        return Definition::new($column_name)
            ->setOptional(true)
            ->setInputType(InputType::number)
            ->setContent(function (string $key, array $data, array $source) use ($filters) {
                return Country::get($source['countries_id'])->getHtmlStatesSelect($key)
                    ->setName($key)
                    ->setSelected(isset_get($source[$key]))
                    ->render();
            })
            ->setSize(3)
            ->setCliField('--states-id STATE-DATABASE-ID')
            ->setAutoComplete(true)
            ->setLabel(tr('State'))
            ->addValidationFunction(function ($validator) {
                $validator->or('state')->isId()->isQueryColumn('SELECT `id` FROM `geo_states` WHERE `id` = :id AND `countries_id` = :countries_id AND `status` IS NULL', [':id' => '$states_id', ':countries_id' => '$countries_id']);
            });
    }


    /**
     * Returns Definition object for column timezone
     *
     * @param string $column_name
     * @return DefinitionInterface
     */
    public static function getState(string $column_name = 'state'): DefinitionInterface
    {
        return Definition::new($column_name)
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
            ->addValidationFunction(function ($validator) {
                // Ensure state exists and that its or states_id
                $validator->or('states_id')->isName()->setColumnFromQuery('states_id', 'SELECT `name` FROM `geo_states` WHERE `name` = :name AND `countries_id` = :countries_id AND `status` IS NULL', [':name' => '$state', ':countries_id' => '$countries_id']);
            });
    }


    /**
     * Returns Definition object for column cities_id
     *
     * @param string $column_name
     * @param array|null $filters
     * @return DefinitionInterface
     */
    public static function getCitiesId(string $column_name = 'cities_id', array $filters = null): DefinitionInterface
    {
        return Definition::new($column_name)
            ->setOptional(true)
            ->setInputType(InputType::number)
            ->setContent(function (string $key, array $data, array $source) use ($filters) {
                return State::get($source['states_id'])->getHtmlCitiesSelect($key)
                    ->setName($key)
                    ->setSelected(isset_get($source[$key]))
                    ->render();
            })
            ->setSize(3)
            ->setCliField('--cities-id CITY-DATABASE-ID')
            ->setAutoComplete(true)
            ->setLabel(tr('City'))
            ->addValidationFunction(function ($validator) {
                $validator->or('city')->isId()->isQueryColumn('SELECT `id` FROM `geo_cities` WHERE `id` = :id AND `states_name`  = :states_id    AND `status` IS NULL', [':id' => '$cities_id', ':states_id' => '$states_id']);
            });
    }


    /**
     * Returns Definition object for column timezone
     *
     * @param string $column_name
     * @return DefinitionInterface
     */
    public static function getCity(string $column_name = 'city'): DefinitionInterface
    {
        return Definition::new($column_name)
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
            ->addValidationFunction(function ($validator) {
                // Ensure city exists and that its or cities_id
                $validator->or('cities_id')->isName()->setColumnFromQuery('cities_id', 'SELECT `name` FROM `geo_cities` WHERE `name` = :name AND `states_name`  = :states_id    AND `status` IS NULL', [':name' => '$city', ':states_id' => '$states_id']);
            });
    }


    /**
     * Returns Definition object for column users_id
     *
     * @param string $column_name
     * @param array|null $filters
     * @return DefinitionInterface
     */
    public static function getUsersId(string $column_name = 'users_id', array $filters = null): DefinitionInterface
    {
        return Definition::new($column_name)
            ->setOptional(true)
            ->setInputType(InputTypeExtended::dbid)
            ->setSize(3)
            ->setAutoComplete(true)
            ->setContent(function (string $key, array $data, array $source) use ($filters) {
                return Users::new()->getHtmlSelect($filters)
                    ->setSelected(isset_get($source[$key]))
                    ->render();
            })
            ->addValidationFunction(function ($validator) {
                $validator->isId()->isQueryColumn('SELECT `id` FROM `accounts_users` WHERE `id` = :id AND `status` IS NULL', [':id' => '$leaders_id']);
            });
    }


    /**
     * Returns Definition object for column users_id
     *
     * @param string $column_name
     * @return DefinitionInterface
     */
    public static function getUser(string $column_name = 'email'): DefinitionInterface
    {
        return Definition::new($column_name)
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
            ->addValidationFunction(function ($validator) {
                $validator->or('users_id')->setColumnFromQuery('users_id', 'SELECT `id` FROM `accounts_users` WHERE `email` = :email', [':email' => '$email']);
            });
    }


    /**
     * Returns Definition object for column code
     *
     * @param string $column_name
     * @return DefinitionInterface
     */
    public static function getCode(string $column_name = 'code'): DefinitionInterface
    {
        return Definition::new($column_name)
            ->setOptional(true)
            ->setSize(3)
            ->setCliField('-c,--code CODE')
            ->setAutoComplete(true)
            ->setLabel(tr('Code'))
            ->addValidationFunction(function ($validator) {
                $validator->isCode();
            });
    }


    /**
     * Returns Definition object for column datetime
     *
     * @param string $column_name
     * @return DefinitionInterface
     */
    public static function getDateTime(string $column_name = 'datetime'): DefinitionInterface
    {
        return Definition::new($column_name)
            ->setOptional(true)
            ->setInputType(InputType::datetime_local)
            ->setSize(3)
            ->setLabel(tr('Date time'));
    }


    /**
     * Returns Definition object for column date
     *
     * @param string $column_name
     * @return DefinitionInterface
     */
    public static function getDate(string $column_name = 'date'): DefinitionInterface
    {
        return Definition::new($column_name)
            ->setOptional(true)
            ->setInputType(InputType::date)
            ->setSize(3)
            ->setAutoComplete(true)
            ->setLabel(tr('Date'));
    }


    /**
     * Returns Definition object for column date
     *
     * @param string $column_name
     * @return DefinitionInterface
     */
    public static function getTime(string $column_name = 'time'): DefinitionInterface
    {
        return Definition::new($column_name)
            ->setOptional(true)
            ->setInputType(InputType::time)
            ->setSize(3)
            ->setAutoComplete(true)
            ->setLabel(tr('Time'));
    }


    /**
     * Returns Definition object for column title
     *
     * @param string $column_name
     * @return DefinitionInterface
     */
    public static function getTitle(string $column_name = 'title'): DefinitionInterface
    {
        return Definition::new($column_name)
            ->setOptional(true)
            ->setMaxLength(24)
            ->setSize(3)
            ->setCliField('-t,--title TITLE')
            ->setAutoComplete(true)
            ->setLabel(tr('Title'))
            ->addValidationFunction(function ($validator) {
                $validator->isName();
            });
    }


    /**
     * Returns Definition object for column name
     *
     * @param string $column_name
     * @return DefinitionInterface
     */
    public static function getName(string $column_name = 'name'): DefinitionInterface
    {
        return Definition::new($column_name)
            ->setMaxLength(64)
            ->setSize(3)
            ->setLabel(tr('Name'))
            ->setCliField(tr('-n,--name NAME'))
            ->setInputType(InputTypeExtended::name)
            ->setAutoComplete(true)
            ->addValidationFunction(function ($validator) {
                $validator->isName();
            });
    }


    /**
     * Returns Definition object for column email
     *
     * @param string $column_name
     * @return DefinitionInterface
     */
    public static function getEmail(string $column_name = 'email'): DefinitionInterface
    {
        return Definition::new($column_name)
            ->setOptional(true)
            ->setInputType(InputType::email)
            ->setMaxlength(128)
            ->setCliField('-e,--email EMAIL')
            ->setAutoComplete(true)
            ->setLabel(tr('Email address'))
            ->addValidationFunction(function ($validator) {
                $validator->isTrue(function ($value, $source) {
                    // This email may NOT yet exist, unless its THIS user.
                    return User::notExists($value, isset_get($source['id']));
                }, tr('This email address already exists'));
            });
    }


    /**
     * Returns Definition object for column url
     *
     * @param string $column_name
     * @return DefinitionInterface
     */
    public static function getUrl(string $column_name = 'url'): DefinitionInterface
    {
        return Definition::new($column_name)
            ->setOptional(true)
            ->setInputType(InputType::url)
            ->setMaxlength(2048)
            ->setAutoComplete(true)
            ->setCliField('--w,--website WEBSITE-URL')
            ->setLabel(tr('Website URL'))
            ->addValidationFunction(function ($validator) {
                $validator->isOptional()->isUrl();
            });
    }


    /**
     * Returns Definition object for column phone
     *
     * @param string $column_name
     * @return DefinitionInterface
     */
    public static function getPhone(string $column_name = 'phone'): DefinitionInterface
    {
        return Definition::new($column_name)
            ->setOptional(true)
            ->setInputType(InputTypeExtended::phone)
            ->setLabel(tr('Phone number'))
            ->setCliField(tr('-p,--phone-number PHONE-NUMBER'))
            ->setMaxlength(16);
    }


    /**
     * Returns Definition object for column phones
     *
     * @param string $column_name
     * @return DefinitionInterface
     */
    public static function getPhones(string $column_name = 'phones'): DefinitionInterface
    {
        return Definition::new($column_name)
            ->setOptional(true)
            ->setMinlength(10)
            ->setMaxLength(64)
            ->setSize(3)
            ->setCliField(tr('-p,--phone-numbers "PHONE-NUMBER,PHONE-NUMBER,..."'))
            ->setAutoComplete(true)
            ->setLabel(tr('Phone numbers'))
            ->setHelpGroup(tr('Personal information'))
            ->setHelpText(tr('Phone numbers where this user can be reached'))
            ->addValidationFunction(function ($validator) {
                $validator->isPhoneNumbers();
                // $validator->sanitizeForceArray(',')->each()->isPhone()->sanitizeForceString()
            });
    }


    /**
     * Returns Definition object for column seo_name
     *
     * @param string $column_name
     * @return DefinitionInterface
     */
    public static function getSeoName(string $column_name = 'seo_name'): DefinitionInterface
    {
        return Definition::new($column_name)
            ->setOptional(true)
            ->setVisible(false)
            ->setReadonly(true);
    }


    /**
     * Returns Definition object for column description
     *
     * @param string $column_name
     * @return DefinitionInterface
     */
    public static function getDescription(string $column_name = 'description'): DefinitionInterface
    {
        return Definition::new($column_name)
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
     * @param string $column_name
     * @return DefinitionInterface
     */
    public static function getComments(string $column_name = 'comments'): DefinitionInterface
    {
        return Definition::new($column_name)
            ->setOptional(true)
            ->setInputType(InputTypeExtended::description)
            ->setSize(12)
            ->setMaxlength(65_535)
            ->setCliField('--comments "COMMENTS"')
            ->setAutoComplete(true)
            ->setLabel(tr('Comments'));
    }
}