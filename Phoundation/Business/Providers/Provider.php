<?php

declare(strict_types=1);

namespace Phoundation\Business\Providers;

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


/**
 * Provider class
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataEntry
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Business
 */
class Provider extends DataEntry
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
        return 'business_providers';
    }


    /**
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getDataEntryName(): string
    {
        return 'provider';
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
        return $this->getSourceValueTypesafe('string', 'address2');
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
        return $this->getSourceValueTypesafe('string', 'address3');
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
     * @return void
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
                        Provider::exists($value, 'name', isset_get($source['id']));
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
                ->setHelpText(tr('Address information for this provider')))
            ->add(Definition::new($this, 'address2')
                ->setOptional(true)
                ->setCliAutoComplete(true)
                ->setCliColumn('--address2 ADDRESS')
                ->setMaxlength(64)
                ->setSize(12)
                ->setLabel(tr('Address 2'))
                ->setHelpText(tr('Additional address information for this provider')))
            ->add(Definition::new($this, 'address3')
                ->setOptional(true)
                ->setCliAutoComplete(true)
                ->setCliColumn('--address3 ADDRESS')
                ->setMaxlength(64)
                ->setSize(12)
                ->setLabel(tr('Address 3'))
                ->setHelpText(tr('Additional address information for this provider')))
            ->add(Definition::new($this, 'zipcode')
                ->setOptional(true)
                ->setCliAutoComplete(true)
                ->setCliColumn('--zip-code ZIPCODE (POSTAL CODE)')
                ->setMaxlength(8)
                ->setSize(6)
                ->setLabel(tr('Postal code / Zipcode'))
                ->setHelpText(tr('Postal code (zipcode) information for this provider')))
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
