<?php

namespace Phoundation\Business\Customers;

use Phoundation\Accounts\Users\Users;
use Phoundation\Business\Companies\Companies;
use Phoundation\Core\Locale\Language\Languages;
use Phoundation\Data\Categories\Categories;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Traits\DataEntryAddress;
use Phoundation\Data\DataEntry\Traits\DataEntryCategory;
use Phoundation\Data\DataEntry\Traits\DataEntryCode;
use Phoundation\Data\DataEntry\Traits\DataEntryCompany;
use Phoundation\Data\DataEntry\Traits\DataEntryEmail;
use Phoundation\Data\DataEntry\Traits\DataEntryGeo;
use Phoundation\Data\DataEntry\Traits\DataEntryLanguage;
use Phoundation\Data\DataEntry\Traits\DataEntryNameDescription;
use Phoundation\Data\DataEntry\Traits\DataEntryPhones;
use Phoundation\Data\DataEntry\Traits\DataEntryPicture;
use Phoundation\Data\DataEntry\Traits\DataEntryUrl;
use Phoundation\Data\Interfaces\InterfaceDataEntry;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Data\Validator\Validator;
use Phoundation\Geo\Cities\Cities;
use Phoundation\Geo\Countries\Countries;
use Phoundation\Geo\Countries\Country;
use Phoundation\Geo\States\State;
use Phoundation\Geo\States\States;

/**
 * Customer class
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataEntry
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Business
 */
class Customer extends DataEntry
{
    use DataEntryGeo;
    use DataEntryUrl;
    use DataEntryCode;
    use DataEntryEmail;
    use DataEntryPhones;
    use DataEntryAddress;
    use DataEntryCompany;
    use DataEntryPicture;
    use DataEntryCategory;
    use DataEntryLanguage;
    use DataEntryNameDescription;


    /**
     * Customer class constructor
     *
     * @param InterfaceDataEntry|string|int|null $identifier
     */
    public function __construct(InterfaceDataEntry|string|int|null $identifier = null)
    {
        static::$entry_name  = 'customer';
        $this->table         = 'business_customers';
        $this->unique_field = 'seo_name';

        parent::__construct($identifier);
    }


    /**
     * Validates the provider record with the specified validator object
     *
     * @param ArgvValidator|PostValidator|GetValidator $validator
     * @param bool $no_arguments_left
     * @param bool $modify
     * @return array
     */
    protected function validate(ArgvValidator|PostValidator|GetValidator $validator, bool $no_arguments_left = false, bool $modify = false): array
    {
        $validator->hasMaxCharacters()
            ->select('name')->isOptional()->isName()
            ->select('code')->isOptional()->isDomain()
            ->select('email')->isOptional()->isEmail()
            ->select('zipcode')->isOptional()->isString()->hasMinCharacters(4)->hasMaxCharacters(7)
            ->select('phones')->isOptional()->sanitizeForceArray(',')->each()->isPhone()->sanitizeForceString()
            ->select('address')->isOptional()->isPrintable()->hasMaxCharacters(64)
            ->select('address2')->isOptional()->isPrintable()->hasMaxCharacters(64)
            ->select('address3')->isOptional()->isPrintable()->hasMaxCharacters(64)
            ->select('categories_id')->isOptional()->isId()->isQueryColumn('SELECT `id` FROM `categories` WHERE `id` = :id AND `status` IS NULL', [':id' => '$categories_id'])
            ->select('languages_id')->isOptional()->isId()->isQueryColumn('SELECT `id` FROM `core_languages` WHERE `id` = :id AND `status` IS NULL', [':id' => '$languages_id'])
            ->select('companies_id')->isOptional()->isId()->isQueryColumn('SELECT `id` FROM `business_companies` WHERE `id` = :id AND `status` IS NULL', [':id' => '$companies_id'])
            ->select('countries_id')->isOptional()->isId()->isQueryColumn('SELECT `id` FROM `geo_countries` WHERE `id` = :id AND `status` IS NULL', [':id' => '$countries_id'])
            ->select('states_id')->isOptional()->isId()->isQueryColumn('SELECT `id` FROM `geo_states` WHERE `id` = :id AND `countries_id` = :countries_id AND `status` IS NULL', [':id' => 'states_id', ':countries_id' => '$countries_id'])
            ->select('cities_id')->isOptional()->isId()->isQueryColumn('SELECT `id` FROM `geo_cities` WHERE `id` = :id AND `states_id`    = :states_id    AND `status` IS NULL', [':id' => 'cities_id', ':states_id'    => '$states_id'])
            ->select('description')->isOptional()->isPrintable()->hasMaxCharacters(65_530)
            ->select('url')->isOptional()->isUrl()
            ->validate();
    }


