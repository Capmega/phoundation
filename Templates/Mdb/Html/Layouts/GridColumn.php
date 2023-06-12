<?php

declare(strict_types=1);


namespace Templates\Mdb\Html\Layouts;

use Phoundation\Web\Http\Html\Html;
use Phoundation\Web\Http\Html\Renderer;


/**
 * MDB Plugin GridColumn class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\Mdb
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
        if ($this->element->getForm()) {
            // Return content rendered in a form
            $this->render = '<div class="col' . ($this->element->getTier()->value ? '-' . Html::safe($this->element->getTier()->value) : '') . '-' . Html::safe($this->element->getSize()->value) . '">' . $this->element->getForm()->setContent($this->element->getContent())->render() . '</div>';
            $this->element->setForm(null);

            return parent::render();
        }

        $this->render = '<div class="col' . ($this->element->getTier()->value ? '-' . Html::safe($this->element->getTier()->value) : '') . '-' . Html::safe($this->element->getSize()->value) . '">' . $this->element->getContent() . '</div>';
        return parent::render();
    }
}