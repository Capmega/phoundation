<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Widgets\Panels\Interfaces;

use Phoundation\Data\Interfaces\IteratorInterface;
use ReturnTypeWillChange;
use Stringable;


interface PanelsInterface extends IteratorInterface
{
    /**
     * @inheritDoc
     */
    public function add(mixed $value, Stringable|string|float|int|null $key = null, bool $skip_null_values = true, bool $exception = true): static;


    /**
     * @inheritDoc
     */
    #[ReturnTypeWillChange] public function get(Stringable|string|float|int $key, mixed $default = null, ?bool $exception = null): ?PanelInterface;
}
