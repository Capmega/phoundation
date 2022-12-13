<?php

namespace Plugins\AdminLte\Elements;



/**
 * Checkbox class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Plugins\AdminLte
 */
class CheckBox extends \Phoundation\Web\Http\Html\Elements\CheckBox
{
    /**
     * Checkbox class constructor
     */
    public function __construct()
    {
        $this->setClass('form-check-input');
        $this->setLabelClass('form-check-label');
        parent::__construct();
    }



    /**
     * Render the HTML for this checkbox
     *
     * @return string
     */
    public function render(): string
    {
        return '<div class="form-check">
                    ' . parent::render() . '                    
                </div>';
    }
}