<?php

declare(strict_types=1);

namespace Phoundation\Date\Interfaces;

use Phoundation\Data\Interfaces\IteratorInterface;

interface DateRangePickerRangesInterface extends IteratorInterface
{
    /**
     * Set the default daterangepicker ranges
     *
     * @return static
     */
    public function useDefault(): static;
}
