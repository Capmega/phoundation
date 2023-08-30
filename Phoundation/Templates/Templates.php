<?php

namespace Phoundation\Templates;

use Phoundation\Data\DataEntry\DataList;


/**
 * Class Templates
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Templates
 */
class Templates extends DataList
{
    /**
     * @inheritDoc
     */
    public static function getTable(): string
    {
        return 'templates_pages';
    }


    /**
     * @inheritDoc
     */
    public static function getEntryClass(): string
    {
        return Template::class;
    }


    /**
     * @inheritDoc
     */
    public static function getUniqueField(): ?string
    {
        return 'seo_name';
    }
}
