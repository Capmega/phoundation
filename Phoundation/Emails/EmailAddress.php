<?php

declare(strict_types=1);

namespace Phoundation\Emails;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Web\Html\Enums\EnumInputType;


/**
 * Class EmailAddress
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Plugins\Emails
 */
class EmailAddress extends DataEntry
{
    /**
     * @inheritDoc
     */
    public static function getTable(): string
    {
        return 'email_address';
    }


    /**
     * @inheritDoc
     */
    public static function getDataEntryName(): string
    {
        return tr('Email address');
    }


    /**
     * @inheritDoc
     */
    public static function getUniqueColumn(): ?string
    {
        return tr('email');
    }


    /**
     * @inheritDoc
     */
    protected function setDefinitions(DefinitionsInterface $definitions): void
    {
        $definitions
            ->add(DefinitionFactory::getUsersEmail($this)
                ->setVisible(false))
            ->add(DefinitionFactory::getUsersId($this)
                ->setVisible(false))
            ->add(DefinitionFactory::getRolesId($this, 'view_roles_id')
                ->setVisible(false))
            ->add(DefinitionFactory::getRolesName($this, 'view_roles_name')
                ->setVisible(false))
            ->add(DefinitionFactory::getRolesId($this, 'send_roles_id')
                ->setVisible(false))
            ->add(DefinitionFactory::getRolesName($this, 'send_roles_name')
                ->setVisible(false))
            ->add(DefinitionFactory::getHost($this, 'smtp_host')
                ->setVisible(false))
            ->add(DefinitionFactory::getPort($this, 'smtp_port')
                ->setVisible(false))
            ->add(Definition::new($this, 'smtp_auth')
                ->setInputType(EnumInputType::checkbox)
                ->setVisible(false))
            ->add(Definition::new($this, 'smtp_secure')
                ->setInputType(EnumInputType::text)
                ->setDataSource(['tls' => tr('TLS')])
                ->setVisible(false))
            ->add(DefinitionFactory::getName($this)
                ->setSize(3))
            ->add(DefinitionFactory::getSeoName($this)
                ->setSize(3))
            ->add(DefinitionFactory::getUsername($this)
                ->setSize(3))
            ->add(DefinitionFactory::getPassword($this)
                ->setSize(3))
            ->add(DefinitionFactory::getDescription($this));
    }
}
