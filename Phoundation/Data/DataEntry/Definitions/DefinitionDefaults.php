<?php

namespace Phoundation\Data\DataEntry\Definitions;

use Phoundation\Accounts\Users\Users;
use Phoundation\Business\Customers\Customers;
use Phoundation\Business\Providers\Providers;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Geo\Cities\Cities;
use Phoundation\Geo\Countries\Countries;
use Phoundation\Geo\States\States;
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
class DefinitionDefaults
{
    /**
     * Returns Definition object for providers_id
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
                $validator->or('provider')->isId()->isQueryColumn('SELECT `id` FROM `geo_providers` WHERE `id` = :id AND `status` IS NULL', [':id' => '$providers_id']);
            });
    }


    /**
     * Returns Definition object for provider
     *
     * @param string $column_name
     * @return DefinitionInterface
     */
    public static function getProvider(string $column_name = 'provider'): DefinitionInterface
    {
        return Definition::new($column_name)
            ->setOptional(true)
            ->setSize(6)
            ->setCliField('-t,--provider')
            ->setLabel(tr('Provider'))
            ->setAutoComplete([
                'word' => function ($word) {
                    return Providers::new()->filteredList($word);
                },
                'noword' => function () {
                    return Providers::new()->list();
                },
            ])
            ->addValidationFunction(function ($validator) {
                // Ensure provider exists and that its or provider
                $validator->or('providers_id')->isProvider();
            });
    }


    /**
     * Returns Definition object for customers_id
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
                $validator->or('customer')->isId()->isQueryColumn('SELECT `id` FROM `geo_customers` WHERE `id` = :id AND `status` IS NULL', [':id' => '$customers_id']);
            });
    }


    /**
     * Returns Definition object for customer
     *
     * @param string $column_name
     * @return DefinitionInterface
     */
    public static function getCustomer(string $column_name = 'customer'): DefinitionInterface
    {
        return Definition::new($column_name)
            ->setOptional(true)
            ->setSize(6)
            ->setCliField('-t,--customer')
            ->setLabel(tr('Customer'))
            ->setAutoComplete([
                'word' => function ($word) {
                    return Customers::new()->filteredList($word);
                },
                'noword' => function () {
                    return Customers::new()->list();
                },
            ])
            ->addValidationFunction(function ($validator) {
                // Ensure customer exists and that its or customer
                $validator->or('customers_id')->isCustomer();
            });
    }


    /**
     * Returns Definition object for timezones_id
     *
     * @param string $column_name
     * @param array|null $filters
     * @return DefinitionInterface
     */
    public static function getTimezonesId(string $column_name = 'timezones_id', array $filters = null): DefinitionInterface
    {
        return Definition::new($column_name)
            ->setOptional(true)
            ->setContent(function (string $key, array $data, array $source) use ($filters) {
                return Timezones::new()->getHtmlSelect()
                    ->setName($key)
                    ->setSelected(isset_get($source[$key]))
                    ->render();
            })
            ->setSize(6)
            ->setLabel(tr('Timezone'))
            ->addValidationFunction(function ($validator) {
                // Ensure timezones id exists and that it's or timezone
                $validator->or('timezone')->isId()->isQueryColumn('SELECT `id` FROM `geo_timezones` WHERE `id` = :id AND `status` IS NULL', [':id' => '$timezones_id']);
            });
    }


    /**
     * Returns Definition object for timezone
     *
     * @param string $column_name
     * @return DefinitionInterface
     */
    public static function getTimezone(string $column_name = 'timezone'): DefinitionInterface
    {
        return Definition::new($column_name)
            ->setOptional(true)
            ->setSize(6)
            ->setCliField('-t,--timezone')
            ->setLabel(tr('Timezone'))
            ->setAutoComplete([
                'word' => function ($word) {
                    return Timezones::new()->filteredList($word);
                },
                'noword' => function () {
                    return Timezones::new()->list();
                },
            ])
            ->addValidationFunction(function ($validator) {
                // Ensure timezone exists and that its or timezone
                $validator->or('timezones_id')->isTimezone();
            });
    }


