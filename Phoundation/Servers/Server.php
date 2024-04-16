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
use Phoundation\Data\DataEntry\Traits\TraitDataEntryCategory;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryCity;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryCode;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryCountry;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryCustomer;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryHostnamePort;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryNameDescription;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryProvider;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryState;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Geo\Cities\Cities;
use Phoundation\Geo\Countries\Countries;
use Phoundation\Geo\States\States;
use Phoundation\Os\Processes\Process;
use Phoundation\Servers\Exception\SshException;
use Phoundation\Servers\Interfaces\ServerInterface;
use Phoundation\Servers\Traits\TraitDataEntrySshAccount;
use Phoundation\Web\Html\Enums\EnumElement;
use Phoundation\Web\Html\Enums\EnumInputType;

/**
 * Server class
 *
 * This class manages a single server
 *
 * @see       DataEntry
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Servers
 */
class Server extends DataEntry implements ServerInterface
{
    use TraitDataEntryCountry;
    use TraitDataEntryState;
    use TraitDataEntryCity;
    use TraitDataEntryCategory;
    use TraitDataEntryCode;
    use TraitDataEntryHostnamePort;
    use TraitDataEntryNameDescription;
    use TraitDataEntryCustomer;
    use TraitDataEntryProvider;
    use TraitDataEntrySshAccount;

    /**
     * Server class constructor
     *
     * @param int|string|DataEntryInterface|null $identifier
     * @param string|null                        $column
     * @param bool|null                          $meta_enabled
     */
    public function __construct(int|string|DataEntryInterface|null $identifier = null, ?string $column = null, ?bool $meta_enabled = null)
    {
        $this->config_path = 'servers.';
        parent::__construct($identifier, $column, $meta_enabled);
    }


    /**
     * Returns the table name used by this object
     *
     * @return string
     */
    public static function getTable(): string
    {
        return 'servers';
    }


    /**
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getDataEntryName(): string
    {
        return 'server';
    }


    /**
     * Returns the field that is unique for this object
     *
     * @return string|null
     */
    public static function getUniqueColumn(): ?string
    {
        return 'hostname';
    }


    /**
     * Returns the cost for this object
     *
     * @return float|null
     */
    public function getCost(): ?float
    {
        return $this->getValueTypesafe('float', 'cost');
    }


    /**
     * Sets the cost for this object
     *
     * @param float|null $cost
     *
     * @return static
     */
    public function setCost(?float $cost): static
    {
        return $this->set('cost', $cost);
    }


    /**
     * Returns the bill_due_date for this object
     *
     * @return string|null
     */
    public function getBillDueDate(): ?string
    {
        return $this->getValueTypesafe('string', 'bill_due_date');
    }


    /**
     * Sets the bill_due_date for this object
     *
     * @param string|null $bill_due_date
     *
     * @return static
     */
    public function setBillDueDate(?string $bill_due_date): static
    {
        return $this->set('bill_due_date', $bill_due_date);
    }


