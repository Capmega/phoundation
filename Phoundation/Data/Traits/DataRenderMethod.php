<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Web\Html\Enums\EnumRenderMethods;
use Phoundation\Web\Html\Enums\Interfaces\EnumRenderMethodsInterface;


/**
 * Trait DataRenderMethod
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataRenderMethod
{
    /**
     * The render_method for this object
     *
     * @var EnumRenderMethodsInterface|null $render_method
     */
    protected ?EnumRenderMethodsInterface $render_method = EnumRenderMethods::html;


    /**
     * Returns the render_method data
     *
     * @return EnumRenderMethodsInterface
     */
    public function getRenderMethod(): EnumRenderMethodsInterface
    {
        return $this->render_method;
    }


    /**
     * Sets the render_method data
     *
     * @param EnumRenderMethodsInterface $render_method
     * @return static
     */
    public function setRenderMethod(EnumRenderMethodsInterface $render_method): static
    {
        $this->render_method = $render_method;
        return $this;
    }
}
