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
        self::$entry_name = 'customer';
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

        $this->form_keys = [
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