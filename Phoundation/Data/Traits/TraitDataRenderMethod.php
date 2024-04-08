<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Web\Html\Enums\EnumWebRenderMethods;
use Phoundation\Web\Html\Enums\Interfaces\EnumWebRenderMethodsInterface;

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
trait TraitDataRenderMethod
{
    /**
     * The render_method for this object
     *
     * @var EnumWebRenderMethodsInterface|null $render_method
     */
    protected ?EnumWebRenderMethodsInterface $render_method = EnumWebRenderMethods::html;


    /**
     * Returns the render_method data
     *
     * @return EnumWebRenderMethodsInterface
     */
    public function getRenderMethod(): EnumWebRenderMethodsInterface
    {
        return $this->render_method;
    }


    /**
     * Sets the render_method data
     *
     * @param EnumWebRenderMethodsInterface $render_method
     *
     * @return static
     */
    public function setRenderMethod(EnumWebRenderMethodsInterface $render_method): static
    {
        $this->render_method = $render_method;

        return $this;
    }
}
