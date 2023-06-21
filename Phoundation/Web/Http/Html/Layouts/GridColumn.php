<?php

declare(strict_types=1);

namespace Phoundation\Web\Http\Html\Layouts;

use PDOStatement;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Http\Html\Enums\DisplayTier;
use Phoundation\Web\Http\Html\Enums\Interfaces\DisplaySizeInterface;
use Phoundation\Web\Http\Html\Traits\UsesSize;
use Phoundation\Web\Http\Html\Traits\UsesTier;
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
     * @param DisplaySizeInterface|int|null $size
     * @return static
     */
    public function setContent(Stringable|string|float|int|null $content, DisplaySizeInterface|int|null $size = null): static
    {
        if ($size !== null) {
            $this->setSize($size);
        }

        return parent::setContent($content);
    }


    /**
     * Adds the specified content to the content of the grid
     *
     * @param Stringable|string|float|int|null $content
     * @param DisplaySizeInterface|int|null $size $size
     * @param bool $use_form
     * @return static
     */
    public function addContent(Stringable|string|float|int|null $content, DisplaySizeInterface|int|null $size = null, bool $use_form = false): static
    {
        if ($size !== null) {
            $this->setSize($size);
        }

        $this->useForm($use_form);

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