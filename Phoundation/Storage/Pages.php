<?php

/**
 * Class Pages
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Storage
 */


declare(strict_types=1);

namespace Phoundation\Storage;

use Phoundation\Data\DataEntries\DataIterator;
use Phoundation\Storage\Interfaces\PagesInterface;
use Phoundation\Web\Requests\Request;


class Pages extends DataIterator implements PagesInterface
{
    /**
     * @inheritDoc
     */
    public static function getTable(): ?string
    {
        return 'storage_pages';
    }


    /**
     * @inheritDoc
     */
    public static function getDefaultContentDataType(): ?string
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
