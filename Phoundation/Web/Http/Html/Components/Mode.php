<?php

namespace Phoundation\Web\Http\Html\Components;

use JetBrains\PhpStorm\ExpectedValues;
use Phoundation\Exception\OutOfBoundsException;


/**
 * Mode trait
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
trait Mode
{
    /**
     * The type of infobox to show
     *
     * @var string|null $mode
     */
    #[ExpectedValues(values: ['white', 'success', 'info', 'warning', 'danger', 'primary', 'secondary', 'tertiary', 'link', 'light', 'dark', null])]
    protected ?string $mode = 'primary';



    /**
     * Sets the type of infobox to show
     *
     * @return string|null
     */
    #[ExpectedValues(values: ['white', 'success', 'info', 'warning', 'danger', 'primary', 'secondary', 'tertiary', 'link', 'light', 'dark', null])]
    public function getMode(): ?string
    {
        return $this->mode;
    }



    /**
     * Set the mode
     *
     * @param string|null $mode
     * @return static
     */
    public function setMode(
        #[ExpectedValues(values: [
            'white',
            'success',
            'green',
            'info',
            'information',
            'blue',
            'warning',
            'yellow',
            'danger',
            'red',
            'error',
            'exception',
            'primary',
            'secondary',
            'tertiary',
            'link',
            'light',
            'dark',
            null
        ])] ?string $mode = null
    ): static {
        $mode = match (strtolower(trim((string) $mode))) {
            'white'                                                     => 'white',
            'blue', 'info', 'information'                               => 'info',
            'green', 'success'                                          => 'success',
            'yellow', 'warning',                                        => 'warning',
            'red', 'error', 'exception', 'danger',                      => 'danger',
            'primary', 'secondary', 'tertiary', 'link', 'light', 'dark' => $mode,
            ''                                                          => null,
            default => throw new OutOfBoundsException(tr('Unknown mode ":mode" specified', [':mode' => $mode]))
        };

        $this->mode = $mode;

        return $this;
    }
}