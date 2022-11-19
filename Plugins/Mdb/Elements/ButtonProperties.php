<?php

namespace Plugins\Mdb\Elements;

use JetBrains\PhpStorm\ExpectedValues;
use Phoundation\Web\Http\Html\Element;



/**
 * MDB Plugin Button class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Plugins\Mdb
 */
class Button extends Element
{
    /**
     * @var string
     */
    #[ExpectedValues(values: ["success", "info", "warning", "danger", "primary", "secondary", "tertiary", "link", "light", "dark"])]
    protected string $button_type = 'primary';

    /**
     * Outlined buttons
     *
     * @var bool $outlined
     */
    protected bool $outlined = false;

    /**
     * Rounded buttons
     *
     * @var bool $rounded
     */
    protected bool $rounded = false;

    /**
     * Floating buttons
     *
     * @var bool $floating
     */
    protected bool $floating = false;

    /**
     * Text wrapping
     *
     * @var bool $wrapping
     */
    protected bool $wrapping = true;



    /**
     * Button class constructor
     */
    public function __construct()
    {
        parent::__construct();
        parent::setElement('button');
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

        $this->setButtonClass();

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
     * Set if the button is outlined or not
     *
     * @param bool $outlined
     * @return Button
     */
    public function setOutlined(bool $outlined): static
    {
        $this->outlined = $outlined;
        return $this;
    }



    /**
     * Returns if the button is outlined or not
     *
     * @return string
     */
    public function getOutlined(): string
    {
        return $this->outlined;
    }



    /**
     * Set if the button is rounded or not
     *
     * @param bool $rounded
     * @return Button
     */
    public function setRounded(bool $rounded): static
    {
        $this->rounded = $rounded;
        return $this;
    }



    /**
     * Returns if the button is rounded or not
     *
     * @return string
     */
    public function getRounded(): string
    {
        return $this->rounded;
    }



    /**
     * Set if the button is wrapping or not
     *
     * @param bool $wrapping
     * @return Button
     */
    public function setWrapping(bool $wrapping): static
    {
        $this->wrapping = $wrapping;
        return $this;
    }



    /**
     * Returns if the button is wrapping or not
     *
     * @return string
     */
    public function getWrapping(): string
    {
        return $this->wrapping;
    }



    /**
     * Set the content for this button
     *
     * @param string|null $content
     * @return $this
     */
    public function setContent(?string $content): static
    {
        if ($this->floating) {
            $this->addClass('btn-floating');
            Icons::new()->setContent($this->content)->render();
            return $this;
        } else {
            return parent::setContent($content); // TODO: Change the autogenerated stub
        }
    }



    /**
     * Set the classes for this button
     *
     * @return void
     */
    protected function setButtonClass(): void
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
            Icons::new()->setContent($this->content)->render();
        }
    }
}