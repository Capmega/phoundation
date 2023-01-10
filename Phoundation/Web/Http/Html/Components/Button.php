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
     * @var string
     */
    #[ExpectedValues(values: ["success", "info", "warning", "danger", "primary", "secondary", "tertiary", "link", "light", "dark"])]
    protected string $button_type = 'primary';

    /**
     * Floating buttons
     *
     * @var bool $floating
     */
    protected bool $floating = false;



    /**
     * Button class constructor
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
     * @param string $button_type
     * @return Button
     */
    public function setButtonType(#[ExpectedValues(values: ["success", "info", "warning", "danger", "primary", "secondary", "tertiary", "link", "light", "dark"])] string $button_type): static
    {
        $this->button_type = strtolower(trim($button_type));

        $this->setButtonClasses();

        return $this;
    }



    /**
     * Returns the button type
     *
     * @return string
     */
    #[ExpectedValues(values: ["success", "info", "warning", "danger", "primary", "secondary", "tertiary", "link", "light", "dark"])] public function getButtonType(): string
    {
        return $this->button_type;
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
     * @param string|null $content
     * @return static
     */
    public function setContent(?string $content): static
    {
        if ($this->floating) {
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
        // Remove the current button type
        foreach ($this->classes as $id => $class) {
            if (str_starts_with($class, 'btn-')) {
                unset($this->classes[$id]);
            }
        }

        $this->addClass('btn-' . ($this->outlined ? 'outline-' : '') . $this->button_type);

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
}