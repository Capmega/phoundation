<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Template;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Components\Input\Interfaces\RenderInterface;
use Phoundation\Web\Html\Template\Exception\TemplateException;
use Phoundation\Web\Html\Template\Interfaces\TemplateInterface;
use Phoundation\Web\Html\Template\Interfaces\TemplatePageInterface;
use Plugins\Phoundation\Phoundation\Components\Menu;


/**
 * Class Template
 *
 * This class contains basic template functionalities. All template classes must extend this class!
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */
abstract class Template implements TemplateInterface
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
     * @var TemplatePageInterface $page
     */
    protected TemplatePageInterface $page;


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
                ':name' => $this->getName(),
            ]));
        }
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
     *
     * @return void
     */
    public function requires(string $name): void
    {
        if (strtolower($name) !== strtolower($this->name)) {
            throw new TemplateException(tr('This page requires the ":name" template', [
                ':name' => $name,
            ]));
        }
    }

    /**
     * Returns a new TemplatePage for this template
     *
     * @return TemplatePageInterface
     */
    public function getPage(): TemplatePageInterface
    {
        if (!isset($this->page)) {
            // Instantiate page object
            $page = new $this->page_class();

            if (!($page instanceof TemplatePageInterface)) {
                throw new OutOfBoundsException(tr('Cannot instantiate ":template" template page object, specified class ":class" is not a sub class of "TemplatePage"', [
                    ':template' => $this->name,
                    'class'     => $this->page_class,
                ]));
            }

            $this->page = $page;
        }

        return $this->page;
    }

    /**
     * Returns a Renderer class for the specified component in the current Template, or NULL if none available
     *
     * @param RenderInterface|string $class
     *
     * @return string|null
     */
    public function getRendererClass(RenderInterface|string $class): ?string
    {
        while (true) {
            if (is_object($class)) {
                $class = get_class($class);
            }

            $class      = Strings::startsNotWith($class, '\\');
            $class_path = Strings::from($class, 'Html\\', needle_required: true);

            if (str_starts_with($class, 'Plugins\\')) {
                // First search for a template driver in the library itself
                $class_path    = Strings::until($class, 'Html\\', needle_required: true) . 'Templates\\' . static::getName() . '\\Html\\' . $class_path;
                $include_class = Strings::untilReverse($class_path, '\\') . '\\Template' . Strings::fromReverse($class_path, '\\');
                $include_file  = str_replace('\\', '/', $include_class);
                $include_file  = DIRECTORY_ROOT . $include_file . '.php';

            } else {
                if (!$class_path) {
                    // This class is not an HTML class. Maybe it's an object that extends an HTML class, so check the parent
                    // class and see if that one works
                    $parent = get_parent_class($class);

                    if (!$parent) {
                        throw new OutOfBoundsException(tr('Specified class ":class" does not appear to be an Html\\ component. An HTML component should contain "Html\\" like (for example) "Plugins\\Phoundation\\Html\\Layout\\Grid""', [
                            ':class' => $class,
                        ]));
                    }

                    $class = $parent;
                    continue;
                }

                if (($class_path === 'Components\\Element') or ($class_path === 'Components\\ElementsBlock')) {
                    // These are the lowest element types, from here there are no renderers available
                    return null;
                }

                // Find the template class path and the template file to include
                $class_path   = Strings::untilReverse($class_path, '\\') . '\\Template' . Strings::fromReverse($class_path, '\\');
                $include_file = str_replace('\\', '/', $class_path);
                $include_file = $this->getDirectory() . 'Html/' . $include_file . '.php';

                // Find the class path in the file, we will return this as the class that should be used for
                // rendering
                $include_class = Strings::untilReverse(get_class($this), '\\');
                $include_class .= '\\Html\\' . $class_path;
            }

            if (str_ends_with($include_class, 'TemplateTemplate')) {
                // "Template" class will be named just "Template"
                $include_class = str_replace('TemplateTemplate', 'Template', $include_class);
                $include_file  = str_replace('TemplateTemplate', 'Template', $include_file);
            }

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
     * Returns the root path for this template
     *
     * @return string
     */
    abstract public function getDirectory(): string;

    /**
     * Returns the description for this template
     *
     * @return string
     */
    abstract public function getDescription(): ?string;
}
