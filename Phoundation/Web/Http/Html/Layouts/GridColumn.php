<?php

namespace Phoundation\Web\Http\Html\Layouts;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Http\Html\Enums\DisplayTier;
use Phoundation\Web\Http\Html\Interfaces\InterfaceDisplaySize;
use Phoundation\Web\Http\Html\Traits\UsesSize;
use Phoundation\Web\Http\Html\Traits\UsesTier;


/**
 * GridColumn class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class GridColumn extends Layout
{
    use UsesSize;
    use UsesTier;


    /**
     * GridColumn class constructor
     */
    public function __construct()
    {
        $this->tier = DisplayTier::md;
        parent::__construct();
    }


    /**
     * Sets the content of the grid
     *
     * @param object|string|null $content
     * @param InterfaceDisplaySize|int|null $size
     * @return static
     */
    public function setContent(object|string|null $content, InterfaceDisplaySize|int|null $size = null): static
    {
        if ($size !== null) {
            $this->setSize($size);
        }

        return parent::setContent($content);
    }


    /**
     * Adds the specified content to the content of the grid
     *
     * @param object|string|null $content
     * @param InterfaceDisplaySize|int|null $size $size
     * @return static
     */
    public function addContent(object|string|null $content, InterfaceDisplaySize|int|null $size = null): static
    {
        if ($size !== null) {
            $this->setSize($size);
        }

        return parent::addContent($content);
    }


    /**
     * Render this grid column
     *
     * @return string|null
     */
    public function render(): ?string
    {
        if (!$this->size) {
            throw new OutOfBoundsException(tr('Cannot render GridColumn, no size specified'));
        }

        return parent::render();
    }
}