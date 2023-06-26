<?php

declare(strict_types=1);

namespace Phoundation\Servers;

use JetBrains\PhpStorm\ExpectedValues;
use Phoundation\Business\Customers\Customers;
use Phoundation\Business\Providers\Providers;
use Phoundation\Data\Categories\Categories;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntry\Traits\DataEntryCustomer;
use Phoundation\Data\DataEntry\Traits\DataEntryDescription;
use Phoundation\Data\DataEntry\Traits\DataEntryHostnamePort;
use Phoundation\Data\DataEntry\Traits\DataEntryProvider;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Geo\Cities\Cities;
use Phoundation\Geo\Countries\Countries;
use Phoundation\Geo\States\States;
use Phoundation\Processes\Process;
use Phoundation\Servers\Traits\DataEntrySshAccount;
use Phoundation\Web\Http\Html\Enums\InputType;
use Phoundation\Web\Http\Html\Enums\InputTypeExtended;


/**
 * Server class
 *
 * This class manages the localhost server
 *
 * @see \Phoundation\Data\DataEntry\DataEntry
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Servers
 */
class Server extends DataEntry
{
    use DataEntryHostnamePort;
    use DataEntryDescription;
    use DataEntryCustomer;
    use DataEntryProvider;
    use DataEntrySshAccount;

    /**
     * Server class constructor
     *
     * @param DataEntryInterface|string|int|null $identifier
     * @param bool $init
     */
    public function __construct(DataEntryInterface|string|int|null $identifier = null, bool $init = true)
    {
        $this->table        = 'servers';
        $this->entry_name   = 'server';
        $this->unique_field = 'seo_hostname';

        parent::__construct($identifier, $init);
    }


    /**
     * Returns the cost for this object
     *
     * @return float|null
     */
    public function getCost(): ?float
    {
        return $this->getDataValue('float', 'cost');
    }


    /**
     * Sets the cost for this object
     *
     * @param float|null $cost
     * @return static
     */
    public function setCost(?float $cost): static
    {
        return $this->setDataValue('cost', $cost);
    }


    /**
     * Returns the bill_due_date for this object
     *
     * @return string|null
     */
    public function getBillDueDate(): ?string
    {
        return $this->getDataValue('string', 'bill_due_date');
    }


    /**
     * Sets the bill_due_date for this object
     *
     * @param string|null $bill_due_date
     * @return static
     */
    public function setBillDueDate(?string $bill_due_date): static
    {
        return $this->setDataValue('bill_due_date', $bill_due_date);
    }


    /**
     * Returns the interval for this object
     *
     * @return string|null
     */
    #[ExpectedValues([null, 'hourly', 'daily', 'weekly', 'monthly', 'bimonthly', 'quarterly', 'semiannual', 'annually'])]
    public function getInterval(): ?string
    {
        return $this->getDataValue('string', 'interval');
    }


