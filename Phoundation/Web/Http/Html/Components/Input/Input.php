<?php

namespace Phoundation\Web\Http\Html\Components\Input;

use Phoundation\Web\Http\Html\Components\Element;


/**
 * Class Input
 *
 * This trait adds functionality for HTML input elements
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
abstract class Input extends Element
{
    use InputElement;


    /**
     * Input class constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->requires_closing_tag = false;
        $this->element              = 'input';
    }


    /**
     * Render and return the HTML for this Input Element
     *
     * @return string|null
     */
    public function render(): ?string
    {
        $this->attributes = array_merge($this->buildInputAttributes(), $this->attributes);
        return parent::render();
    }
}