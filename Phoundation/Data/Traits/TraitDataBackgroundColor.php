<?php

/**
 * Trait TraitDataBackgroundColor
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://openbackground_color.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;


use Phoundation\Exception\OutOfBoundsException;

trait TraitDataBackgroundColor
{
    /**
     * The background_color to use
     *
     * @var string|null $background_color
     */
    protected ?string $background_color = null;


    /**
     * Returns the background_color
     *
     * @return string|null
     */
    public function getBackgroundColor(): ?string
    {
        return $this->background_color;
    }


    /**
     * Sets the background_color
     *
     * @param string|null $background_color
     *
     * @return static
     */
    public function setBackgroundColor(?string $background_color): static
    {
        $background_color = match ($background_color) {
            'green', 'success'                    => 'green',
            'yellow', 'warning'                   => 'yellow',
            'red', 'danger', 'error', 'exception' => 'red',
            null, 'blue', 'information'           => 'blue',
            'light'                               => 'light',
            'dark'                                => 'dark',
            'primary'                             => 'primary',
            'secondary'                           => 'secondary',
            default                               => throw new OutOfBoundsException(tr('Unknown or unsupported background color ":color" specified. Please use one of "green", "yellow", "blue", or "red"', [
                ':color' => $background_color
            ]))
        };

        $this->background_color = $background_color;

        return $this;
    }
}
