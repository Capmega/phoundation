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
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
     * Provider class constructor
     *
     * @param DataEntryInterface|string|int|null $identifier
     * @param bool $init
     */
    public function __construct(DataEntryInterface|string|int|null $identifier = null, bool $init = true)
    {
        $this->table        = 'business_providers';
        $this->entry_name   = 'provider';
        $this->unique_field = 'seo_name';

        parent::__construct($identifier, $init);
    }


    /**
     * Returns the address2 for this object
     *
     * @return string|null
     */
    public function getAddress2(): ?string
    {
        return $this->getDataValue('string', 'address2');
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
        return $this->getDataValue('string', 'address3');
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
     * @param DefinitionsInterface $definitions
     * @return void
     */
    protected function initDefinitions(DefinitionsInterface $definitions): void
    {
        $definitions
            ->addDefinition(DefinitionFactory::getCategoriesId())
            ->addDefinition(DefinitionFactory::getCategory())
            ->addDefinition(DefinitionFactory::getCompaniesId())
            ->addDefinition(DefinitionFactory::getCompany())
            ->addDefinition(DefinitionFactory::getName()
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->isFalse(function($value, $source) {
                        Provider::exists($value, isset_get($source['id']));
                    }, tr('already exists'));
                }))
            ->addDefinition(DefinitionFactory::getSeoName())
            ->addDefinition(DefinitionFactory::getCode())
            ->addDefinition(DefinitionFactory::getEmail())
            ->addDefinition(DefinitionFactory::getLanguagesId())
            ->addDefinition(DefinitionFactory::getLanguage())
            ->addDefinition(Definition::new('address1')
                ->setOptional(true)
                ->setAutoComplete(true)
                ->setCliField('--address1 ADDRESS')
                ->setMaxlength(64)
                ->setSize(12)
                ->setLabel(tr('Address 1'))
                ->setHelpText(tr('Address information for this provider')))
            ->addDefinition(Definition::new('address2')
                ->setOptional(true)
                ->setAutoComplete(true)
                ->setCliField('--address2 ADDRESS')
                ->setMaxlength(64)
                ->setSize(12)
                ->setLabel(tr('Address 2'))
                ->setHelpText(tr('Additional address information for this provider')))
            ->addDefinition(Definition::new('address3')
                ->setOptional(true)
                ->setAutoComplete(true)
                ->setCliField('--address3 ADDRESS')
                ->setMaxlength(64)
                ->setSize(12)
                ->setLabel(tr('Address 3'))
                ->setHelpText(tr('Additional address information for this provider')))
            ->addDefinition(Definition::new('zipcode')
                ->setOptional(true)
                ->setAutoComplete(true)
                ->setCliField('--zip-code ZIPCODE (POSTAL CODE)')
                ->setMaxlength(8)
                ->setSize(6)
                ->setLabel(tr('Postal code / Zipcode'))
                ->setHelpText(tr('Postal code (zipcode) information for this provider')))
            ->addDefinition(DefinitionFactory::getCountriesId())
            ->addDefinition(DefinitionFactory::getCountry())
            ->addDefinition(DefinitionFactory::getStatesId())
            ->addDefinition(DefinitionFactory::getState())
            ->addDefinition(DefinitionFactory::getCitiesId())
            ->addDefinition(DefinitionFactory::getCity())
            ->addDefinition(DefinitionFactory::getPhones())
            ->addDefinition(DefinitionFactory::getUrl())
            ->addDefinition(DefinitionFactory::getDescription())
            ->addDefinition(Definition::new('picture')
                ->setVirtual(true)
                ->setVisible(false));
    }
}