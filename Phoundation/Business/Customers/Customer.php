<?php

declare(strict_types=1);

namespace Phoundation\Business\Customers;

use Phoundation\Business\Companies\Companies;
use Phoundation\Business\Providers\Provider;
use Phoundation\Core\Locale\Language\Languages;
use Phoundation\Data\Categories\Categories;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryAddress;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryCategory;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryCode;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryCompany;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryEmail;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryGeo;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryLanguage;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryNameDescription;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryPhones;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryPicture;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryUrl;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
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
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Business
 */
class Customer extends DataEntry
{
    use TraitDataEntryGeo;
    use TraitDataEntryUrl;
    use TraitDataEntryCode;
    use TraitDataEntryEmail;
    use TraitDataEntryPhones;
    use TraitDataEntryAddress;
    use TraitDataEntryCompany;
    use TraitDataEntryPicture;
    use TraitDataEntryCategory;
    use TraitDataEntryLanguage;
    use TraitDataEntryNameDescription;


    /**
     * Returns the table name used by this object
     *
     * @return string
     */
    public static function getTable(): string
    {
        return 'business_customers';
    }


    /**
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getDataEntryName(): string
    {
        return 'customer';
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
     * Returns the address2 for this object
     *
     * @return string|null
     */
    public function getAddress2(): ?string
    {
        return $this->getValueTypesafe('string', 'address2');
    }


    /**
     * Sets the address2 for this object
     *
     * @param string|null $address2
     * @return static
     */
    public function setAddress2(?string $address2): static
    {
        return $this->setValue('address2', $address2);
    }


    /**
     * Returns the address3 for this object
     *
     * @return string|null
     */
    public function getAddress3(): ?string
    {
        return $this->getValueTypesafe('string', 'address3');
    }


    /**
     * Sets the address3 for this object
     *
     * @param string|null $address3
     * @return static
     */
    public function setAddress3(?string $address3): static
    {
        return $this->setValue('address3', $address3);
    }


    /**
     * Sets the available data keys for the User class
     *
     * @param DefinitionsInterface $definitions
     */
    protected function setDefinitions(DefinitionsInterface $definitions): void
    {
        $definitions
            ->add(DefinitionFactory::getCategoriesId($this))
            ->add(DefinitionFactory::getCategory($this))
            ->add(DefinitionFactory::getCompaniesId($this))
            ->add(DefinitionFactory::getCompany($this))
            ->add(DefinitionFactory::getName($this)
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->isFalse(function($value, $source) {
                        Customer::exists($value, 'name', isset_get($source['id']));
                    }, tr('already exists'));
                }))
            ->add(DefinitionFactory::getSeoName($this))
            ->add(DefinitionFactory::getCode($this))
            ->add(DefinitionFactory::getEmail($this))
            ->add(DefinitionFactory::getLanguagesId($this))
            ->add(DefinitionFactory::getLanguage($this))
            ->add(Definition::new($this, 'address1')
                ->setOptional(true)
                ->setCliAutoComplete(true)
                ->setCliColumn('--address1 ADDRESS')
                ->setMaxlength(64)
                ->setSize(12)
                ->setLabel(tr('Address 1'))
                ->setHelpText(tr('Address information for this customer')))
            ->add(Definition::new($this, 'address2')
                ->setOptional(true)
                ->setCliAutoComplete(true)
                ->setCliColumn('--address2 ADDRESS')
                ->setMaxlength(64)
                ->setSize(12)
                ->setLabel(tr('Address 2'))
                ->setHelpText(tr('Additional address information for this customer')))
            ->add(Definition::new($this, 'address3')
                ->setOptional(true)
                ->setCliAutoComplete(true)
                ->setCliColumn('--address3 ADDRESS')
                ->setMaxlength(64)
                ->setSize(12)
                ->setLabel(tr('Address 3'))
                ->setHelpText(tr('Additional address information for this customer')))
            ->add(Definition::new($this, 'zipcode')
                ->setOptional(true)
                ->setCliAutoComplete(true)
                ->setCliColumn('--zip-code ZIPCODE (POSTAL CODE)')
                ->setMaxlength(8)
                ->setSize(6)
                ->setLabel(tr('Postal code / Zipcode'))
                ->setHelpText(tr('Postal code (zipcode) information for this customer')))
            ->add(DefinitionFactory::getCountriesId($this))
            ->add(DefinitionFactory::getCountry($this))
            ->add(DefinitionFactory::getStatesId($this))
            ->add(DefinitionFactory::getState($this))
            ->add(DefinitionFactory::getCitiesId($this))
            ->add(DefinitionFactory::getCity($this))
            ->add(DefinitionFactory::getPhones($this))
            ->add(DefinitionFactory::getUrl($this))
            ->add(DefinitionFactory::getDescription($this))
            ->add(Definition::new($this, 'picture')
                ->setVirtual(true)
                ->setRender(false));
    }
}
