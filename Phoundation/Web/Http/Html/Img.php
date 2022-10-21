<?php

namespace Phoundation\Web\Http\Html;



/**
 * Class Img
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class Img extends Element
{
    /**
     * @var string|null $none
     */
    protected ?string $none = null;

    /**
     * @var string|null $empty
     */
    protected ?string $empty = null;



    /**
     * Generates and returns the HTML string for a <select> control
     *
     * @return string
     */
    public function render(): string
    {
    }
}