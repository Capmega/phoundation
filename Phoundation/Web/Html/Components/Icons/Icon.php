<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Icons;


use Phoundation\Web\Html\Components\Element;

/**
 * Icon class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class Icon extends Element
{
    /**
     * The icoin being displayed
     *
     * @var string|null $icon
     */
    protected ?string $icon = null;


    /**
     * Returns the icon for this object
     *
     * @return string|null
     */
    public function getIcon(): ?string
    {
        return $this->icon;
    }


    /**
     * Sets the icon for this object
     *
     * @param string|null $icon
     * @param string $subclass
     * @return static
     */
    public function setIcon(?string $icon, string $subclass = ''): static
    {
        $this->addClass($subclass);
        $this->icon = $icon;
        return $this;
    }


    /**
     * Form class constructor
     *
     * @param string|null $content
     */
    public function __construct(?string $content = null)
    {
        parent::__construct($content);
        $this->setElement('i');
    }


    /**
     * @inheritDoc
     */
    public function render(): ?string
    {
        $this->addClass($this->icon);
        return parent::render();
    }
}