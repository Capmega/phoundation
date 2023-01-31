<?php

namespace Templates\AdminLte\Html\Layouts;

use Phoundation\Web\Http\Html\Renderer;



/**
 * AdminLte Plugin GridColumn class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\AdminLte
 */
class GridColumn extends Renderer
{
    /**
     * GridColumn class constructor
     */
    public function __construct(\Phoundation\Web\Http\Html\Layouts\GridColumn $element)
    {
        parent::__construct($element);
    }



    /**
     * Render this grid column
     *
     * @return string|null
     */
    public function render(): ?string
    {
        $this->render = '<div class="col' . ($this->element->getTier() ? '-' . $this->element->getTier() : '') . '-' . $this->element->getSize() . '">' . $this->element->getContent() . '</div>';
        return parent::render();
    }
}