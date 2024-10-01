<?php

/**
 * Class IteratorBase
 *
 * This is a basic implementation of the default PHP Iterator class to manage arrays within an object.
 *
 * This class also adds the following methods:
 *
 * IteratorBase::__toString(): string
 * IteratorBase::__toArray(): array
 * IteratorBase::getSource(): array
 * IteratorBase::getSourceKeys(): array
 * IteratorBase::setSource(IteratorInterface|PDOStatement|array|string|null $source = null, array|null $execute = null): static
 * IteratorBase::getCount(): int
 * IteratorBase::count(): int
 * IteratorBase::clear(): static
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data;

use Phoundation\Data\Interfaces\IteratorBaseInterface;
use Phoundation\Data\Traits\TraitIterator;


class IteratorBase implements IteratorBaseInterface
{
    use TraitIterator;
}
