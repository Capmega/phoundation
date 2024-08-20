<?php

/**
 * Trait TraitGetInstance
 *
 * This trait contains just the static getInstance() method, used for Singleton objects
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;


trait TraitGetInstance
{
    /**
     * Singleton
     *
     * @return static
     */
    public static function getInstance(): static
    {
        if (!isset(static::$instance)) {
            static::$instance = new static();
        }

        return static::$instance;
    }
}
