<?php

declare(strict_types=1);


namespace Templates\Mdb\Html\Layouts;

use Phoundation\Web\Http\Html\Html;
use Phoundation\Web\Http\Html\Renderer;


/**
 * MDB Plugin Container class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\Mdb
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
        return '<div class="container' . ($this->element->getTier()->value ? '-' . Html::safe($this->getTier()->value) : null) . '">' . $this->element->getContent() . '</div>';
    }
}