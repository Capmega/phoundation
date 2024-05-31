<?php

/**
 * Class Components
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */

declare(strict_types=1);

namespace Phoundation\Web\Html\Components;

class Components
{
    /**
     * Returns a new <P> element object
     *
     * @param string|null $content
     *
     * @return P
     */
    public static function header(?string $content = null): Header
    {
        return new Header($content);
    }


    /**
     * Returns a new <P> element object
     *
     * @param string|null $content
     *
     * @return P
     */
    public static function paragraph(?string $content = null): P
    {
        return new P($content);
    }


    /**
     * Returns a new <div> element object
     *
     * @param string|null $content
     *
     * @return Div
     */
    public static function div(?string $content = null): Div
    {
        return new Div($content);
    }


    /**
     * Returns a new <span> element object
     *
     * @param string|null $content
     *
     * @return Span
     */
    public static function span(?string $content = null): Span
    {
        return new Span($content);
    }
}
