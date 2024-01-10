<?php

declare(strict_types=1);

namespace Phoundation\Emails;

use Phoundation\Data\DataEntry\DataList;


/**
 * Class Emails
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Plugins\Emails
 */
class Emails extends DataList
{

    /**
     * @inheritDoc
     */
    public static function getTable(): string
    {
        return 'emails';
    }

    /**
     * @inheritDoc
     */
    public static function getEntryClass(): string
    {
        return Email::class;
    }

    /**
     * @inheritDoc
     */
    public static function getUniqueColumn(): ?string
    {
        return 'code';
    }
}
