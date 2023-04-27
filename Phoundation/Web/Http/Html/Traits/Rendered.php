<?php

namespace Phoundation\Web\Http\Html\Traits;

/**
 * Trait Rendered
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait Rendered
{
    /**
     * Returns if this object has been rendered or not
     *
     * @var bool $rendered
     */
    protected static bool $rendered = false;

    /**
     * Returns the rendered
     *
     * @return string|null
     */
    public function getRendered(): ?string
    {
        return self::$rendered;
    }
}