    /**
     * Returns Definition object for countries_id
     *
     * @param string $column_name
     * @param array|null $filters
     * @return DefinitionInterface
     */
    public static function getCountriesId(string $column_name = 'countries_id', array $filters = null): DefinitionInterface
    {
        return Definition::new($column_name)
            ->setOptional(true)
            ->setContent(function (string $key, array $data, array $source) use ($filters) {
                return Countries::new()->getHtmlSelect()
                    ->setName($key)
                    ->setSelected(isset_get($source[$key]))
                    ->render();
            })
            ->setSize(6)
            ->setLabel(tr('Country'))
            ->addValidationFunction(function ($validator) {
                // Ensure countries_id exists and that its or country
                $validator->or('country')->isId()->isQueryColumn('SELECT `id` FROM `geo_countries` WHERE `id` = :id AND `status` IS NULL', [':id' => '$countries_id']);
            });
    }


    /**
     * Returns Definition object for timezone
     *
     * @param string $column_name
     * @return DefinitionInterface
     */
    public static function getCountry(string $column_name = 'country'): DefinitionInterface
    {
        return Definition::new($column_name)
            ->setOptional(true)
            ->setSize(6)
            ->setCliField('-c,--country')
            ->setLabel(tr('Country'))
            ->setAutoComplete([
                'word' => function ($word) {
                    return Countries::new()->filteredList($word);
                },
                'noword' => function () {
                    return Countries::new()->list();
                },
            ])
            ->addValidationFunction(function ($validator) {
                // Ensure country exists and that its or countries_id
                $validator->or('countries_id')->isCountry();
            });
    }


    /**
     * Returns Definition object for states_id
     *
     * @param string $column_name
     * @param array|null $filters
     * @return DefinitionInterface
     */
    public static function getStatesId(string $column_name = 'states_id', array $filters = null): DefinitionInterface
    {
        return Definition::new($column_name)
            ->setOptional(true)
            ->setContent(function (string $key, array $data, array $source) use ($filters) {
                return States::new()->getHtmlSelect()
                    ->setName($key)
                    ->setSelected(isset_get($source[$key]))
                    ->render();
            })
            ->setSize(6)
            ->setLabel(tr('State'))
            ->addValidationFunction(function ($validator) {
                // Ensure states_id exists and that its or state
                $validator->or('state')->isId()->isQueryColumn('SELECT `id` FROM `geo_states` WHERE `id` = :id AND `status` IS NULL', [':id' => '$states_id']);
            });
    }


    /**
     * Returns Definition object for timezone
     *
     * @param string $column_name
     * @return DefinitionInterface
     */
    public static function getState(string $column_name = 'state'): DefinitionInterface
    {
        return Definition::new($column_name)
            ->setOptional(true)
            ->setSize(6)
            ->setCliField('-c,--state')
            ->setLabel(tr('State'))
            ->setAutoComplete([
                'word' => function ($word) {
                    return States::new()->filteredList($word);
                },
                'noword' => function () {
                    return States::new()->list();
                },
            ])
            ->addValidationFunction(function ($validator) {
                // Ensure state exists and that its or states_id
                $validator->or('states_id')->isState();
            });
    }


    /**
     * Returns Definition object for cities_id
     *
     * @param string $column_name
     * @param array|null $filters
     * @return DefinitionInterface
     */
    public static function getCitiesId(string $column_name = 'cities_id', array $filters = null): DefinitionInterface
    {
        return Definition::new($column_name)
            ->setOptional(true)
            ->setContent(function (string $key, array $data, array $source) use ($filters) {
                return Cities::new()->getHtmlSelect()
                    ->setName($key)
                    ->setSelected(isset_get($source[$key]))
                    ->render();
            })
            ->setSize(6)
            ->setLabel(tr('City'))
            ->addValidationFunction(function ($validator) {
                // Ensure cities id exists and that its or city
                $validator->or('city')->isId()->isQueryColumn('SELECT `id` FROM `geo_cities` WHERE `id` = :id AND `status` IS NULL', [':id' => '$cities_id']);
            });
    }


    /**
     * Returns Definition object for timezone
     *
     * @param string $column_name
     * @return DefinitionInterface
     */
    public static function getCity(string $column_name = 'city'): DefinitionInterface
    {
        return Definition::new($column_name)
            ->setOptional(true)
            ->setSize(6)
            ->setCliField('-c,--city')
            ->setLabel(tr('City'))
            ->setAutoComplete([
                'word' => function ($word) {
                    return Cities::new()->filteredList($word);
                },
                'noword' => function () {
                    return Cities::new()->list();
                },
            ])
            ->addValidationFunction(function ($validator) {
                // Ensure city exists and that its or cities_id
                $validator->or('cities_id')->isCity();
            });
    }


