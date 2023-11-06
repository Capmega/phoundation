<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components;


/**
 * Icon class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
     */
    public function __construct()
    {
        parent::__construct();
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