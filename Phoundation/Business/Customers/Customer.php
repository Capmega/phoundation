<?php

namespace Phoundation\Business\Customers;

use Phoundation\Accounts\Users\Users;
use Phoundation\Business\Companies\Companies;
use Phoundation\Core\Locale\Language\Languages;
use Phoundation\Data\Categories\Categories;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\DataEntryAddress;
use Phoundation\Data\DataEntry\DataEntryCategory;
use Phoundation\Data\DataEntry\DataEntryCode;
use Phoundation\Data\DataEntry\DataEntryCompany;
use Phoundation\Data\DataEntry\DataEntryEmail;
use Phoundation\Data\DataEntry\DataEntryGeo;
use Phoundation\Data\DataEntry\DataEntryLanguage;
use Phoundation\Data\DataEntry\DataEntryNameDescription;
use Phoundation\Data\DataEntry\DataEntryPhones;
use Phoundation\Data\DataEntry\DataEntryPicture;
use Phoundation\Data\DataEntry\DataEntryUrl;
use Phoundation\Data\Validator\Validator;
use Phoundation\Geo\Countries\Countries;
use Phoundation\Geo\Countries\Country;
use Phoundation\Geo\States\State;



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
     * Validates the customer record with the specified validator object
     *
     * @param Validator $validator
     * @return void
     */
    public static function validate(Validator $validator): void
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
            ->select('languages_id')->isOptional()->isId()->isQueryColumn('SELECT `id` FROM `languages` WHERE `id` = :id AND `status` IS NULL', [':id' => '$languages_id'])
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
     * Customer class constructor
     *
     * @param int|string|null $identifier
     */
    public function __construct(int|string|null $identifier = null)
    {
        static::$entry_name = 'customer';
        $this->table      = 'business_customers';

        parent::__construct($identifier);
    }



    /**
     * Sets the available data keys for the User class
     *
     * @return void
     */
    protected function setKeys(): void
    {
        $this->keys = [
            'id' => [
                'disabled'  => true,
                'type'     => 'numeric',
                'label'    => tr('Database ID')
            ],
            'created_on' => [
                'disabled' => true,
                'type'     => 'date',
                'label'    => tr('Created on')
            ],
            'created_by' => [
                'disabled' => true,
                'element'  => function (string $key, array $data, array $source) {
                    return Users::getHtmlSelect($key)
                        ->setSelected(isset_get($source['created_by']))
                        ->render();
                },
                'label'    => tr('Created by')
            ],
            'meta_id' => [
                'disabled' => true,
                'element'  => null, //Meta::new()->getHtmlTable(), // TODO implement
                'label'    => tr('Meta information')
            ],
            'status' => [
                'disabled' => true,
                'default'  => tr('Ok'),
                'label'    => tr('Status')
            ],
            'name' => [
                'label'    => tr('Name')
            ],
            'seo_name' => [
                'display'  => false
            ],
            'code' => [
                'label'    => tr('Code')
            ],
            'email' => [
                'label'    => tr('Email'),
                'type'     => 'email'
            ],
            'phones' => [
                'label'    => tr('Phones')
            ],
            'url' => [
                'label'    => tr('Url'),
                'type'     => 'url',
            ],
            'address' => [
                'label'     => tr('Address 1')
            ],
            'address2' => [
                'label'     => tr('Address 2')
            ],
            'address3' => [
                'label'     => tr('Address 3')
            ],
            'zipcode' => [
                'label'     => tr('Postal code')
            ],
            'categories_id' => [
                'element'  => function (string $key, array $data, array $source) {
                    return Categories::getHtmlSelect($key)
                        ->setSelected(isset_get($source['categories_id']))
                        ->render();
                },
                'label'    => tr('Category'),
            ],
            'companies_id' => [
                'element'  => function (string $key, array $data, array $source) {
                    return Companies::getHtmlSelect($key)
                        ->setSelected(isset_get($source['companies_id']))
                        ->render();
                },
                'label'    => tr('Company'),
            ],
            'languages_id' => [
                'element'  => function (string $key, array $data, array $source) {
                    return Languages::getHtmlSelect($key)
                        ->setSelected(isset_get($source['languages_id']))
                        ->render();
                },
                'label'    => tr('Language'),
            ],
            'countries_id' => [
                'element'  => function (string $key, array $data, array $source) {
                    return Countries::getHtmlCountriesSelect($key)
                        ->setSelected(isset_get($source['countries_id']))
                        ->render();
                },
                'label'    => tr('Country')
            ],
            'states_id' => [
                'element'  => function (string $key, array $data, array $source) {
                    return Country::get($source['countries_id'])->getHtmlStatesSelect($key)
                        ->setSelected(isset_get($source['states_id']))
                        ->render();
                },
                'label'    => tr('State'),
            ],
            'cities_id' => [
                'element'  => function (string $key, array $data, array $source) {
                    return State::get($source['states_id'])->getHtmlCitiesSelect($key)
                        ->setSelected(isset_get($source['cities_id']))
                        ->render();
                },
                'label'    => tr('City'),
            ],
            'description' => [
                'element'  => 'text',
                'label'    => tr('Description'),
            ],
        ];

        $this->keys_display = [
            'id'            => 12,
            'created_by'    => 6,
            'created_on'    => 6,
            'meta_id'       => 6,
            'status'        => 6,
            'name'          => 6,
            'code'          => 6,
            'email'         => 6,
            'phones'        => 6,
            'url'           => 12,
            'address'       => 12,
            'address2'      => 12,
            'address3'      => 6,
            'zipcode'       => 6,
            'categories_id' => 6,
            'companies_id'  => 6,
            'languages_id'  => 6,
            'countries_id'  => 6,
            'states_id'     => 6,
            'cities_id'     => 6,
            'description'   => 12,
        ] ;
    }
}