<?php

namespace Phoundation\Servers;

use JetBrains\PhpStorm\ExpectedValues;
use Phoundation\Accounts\Users\Users;
use Phoundation\Business\Customers\Customers;
use Phoundation\Business\Providers\Providers;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Traits\DataEntryCustomer;
use Phoundation\Data\DataEntry\Traits\DataEntryHostnamePort;
use Phoundation\Data\DataEntry\Traits\DataEntryNameDescription;
use Phoundation\Data\DataEntry\Traits\DataEntryProvider;
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
    use DataEntryNameDescription;
    use DataEntryCustomer;
    use DataEntryProvider;
    use DataEntrySshAccount;


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
    #[ExpectedValues([null, 'hourly', 'daily', 'weekly', 'monthly', 'bimonthly', 'quarterly', 'semiannual', 'anually'])]
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
    public function setInterval(#[ExpectedValues([null, 'hourly', 'daily', 'weekly', 'monthly', 'bimonthly', 'quarterly', 'semiannual', 'anually'])] ?string $interval): static
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
     * @inheritDoc
     */
    protected function setKeys(): void
    {
        $this->keys = [
            'id' => [
                'disabled' => true,
                'type'     => 'numeric',
                'label'    => tr('Database ID')
            ],
            'created_on' => [
                'disabled'  => true,
                'type'      => 'text',
                'label'     => tr('Created on')
            ],
            'created_by' => [
                'element'  => function (string $key, array $data, array $source) {
                    return Users::getHtmlSelect($key)
                        ->setSelected(isset_get($source['created_by']))
                        ->setDisabled(true)
                        ->render();
                },
                'label'    => tr('Created by')
            ],
            'meta_id' => [
                'visible' => false,
            ],
            'status' => [
                'disabled' => true,
                'default'  => tr('Ok'),
                'label'    => tr('Status')
            ],
            'meta_state' => [
                'visible' => false,
            ],
            'name' => [
                'maxlength' => 64,
                'label'     => tr('Name')
            ],
            'seo_name' => [
                'visible' => false,
            ],
            'hostname' => [
                'maxlength' => 128,
                'type'      => 'domain',
                'label'     => tr('Hostname')
            ],
            'seo_hostname' => [
                'visible' => false,
            ],
            'port' => [
                'type'    => 'number',
                'min'     => 1,
                'max'     => 65535,
                'label'   => tr('SSH port')
            ],
            'cost' => [
                'type'    => 'number',
                'label'   => tr('Cost')
            ],
            'bill_due_date' => [
                'type'    => 'date',
                'label'   => tr('Bill due date')
            ],
            'interval' => [
                'label'   => tr('Payment interval'),
                'source'  => [
                    'hourly'     => tr('Hourly'),
                    'daily'      => tr('Daily'),
                    'weekly'     => tr('Weekly'),
                    'monthly'    => tr('Monthly'),
                    'bimonthly'  => tr('Bimonthly'),
                    'quarterly'  => tr('Quarterly'),
                    'semiannual' => tr('Semiannual'),
                    'anually'    => tr('Anually'),
                ],
            ],
            'providers_id' => [
                'element'  => function (string $key, array $data, array $source) {
                    return Providers::getHtmlSelect($key)
                        ->setSelected(isset_get($source['providers_id']))
                        ->render();
                },
                'label'    => tr('Provider'),
            ],
            'customers_id' => [
                'element'  => function (string $key, array $data, array $source) {
                    return Customers::getHtmlSelect($key)
                        ->setSelected(isset_get($source['customers_id']))
                        ->render();
                },
                'label'    => tr('Customer'),
            ],
            'ssh_accounts_id' => [
                'element'  => function (string $key, array $data, array $source) {
                    return SshAccounts::getHtmlSelect($key)
                        ->setSelected(isset_get($source['accounts_id']))
                        ->render();
                },
                'label'    => tr('SSH account'),
            ],
            'description' => [
                'maxlength' => 2047,
                'label'   => tr('Description')
            ],
            'os_name' => [
                'label'   => tr('Operating system'),
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
            ],
            'os_version' => [
                'maxlength' => 16,
                'label'   => tr('OSOperating system version')
            ],
            'web_services' => [
                'type'  => 'checkbox',
                'label' => tr('Web services'),
            ],
            'mail_services' => [
                'type'  => 'checkbox',
                'label' => tr('Email services'),
            ],
            'database_services' => [
                'type'  => 'checkbox',
                'label' => tr('Database services'),
            ],
            'allow_sshd_modification' => [
                'type'  => 'checkbox',
                'label' => tr('Allow SSHD modification'),
            ],
        ];

        $this->keys_display = [
            'id'                      => 3,
            'created_by'              => 3,
            'created_on'              => 3,
            'status'                  => 3,
            'name'                    => 6,
            'accounts_id'             => 6,
            'hostname'                => 8,
            'port'                    => 4,
            'providers_id'            => 6,
            'customers_id'            => 6,
            'cost'                    => 4,
            'bill_due_date'           => 4,
            'interval'                => 4,
            'os_name'                 => 6,
            'os_versions'             => 6,
            'web_services'            => 3,
            'mail_services'           => 3,
            'database_services'        => 3,
            'allow_sshd_modification' => 3,
            'description'             => 12,
        ] ;
    }
}