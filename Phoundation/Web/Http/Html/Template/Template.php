<?php

namespace Phoundation\Web\Http\Html\Template;

use Phoundation\Core\Log;
use Phoundation\Core\Strings;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Http\Html\Template\Exception\TemplateException;



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
     * @var string $name
     */
    protected string $name;

    /**
     * The class name to use for the page
     *
     * @var string $page_class
     */
    protected string $page_class;

    /**
     * The class name to use for the menus
     *
     * @var string $menus_class
     */
    protected string $menus_class;

    /**
     * The Template page object
     *
     * @var TemplatePage $page
     */
    protected TemplatePage $page;



    /**
     * Template constructor
     *
     * @return void
     */
    public function __construct()
    {
        if (empty($this->page_class)) {
            $this->page_class  = TemplatePage::class;
            $this->menus_class = TemplateMenus::class;
        }

        if (empty($this->menus_class)) {
            throw new OutOfBoundsException('Cannot start template "' . $this->getName() . '", the menus class was not defined');
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
        if (strtolower($name) !== strtolower($this->name)) {
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
    public function getPage(): TemplatePage
    {
        if (!isset($this->page)) {
            // Instantiate page object
            $page = new $this->page_class(new $this->menus_class());

            if (!($page instanceof TemplatePage)) {
                throw new OutOfBoundsException(tr('Cannot instantiate ":template" template page object, specified class ":class" is not a sub class of "TemplatePage"', [
                    ':template' => $this->name,
                    'class'     => $this->page_class
                ]));
            }

            $this->page = $page;
        }

        return $this->page;
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