    /**
     * Returns Definition object for users_id
     *
     * @param string $column_name
     * @param array|null $filters
     * @return DefinitionInterface
     */
    public static function getUsersId(string $column_name = 'users_id', array $filters = null): DefinitionInterface
    {
        return Definition::new($column_name)
            ->setInputType(InputTypeExtended::dbid)
            ->setContent(function (string $key, array $data, array $source) use ($filters) {
                return Users::new()->getHtmlSelect($filters)->render();
            })
            ->addValidationFunction(function ($validator) {
                $validator->or('user')->isId()->isQueryColumn('SELECT `id` FROM `accounts_users` WHERE `id` = :id', [':id' => '$id']);
            });
    }


    /**
     * Returns Definition object for users_id
     *
     * @param string $column_name
     * @return DefinitionInterface
     */
    public static function getUser(string $column_name = 'user'): DefinitionInterface
    {
        return Definition::new($column_name)
            ->setVirtual(true)
            ->setOptional(true)
            ->setCliField('-u,--user')
            ->setLabel(tr('User'))
            ->setAutoComplete([
                'word' => function ($word) {
                    return Users::new()->filteredList($word);
                },
                'noword' => function () {
                    return Users::new()->list();
                },
            ])
            ->addValidationFunction(function ($validator) {
                $validator->or('users_id')->isUsername()->setColumnFromQuery('users_id', 'SELECT `id` FROM `accounts_users` WHERE `username` = :username', [':username' => '$username']);
            });
    }


    /**
     * Returns Definition object for code
     *
     * @param string $column_name
     * @return DefinitionInterface
     */
    public static function getCode(string $column_name = 'code'): DefinitionInterface
    {
        return Definition::new($column_name)
            ->setInputType(InputTypeExtended::code)
            ->setSize(2)
            ->setLabel(tr('Code'))
            ->setCliField('-c,--code CODE')
            ->addValidationFunction(function ($validator) {
                $validator->isCode();
            });
    }


    /**
     * Returns Definition object for datetime
     *
     * @param string $column_name
     * @return DefinitionInterface
     */
    public static function getDateTime(string $column_name = 'datetime'): DefinitionInterface
    {
        return Definition::new($column_name)
            ->setInputType(InputType::datetime_local)
            ->setSize(2)
            ->setLabel(tr('Date time'))
            ->addValidationFunction(function ($validator) {
                $validator->isDateTime();
            });
    }


    /**
     * Returns Definition object for title
     *
     * @param string $column_name
     * @return DefinitionInterface
     */
    public static function getTitle(string $column_name = 'title'): DefinitionInterface
    {
        return Definition::new($column_name)
            ->setReadonly(true)
            ->setLabel('Title')
            ->setMaxlength(255)
            ->addValidationFunction(function ($validator) {
                $validator->hasMaxCharacters(255)->isPrintable();
            });
    }


    /**
     * Returns Definition object for name
     *
     * @param string $column_name
     * @return DefinitionInterface
     */
    public static function getName(string $column_name = 'name'): DefinitionInterface
    {
        return Definition::new($column_name)
            ->setInputType(InputTypeExtended::name)
            ->setLabel(tr('Name'))
            ->setCliField(tr('-n,--name NAME'))
            ->setMaxlength(64);
    }


    /**
     * Returns Definition object for seo_name
     *
     * @param string $column_name
     * @return DefinitionInterface
     */
    public static function getSeoName(string $column_name = 'seo_name'): DefinitionInterface
    {
        return Definition::new($column_name)
            ->setVisible(true)
            ->setReadonly(true);
    }


    /**
     * Returns Definition object for description
     *
     * @param string $column_name
     * @return DefinitionInterface
     */
    public static function getDescription(string $column_name = 'description'): DefinitionInterface
    {
        return Definition::new($column_name)
            ->setOptional(true)
            ->setInputType(InputTypeExtended::description)
            ->setMaxlength(65_535)
            ->setCliField('-d,--description')
            ->setAutoComplete(true)
            ->setLabel(tr('Description'));
    }


    /**
     * Returns Definition object for comments
     *
     * @param string $column_name
     * @return DefinitionInterface
     */
    public static function getComments(string $column_name = 'comments'): DefinitionInterface
    {
        return Definition::new($column_name)
            ->setOptional(true)
            ->setInputType(InputTypeExtended::description)
            ->setMaxlength(65_535)
            ->setCliField('--comments')
            ->setAutoComplete(true)
            ->setLabel(tr('Comments'));
    }
}