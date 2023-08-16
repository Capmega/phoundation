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
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
    public static function getUniqueField(): ?string
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
        return $this->getSourceValue('string', 'address2');
    }


    /**
     * Sets the address2 for this object
     *
     * @param string|null $address2
     * @return static
     */
    public function setAddress2(?string $address2): static
    {
        return $this->setSourceValue('address2', $address2);
    }


    /**
     * Returns the address3 for this object
     *
     * @return string|null
     */
    public function getAddress3(): ?string
    {
        return $this->getSourceValue('string', 'address3');
    }


    /**
     * Sets the address3 for this object
     *
     * @param string|null $address3
     * @return static
     */
    public function setAddress3(?string $address3): static
    {
        return $this->setSourceValue('address3', $address3);
    }


    /**
     * Sets the available data keys for the User class
     *
     * @param DefinitionsInterface $definitions
     */
    protected function initDefinitions(DefinitionsInterface $definitions): void
    {
        $definitions
            ->addDefinition(DefinitionFactory::getCategoriesId($this))
            ->addDefinition(DefinitionFactory::getCategory($this))
            ->addDefinition(DefinitionFactory::getCompaniesId($this))
            ->addDefinition(DefinitionFactory::getCompany($this))
            ->addDefinition(DefinitionFactory::getName($this)
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->isFalse(function($value, $source) {
                        Customer::exists($value, 'name', isset_get($source['id']));
                    }, tr('already exists'));
                }))
            ->addDefinition(DefinitionFactory::getSeoName($this))
            ->addDefinition(DefinitionFactory::getCode($this))
            ->addDefinition(DefinitionFactory::getEmail($this))
            ->addDefinition(DefinitionFactory::getLanguagesId($this))
            ->addDefinition(DefinitionFactory::getLanguage($this))
            ->addDefinition(Definition::new($this, 'address1')
                ->setOptional(true)
                ->setCliAutoComplete(true)
                ->setCliField('--address1 ADDRESS')
                ->setMaxlength(64)
                ->setSize(12)
                ->setLabel(tr('Address 1'))
                ->setHelpText(tr('Address information for this customer')))
            ->addDefinition(Definition::new($this, 'address2')
                ->setOptional(true)
                ->setCliAutoComplete(true)
                ->setCliField('--address2 ADDRESS')
                ->setMaxlength(64)
                ->setSize(12)
                ->setLabel(tr('Address 2'))
                ->setHelpText(tr('Additional address information for this customer')))
            ->addDefinition(Definition::new($this, 'address3')
                ->setOptional(true)
                ->setCliAutoComplete(true)
                ->setCliField('--address3 ADDRESS')
                ->setMaxlength(64)
                ->setSize(12)
                ->setLabel(tr('Address 3'))
                ->setHelpText(tr('Additional address information for this customer')))
            ->addDefinition(Definition::new($this, 'zipcode')
                ->setOptional(true)
                ->setCliAutoComplete(true)
                ->setCliField('--zip-code ZIPCODE (POSTAL CODE)')
                ->setMaxlength(8)
                ->setSize(6)
                ->setLabel(tr('Postal code / Zipcode'))
                ->setHelpText(tr('Postal code (zipcode) information for this customer')))
            ->addDefinition(DefinitionFactory::getCountriesId($this))
            ->addDefinition(DefinitionFactory::getCountry($this))
            ->addDefinition(DefinitionFactory::getStatesId($this))
            ->addDefinition(DefinitionFactory::getState($this))
            ->addDefinition(DefinitionFactory::getCitiesId($this))
            ->addDefinition(DefinitionFactory::getCity($this))
            ->addDefinition(DefinitionFactory::getPhones($this))
            ->addDefinition(DefinitionFactory::getUrl($this))
            ->addDefinition(DefinitionFactory::getDescription($this))
            ->addDefinition(Definition::new($this, 'picture')
                ->setVirtual(true)
                ->setVisible(false));
    }
}