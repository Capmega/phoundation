<?php

declare(strict_types=1);


namespace Templates\None\Html\Layouts;

use Phoundation\Web\Http\Html\Html;
use Phoundation\Web\Http\Html\Renderer;


/**
 * None Plugin GridColumn class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\None
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
        $this->render = '   <div class="col' . ($this->element->getTier()->value ? '-' . Html::safe($this->element->getTier()->value) : '') . '-' . Html::safe($this->element->getSize()->value) . '">';

        if ($this->element->getForm()) {
            // Return column content rendered in a form
            $this->render .= $this->element->getForm()->setContent($this->element->getContent())->render();
            $this->element->setForm(null);
        } else {
            $this->render .= $this->element->getContent();
        }

        $this->render .= '</div>';
        return parent::render();
    }
}