    /**
     * Sets the interval for this object
     *
     * @param string|null $interval
     * @return static
     */
    public function setInterval(#[ExpectedValues([null, 'hourly', 'daily', 'weekly', 'monthly', 'bimonthly', 'quarterly', 'semiannual', 'annually'])] ?string $interval): static
    {
        return $this->setDataValue('interval', $interval);
    }


    /**
     * Returns the os_name for this object
     *
     * @return string|null
     */
    #[ExpectedValues([null, 'debian','ubuntu','redhat','gentoo','slackware','linux','windows','freebsd','macos','other'])]
    public function getOsName(): ?string
    {
        return $this->getDataValue('string', 'os_name');
    }


    /**
     * Sets the os_name for this object
     *
     * @param string|null $os_name
     * @return static
     */
    public function setOsName(#[ExpectedValues([null, 'debian','ubuntu','redhat','gentoo','slackware','linux','windows','freebsd','macos','other'])] ?string $os_name): static
    {
        return $this->setDataValue('os_name', $os_name);
    }


    /**
     * Returns the os_version for this object
     *
     * @return string|null
     */
    public function getOsVersion(): ?string
    {
        return $this->getDataValue('string', 'os_version');
    }


    /**
     * Sets the os_version for this object
     *
     * @param string|null $os_version
     * @return static
     */
    public function setOsVersion(?string $os_version): static
    {
        return $this->setDataValue('os_version', $os_version);
    }



    /**
     * Returns the web_services for this object
     *
     * @return bool
     */
    public function getWebServices(): bool
    {
        return $this->getDataValue('bool', 'web_services', false);
    }


    /**
     * Sets the web_services for this object
     *
     * @param bool|null $web_services
     * @return static
     */
    public function setWebServices(?bool $web_services): static
    {
        return $this->setDataValue('web_services', (bool) $web_services);
    }


    /**
     * Returns the mail_services for this object
     *
     * @return bool
     */
    public function getMailServices(): bool
    {
        return $this->getDataValue('bool', 'mail_services', false);
    }


    /**
     * Sets the mail_services for this object
     *
     * @param bool|null $mail_services
     * @return static
     */
    public function setMailServices(?bool $mail_services): static
    {
        return $this->setDataValue('mail_services', (bool) $mail_services);
    }


    /**
     * Returns the database_services for this object
     *
     * @return bool
     */
    public function getDatabaseServices(): bool
    {
        return $this->getDataValue('bool', 'database_services', false);
    }


    /**
     * Sets the database_services for this object
     *
     * @param bool|null $database_services
     * @return static
     */
    public function setDatabaseServices(?bool $database_services): static
    {
        return $this->setDataValue('database_services', (bool) $database_services);
    }


    /**
     * Returns the allow_sshd_modifications for this object
     *
     * @return bool
     */
    public function getAllowSshdModifications(): bool
    {
        return $this->getDataValue('bool', 'allow_sshd_modifications', false);
    }


    /**
     * Sets the allow_sshd_modifications for this object
     *
     * @param bool|null $allow_sshd_modifications
     * @return static
     */
    public function setAllowSshdModifications(?bool $allow_sshd_modifications): static
    {
        return $this->setDataValue('allow_sshd_modifications', (bool) $allow_sshd_modifications);
    }


    /**
     * Returns the username for the SSH account for this server
     *
     * @return string
     */
    public function getUsername(): string
    {
        return $this->getSshAccount()->getUsername();
    }


    /**
     * Returns the command line as it should be executed for this server
     *
     * @param string $command_line
     * @return string
     */
    public function getSshCommandLine(string $command_line): string
    {
        return Process::new('ssh')
            ->addArgument('-p')
            ->addArgument($this->getPort())
            ->addArgument($this->getUsername() . '@' . $this->getHostname())
            ->addArgument($command_line)
            ->getFullCommandLine();
    }


    /**
     * Sets the available data keys for this entry
     *
     * @param DefinitionsInterface $definitions
     */
    protected function initDefinitions(DefinitionsInterface $definitions): void
    {
        $definitions
            ->addDefinition(Definition::new('seo_hostname')
                ->setVirtual(true)
                ->setReadonly(true))
            ->addDefinition(Definition::new('ssh_account')
                ->setVirtual(true)
                ->setInputType(InputTypeExtended::name)
                ->setCliField('-a,--account ACCOUNT-NAME')
                ->setAutoComplete([
                    'word'   => function($word) { return SshAccounts::new()->filteredList($word); },
                    'noword' => function()      { return SshAccounts::new()->getSource(); },
                ])
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->xor('ssh_accounts_id')->setColumnFromQuery('ssh_accounts_id', 'SELECT `id` FROM `ssh_accounts` WHERE `name` = :name AND `status` IS NULL', [':name' => '$ssh_account']);
                }))
            ->addDefinition(Definition::new('category')
                ->setOptional(true)
                ->setVirtual(true)
                ->setCliField('--category CATEGORY-NAME')
                ->setAutoComplete([
                    'word'   => function($word) { return Categories::new()->filteredList($word); },
                    'noword' => function()      { return Categories::new()->getSource(); },
                ])
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->xor('categories_id')->setColumnFromQuery('categories_id', 'SELECT `id` FROM `categories` WHERE `name` = :name AND `status` IS NULL', [':name' => '$category']);
                }))
            ->addDefinition(Definition::new('provider')
                ->setOptional(true)
                ->setVirtual(true)
                ->setCliField('--provider PROVIDER-NAME')
                ->setAutoComplete([
                    'word'   => function($word) { return Providers::new()->filteredList($word); },
                    'noword' => function()      { return Providers::new()->getSource(); },
                ])
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->xor('providers_id')->setColumnFromQuery('providers_id', 'SELECT `id` FROM `business_providers` WHERE `name` = :name AND `status` IS NULL', [':name' => '$provider']);
                }))
            ->addDefinition(Definition::new('customer')
                ->setOptional(true)
                ->setVirtual(true)
                ->setCliField('--customer CUSTOMER-NAME')
                ->setAutoComplete([
                    'word'   => function($word) { return Customers::new()->filteredList($word); },
                    'noword' => function()      { return Customers::new()->getSource(); },
                ])
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->xor('customers_id')->setColumnFromQuery('customers_id', 'SELECT `id` FROM `business_customers` WHERE `name` = :name AND `status` IS NULL', [':name' => '$customer']);
                }))
            ->addDefinition(Definition::new('country')
                ->setOptional(true)
                ->setVirtual(true)
                ->setInputType(InputType::text)
                ->setMaxlength(200)
                ->setCliField('--country COUNTRY-NAME')
                ->setAutoComplete([
                    'word'   => function($word) { return Countries::new()->filteredList($word); },
                    'noword' => function()      { return Countries::new()->getSource(); },
                ])
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->xor('countries_id')->setColumnFromQuery('countries_id', 'SELECT `id` FROM `geo_countries` WHERE `name` = :name AND `status` IS NULL', [':name' => '$country']);
                }))
            ->addDefinition(Definition::new('state')
                ->setOptional(true)
                ->setVirtual(true)
                ->setInputType(InputType::text)
                ->setMaxlength(200)
                ->setCliField('--state STATE-NAME')
                ->setAutoComplete([
                    'word'   => function($word) { return States::new()->filteredList($word); },
                    'noword' => function()      { return States::new()->getSource(); },
                ])
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->xor('states_id')->setColumnFromQuery('states_id', 'SELECT `id` FROM `geo_states` WHERE `name` = :name AND `status` IS NULL', [':name' => '$state']);
                }))
            ->addDefinition(Definition::new('city')
                ->setOptional(true)
                ->setVirtual(true)
                ->setInputType(InputType::text)
                ->setMaxlength(200)
                ->setCliField('--city STATE-NAME')
                ->setAutoComplete([
                    'word'   => function($word) { return Cities::new()->filteredList($word); },
                    'noword' => function()      { return Cities::new()->getSource(); },
                ])
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->xor('cities_id')->setColumnFromQuery('cities_id', 'SELECT `id` FROM `geo_cities` WHERE `name` = :name AND `status` IS NULL', [':name' => '$city']);
                }))
            ->addDefinition(Definition::new('hostname')
                ->setInputType(InputType::text)
                ->setMaxlength(128)
                ->setSize(4)
                ->setLabel(tr('Hostname'))
                ->setCliField('-h,--hostname HOSTNAME')
                ->setHelpGroup(tr('Identification and network'))
                ->setHelpText(tr('The unique hostname for this server'))
                ->setAutoComplete(true))
            ->addDefinition(Definition::new('account')
                ->setVirtual(true)
                ->setInputType(InputTypeExtended::name)
                ->setLabel(tr('account'))
                ->setCliField('--accounts-id DATABASE-ID')
                ->setHelpGroup(tr('Identification and network'))
                ->setHelpText(tr('The unique hostname for this server'))
                ->setAutoComplete([
                    'word'   => function($word) { return SshAccounts::new()->filteredList($word); },
                    'noword' => function()      { return SshAccounts::new()->getSource(); },
                ])
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->xor('ssh_accounts_id')->setColumnFromQuery('ssh_accounts_id', 'SELECT `id` FROM `ssh_accounts` WHERE `name` = :name AND `status` IS NULL', [':name' => '$ssh_account']);
                }))
            ->addDefinition(Definition::new('ssh_accounts_id')
                ->setInputType(InputTypeExtended::dbid)
                ->setSize(4)
                ->setLabel(tr('Account'))
                ->setHelpText(tr('The unique hostname for this server'))
                ->setAutoComplete([
                    'word'   => function($word) { return SshAccounts::new()->filteredList($word); },
                    'noword' => function()      { return SshAccounts::new()->getSource(); },
                ])
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->isColumnFromQuery('ssh_accounts_id', 'SELECT `id` FROM `ssh_accounts` WHERE `name` = :name AND `status` IS NULL', [':name' => '$ssh_account']);
                }))
            ->addDefinition(Definition::new('port')
                ->setOptional(true)
                ->setInputType(InputTypeExtended::integer)
                ->setMin(1)
                ->setMax(65535)
                ->setSize(2)
                ->setLabel(tr('Port'))
                ->setCliField('-p,--port PORT (1 - 65535)')
                ->setHelpGroup(tr('Identification and network'))
                ->setHelpText(tr('The port where one can connect to the servers SSH service')))
            ->addDefinition(Definition::new('code')
                ->setOptional(true)
                ->setInputType(InputType::text)
                ->setSize(2)
                ->setMaxlength(16)
                ->setLabel(tr('Code'))
                ->setCliField('-c,--code CODE')
                ->setHelpGroup(tr('Identification and network'))
                ->setHelpText(tr('A unique identifying code for this server'))
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->isAlphaNumeric();
                }))
            ->addDefinition(Definition::new('code')
                ->setOptional(true)
                ->setInputType(InputTypeExtended::float)
                ->setMin(0)
                ->setStep('any')
                ->setSize(4)
                ->setLabel(tr('Cost'))
                ->setCliField('--cost CURRENCY')
                ->setHelpGroup(tr('Payment'))
                ->setHelpText(tr('The cost per interval for this server')))
            ->addDefinition(Definition::new('bill_due_date')
                ->setOptional(true)
                ->setInputType(InputType::date)
                ->setMin(0)
                ->setStep('any')
                ->setSize(4)
                ->setLabel(tr('Bill due date'))
                ->setCliField('-b,--bill-due-date DATE')
                ->setHelpGroup(tr('Payment'))
                ->setHelpText(tr('The next date when payment for this server is due')))
            ->addDefinition(Definition::new('interval')
                ->setOptional(true)
                ->setInputType(InputType::date)
                ->setSize(4)
                ->setLabel(tr('Payment interval'))
                ->setCliField('-i,--interval POSITIVE-INTEGER')
                ->setSource([
                    'hourly'     => tr('Hourly'),
                    'daily'      => tr('Daily'),
                    'weekly'     => tr('Weekly'),
                    'monthly'    => tr('Monthly'),
                    'bimonthly'  => tr('Bimonthly'),
                    'quarterly'  => tr('Quarterly'),
                    'semiannual' => tr('Semiannual'),
                    'annually'   => tr('Annually'),
                ])
                ->setHelpGroup(tr('Payment'))
                ->setHelpText(tr('The interval for when this server must be paid')))
            ->addDefinition(Definition::new('categories_id')
                ->setOptional(true)
                ->setCliField('--categories-id CATEGORIES-ID')
                ->setInputType(InputTypeExtended::dbid)
                ->setHelpText(tr('The category for this server'))
                ->setContent(function (DefinitionInterface $definition, string $key, string $field_name, array $source) {
                    return Categories::new()->getHtmlSelect($key)
                        ->setName($field_name)
                        ->setSelected(isset_get($source['categories_id']))
                        ->render();
                })
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->xor('category')->isColumnFromQuery('SELECT `id` FROM `categories` WHERE `id` = :id AND `status` IS NULL', [':name' => '$categories_id']);
                }))
            ->addDefinition(Definition::new('providers_id')
                ->setOptional(true)
                ->setCliField('--providers-id PROVIDERS-ID')
                ->setInputType(InputTypeExtended::dbid)
                ->setHelpText(tr('The service provider where this server is hosted'))
                ->setContent(function (DefinitionInterface $definition, string $key, string $field_name, array $source) {
                    return Categories::new()->getHtmlSelect($key)
                        ->setName($field_name)
                        ->setSelected(isset_get($source['providers_id']))
                        ->render();
                })
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->xor('provider')->isColumnFromQuery('SELECT `id` FROM `business_providers` WHERE `id` = :id AND `status` IS NULL', [':name' => '$providers_id']);
                }))
            ->addDefinition(Definition::new('customers_id')
                ->setOptional(true)
                ->setCliField('--customers-id CUSTOMERS-ID')
                ->setInputType(InputTypeExtended::dbid)
                ->setHelpText(tr('The client using this server'))
                ->setContent(function (DefinitionInterface $definition, string $key, string $field_name, array $source) {
                    return Categories::new()->getHtmlSelect($key)
                        ->setName($field_name)
                        ->setSelected(isset_get($source['customers_id']))
                        ->render();
                })
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->xor('customer')->isColumnFromQuery('SELECT `id` FROM `business_customers` WHERE `id` = :id AND `status` IS NULL', [':name' => '$customers_id']);
                }))
            ->addDefinition(Definition::new('countries_id')
                ->setOptional(true)
                ->setCliField('--countries-id COUNTRIES-ID')
                ->setInputType(InputTypeExtended::dbid)
                ->setHelpGroup(tr('Location'))
                ->setHelpText(tr('The country where this server is hosted'))
                ->setContent(function (DefinitionInterface $definition, string $key, string $field_name, array $source) {
                    return Categories::new()->getHtmlSelect($key)
                        ->setName($field_name)
                        ->setSelected(isset_get($source['countries_id']))
                        ->render();
                })
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->xor('country')->isColumnFromQuery('SELECT `id` FROM `geo_countries` WHERE `id` = :id AND `status` IS NULL', [':name' => '$countries_id']);
                }))
            ->addDefinition(Definition::new('states_id')
                ->setOptional(true)
                ->setCliField('--states-id STATES-ID')
                ->setInputType(InputTypeExtended::dbid)
                ->setHelpGroup(tr('Location'))
                ->setHelpText(tr('The state where this server is hosted'))
                ->setContent(function (DefinitionInterface $definition, string $key, string $field_name, array $source) {
                    return Categories::new()->getHtmlSelect($key)
                        ->setName($field_name)
                        ->setSelected(isset_get($source['states_id']))
                        ->render();
                })
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->xor('state')->isColumnFromQuery('SELECT `id` FROM `geo_states` WHERE `id` = :id AND `status` IS NULL', [':name' => '$states_id']);
                }))
            ->addDefinition(Definition::new('cities_id')
                ->setOptional(true)
                ->setCliField('--cities-id CITIES-ID')
                ->setInputType(InputTypeExtended::dbid)
                ->setContent(function (DefinitionInterface $definition, string $key, string $field_name, array $source) {
                    return Categories::new()->getHtmlSelect($key)
                        ->setName($field_name)
                        ->setSelected(isset_get($source['cities_id']))
                        ->render();
                })
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->xor('city')->isColumnFromQuery('SELECT `id` FROM `geo_cities` WHERE `id` = :id AND `status` IS NULL', [':name' => '$cities_id']);
                }))
            ->addDefinition(Definition::new('os_name')
                ->setOptional(true)
                ->setInputType(InputType::text)
                ->setSize(9)
                ->setLabel(tr('Operating system'))
                ->setCliField('-o,--os-name OPERATING-SYSTEM-NAME')
                ->setAutoComplete(true)
                ->setSource([
                    'debian'    => tr('Debian'),
                    'ubuntu'    => tr('Ubuntu'),
                    'redhat'    => tr('Redhat'),
                    'gentoo'    => tr('Gentoo'),
                    'slackware' => tr('Slackware'),
                    'linux'     => tr('Linux'),
                    'windows'   => tr('Windows'),
                    'freebsd'   => tr('FreeBSD'),
                    'macos'     => tr('Mac OS'),
                    'other'     => tr('Other')
                ])
                ->setHelpText(tr('The name of the operating system installed on this server')))
            ->addDefinition(Definition::new('os_version')
                ->setOptional(true)
                ->setInputType(InputType::text)
                ->setSize(9)
                ->setSize(16)
                ->setLabel(tr('Operating system version'))
                ->setCliField('-v,--os-version VERSION')
                ->setHelpText(tr('The current version of the installed operating system')))
            ->addDefinition(Definition::new('web_services')
                ->setOptional(true)
                ->setInputType(InputType::checkbox)
                ->setSize(3)
                ->setLabel(tr('Web services'))
                ->setCliField('-w,--web-services')
                ->setHelpText(tr('Sets if this server manages web services')))
            ->addDefinition(Definition::new('mail_services')
                ->setOptional(true)
                ->setInputType(InputType::checkbox)
                ->setSize(3)
                ->setLabel(tr('Email services'))
                ->setCliField('-m,--mail-services')
                ->setHelpText(tr('Sets if this server manages mail services')))
            ->addDefinition(Definition::new('database_services')
                ->setOptional(true)
                ->setInputType(InputType::checkbox)
                ->setSize(3)
                ->setLabel(tr('Database services'))
                ->setCliField('-e,--database-services')
                ->setHelpText(tr('Sets if this server manages database services')))
            ->addDefinition(Definition::new('mail_services')
                ->setOptional(true)
                ->setInputType(InputType::checkbox)
                ->setSize(3)
                ->setLabel(tr('Allow SSHD modification'))
                ->setCliField('-s,--allow-sshd-modification')
                ->setHelpText(tr('Sets if this server allows automated modification of SSH configuration')))
            ->addDefinition(DefinitionFactory::getDescription()
                ->setHelpText(tr('A description for this server')));
    }
}