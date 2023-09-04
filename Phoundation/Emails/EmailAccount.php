<?php

namespace Phoundation\Emails;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;


/**
 * Class EmailAccount
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Plugins\Email
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
    protected function initDefinitions(DefinitionsInterface $definitions): void
    {
        // TODO: Implement initDefinitions() method.
    }
}
