<?php

/**
 * GridColumn class
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Layouts;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Bootstrap;
use Phoundation\Web\Html\Enums\EnumContainerTier;
use Phoundation\Web\Html\Enums\EnumDisplaySize;
use Phoundation\Web\Html\Traits\TraitUsesSize;
use Phoundation\Web\Html\Traits\TraitUsesTier;
use Stringable;


class GridColumn extends Layout
{
    use TraitUsesSize;
    use TraitUsesTier;

    /**
     * GridColumn class constructor
     */
    public function __construct()
    {
        $this->tier = Bootstrap::getGridContainerTier(EnumContainerTier::md);
        parent::__construct();
    }


    /**
     * Sets the content of the grid
     *
     * @param Stringable|string|float|int|null $content
     * @param bool                             $make_safe
     * @param EnumDisplaySize|int|null         $size
     *
     * @return static
     */
    public function setContent(Stringable|string|float|int|null $content, bool $make_safe = false, EnumDisplaySize|int|null $size = null): static
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
     * @param bool                             $make_safe
     * @param EnumDisplaySize|int|null         $size
     * @param bool                             $use_form
     *
     * @return static
     */
    public function addContent(Stringable|string|float|int|null $content, bool $make_safe = false, EnumDisplaySize|int|null $size = null, bool $use_form = false): static
    {
        if ($size !== null) {
            $this->setSize($size);
        }

        $this->useForm($use_form);

        return parent::appendContent($content, $make_safe);
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
