<?php

declare(strict_types=1);

namespace Templates\AdminLte\Html\Components;

use Phoundation\Web\Html\Renderer;


/**
 * AdminLte Template HtmlTable class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\AdminLte
 */
class HtmlTable extends Renderer
{
    /**
     * Table class constructor
     */
    public function __construct(\Phoundation\Web\Html\Components\HtmlTable $element)
    {
        $element->addClass('table');
        parent::__construct($element);
    }
}
