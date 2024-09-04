<?php

/**
 * Trait TraitDataForegroundColor
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://openforeground_color.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;


use Phoundation\Exception\OutOfBoundsException;

trait TraitDataForegroundColor
{
    /**
     * The foreground_color to use
     *
     * @var string|null $foreground_color
     */
    protected ?string $foreground_color = null;


    /**
     * Returns the foreground_color
     *
     * @return string|null
     */
    public function getForegroundColor(): ?string
    {
        return $this->foreground_color;
    }


    /**
     * Sets the foreground_color
     *
     * @param string|null $foreground_color
     *
     * @return static
     */
    public function setForegroundColor(?string $foreground_color): static
    {
        $foreground_color = match ($foreground_color) {
            'green', 'success'                    => 'green',
            'yellow', 'warning'                   => 'yellow',
            'red', 'danger', 'error', 'exception' => 'red',
            null, 'blue', 'information'           => 'blue',
            default                               => throw new OutOfBoundsException(tr('Unknown or unsupported foreground color ":color" specified. Please use one of "green", "yellow", "blue", or "red"', [
                ':color' => $foreground_color
            ]))
        };

        $this->foreground_color = $foreground_color;

        return $this;
    }
}
