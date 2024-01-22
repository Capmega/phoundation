<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Stringable;


/**
 * Trait DataEntryProduct
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryProduct
{
    /**
     * Returns the product for this object
     *
     * @return string|null
     */
    public function getProduct(): ?string
    {
        return $this->getSourceColumnValue('string', 'product');
    }


    /**
     * Sets the product for this object
     *
     * @param Stringable|string|null $product
     * @return static
     */
    public function setProduct(Stringable|string|null $product): static
    {
        return $this->setSourceValue('product', (string) $product);
    }
}
