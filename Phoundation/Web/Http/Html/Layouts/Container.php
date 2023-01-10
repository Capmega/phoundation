<?php

namespace Phoundation\Web\Http\Html\Layouts;

use JetBrains\PhpStorm\ExpectedValues;



/**
 * Container class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class Container extends Layout
{
    /**
     * Container value for this container
     *
     * @var string|null $type
     */
    #[ExpectedValues(values:[null, "sm", "md", "lg", "xl", "xxl"])]
    protected ?string $type = 'xxl';



    /**
     * Sets the type for this container
     *
     * @param string $type
     * @return static
     */
    public function setType(#[ExpectedValues(values:[null, "sm", "md", "lg", "xl", "xxl"])] string $type): static
    {
        $this->type = $type;
        return $this;
    }



    /**
     * Returns the type for this container
     *
     * @return string
     */
    #[ExpectedValues(values:[null, "sm", "md", "lg", "xl", "xxl"])] public function getType(): string
    {
        return $this->type;
    }
}