    /**
     * Returns the interval for this object
     *
     * @return string|null
     */
    #[ExpectedValues([
        null,
        'hourly',
        'daily',
        'weekly',
        'monthly',
        'bimonthly',
        'quarterly',
        'semiannual',
        'annually',
    ])]
    public function getInterval(): ?string
    {
        return $this->getValueTypesafe('string', 'interval');
    }


    /**
     * Sets the interval for this object
     *
     * @param string|null $interval
     *
     * @return static
     */
    public function setInterval(#[ExpectedValues([
        null,
        'hourly',
        'daily',
        'weekly',
        'monthly',
        'bimonthly',
        'quarterly',
        'semiannual',
        'annually',
    ])] ?string $interval): static
    {
        return $this->set('interval', $interval);
    }


    /**
     * Returns the os_name for this object
     *
     * @return string|null
     */
    #[ExpectedValues([
        null,
        'debian',
        'ubuntu',
        'redhat',
        'gentoo',
        'slackware',
        'linux',
        'windows',
        'freebsd',
        'macos',
        'other',
    ])]
    public function getOsName(): ?string
    {
        return $this->getValueTypesafe('string', 'os_name');
    }


    /**
     * Sets the os_name for this object
     *
     * @param string|null $os_name
     *
     * @return static
     */
    public function setOsName(#[ExpectedValues([
        null,
        'debian',
        'ubuntu',
        'redhat',
        'gentoo',
        'slackware',
        'linux',
        'windows',
        'freebsd',
        'macos',
        'other',
    ])] ?string $os_name): static
    {
        return $this->set('os_name', $os_name);
    }


    /**
     * Returns the os_version for this object
     *
     * @return string|null
     */
    public function getOsVersion(): ?string
    {
        return $this->getValueTypesafe('string', 'os_version');
    }


    /**
     * Sets the os_version for this object
     *
     * @param string|null $os_version
     *
     * @return static
     */
    public function setOsVersion(?string $os_version): static
    {
        return $this->set('os_version', $os_version);
    }


    /**
     * Returns the web_services for this object
     *
     * @return bool
     */
    public function getWebServices(): bool
    {
        return $this->getValueTypesafe('bool', 'web_services', false);
    }


    /**
     * Sets the web_services for this object
     *
     * @param bool|null $web_services
     *
     * @return static
     */
    public function setWebServices(?bool $web_services): static
    {
        return $this->set('web_services', (bool) $web_services);
    }


    /**
     * Returns the mail_services for this object
     *
     * @return bool
     */
    public function getMailServices(): bool
    {
        return $this->getValueTypesafe('bool', 'mail_services', false);
    }


    /**
     * Sets the mail_services for this object
     *
     * @param bool|null $mail_services
     *
     * @return static
     */
    public function setMailServices(?bool $mail_services): static
    {
        return $this->set('mail_services', (bool) $mail_services);
    }


    /**
     * Returns the database_services for this object
     *
     * @return bool
     */
    public function getDatabaseServices(): bool
    {
        return $this->getValueTypesafe('bool', 'database_services', false);
    }


    /**
     * Sets the database_services for this object
     *
     * @param bool|null $database_services
     *
     * @return static
     */
    public function setDatabaseServices(?bool $database_services): static
    {
        return $this->set('database_services', (bool) $database_services);
    }


    /**
     * Returns the allow_sshd_modifications for this object
     *
     * @return bool
     */
    public function getAllowSshdModifications(): bool
    {
        return $this->getValueTypesafe('bool', 'allow_sshd_modifications', false);
    }


    /**
     * Sets the allow_sshd_modifications for this object
     *
     * @param bool|null $allow_sshd_modifications
     *
     * @return static
     */
    public function setAllowSshdModifications(?bool $allow_sshd_modifications): static
    {
        return $this->set('allow_sshd_modifications', (bool) $allow_sshd_modifications);
    }


    /**
     * Returns the command line as it should be executed for this server
     *
     * @param string $command_line
     *
     * @return string
     */
    public function getSshCommandLine(string $command_line): string
    {
        if (!$this->getHostname()) {
            throw new SshException(tr('Cannot generate SSH command line, server ":server" has no hostname', [
                ':server' => $this->getLogId(),
            ]));
        }
        if (empty($this->ssh_account)) {
            throw new SshException(tr('Cannot generate SSH command line, no account specified for hostname ":hostname"', [
                ':hostname' => $this->getHostname(),
            ]));
        }
        if (!$this->ssh_account->getFile()) {
            throw new SshException(tr('Cannot generate SSH command line, the SSH account ":account" has no private key specified', [
                ':account' => $this->ssh_account->getLogId(),
            ]));
        }
        $username = $this->getUsername();
        if ($username) {
            $username .= '@';
        }

        return Process::new('ssh')
                      ->addArguments($this->getPort() ? [
                          '-p',
                          $this->getPort(),
                      ] : null)
                      ->addArguments([
                          '-t',
                          '-i',
                          $this->getSshAccount()
                               ->getFile(),
                      ])
                      ->addArgument($username . $this->getHostname())
                      ->addArgument($command_line)
                      ->getBasicCommandLine();
    }


    /**
     * Returns the username for the SSH account for this server
     *
     * @return string|null
     */
    public function getUsername(): ?string
    {
        return $this->getSshAccount()
                    ->getUsername();
    }


    /**
     * Sets the available data keys for this entry
     *
     * @param DefinitionsInterface $definitions
     */
    protected function setDefinitions(DefinitionsInterface $definitions): void
    {
        $definitions->add(Definition::new($this, 'seo_hostname')
                                    ->setVirtual(true)
                                    ->setReadonly(true))
                    ->add(Definition::new($this, 'category')
                                    ->setOptional(true)
                                    ->setVirtual(true)
                                    ->setCliColumn('--category CATEGORY-NAME')
                                    ->setCliAutoComplete([
                                        'word'   => function ($word) {
                                            return Categories::new()
                                                             ->getMatchingKeys($word);
                                        },
                                        'noword' => function () {
                                            return Categories::new()
                                                             ->getSource();
                                        },
                                    ])
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        $validator->xorColumn('categories_id')
                                                  ->setColumnFromQuery('categories_id', 'SELECT `id` FROM `categories` WHERE `name` = :name AND `status` IS NULL', [':name' => '$category']);
                                    }))
                    ->add(Definition::new($this, 'provider')
                                    ->setOptional(true)
                                    ->setVirtual(true)
                                    ->setCliColumn('--provider PROVIDER-NAME')
                                    ->setCliAutoComplete([
                                        'word'   => function ($word) {
                                            return Providers::new()
                                                            ->getMatchingKeys($word);
                                        },
                                        'noword' => function () {
                                            return Providers::new()
                                                            ->getSource();
                                        },
                                    ])
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        $validator->xorColumn('providers_id')
                                                  ->setColumnFromQuery('providers_id', 'SELECT `id` FROM `business_providers` WHERE `name` = :name AND `status` IS NULL', [':name' => '$provider']);
                                    }))
                    ->add(Definition::new($this, 'customer')
                                    ->setOptional(true)
                                    ->setVirtual(true)
                                    ->setCliColumn('--customer CUSTOMER-NAME')
                                    ->setCliAutoComplete([
                                        'word'   => function ($word) {
                                            return Customers::new()
                                                            ->getMatchingKeys($word);
                                        },
                                        'noword' => function () {
                                            return Customers::new()
                                                            ->getSource();
                                        },
                                    ])
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        $validator->xorColumn('customers_id')
                                                  ->setColumnFromQuery('customers_id', 'SELECT `id` FROM `business_customers` WHERE `name` = :name AND `status` IS NULL', [':name' => '$customer']);
                                    }))
                    ->add(Definition::new($this, 'country')
                                    ->setOptional(true)
                                    ->setVirtual(true)
                                    ->setInputType(EnumInputType::text)
                                    ->setMaxlength(200)
                                    ->setCliColumn('--country COUNTRY-NAME')
                                    ->setCliAutoComplete([
                                        'word'   => function ($word) {
                                            return Countries::new()
                                                            ->getMatchingKeys($word);
                                        },
                                        'noword' => function () {
                                            return Countries::new()
                                                            ->getSource();
                                        },
                                    ])
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        $validator->xorColumn('countries_id')
                                                  ->setColumnFromQuery('countries_id', 'SELECT `id` FROM `geo_countries` WHERE `name` = :name AND `status` IS NULL', [':name' => '$country']);
                                    }))
                    ->add(Definition::new($this, 'state')
                                    ->setOptional(true)
                                    ->setVirtual(true)
                                    ->setInputType(EnumInputType::text)
                                    ->setMaxlength(200)
                                    ->setCliColumn('--state STATE-NAME')
                                    ->setCliAutoComplete([
                                        'word'   => function ($word) {
                                            return States::new()
                                                         ->getMatchingKeys($word);
                                        },
                                        'noword' => function () {
                                            return States::new()
                                                         ->getSource();
                                        },
                                    ])
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        $validator->xorColumn('states_id')
                                                  ->setColumnFromQuery('states_id', 'SELECT `id` FROM `geo_states` WHERE `name` = :name AND `status` IS NULL', [':name' => '$state']);
                                    }))
                    ->add(Definition::new($this, 'city')
                                    ->setOptional(true)
                                    ->setVirtual(true)
                                    ->setInputType(EnumInputType::text)
                                    ->setMaxlength(200)
                                    ->setCliColumn('--city STATE-NAME')
                                    ->setCliAutoComplete([
                                        'word'   => function ($word) {
                                            return Cities::new()
                                                         ->getMatchingKeys($word);
                                        },
                                        'noword' => function () {
                                            return Cities::new()
                                                         ->getSource();
                                        },
                                    ])
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        $validator->xorColumn('cities_id')
                                                  ->setColumnFromQuery('cities_id', 'SELECT `id` FROM `geo_cities` WHERE `name` = :name AND `status` IS NULL', [':name' => '$city']);
                                    }))
                    ->add(DefinitionFactory::getName($this)
                                           ->setOptional(false)
                                           ->setInputType(EnumInputType::name)
                                           ->setSize(12)
                                           ->setMaxlength(64)
                                           ->setHelpText(tr('The name for this role'))
                                           ->addValidationFunction(function (ValidatorInterface $validator) {
                                               $validator->isUnique(tr('value ":name" already exists', [':name' => $validator->getSelectedValue()]));
                                           }))
                    ->add(DefinitionFactory::getSeoName($this))
                    ->add(Definition::new($this, 'hostname')
                                    ->setInputType(EnumInputType::text)
                                    ->setMaxlength(128)
                                    ->setSize(4)
                                    ->setLabel(tr('Hostname'))
                                    ->setCliColumn('-h,--hostname HOSTNAME')
                                    ->setHelpGroup(tr('Identification and network'))
                                    ->setHelpText(tr('The unique hostname for this server'))
                                    ->setCliAutoComplete(true))
                    ->add(Definition::new($this, 'ssh_accounts_name')
                                    ->setVirtual(true)
                                    ->setRender(false)
                                    ->setInputType(EnumInputType::name)
                                    ->setLabel(tr('Account'))
                                    ->setCliColumn('-a,--account ACCOUNT-NAME')
                                    ->setHelpGroup(tr('Identification and network'))
                                    ->setHelpText(tr('The unique hostname for this server'))
                                    ->setCliAutoComplete([
                                        'word'   => function ($word) {
                                            return SshAccounts::new()
                                                              ->getMatchingKeys($word);
                                        },
                                        'noword' => function () {
                                            return SshAccounts::new()
                                                              ->getSource();
                                        },
                                    ])
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        showdie('fuck!');
                                        $validator->xorColumn('ssh_accounts_id')
                                                  ->setColumnFromQuery('ssh_accounts_id', 'SELECT `id` FROM `ssh_accounts` WHERE `name` = :name AND `status` IS NULL', [':name' => '$ssh_account']);
                                    }))
                    ->add(Definition::new($this, 'ssh_accounts_id')
                                    ->setInputType(EnumInputType::dbid)
                                    ->setSize(4)
                                    ->setLabel(tr('Account'))
                                    ->setHelpText(tr('The unique hostname for this server'))
                                    ->setCliAutoComplete([
                                        'word'   => function ($word) {
                                            return SshAccounts::new()
                                                              ->getMatchingKeys($word);
                                        },
                                        'noword' => function () {
                                            return SshAccounts::new()
                                                              ->getSource();
                                        },
                                    ])
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        $validator->isQueryResult('SELECT `id` FROM `ssh_accounts` WHERE `id` = :id AND `status` IS NULL', [':id' => '$ssh_accounts_id']);
                                    }))
                    ->add(Definition::new($this, 'port')
                                    ->setOptional(true, 22)
                                    ->setInputType(EnumInputType::integer)
                                    ->setMin(1)
                                    ->setMax(65535)
                                    ->setSize(2)
                                    ->setLabel(tr('Port'))
                                    ->setCliColumn('-p,--port PORT (1 - 65535)')
                                    ->setHelpGroup(tr('Identification and network'))
                                    ->setHelpText(tr('The port where one can connect to the servers SSH service')))
                    ->add(Definition::new($this, 'code')
                                    ->setOptional(true)
                                    ->setInputType(EnumInputType::text)
                                    ->setSize(2)
                                    ->setMaxlength(16)
                                    ->setLabel(tr('Code'))
                                    ->setCliColumn('-c,--code CODE')
                                    ->setHelpGroup(tr('Identification and network'))
                                    ->setHelpText(tr('A unique identifying code for this server'))
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        $validator->isAlphaNumeric();
                                    }))
                    ->add(Definition::new($this, 'cost')
                                    ->setOptional(true)
                                    ->setInputType(EnumInputType::float)
                                    ->setMin(0)
                                    ->setStep('any')
                                    ->setSize(4)
                                    ->setLabel(tr('Cost'))
                                    ->setCliColumn('--cost CURRENCY')
                                    ->setHelpGroup(tr('Payment'))
                                    ->setHelpText(tr('The cost per interval for this server')))
                    ->add(Definition::new($this, 'bill_due_date')
                                    ->setOptional(true)
                                    ->setInputType(EnumInputType::date)
                                    ->setMin(0)
                                    ->setStep('any')
                                    ->setSize(4)
                                    ->setLabel(tr('Bill due date'))
                                    ->setCliColumn('-b,--bill-due-date DATE')
                                    ->setHelpGroup(tr('Payment'))
                                    ->setHelpText(tr('The next date when payment for this server is due')))
                    ->add(Definition::new($this, 'interval')
                                    ->setOptional(true)
                                    ->setInputType(EnumInputType::date)
                                    ->setSize(4)
                                    ->setLabel(tr('Payment interval'))
                                    ->setCliColumn('-i,--interval POSITIVE-INTEGER')
                                    ->setDataSource([
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
                    ->add(Definition::new($this, 'categories_id')
                                    ->setOptional(true)
                                    ->setCliColumn('--categories-id CATEGORIES-ID')
                                    ->setInputType(EnumInputType::dbid)
                                    ->setHelpText(tr('The category for this server'))
                                    ->setElement(EnumElement::select)
                                    ->setContent(function (DefinitionInterface $definition, string $key, string $field_name, array $source) {
                                        return Categories::new()
                                                         ->getHtmlSelect()
                                                         ->setName($field_name)
                                                         ->setSelected(isset_get($source['categories_id']));
                                    })
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        $validator->xorColumn('category')
                                                  ->isColumnFromQuery('SELECT `id` FROM `categories` WHERE `id` = :id AND `status` IS NULL', [':name' => '$categories_id']);
                                    }))
                    ->add(Definition::new($this, 'providers_id')
                                    ->setOptional(true)
                                    ->setCliColumn('--providers-id PROVIDERS-ID')
                                    ->setHelpText(tr('The service provider where this server is hosted'))
                                    ->setInputType(EnumInputType::dbid)
                                    ->setElement(EnumElement::select)
                                    ->setContent(function (DefinitionInterface $definition, string $key, string $field_name, array $source) {
                                        return Categories::new()
                                                         ->getHtmlSelect()
                                                         ->setName($field_name)
                                                         ->setSelected(isset_get($source['providers_id']));
                                    })
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        $validator->xorColumn('provider')
                                                  ->isColumnFromQuery('SELECT `id` FROM `business_providers` WHERE `id` = :id AND `status` IS NULL', [':name' => '$providers_id']);
                                    }))
                    ->add(Definition::new($this, 'customers_id')
                                    ->setOptional(true)
                                    ->setCliColumn('--customers-id CUSTOMERS-ID')
                                    ->setInputType(EnumInputType::dbid)
                                    ->setHelpText(tr('The client using this server'))
                                    ->setElement(EnumElement::select)
                                    ->setContent(function (DefinitionInterface $definition, string $key, string $field_name, array $source) {
                                        return Categories::new()
                                                         ->getHtmlSelect()
                                                         ->setName($field_name)
                                                         ->setSelected(isset_get($source['customers_id']));
                                    })
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        $validator->xorColumn('customer')
                                                  ->isColumnFromQuery('SELECT `id` FROM `business_customers` WHERE `id` = :id AND `status` IS NULL', [':name' => '$customers_id']);
                                    }))
                    ->add(Definition::new($this, 'countries_id')
                                    ->setOptional(true)
                                    ->setCliColumn('--countries-id COUNTRIES-ID')
                                    ->setInputType(EnumInputType::dbid)
                                    ->setElement(EnumElement::select)
                                    ->setHelpGroup(tr('Location'))
                                    ->setHelpText(tr('The country where this server is hosted'))
                                    ->setContent(function (DefinitionInterface $definition, string $key, string $field_name, array $source) {
                                        return Categories::new()
                                                         ->getHtmlSelect()
                                                         ->setName($field_name)
                                                         ->setSelected(isset_get($source['countries_id']));
                                    })
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        $validator->xorColumn('country')
                                                  ->isColumnFromQuery('SELECT `id` FROM `geo_countries` WHERE `id` = :id AND `status` IS NULL', [':name' => '$countries_id']);
                                    }))
                    ->add(Definition::new($this, 'states_id')
                                    ->setOptional(true)
                                    ->setCliColumn('--states-id STATES-ID')
                                    ->setInputType(EnumInputType::dbid)
                                    ->setElement(EnumElement::select)
                                    ->setHelpGroup(tr('Location'))
                                    ->setHelpText(tr('The state where this server is hosted'))
                                    ->setContent(function (DefinitionInterface $definition, string $key, string $field_name, array $source) {
                                        return Categories::new()
                                                         ->getHtmlSelect()
                                                         ->setName($field_name)
                                                         ->setSelected(isset_get($source['states_id']));
                                    })
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        $validator->xorColumn('state')
                                                  ->isColumnFromQuery('SELECT `id` FROM `geo_states` WHERE `id` = :id AND `status` IS NULL', [':name' => '$states_id']);
                                    }))
                    ->add(Definition::new($this, 'cities_id')
                                    ->setOptional(true)
                                    ->setCliColumn('--cities-id CITIES-ID')
                                    ->setInputType(EnumInputType::dbid)
                                    ->setElement(EnumElement::select)
                                    ->setContent(function (DefinitionInterface $definition, string $key, string $field_name, array $source) {
                                        return Categories::new()
                                                         ->getHtmlSelect()
                                                         ->setName($field_name)
                                                         ->setSelected(isset_get($source['cities_id']));
                                    })
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        $validator->xorColumn('city')
                                                  ->isColumnFromQuery('SELECT `id` FROM `geo_cities` WHERE `id` = :id AND `status` IS NULL', [':name' => '$cities_id']);
                                    }))
                    ->add(Definition::new($this, 'os_name')
                                    ->setOptional(true)
                                    ->setInputType(EnumInputType::text)
                                    ->setSize(9)
                                    ->setLabel(tr('Operating system'))
                                    ->setCliColumn('-o,--os-name OPERATING-SYSTEM-NAME')
                                    ->setCliAutoComplete(true)
                                    ->setDataSource([
                                        'debian'    => tr('Debian'),
                                        'ubuntu'    => tr('Ubuntu'),
                                        'redhat'    => tr('Redhat'),
                                        'gentoo'    => tr('Gentoo'),
                                        'slackware' => tr('Slackware'),
                                        'linux'     => tr('Linux'),
                                        'windows'   => tr('Windows'),
                                        'freebsd'   => tr('FreeBSD'),
                                        'macos'     => tr('Mac OS'),
                                        'other'     => tr('Other'),
                                    ])
                                    ->setHelpText(tr('The name of the operating system installed on this server')))
                    ->add(Definition::new($this, 'os_version')
                                    ->setOptional(true)
                                    ->setInputType(EnumInputType::text)
                                    ->setMinlength(9)
                                    ->setMaxlength(16)
                                    ->setLabel(tr('Operating system version'))
                                    ->setCliColumn('-v,--os-version VERSION')
                                    ->setHelpText(tr('The current version of the installed operating system')))
                    ->add(Definition::new($this, 'web_services')
                                    ->setOptional(true)
                                    ->setInputType(EnumInputType::checkbox)
                                    ->setSize(3)
                                    ->setLabel(tr('Web services'))
                                    ->setCliColumn('-w,--web-services')
                                    ->setHelpText(tr('Sets if this server manages web services')))
                    ->add(Definition::new($this, 'mail_services')
                                    ->setOptional(true)
                                    ->setInputType(EnumInputType::checkbox)
                                    ->setSize(3)
                                    ->setLabel(tr('Email services'))
                                    ->setCliColumn('-m,--mail-services')
                                    ->setHelpText(tr('Sets if this server manages mail services')))
                    ->add(Definition::new($this, 'database_services')
                                    ->setOptional(true)
                                    ->setInputType(EnumInputType::checkbox)
                                    ->setSize(3)
                                    ->setLabel(tr('Database services'))
                                    ->setCliColumn('-e,--database-services')
                                    ->setHelpText(tr('Sets if this server manages database services')))
                    ->add(Definition::new($this, 'allow_sshd_modification')
                                    ->setOptional(true)
                                    ->setInputType(EnumInputType::checkbox)
                                    ->setSize(3)
                                    ->setLabel(tr('Allow SSHD modification'))
                                    ->setCliColumn('-s,--allow-sshd-modification')
                                    ->setHelpText(tr('Sets if this server allows automated modification of SSH configuration')))
                    ->add(DefinitionFactory::getDescription($this)
                                           ->setHelpText(tr('A description for this server')));
    }
}
