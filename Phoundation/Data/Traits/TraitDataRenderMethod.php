<?php

/**
 * Trait TraitDataRenderMethod
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

use Phoundation\Web\Html\Enums\EnumWebRenderMethods;


trait TraitDataRenderMethod
{
    /**
     * The render_method for this object
     *
     * @var EnumWebRenderMethods|null $render_method
     */
    protected ?EnumWebRenderMethods $render_method = EnumWebRenderMethods::html;


    /**
     * Returns the render_method data
     *
     * @return EnumWebRenderMethods
     */
    public function getRenderMethod(): EnumWebRenderMethods
    {
        return $this->render_method;
    }


    /**
     * Sets the render_method data
     *
     * @param EnumWebRenderMethods $render_method
     *
     * @return static
     */
    public function setRenderMethod(EnumWebRenderMethods $render_method): static
    {
        $this->render_method = $render_method;

        return $this;
    }
}
