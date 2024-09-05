<?php

/**
 * Trait TraitMethodHasRendered
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;


trait TraitMethodHasRendered
{
    /**
     * Render output storage
     *
     * @var string|null
     */
    protected ?string $render = null;


    /**
     * Returns true if the object has been rendered (and Object::render() will return cached render data), false
     * otherwise
     *
     * @return bool
     */
    public function hasRendered(): bool
    {
        return (bool) $this->render;
    }


    /**
     * Clears the render cache for this object
     *
     * @return static
     */
    public function clearRenderCache(): static
    {
        $this->render = null;
        return $this;
    }
}