    /**
     * Returns the address2 for this object
     *
     * @return string|null
     */
    public function getAddress2(): ?string
    {
        return $this->getDataValue('address2');
    }


    /**
     * Sets the address2 for this object
     *
     * @param string|null $address2
     * @return static
     */
    public function setAddress2(?string $address2): static
    {
        return $this->setDataValue('address2', $address2);
    }


    /**
     * Returns the address3 for this object
     *
     * @return string|null
     */
    public function getAddress3(): ?string
    {
        return $this->getDataValue('address3');
    }


    /**
     * Sets the address3 for this object
     *
     * @param string|null $address3
     * @return static
     */
    public function setAddress3(?string $address3): static
    {
        return $this->setDataValue('address3', $address3);
    }


    /**
     * Sets the available data keys for the User class
     *
     * @return array
     */
    protected static function getFieldDefinitions(): array
    {
        return [
            'country' => [
                'complete' => [
                    'word'   => function($word) { return Countries::new()->filteredList($word); },
                    'noword' => function()      { return Countries::new()->list(); },
                ],
                'virtual'    => true,
                'cli'        => '--country COUNTRY-NAME',
                'help_group' => tr('Location information'),
                'help'       => tr('The country where this customer is located'),
            ],
            'state' => [
                'complete' => [
                    'word'   => function($word) { return States::new()->filteredList($word); },
                    'noword' => function()      { return States::new()->list(); },
                ],
                'virtual'    => true,
                'cli'        => '--state STATE-NAME',
                'help_group' => tr('Location information'),
                'help'       => tr('The state where this customer is located'),
            ],
            'city' => [
                'complete' => [
                    'word'   => function($word) { return Cities::new()->filteredList($word); },
                    'noword' => function()      { return Cities::new()->list(); },
                ],
                'virtual'    => true,
                'cli'        => '--city CITY-NAME',
                'help_group' => tr('Location information'),
                'help'       => tr('The city where this customer is located'),
            ],
            'category' => [
                'complete' => [
                    'word'   => function($word) { return Categories::new()->filteredList($word); },
                    'noword' => function()      { return Categories::new()->list(); },
                ],
                'virtual'    => true,
                'cli'        => '--category CATEGORY-NAME',
                'help_group' => tr('Organisation information'),
                'help'       => tr('The category under which this customer is organized'),
            ],
            'company' => [
                'complete' => [
                    'word'   => function($word) { return Companies::new()->filteredList($word); },
                    'noword' => function()      { return Companies::new()->list(); },
                ],
                'virtual'    => true,
                'cli'        => '--company COMPANY-NAME',
                'help_group' => tr('Organisation information'),
                'help'       => tr('The language in which the site will be displayed to the user'),
            ],
            'language' => [
                'complete' => [
                    'word'   => function($word) { return Languages::new()->filteredList($word); },
                    'noword' => function()      { return Languages::new()->list(); },
                ],
                'virtual'    => true,
                'cli'        => '-l,--language LANGUAGE-NAME',
                'help_group' => tr('Location information'),
                'help'       => tr('The language in which the site will be displayed to the user'),
            ],
            'name' => [
                'required'   => true,
                'complete'   => true,
                'cli'        => '-n,--name NAME',
                'size'       => 6,
                'maxlength'  => 64,
                'label'      => tr('Name'),
                'help_group' => tr('Identification'),
                'help'       => tr('The name for this customer'),
            ],
            'seo_name' => [
                'visible'  => false
            ],
            'code' => [
                'complete'   => true,
                'cli'        => '-c,--code CODE',
                'size'       => 6,
                'maxlength'  => 64,
                'label'      => tr('Code'),
                'help_group' => tr('Identification'),
                'help'       => tr('The unique code for this customer'),
            ],
            'email' => [
                'complete'   => true,
                'type'       => 'email',
                'cli'        => '-e,--email CODE',
                'size'       => 6,
                'maxlength'  => 128,
                'label'      => tr('Email'),
                'help_group' => tr('Contact'),
                'help'       => tr('The contact email for this customer'),
            ],
            'phones' => [
                'complete'   => true,
                'cli'        => '-p,--phones PHONE,PHONE',
                'size'       => 6,
                'maxlength'  => 64,
                'label'      => tr('Phones'),
                'help_group' => tr('Contact'),
                'help'       => tr('The customer phone number(s)'),
            ],
            'picture' => [
                'visible'  => false
            ],
            'url' => [
                'complete'   => true,
                'cli'        => '-u,--url URL',
                'size'       => 6,
                'maxlength'  => 2048,
                'label'      => tr('URL'),
                'help'       => tr('A URL with more information about this customer'),
            ],
            'address1' => [
                'complete'  => true,
                'cli'       => '--address1 URL',
                'size'      => 12,
                'maxlength' => 64,
                'label'     => tr('Address 1'),
                'help'      => tr('Address information for this customer'),
            ],
            'address2' => [
                'complete'  => true,
                'cli'       => '--address2 URL',
                'size'      => 12,
                'maxlength' => 64,
                'label'     => tr('Address 2'),
                'help'      => tr('Address information for this customer'),
            ],
            'address3' => [
                'complete'  => true,
                'cli'       => '--address3 URL',
                'size'      => 6,
                'maxlength' => 64,
                'label'     => tr('Address 3'),
                'help'      => tr('Address information for this customer'),
            ],
            'zipcode' => [
                'complete'  => true,
                'cli'       => '--address3 URL',
                'size'      => 6,
                'maxlength' => 8,
                'label'     => tr('Postal code'),
                'help'      => tr('Postal code (zipcode) information for this customer'),
            ],
            'countries_id' => [
                'element'  => function (string $key, array $data, array $source) {
                    return Countries::getHtmlCountriesSelect($key)
                        ->setSelected(isset_get($source['countries_id']))
                        ->render();
                },
                'cli'        => '--countries-id',
                'complete'   => true,
                'label'      => tr('Country'),
                'size'       => 4,
                'help_group' => tr('Location information'),
                'help'       => tr('The database id of the country where this customer is located'),
            ],
            'states_id' => [
                'element'  => function (string $key, array $data, array $source) {
                    return Country::get($source['countries_id'])->getHtmlStatesSelect($key)
                        ->setSelected(isset_get($source['states_id']))
                        ->render();
                },
                'cli'        => '--states-id',
                'complete'   => true,
                'label'      => tr('State'),
                'size'       => 4,
                'help_group' => tr('Location information'),
                'help'       => tr('The database id of the state where this customer is located'),
            ],
            'cities_id' => [
                'element'  => function (string $key, array $data, array $source) {
                    return State::get($source['states_id'])->getHtmlCitiesSelect($key)
                        ->setSelected(isset_get($source['cities_id']))
                        ->render();
                },
                'cli'        => '--companies-id',
                'complete'   => true,
                'label'      => tr('City'),
                'size'       => 4,
                'help_group' => tr('Location information'),
                'help'       => tr('The database id of the country where this customer is located'),
            ],
            'categories_id' => [
                'element'  => function (string $key, array $data, array $source) {
                    return Categories::getHtmlSelect($key)
                        ->setSelected(isset_get($source['categories_id']))
                        ->render();
                },
                'cli'        => '--categories-id',
                'complete'   => true,
                'label'      => tr('Category'),
                'size'       => 4,
                'help_group' => tr('Location information'),
                'help'       => tr('The database id of the category under which this customer is organized'),
            ],
            'companies_id' => [
                'element'  => function (string $key, array $data, array $source) {
                    return Companies::getHtmlSelect($key)
                        ->setSelected(isset_get($source['companies_id']))
                        ->render();
                },
                'cli'        => '--companies-id',
                'complete'   => true,
                'label'      => tr('Company'),
                'size'       => 4,
                'help_group' => tr('Organisation information'),
                'help'       => tr('The database id of the company that is linked to this organization'),
            ],
            'languages_id' => [
                'element'  => function (string $key, array $data, array $source) {
                    return Languages::getHtmlSelect($key)
                        ->setSelected(isset_get($source['languages_id']))
                        ->render();
                },
                'cli'        => '--languages-id',
                'complete'   => true,
                'label'      => tr('Language'),
                'size'       => 4,
                'help_group' => tr('Location information'),
                'help'       => tr('The language in which the site will be displayed to the user'),
            ],
            'description' => [
                'element'    => 'text',
                'cli'        => '-d,--description',
                'complete'   => true,
                'label'      => tr('Description'),
                'maxlength'  => 65_535,
                'size'       => 12,
                'help_group' => tr('Account information'),
                'help'       => tr('A description about this user'),
            ],
        ];
    }
}