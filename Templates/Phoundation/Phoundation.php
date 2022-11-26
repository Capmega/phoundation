<?php

namespace Templates\Phoundation;

use Phoundation\Web\Http\Html\Template\Template;
use Phoundation\Web\WebPage;


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



    /**
     * Return a description for this template
     *
     * @return string
     */
    public function getDescription(): string
    {
        return 'This is the default template for your website. You are free to add or build other templates';
    }
}