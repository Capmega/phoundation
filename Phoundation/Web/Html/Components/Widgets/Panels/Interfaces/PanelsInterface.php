<?php

namespace Phoundation\Web\Html\Components\Widgets\Panels\Interfaces;

use Phoundation\Data\Interfaces\IteratorInterface;
use Stringable;

/**
 * Panels class
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation/Web
 */
interface PanelsInterface extends IteratorInterface
{
    /**
     * @inheritDoc
     */
    public function add(mixed $value, float|Stringable|int|string|null $key = null, bool $skip_null = true, bool $exception = true): static;


    /**
     * @inheritDoc
     */
    public function get(float|Stringable|int|string $key, bool $exception = true): ?PanelInterface;
}