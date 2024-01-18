<?php

declare(strict_types=1);

namespace Phoundation\Web\Http;

use Phoundation\Web\Page;


/**
 * Class Protocols
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class Protocols
{
    /**
     * Returns the current domain
     *
     * @note This is a wrapper for Page::getDomain();
     * @return string
     */
    public static function getCurrent(): string
    {
        return Page::getProtocol();
    }


    /**
     * Returns true if the specified domain is the current domain
     *
     * @param string $protocol
     * @return bool
     */
    public static function isCurrent(string $protocol): bool
    {
        return static::getCurrent() === $protocol;
    }
}