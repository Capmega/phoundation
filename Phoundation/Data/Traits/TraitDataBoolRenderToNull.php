<?php

/**
 * Trait TraitDataBoolRenderToNull
 *
 * This is a property that will determine how its using object will render output. If the flag is true, the rendered output will always be NULL
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;


trait TraitDataBoolRenderToNull
{
    /**
     * Tracks if this component object renders any output or NULL
     *
     * @var bool $render_to_null
     */
    protected bool $render_to_null = false;


    /**
     * Returns if this control renders any output or not
     *
     * @return bool
     */
    public function getRenderToNull(): bool
    {
        return $this->render_to_null;
    }


    /**
     * Set if this control renders any output or not
     *
     * @param bool $render If true, will render the component. If false, the component will render with NULL output
     *
     * @return static
     */
    public function setRenderToNull(bool $render): static
    {
        $this->render_to_null = $render;
        return $this;
    }
}
