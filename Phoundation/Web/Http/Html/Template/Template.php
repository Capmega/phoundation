<?php

namespace Phoundation\Web\Http\Html\Template;

use Phoundation\Core\Strings;
use Phoundation\Web\Http\Html\Template\Exception\TemplateException;
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
abstract class Template
{
    /**
     * The template name
     *
     * @var string
     */
    protected string $name;

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
     * Returns a new Template object
     *
     * @return static
     */
    public static function new(): static
    {
        return new static();
    }



    /**
     * This function checks if this template is the required template
     *
     * This is in case a specific site requires a specific template
     *
     * @param string $name
     * @return void
     */
    public function requires(string $name): void
    {
        if ($name !== $this->name) {
            throw new TemplateException(tr('This page requires the ":name" template', [
                ':name' => $name
            ]));
        }
    }



    /**
     * Returns a new TemplatePage for this template
     *
     * @return TemplatePage
     */
    public function getTemplatePage(): TemplatePage
    {
        return new $this->template_page;
    }



    /**
     * Returns the name for this template
     *
     * @return string
     */
    public function getName(): string
    {
        return Strings::from(Strings::fromReverse(get_class($this), '\\'), '\\');
    }


    /**
     * Returns the description for this template
     *
     * @return string
     */
    public abstract function getDescription(): string;
}