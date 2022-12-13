<?php

namespace Templates\AdminLte;

use Phoundation\Web\Http\Html\Template\Template;



/**
 * Class AdminLte
 *
 * This is the AdminLte template
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class AdminLte extends Template
{
    /**
     * Template constructor
     *
     * @return void
     */
    public function __construct()
    {
        $this->name             = 'AdminLte';
        $this->page_class       = TemplatePage::class;
        $this->components_class = TemplateComponents::class;

        parent::__construct();
    }



    /**
     * Return a description for this template
     *
     * @return string
     */
    public function getDescription(): string
    {
        return 'This is the AdminLte admin template for your website. You are free to add or build other templates';
    }
}