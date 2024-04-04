<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Traits;


/**
 * Trait TraitRendered
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */
trait TraitRendered
{
    /**
     * Returns if this object has been rendered or not
     *
     * @var bool $rendered
     */
    protected bool $rendered = false;


    /**
     * Returns if the object has been rendered or not
     *
     * @return bool
     */
    public function getRendered(): bool
    {
        return $this->rendered;
    }
}