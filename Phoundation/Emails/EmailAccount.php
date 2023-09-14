<?php

namespace Phoundation\Emails;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Web\Http\Html\Enums\InputType;
use Phoundation\Web\Http\Html\Enums\InputTypeExtended;


/**
 * Class EmailAccount
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Plugins\Emails
 */
class EmailAccount extends DataEntry
{
    /**
     * @inheritDoc
     */
    public static function getTable(): string
    {
        return 'email_accounts';
    }


    /**
     * @inheritDoc
     */
    public static function getDataEntryName(): string
    {
        return tr('Email account');
    }


    /**
     * @inheritDoc
     */
    public static function getUniqueField(): ?string
    {
        return tr('email');
    }


    /**
     * @inheritDoc
     */
    protected function setDefinitions(DefinitionsInterface $definitions): void
    {
        $definitions
            ->addDefinition(DefinitionFactory::getUsersEmail($this)
                ->setVisible(false))
            ->addDefinition(DefinitionFactory::getUsersId($this)
                ->setVisible(false))
            ->addDefinition(DefinitionFactory::getRolesId($this, 'view_roles_id')
                ->setVisible(false))
            ->addDefinition(DefinitionFactory::getRolesName($this, 'view_roles_name')
                ->setVisible(false))
            ->addDefinition(DefinitionFactory::getRolesId($this, 'send_roles_id')
                ->setVisible(false))
            ->addDefinition(DefinitionFactory::getRolesName($this, 'send_roles_name')
                ->setVisible(false))
            ->addDefinition(DefinitionFactory::getHost($this, 'smtp_host')
                ->setVisible(false))
            ->addDefinition(DefinitionFactory::getPort($this, 'smtp_port')
                ->setVisible(false))
            ->addDefinition(Definition::new($this, 'smtp_auth')
                ->setInputType(InputType::checkbox)
                ->setVisible(false))
            ->addDefinition(Definition::new($this, 'smtp_secure')
                ->setInputType(InputType::text)
                ->setSource(['tls' => tr('TLS')])
                ->setVisible(false))
            ->addDefinition(DefinitionFactory::getName($this)
                ->setSize(3))
            ->addDefinition(DefinitionFactory::getSeoName($this)
                ->setSize(3))
            ->addDefinition(DefinitionFactory::getUsername($this)
                ->setSize(3))
            ->addDefinition(DefinitionFactory::getPassword($this)
                ->setSize(3))
            ->addDefinition(DefinitionFactory::getDescription($this));
    }
}
