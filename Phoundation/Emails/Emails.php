<?php

/**
 * Class Emails
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

use Phoundation\Data\DataEntry\DataIterator;


class Emails extends DataIterator
{

    /**
     * @inheritDoc
     */
    public static function getTable(): ?string
    {
        return 'emails';
    }


    /**
     * @inheritDoc
     */
    public static function getDefaultContentDataTypes(): ?string
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
