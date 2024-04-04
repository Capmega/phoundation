<?php

declare(strict_types=1);

namespace Phoundation\Emails;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Web\Html\Enums\EnumElementInputType;


/**
 * Class EmailAccount
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Plugins\Emails
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
                                   ->setRender(false))
            ->add(DefinitionFactory::getUsersId($this)
                                   ->setRender(false))
            ->add(DefinitionFactory::getRolesId($this, 'view_roles_id')
                                   ->setRender(false))
            ->add(DefinitionFactory::getRolesName($this, 'view_roles_name')
                                   ->setRender(false))
            ->add(DefinitionFactory::getRolesId($this, 'send_roles_id')
                                   ->setRender(false))
            ->add(DefinitionFactory::getRolesName($this, 'send_roles_name')
                                   ->setRender(false))
            ->add(DefinitionFactory::getHost($this, 'smtp_host')
                                   ->setRender(false))
            ->add(DefinitionFactory::getPort($this, 'smtp_port')
                                   ->setRender(false))
            ->add(Definition::new($this, 'smtp_auth')
                            ->setInputType(EnumElementInputType::checkbox)
                            ->setRender(false))
            ->add(Definition::new($this, 'smtp_secure')
                            ->setInputType(EnumElementInputType::text)
                            ->setDataSource(['tls' => tr('TLS')])
                            ->setRender(false))
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
