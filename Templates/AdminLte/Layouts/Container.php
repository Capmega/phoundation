<?php

namespace Templates\AdminLte\Layouts;



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
class Container extends \Phoundation\Web\Http\Html\Layouts\Container
{
    /**
     * Render the HTML for this container
     *
     * @return string
     */
    public function render(): string
    {
        return '<div class="container' . ($this->type ? '-' . $this->type : null) . '">' . $this->content . '</div>';
    }
}