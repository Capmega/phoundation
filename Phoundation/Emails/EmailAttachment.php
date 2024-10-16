<?php

/**
 * Class EmailAttachment
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Plugins\Emails
 */


declare(strict_types=1);

namespace Phoundation\Emails;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Web\Html\Enums\EnumInputType;


class EmailAttachment extends DataEntry
{
    /**
     * @inheritDoc
     */
    public static function getTable(): ?string
    {
        return 'email_attachments';
    }


    /**
     * @inheritDoc
     */
    public static function getDataEntryName(): string
    {
        return tr('Email attachment');
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
        $definitions->add(DefinitionFactory::newUsersEmail($this)
                                           ->setRender(false))
                    ->add(DefinitionFactory::newUsersId($this)
                                           ->setRender(false))
                    ->add(DefinitionFactory::newRolesId($this, 'view_roles_id')
                                           ->setRender(false))
                    ->add(DefinitionFactory::newRolesName($this, 'view_roles_name')
                                           ->setRender(false))
                    ->add(DefinitionFactory::newRolesId($this, 'send_roles_id')
                                           ->setRender(false))
                    ->add(DefinitionFactory::newRolesName($this, 'send_roles_name')
                                           ->setRender(false))
                    ->add(DefinitionFactory::getHost($this, 'smtp_host')
                                           ->setRender(false))
                    ->add(DefinitionFactory::getPort($this, 'smtp_port')
                                           ->setRender(false))
                    ->add(Definition::new($this, 'smtp_auth')
                                    ->setInputType(EnumInputType::checkbox)
                                    ->setRender(false))
                    ->add(Definition::new($this, 'smtp_secure')
                                    ->setInputType(EnumInputType::text)
                                    ->setDataSource(['tls' => tr('TLS')])
                                    ->setRender(false))
                    ->add(DefinitionFactory::newName($this)
                                           ->setSize(3))
                    ->add(DefinitionFactory::newSeoName($this)
                                           ->setSize(3))
                    ->add(DefinitionFactory::newUsername($this)
                                           ->setSize(3))
                    ->add(DefinitionFactory::newPassword($this)
                                           ->setSize(3))
                    ->add(DefinitionFactory::newDescription($this));
    }
}
