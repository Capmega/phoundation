<?php

/**
 * Class EmailAccount
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Plugins\Emails
 */


declare(strict_types=1);

namespace Phoundation\Emails;

use Phoundation\Data\DataEntries\DataEntry;
use Phoundation\Data\DataEntries\Definitions\Definition;
use Phoundation\Data\DataEntries\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Web\Html\Enums\EnumInputType;


class EmailAccount extends DataEntry
{
    /**
     * @inheritDoc
     */
    public static function getTable(): ?string
    {
        return 'email_accounts';
    }


    /**
     * @inheritDoc
     */
    public static function getEntryName(): string
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
    protected function setDefinitionsObject(DefinitionsInterface $_definitions): static
    {
        $_definitions->add(DefinitionFactory::newUsersEmail()
                                             ->setRender(false))

                      ->add(DefinitionFactory::newUsersId()
                                           ->setRender(false))

                      ->add(DefinitionFactory::newRolesId('view_roles_id')
                                           ->setRender(false))

                    ->add(DefinitionFactory::newRolesName('view_roles_name')
                                           ->setRender(false))

                    ->add(DefinitionFactory::newRolesId('send_roles_id')
                                           ->setRender(false))

                    ->add(DefinitionFactory::newRolesName('send_roles_name')
                                           ->setRender(false))

                    ->add(DefinitionFactory::newDomain('smtp_host')
                                           ->setRender(false))

                    ->add(DefinitionFactory::newPort($this, 'smtp_port')
                                           ->setRender(false))

                    ->add(Definition::new('smtp_auth')
                                    ->setInputType(EnumInputType::checkbox)
                                    ->setRender(false))

                    ->add(Definition::new('smtp_secure')
                                    ->setInputType(EnumInputType::text)
                                    ->setSource(['tls' => tr('TLS')])
                                    ->setRender(false))

                    ->add(DefinitionFactory::newName()
                                           ->setSize(3))

                    ->add(DefinitionFactory::newSeoName()
                                           ->setSize(3))

                    ->add(DefinitionFactory::newUsername()
                                           ->setSize(3))

                    ->add(DefinitionFactory::newPassword()
                                           ->setSize(3))

                    ->add(DefinitionFactory::newDescription());

        return $this;
    }
}
