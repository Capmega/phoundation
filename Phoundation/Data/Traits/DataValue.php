<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;


use Phoundation\Web\Http\Html\Html;

/**
 * Trait DataValue
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataValue
{
    /**
     * The value for this object
     *
     * @var string|null $value
     */
    protected ?string $value = null;


    /**
     * Returns the value
     *
     * @return string|null
     */
    public function getValue(): ?string
    {
        return $this->value;
    }


    /**
     * Sets the value
     *
     * @param string|null $value
     * @param bool $make_safe
     * @return static
     */
    public function setValue(?string $value, bool $make_safe = true): static
    {
        if ($make_safe) {
            $this->value = Html::safe($value);

        } else {
            $this->value = $value;
        }

        return $this;
    }
}