<?php

declare(strict_types=1);


namespace Templates\None\Html\Layouts;

use Phoundation\Web\Html\Html;
use Phoundation\Web\Html\Renderer;


/**
 * None Plugin Container class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\None
 */
class Container extends Renderer
{
    /**
     * Container class constructor
     */
    public function __construct(\Phoundation\Web\Html\Layouts\Container $element)
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
        return '<div class="container' . ($this->render_object->getTier()->value ? '-' . Html::safe($this->render_object->getTier()->value) : null) . '">' . $this->render_object->getContent() . '</div>';
    }
}