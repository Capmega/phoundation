<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Template;

use Phoundation\Core\Strings;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Html\Components\Element;
use Phoundation\Web\Html\Components\ElementsBlock;
use Phoundation\Web\Html\Template\Exception\TemplateException;
use Plugins\Phoundation\Components\Menu;


/**
 * Class Template
 *
 * This class contains basic template functionalities. All template classes must extend this class!
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
            $this->menus_class = Menu::class;
        }

        if (empty($this->menus_class)) {
            throw new OutOfBoundsException(tr('Cannot start template ":name", the menus class was not defined', [
                ':name' => $this->getName()
            ]));
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
            $page = new $this->page_class();

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
     * Returns a Renderer class for the specified component in the current Template, or NULL if none available
     *
     * @param Element|ElementsBlock|string $class
     * @return string|null
     */
    public function getRendererClass(Element|ElementsBlock|string $class): ?string
    {
        while (true) {
            if (is_object($class)) {
                $class = get_class($class);
            }

            $class_path = Strings::from($class, 'Html\\', 0, true);

            if (!$class_path) {
                // This class is not an HTML class. Maybe its an object that extends an HTML class, so check the parent
                // class and see if that one works
                $parent = get_parent_class($class);

                if (!$parent) {
                    throw new OutOfBoundsException(tr('Specified class ":class" does not appear to be an Html\\ component. An HTML component should contain "Html\\" like (for example) "Plugins\\Phoundation\\Html\\Layout\\Grid""', [
                        ':class' => $class
                    ]));
                }

                $class = $parent;
                continue;
            }

            if (($class_path === 'Components\Element') or ($class_path === 'Components\ElementsBlock')) {
                // These are the lowest element types, from here there are no renderers available
                return null;
            }

            // Find the file to include
            $include_file   = str_replace('\\', '/', $class_path);
            $include_file   = $this->getDirectory() . 'Html/' . $include_file . '.php';

            // Find the class path that is in the file, we will return this as the class that should be used for
            // rendering
            $include_class  = Strings::untilReverse(get_class($this), '\\');
            $include_class .= '\\Html\\' . $class_path;

            if (file_exists($include_file)) {
                // Include the file and return the class path
                include_once($include_file);
                return $include_class;
            }

            // So at this point, we did not find a file. Try the parent of this class, see if that one perhaps has a
            // renderer available?
            $class = get_parent_class($class);

            if (!$class) {
                // There was no parent
                break;
            }
        }

        return null;
    }


    /**
     * Returns the description for this template
     *
     * @return string
     */
    abstract public function getDescription(): string;


    /**
     * Returns the root path for this template
     *
     * @return string
     */
    abstract public function getDirectory(): string;
}