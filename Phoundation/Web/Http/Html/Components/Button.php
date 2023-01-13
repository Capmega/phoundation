<?php

namespace Phoundation\Web\Http\Html\Components;

use JetBrains\PhpStorm\ExpectedValues;
use Phoundation\Core\Config;



/**
 * Button class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class Button extends Element
{
    use ButtonProperties;



    /**
     * @var string $kind
     */
    #[ExpectedValues(values: ['success', 'info', 'warning', 'danger', 'primary', 'secondary', 'tertiary', 'link', 'light', 'dark'])]
    protected string $kind = 'primary';

    /**
     * Floating buttons
     *
     * @var bool $floating
     */
    protected bool $floating = false;



    /**
     * Button class constructor
     * @todo Get rid of the web.defaults.elements.classes.button path as this was an idea before the templating system
     */
    public function __construct()
    {
        parent::__construct();
        parent::setElement('button');
        parent::setClasses(Config::getString('web.defaults.elements.classes.button', 'btn'));
    }



    /**
     * Set the button type
     *
     * @param string $kind
     * @return Button
     */
    public function setKind(#[ExpectedValues(values: ['success', 'info', 'warning', 'danger', 'primary', 'secondary', 'tertiary', 'link', 'light', 'dark'])] string $kind): static
    {
        $this->kind = strtolower(trim($kind));

        $this->setButtonClasses();

        return $this;
    }



    /**
     * Returns the button type
     *
     * @return string
     */
    #[ExpectedValues(values: ['success', 'info', 'warning', 'danger', 'primary', 'secondary', 'tertiary', 'link', 'light', 'dark'])] public function getKind(): string
    {
        return $this->kind;
    }



    /**
     * Set if the button is floating or not
     *
     * @param bool $floating
     * @return Button
     */
    public function setFloating(bool $floating): static
    {
        $this->floating = $floating;
        return $this;
    }



    /**
     * Returns if the button is floating or not
     *
     * @return string
     */
    public function getFloating(): string
    {
        return $this->floating;
    }



    /**
     * Set the content for this button
     *
     * @todo add documentation for when button is floating as it is unclear what is happening there
     * @param object|string|null $content
     * @return static
     */
    public function setContent(object|string|null $content): static
    {
        if ($this->floating) {
            // What does this do?????????????
            $this->addClass('btn-floating');
            Icons::new()->setContent($this->content)->render();
            return $this;
        }

        return parent::setContent($content);
    }



    /**
     * Set the classes for this button
     *
     * @return void
     */
    protected function setButtonClasses(): void
    {
        // Remove the current button kind
        foreach ($this->classes as $id => $class) {
            if (str_starts_with($class, 'btn-')) {
                unset($this->classes[$id]);
            }
        }

        $this->addClass('btn-' . ($this->outlined ? 'outline-' : '') . $this->kind);

        if ($this->flat) {
            $this->addClass('btn-flat');
        }

        if ($this->size) {
            $this->addClass('btn-' . $this->size);
        }

        if ($this->rounded) {
            $this->addClass('btn-rounded');
        }
        if (!$this->wrapping) {
            $this->addClass('text-nowrap');
        }

        if ($this->floating) {
            $this->addClass('btn-floating');
            $this->setContent(Icons::new()->setContent($this->content)->render());
        }
    }



    /**
     * Renders and returns the HTML for this object
     *
     * @return string|null
     */
    public function render(): ?string
    {
        $this->attributes['type'] = $this->type;

        if ($this->anchor_url) {
            $this->attributes['href'] = $this->anchor_url;
        }

        return parent::render();
    }
}