<?php

/**
 * Provider class
 *
 *
 *
 * @see       \Phoundation\Data\DataEntries\DataEntry
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Business
 */


declare(strict_types=1);

namespace Phoundation\Business\Providers;

use Phoundation\Business\Providers\Interfaces\ProviderInterface;
use Phoundation\Data\DataEntries\DataEntry;
use Phoundation\Data\DataEntries\Definitions\Definition;
use Phoundation\Data\DataEntries\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryAddress;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryCategory;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryCode;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryCompany;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryEmail;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryGeo;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryProfilePictureFile;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryLanguage;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryNameDescription;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryPhones;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryUrl;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;


class Provider extends DataEntry implements ProviderInterface
{
    use TraitDataEntryGeo;
    use TraitDataEntryUrl;
    use TraitDataEntryCode;
    use TraitDataEntryEmail;
    use TraitDataEntryPhones;
    use TraitDataEntryAddress;
    use TraitDataEntryCompany;
    use TraitDataEntryProfilePictureFile;
    use TraitDataEntryCategory;
    use TraitDataEntryLanguage;
    use TraitDataEntryNameDescription;

    /**
     * Returns the table name used by this object
     *
     * @return string|null
     */
    public static function getTable(): ?string
    {
        return 'business_providers';
    }


    /**
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getEntryName(): string
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
        return $this->getTypesafe('string', 'address2');
    }


    /**
     * Sets the address2 for this object
     *
     * @param string|null $address2
     *
     * @return static
     */
    public function setAddress2(?string $address2): static
    {
        return $this->set($address2, 'address2');
    }


    /**
     * Returns the address3 for this object
     *
     * @return string|null
     */
    public function getAddress3(): ?string
    {
        return $this->getTypesafe('string', 'address3');
    }


    /**
     * Sets the address3 for this object
     *
     * @param string|null $address3
     *
     * @return static
     */
    public function setAddress3(?string $address3): static
    {
        return $this->set($address3, 'address3');
    }


    /**
     * Sets the available data keys for the User class
     *
     * @param DefinitionsInterface $definitions
     *
     * @return void
     */
    protected function setDefinitionsObject(DefinitionsInterface $definitions): static
    {
        $definitions->add(DefinitionFactory::getCategoriesId($this))
                    ->add(DefinitionFactory::getCategory($this))
                    ->add(DefinitionFactory::getCompaniesId($this))
                    ->add(DefinitionFactory::getCompany($this))
                    ->add(DefinitionFactory::getName($this)
                                           ->addValidationFunction(function (ValidatorInterface $validator) {
                                               $validator->isFalse(function ($value, $source) {
                                                   Provider::exists(['name' => $value], isset_get($source['id']));
                                               }, tr('already exists'));
                                           }))
                    ->add(DefinitionFactory::getSeoName($this))
                    ->add(DefinitionFactory::getCode($this))
                    ->add(DefinitionFactory::getEmail($this))
                    ->add(DefinitionFactory::getLanguagesId($this))
                    ->add(DefinitionFactory::getLanguage($this))
                    ->add(Definition::new('address1')
                                    ->setOptional(true)
                                    ->setCliAutoComplete(true)
                                    ->setCliColumn('--address1 ADDRESS')
                                    ->setMaxlength(64)
                                    ->setSize(12)
                                    ->setLabel(tr('Address 1'))
                                    ->setHelpText(tr('Address information for this provider')))
                    ->add(Definition::new('address2')
                                    ->setOptional(true)
                                    ->setCliAutoComplete(true)
                                    ->setCliColumn('--address2 ADDRESS')
                                    ->setMaxlength(64)
                                    ->setSize(12)
                                    ->setLabel(tr('Address 2'))
                                    ->setHelpText(tr('Additional address information for this provider')))
                    ->add(Definition::new('address3')
                                    ->setOptional(true)
                                    ->setCliAutoComplete(true)
                                    ->setCliColumn('--address3 ADDRESS')
                                    ->setMaxlength(64)
                                    ->setSize(12)
                                    ->setLabel(tr('Address 3'))
                                    ->setHelpText(tr('Additional address information for this provider')))
                    ->add(Definition::new('zipcode')
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
                    ->add(Definition::new('picture')
                                    ->setVirtual(true)
                                    ->setRender(false));
    }
}
