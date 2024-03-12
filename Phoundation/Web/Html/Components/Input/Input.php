<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Input;

use Phoundation\Web\Html\Components\Element;
use Phoundation\Web\Html\Components\Input\Interfaces\InputInterface;
use Phoundation\Web\Html\Traits\TraitInputElement;


/**
 * Class Input
 *
 * This class gives basic <input> functionality
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
abstract class Input extends Element implements InputInterface
{
    use TraitInputElement;


    /**
     * Input class constructor
     *
     * @param string|null $content
     */
    public function __construct(?string $content = null)
    {
        parent::__construct($content);

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
        $this->attributes = $this->renderInputAttributes()->appendSource($this->attributes);
        return parent::render();
    }
}