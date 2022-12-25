<?php

namespace Templates\AdminLte\Components\Widgets;


/**
 * Checkbox class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\AdminLte
 */
class CheckBox extends \Phoundation\Web\Http\Html\Components\CheckBox
{
    /**
     * Checkbox class constructor
     */
    public function __construct()
    {
        $this->setType('primary');
        parent::__construct();
    }



    /**
     * Render the HTML for this checkbox
     *
     * @return string|null
     */
    public function render(): ?string
    {
        return '  <div class="icheck-' . $this->type . '">
                    ' . parent::render() . '
                  </div>';
    }
}