<?php

namespace Phoundation\Storage;

use Phoundation\Data\DataEntry\DataList;
use Phoundation\Storage\Interfaces\PagesInterface;
use Phoundation\Web\Page;


/**
 * Class Pages
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Pages
 */
class Pages extends DataList implements PagesInterface
{
    /**
     * @inheritDoc
     */
    public static function getTable(): string
    {
        return 'storage_pages';
    }


    /**
     * @inheritDoc
     */
    public static function getEntryClass(): string
    {
        return Page::class;
    }


    /**
     * @inheritDoc
     */
    public static function getUniqueField(): ?string
    {
        return 'seo_name';
    }
}
