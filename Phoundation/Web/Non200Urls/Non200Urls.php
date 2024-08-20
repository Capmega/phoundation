<?php

/**
 * Class Non200Urls
 *
 * This class manages lists of non HTTP-200 URLs
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Non200Urls;

use Phoundation\Data\DataEntry\DataIterator;
use Phoundation\Exception\OutOfBoundsException;


class Non200Urls extends DataIterator
{
    /**
     * @inheritDoc
     */
    public static function getTable(): ?string
    {
        return 'web_non200_urls';
    }


    /**
     * @inheritDoc
     */
    public static function getDefaultContentDataTypes(): ?string
    {
        return Non200Url::class;
    }


    /**
     * @inheritDoc
     */
    public static function getUniqueColumn(): ?string
    {
        return null;
    }


    /**
     * Process non processed non200url entries
     *
     * @param int|null $count
     *
     * @return void
     */
    public static function process(?int $count = null): void
    {
        if ($count < 1) {
            throw new OutOfBoundsException(tr('Invalid count ":count" specified, must be 1 or higher', [
                ':count' => $count,
            ]));
        }
        $entries = sql()->query('SELECT *
                                       FROM   `web_non_200_urls`
                                       WHERE  `status` IS NULLL');
        while ($entry = $entries->fetch()) {
            $entry->process();
            if (--$count < 0) {
                // We're done!
                break;
            }
        }
    }
}
