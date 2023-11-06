<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Layouts;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Html\Enums\DisplayTier;
use Phoundation\Web\Html\Enums\Interfaces\DisplaySizeInterface;
use Phoundation\Web\Html\Traits\UsesSize;
use Phoundation\Web\Html\Traits\UsesTier;
use Stringable;


/**
 * GridColumn class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
     * @param Stringable|string|float|int|null $content
     * @param bool $make_safe
     * @param DisplaySizeInterface|int|null $size
     * @return static
     */
    public function setContent(Stringable|string|float|int|null $content, bool $make_safe = false, DisplaySizeInterface|int|null $size = null): static
    {
        if ($size !== null) {
            $this->setSize($size);
        }

        return parent::setContent($content, $make_safe);
    }


    /**
     * Adds the specified content to the content of the grid
     *
     * @param Stringable|string|float|int|null $content
     * @param DisplaySizeInterface|int|null $size $size
     * @param bool $use_form
     * @return static
     */
    public function addContent(Stringable|string|float|int|null $content, bool $make_safe = false, DisplaySizeInterface|int|null $size = null, bool $use_form = false): static
    {
        if ($size !== null) {
            $this->setSize($size);
        }

        $this->useForm($use_form);

        return parent::addContent($content, $make_safe);
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