<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Input\Interfaces;

interface RenderJsonInterface
{
    /**
     * Renders and returns the JSON array for this object
     *
     * @return array
     */
    public function renderJson(): array;
}
