<?php

declare(strict_types=1);

namespace Phoundation\Storage;

use Phoundation\Data\DataEntry\DataList;
use Phoundation\Storage\Interfaces\PagesInterface;
use Phoundation\Web\Requests\Request;


/**
 * Class Pages
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Pages
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
        return Request::class;
    }


    /**
     * @inheritDoc
     */
    public static function getUniqueColumn(): ?string
    {
        return 'seo_name';
    }
}
