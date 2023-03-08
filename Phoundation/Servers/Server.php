<?php

namespace Phoundation\Servers;

use JetBrains\PhpStorm\ExpectedValues;
use Phoundation\Business\Customers\Customers;
use Phoundation\Business\Providers\Providers;
use Phoundation\Data\Categories\Categories;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Traits\DataEntryCustomer;
use Phoundation\Data\DataEntry\Traits\DataEntryDescription;
use Phoundation\Data\DataEntry\Traits\DataEntryHostnamePort;
use Phoundation\Data\DataEntry\Traits\DataEntryProvider;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Geo\Cities\Cities;
use Phoundation\Geo\Countries\Countries;
use Phoundation\Geo\Countries\Country;
use Phoundation\Geo\States\State;
use Phoundation\Geo\States\States;
use Phoundation\Processes\Process;
use Phoundation\Servers\Traits\DataEntrySshAccount;


/**
 * Server class
 *
 * This class manages the localhost server
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
     * @param int|string|null $identifier
     */
    public function __construct(int|string|null $identifier = null)
    {
        static::$entry_name  = 'server';
        $this->table         = 'servers';
        $this->unique_field = 'seo_hostname';

        parent::__construct($identifier);
    }


    /**
     * Returns the cost for this object
     *
     * @return float|null
     */
    public function getCost(): ?float
    {
        return $this->getDataValue('cost');
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
        return $this->getDataValue('bill_due_date');
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
        return $this->getDataValue('interval');
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
        return $this->getDataValue('os_name');
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
        return $this->getDataValue('os_version');
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
        return (bool) $this->getDataValue('web_services');
    }



    /**
     * Sets the web_services for this object
     *
     * @param bool $web_services
     * @return static
     */
    public function setWebServices(bool $web_services): static
    {
        return $this->setDataValue('web_services', $web_services);
    }



    /**
     * Returns the mail_services for this object
     *
     * @return bool
     */
    public function getMailServices(): bool
    {
        return (bool) $this->getDataValue('mail_services');
    }



    /**
     * Sets the mail_services for this object
     *
     * @param bool $mail_services
     * @return static
     */
    public function setMailServices(bool $mail_services): static
    {
        return $this->setDataValue('mail_services', $mail_services);
    }



    /**
     * Returns the database_services for this object
     *
     * @return bool
     */
    public function getDatabaseServices(): bool
    {
        return (bool) $this->getDataValue('database_services');
    }



    /**
     * Sets the database_services for this object
     *
     * @param bool $database_services
     * @return static
     */
    public function setDatabaseServices(bool $database_services): static
    {
        return $this->setDataValue('database_services', $database_services);
    }



    /**
     * Returns the allow_sshd_modifications for this object
     *
     * @return bool
     */
    public function getAllowSshdModifications(): bool
    {
        return (bool) $this->getDataValue('allow_sshd_modifications');
    }



    /**
     * Sets the allow_sshd_modifications for this object
     *
     * @param bool $allow_sshd_modifications
     * @return static
     */
    public function setAllowSshdModifications(bool $allow_sshd_modifications): static
    {
        return $this->setDataValue('allow_sshd_modifications', $allow_sshd_modifications);
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
     * Validates the provider record with the specified validator object
     *
     * @param ArgvValidator|PostValidator|GetValidator $validator
     * @param bool $no_arguments_left
     * @param bool $modify
     * @return array
     */
    protected function validate(ArgvValidator|PostValidator|GetValidator $validator, bool $no_arguments_left = false, bool $modify = false): array
    {
        $data = $validator
            ->select($this->getAlternateValidationField('hostname'), true)->isOptional()->hasMaxCharacters(128)->isDomain()
            ->select($this->getAlternateValidationField('code'), true)->isOptional()->hasMaxCharacters(16)->isAlphaNumeric()
            ->select($this->getAlternateValidationField('os_name'), true)->isOptional()->hasMaxCharacters(12)->inArray('debian','ubuntu','redhat','gentoo','slackware','linux','windows','freebsd','macos','other')
            ->select($this->getAlternateValidationField('os_version'), true)->isOptional()->hasMaxCharacters(16)->isPrintable()
            ->select($this->getAlternateValidationField('interval'), true)->isOptional()->hasMaxCharacters(12)->inArray(['hourly','daily','weekly','monthly','bimonthly','quarterly','semiannual','annually','none'])
            ->select($this->getAlternateValidationField('bill_due_date'), true)->isOptional()->isDate()
            ->select($this->getAlternateValidationField('port'), true)->isOptional()->isBetween(1, 65_535)
            ->select($this->getAlternateValidationField('cost'), true)->isOptional()->isCurrency()
            ->select($this->getAlternateValidationField('account'), true)->xor('accounts_id')->isName()->isQueryColumn   ('SELECT `name` FROM `ssh_accounts`  WHERE `name` = :name AND `status` IS NULL', [':name' => '$account'])
            ->select($this->getAlternateValidationField('accounts_id'), true)->xor('account')->isId()->isQueryColumn     ('SELECT `id`   FROM `ssh_accounts`  WHERE `id`   = :id   AND `status` IS NULL', [':id'   => '$accounts_id'])
            ->select($this->getAlternateValidationField('category'), true)->xor('categories_id')->isName()->isQueryColumn('SELECT `name` FROM `categories`    WHERE `name` = :name AND `status` IS NULL', [':name' => '$category'])
            ->select($this->getAlternateValidationField('categories_id'), true)->xor('category')->isId()->isQueryColumn  ('SELECT `id`   FROM `categories`    WHERE `id`   = :id   AND `status` IS NULL', [':id'   => '$categories_id'])
            ->select($this->getAlternateValidationField('provider'), true)->xor('providers_id')->isName()->isQueryColumn ('SELECT `name` FROM `providers`     WHERE `name` = :name AND `status` IS NULL', [':name' => '$provider'])
            ->select($this->getAlternateValidationField('providers_id'), true)->xor('provider')->isId()->isQueryColumn   ('SELECT `id`   FROM `providers`     WHERE `id`   = :id   AND `status` IS NULL', [':id'   => '$providers_id'])
            ->select($this->getAlternateValidationField('customer'), true)->xor('customers_id')->isName()->isQueryColumn ('SELECT `name` FROM `customers`     WHERE `name` = :name AND `status` IS NULL', [':name' => '$customer'])
            ->select($this->getAlternateValidationField('customers_id'), true)->xor('customer')->isId()->isQueryColumn   ('SELECT `id`   FROM `customers`     WHERE `id`   = :id   AND `status` IS NULL', [':id'   => '$customers_id'])
            ->select($this->getAlternateValidationField('country'), true)->xor('countries_id')->isName()->isQueryColumn  ('SELECT `name` FROM `geo_countries` WHERE `name` = :name AND `status` IS NULL', [':name' => '$country'])
            ->select($this->getAlternateValidationField('countries_id'), true)->xor('country')->isId()->isQueryColumn    ('SELECT `id`   FROM `geo_countries` WHERE `id`   = :id   AND `status` IS NULL', [':id'   => '$countries_id'])
            ->select($this->getAlternateValidationField('state'), true)->xor('states_id')->isName()->isQueryColumn       ('SELECT `name` FROM `geo_states`    WHERE `name` = :name AND `countries_id` = :countries_id AND `status` IS NULL', [':name' => '$state'    , ':countries_id' => '$countries_id'])
            ->select($this->getAlternateValidationField('states_id'), true)->xor('state')->isId()->isQueryColumn         ('SELECT `id`   FROM `geo_states`    WHERE `id`   = :id   AND `countries_id` = :countries_id AND `status` IS NULL', [':id'   => '$states_id', ':countries_id' => '$countries_id'])
            ->select($this->getAlternateValidationField('city'), true)->xor('cities_id')->isName()->isQueryColumn        ('SELECT `name` FROM `geo_cities`    WHERE `name` = :name AND `states_id`    = :states_id    AND `status` IS NULL', [':name' => '$city'     , ':states_id'    => '$states_id'])
            ->select($this->getAlternateValidationField('cities_id'), true)->xor('city')->isId()->isQueryColumn          ('SELECT `id`   FROM `geo_cities`    WHERE `id`   = :id   AND `states_id`    = :states_id    AND `status` IS NULL', [':id'   => '$cities_id', ':states_id'    => '$states_id'])
            ->select($this->getAlternateValidationField('description'), true)->isOptional()->hasMaxCharacters(65_530)->isPrintable()
            ->select($this->getAlternateValidationField('allow_sshd_modification'))->isOptional()->isBoolean()
            ->select($this->getAlternateValidationField('database_services'))->isOptional()->isBoolean()
            ->select($this->getAlternateValidationField('mail_services'))->isOptional()->isBoolean()
            ->select($this->getAlternateValidationField('web_services'))->isOptional()->isBoolean()
            ->noArgumentsLeft($no_arguments_left)
            ->validate();

        // Ensure the hostname doesn't exist yet as it is a unique identifier
        if ($data['hostname']) {
            Server::notExists($data['hostname'], $this->getId(), true);
        }

        return $data;
    }



    /**
     * @inheritDoc
     */
    public static function getFieldDefinitions(): array
    {
        return [
            'seo_hostname' => [
                'visible'  => false,
                'readonly' => true,
            ],
            'ssh_account' => [
                'virtual'  => true,
                'cli'      => '-a,--account ACCOUNT-NAME',
                'complete' => [
                    'word'   => function($word) { return SshAccounts::new()->filteredList($word); },
                    'noword' => function()      { return SshAccounts::new()->list(); },
                ],
            ],
            'category' => [
                'virtual'  => true,
                'cli'      => '--category CATEGORY-NAME',
                'complete' => [
                    'word'   => function($word) { return Categories::new()->filteredList($word); },
                    'noword' => function()      { return Categories::new()->list(); },
                ],
            ],
            'provider' => [
                'virtual'  => true,
                'cli'      => '--provider PROVIDER-NAME',
                'complete' => [
                    'word'   => function($word) { return Providers::new()->filteredList($word); },
                    'noword' => function()      { return Providers::new()->list(); },
                ],
            ],
            'customer' => [
                'virtual'  => true,
                'cli'      => '--customer CUSTOMER NAME',
                'complete' => [
                    'word'   => function($word) { return Customers::new()->filteredList($word); },
                    'noword' => function()      { return Customers::new()->list(); },
                ],
            ],
            'country' => [
                'virtual'  => true,
                'cli'      => '--country COUNTRY NAME',
                'complete' => [
                    'word'   => function($word) { return Countries::new()->filteredList($word); },
                    'noword' => function()      { return Countries::new()->list(); },
                ],
            ],
            'state' => [
                'virtual'  => true,
                'cli'      => '--state STATE-NAME',
                'complete' => [
                    'word'   => function($word) { return States::new()->filteredList($word); },
                    'noword' => function()      { return States::new()->list(); },
                ],
            ],
            'city' => [
                'virtual'  => true,
                'cli'      => '--city CITY-NAME',
                'complete' => [
                    'word'   => function($word) { return Cities::new()->filteredList($word); },
                    'noword' => function()      { return Cities::new()->list(); },
                ],
            ],
            'hostname' => [
                'required'   => true,
                'complete'   => true,
                'size'       => 4,
                'maxlength'  => 128,
                'type'       => 'domain',
                'cli'        => '-h,--hostname HOSTNAME',
                'label'      => tr('Hostname'),
                'help_group' => tr('Identification and network'),
                'help'       => tr('The unique hostname for this server'),
            ],
            'ssh_accounts_id' => [
                'required' => true,
                'element'  => function (string $key, array $data, array $source) {
                    return SshAccounts::getHtmlSelect($key)
                        ->setSelected(isset_get($source['accounts_id']))
                        ->render();
                },
                'complete'   => [
                    'word'   => function($word) { return SshAccounts::new()->filteredList($word); },
                    'noword' => function()      { return SshAccounts::new()->list(); },
                ],
                'cli'        => '--accounts-id DATABASE-ID',
                'label'      => tr('SSH account'),
                'size'       => 4,
                'help_group' => tr(''),
                'help'       => tr('The default SSH account used to communicat with this server'),
            ],
            'port' => [
                'required'   => true,
                'complete'   => true,
                'type'       => 'number',
                'min'        => 1,
                'max'        => 65535,
                'size'       => 2,
                'cli'        => '-p,--port PORT (1 - 65535)',
                'label'      => tr('SSH port'),
                'help_group' => tr('Identification and network'),
                'help'       => tr('The port where one can connect to the servers SSH service'),
            ],
            'code' => [
                'size'       => 2,
                'maxlength'  => 16,
                'complete'   => true,
                'cli'        => '-c,--code CODE',
                'label'      => tr('Code'),
                'help_group' => tr('Identification and network'),
                'help'       => tr('A unique identifying code for this server'),
            ],

            'cost' => [
                'type'       => 'number',
                'min'        => 0,
                'step'       => 'any',
                'size'       => 4,
                'complete'   => true,
                'cli'        => '--cost CURRENCY',
                'label'      => tr('Cost'),
                'help_group' => tr('Payment'),
                'help'       => tr('The cost per interval for this server'),
            ],
            'bill_due_date' => [
                'type'       => 'date',
                'size'       => 4,
                'complete'   => true,
                'cli'        => '-b,--bill-due-date DATE',
                'label'      => tr('Bill due date'),
                'help_group' => tr('Payment'),
                'help'       => tr('The next date when payment for this server is due'),
            ],
            'interval' => [
                'element' => 'select',
                'source'  => [
                    'hourly'     => tr('Hourly'),
                    'daily'      => tr('Daily'),
                    'weekly'     => tr('Weekly'),
                    'monthly'    => tr('Monthly'),
                    'bimonthly'  => tr('Bimonthly'),
                    'quarterly'  => tr('Quarterly'),
                    'semiannual' => tr('Semiannual'),
                    'annually'   => tr('Annually'),
                ],
                'size'       => 4,
                'complete'   => true,
                'cli'        => '-i,--interval POSITIVE-INTEGER',
                'label'      => tr('Payment interval'),
                'help_group' => tr('Payment'),
                'help'       => tr('The interval for when this server must be paid'),
            ],

            'categories_id' => [
                'element'  => function (string $key, array $data, array $source) {
                    return Categories::getHtmlSelect($key)
                        ->setSelected(isset_get($source['categories_id']))
                        ->render();
                },
                'complete'   => true,
                'cli'        => '--categories-id DATABASE-ID',
                'label'      => tr('Category'),
                'size'       => 4,
                'help_group' => tr(''),
                'help'       => tr('The category under which this server is organised'),
            ],
            'providers_id' => [
                'element'  => function (string $key, array $data, array $source) {
                    return Providers::getHtmlSelect($key)
                        ->setSelected(isset_get($source['providers_id']))
                        ->render();
                },
                'complete'   => true,
                'cli'        => '--providers-id DATABASE-ID',
                'label'      => tr('Provider'),
                'size'       => 4,
                'help_group' => tr(''),
                'help'       => tr('The hosting provider that rents this server'),
            ],
            'customers_id' => [
                'element'  => function (string $key, array $data, array $source) {
                    return Customers::getHtmlSelect($key)
                        ->setSelected(isset_get($source['customers_id']))
                        ->render();
                },
                'complete'   => true,
                'cli'        => '--customers-id DATABASE-ID',
                'label'      => tr('Customer'),
                'size'       => 4,
                'help_group' => tr(''),
                'help'       => tr('The customer to which this server is assigned'),
            ],

            'countries_id' => [
                'element'  => function (string $key, array $data, array $source) {
                    return Countries::getHtmlCountriesSelect($key)
                        ->setSelected(isset_get($source['countries_id']))
                        ->render();
                },
                'complete'   => true,
                'cli'        => '--countries-id DATABASE-ID',
                'label'      => tr('Country'),
                'size'       => 4,
                'help_group' => tr(''),
                'help'       => tr('The country where this server is located'),
            ],
            'states_id' => [
                'element'  => function (string $key, array $data, array $source) {
                    return Country::get($source['countries_id'])->getHtmlStatesSelect($key)
                        ->setSelected(isset_get($source['states_id']))
                        ->render();
                },
                'cli'        => '--states-id DATABASE-ID',
                'execute'    => 'countries_id',
                'label'      => tr('State'),
                'size'       => 4,
                'help_group' => tr(''),
                'help'       => tr('The state where this server is located'),
            ],
            'cities_id' => [
                'element'  => function (string $key, array $data, array $source) {
                    return State::get($source['states_id'])->getHtmlCitiesSelect($key)
                        ->setSelected(isset_get($source['cities_id']))
                        ->render();
                },
                'complete'   => true,
                'execute'    => 'states_id',
                'cli'        => '--cities-id DATABASE-ID',
                'label'      => tr('City'),
                'size'       => 4,
                'help_group' => tr(''),
                'help'       => tr('The city where this server is located'),
            ],

            'os_name' => [
                'source'  => [
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
                ],
                'complete'   => true,
                'cli'        => '-o,--os-name OPERATING-SYSTEM-NAME',
                'label'      => tr('Operating system'),
                'size'       => 9,
                'help_group' => tr(''),
                'help'       => tr('The name of the operating system installed on this server'),
            ],
            'os_version' => [
                'maxlength'  => 16,
                'complete'   => true,
                'cli'        => '-v,--os-version VERSION',
                'label'      => tr('Operating System version'),
                'size'       => 3,
                'help_group' => tr(''),
                'help'       => tr('The current version of the installed operating system'),
            ],

            'web_services' => [
                'default'    => false,
                'type'       => 'checkbox',
                'complete'   => false,
                'cli'        => '-w,--web-services',
                'label'      => tr('Web services'),
                'size'       => 3,
                'help_group' => tr(''),
                'help'       => tr('Sets if this server manages web services'),
            ],
            'mail_services' => [
                'default'    => false,
                'type'       => 'checkbox',
                'complete'   => false,
                'cli'        => '-m,--mail-services',
                'label'      => tr('Email services'),
                'size'       => 3,
                'help_group' => tr(''),
                'help'       => tr('Sets if this server manages mail services'),
            ],
            'database_services' => [
                'default'    => false,
                'type'       => 'checkbox',
                'complete'   => false,
                'cli'        => '-e,--database-services SERVICE-NAME [SERVICE-NAME]',
                'label'      => tr('Database services'),
                'size'       => 3,
                'help_group' => tr(''),
                'help'       => tr('Sets if this server manages database services'),
            ],
            'allow_sshd_modification' => [
                'default'    => false,
                'type'       => 'checkbox',
                'complete'   => false,
                'cli'        => '-s,--allow-sshd-modification',
                'label'      => tr('Allow SSHD modification'),
                'size'       => 3,
                'help_group' => tr(''),
                'help'       => tr('Sets if this server allows modification of SSH configuration'),
            ],

            'description' => [
                'maxlength'  => 2047,
                'complete'   => true,
                'cli'        => '-d,--description DESCRIPTION',
                'label'      => tr('Description'),
                'size'       => 12,
                'help_group' => tr(''),
                'help'       => tr('A description for this server'),
            ],
        ];
    }
}