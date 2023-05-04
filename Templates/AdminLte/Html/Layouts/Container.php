<?php

declare(strict_types=1);


namespace Templates\AdminLte\Html\Layouts;

use Phoundation\Web\Http\Html\Html;
use Phoundation\Web\Http\Html\Renderer;


/**
 * AdminLte Plugin Container class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\AdminLte
 */
class Container extends Renderer
{
    /**
     * Container class constructor
     */
    public function __construct(\Phoundation\Web\Http\Html\Layouts\Container $element)
    {
        parent::__construct($element);
    }


    /**
     * Render the HTML for this container
     *
     * @return string|null
     */
    public function render(): ?string
    {
        return '<div class="container' . ($this->element->getTier()->value ? '-' . Html::safe($this->element->getTier()->value) : null) . '">' . Html::safe($this->element->getContent()) . '</div>';
    }
}