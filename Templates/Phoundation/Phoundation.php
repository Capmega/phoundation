<?php

namespace Templates\Phoundation;

use Phoundation\Web\Http\Html\Template\Template;



/**
 * Class Phoundation
 *
 * This is the phoundation template
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class Phoundation extends Template
{
    /**
     * Template constructor
     *
     * @return void
     */
    public function __construct()
    {
        $this->template_page = TemplatePage::class;
        parent::__construct();
    }
}