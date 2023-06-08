<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;


/**
 * Trait UsesNewKey
 *
 *
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package Phoundation\Data
 */
trait UsesNewKey
{
    use DataKey;


    /**
     * UsesNewKey class constructor
     *
     * @param string|null $key
     */
    public function __construct(?string $key = null)
    {
        $this->key = $key;
    }


    /**
     * Returns a new static object
     *
     * @param string|null $key
     * @return static
     */
    public static function new(?string $key = null): static
    {
        return new static($key);
    }
}
