<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Widgets\Panels\Interfaces;

use Phoundation\Data\Interfaces\IteratorInterface;
use Stringable;

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