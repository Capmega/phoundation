<?php

namespace Phoundation\Web\Http\Html\Template;

use Phoundation\Web\Page;



/**
 * Class Template
 *
 * This class contains basic template functionalities. All template classes must extend this class!
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class Template
{
    /**
     * The template page that should be used
     *
     * @var string
     */
    protected string $template_page;



    /**
     * Template constructor
     *
     * @return void
     */
    public function __construct()
    {
        if (empty($this->template_page)) {
            $this->template_page = TemplatePage::class;
        }
    }



    /**
     * Returns a new template object
     *
     * @return static
     */
    public static function new(): static
    {
        return new static();
    }



    /**
     * Execute the template
     *
     * @param string $target
     * @return void
     */
    public function execute(Page $page, string $target): void
    {
        $this->template_page::new($page)->execute($target);
    }
}