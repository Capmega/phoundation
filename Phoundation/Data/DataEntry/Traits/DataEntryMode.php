<?php

namespace Phoundation\Data\DataEntry\Traits;

use JetBrains\PhpStorm\ExpectedValues;
use Phoundation\Exception\OutOfBoundsException;



/**
 * Trait DataEntryMode
 *
 * This trait contains methods for DataEntry objects that require a mode
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryMode
{
    /**
     * Returns the mode for this object
     *
     * @return string|null
     */
    #[ExpectedValues(values: ['white', 'success', 'info', 'warning', 'danger', 'primary', 'secondary', 'tertiary', 'link', 'light', 'dark', null])]
    public function getMode(): ?string
    {
        return $this->getDataValue('mode');
    }



    /**
     * Sets the mode for this object
     *
     * @param string|null $mode
     * @return static
     */
    public function setMode(?string $mode): static
    {
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

        return $this->setDataValue('mode', $mode);
    }
}