<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Forms\Interfaces;

use Phoundation\Web\Html\Components\Input\Interfaces\RenderInterface;
use Phoundation\Web\Html\Components\Interfaces\ElementsBlockInterface;

interface DataEntryFormColumnInterface extends ElementsBlockInterface
{
    /**
     * Returns the component
     *
     * @return RenderInterface|string|null
     */
    public function getColumnComponent(): RenderInterface|string|null;


    /**
     * Sets the component
     *
     * @param RenderInterface|string|null $column_component
     *
     * @return static
     */
    public function setColumnComponent(RenderInterface|string|null $column_component): static;


    /**
     * Renders and returns the HTML for this component
     *
     * @return string|null
     */
    public function render(): ?string;
}