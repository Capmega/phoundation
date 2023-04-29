<?php

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Exception\OutOfBoundsException;


/**
 * Trait DataEntryLine
 *
 * This trait contains methods for DataEntry objects that require a line
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryLine
{
    /**
     * Returns the line number for this object
     *
     * @return int|null
     */
    public function getLine(): ?int
    {
        return $this->getDataValue('line');
    }


    /**
     * Sets the line for this object
     *
     * @param int|null $line
     * @return static
     */
    public function setLine(?int $line): static
    {
        if ($line < 1) {
            throw new OutOfBoundsException(tr('Specified line ":line" is invalid, it should be 1 or more', [
                ':line' => $line
            ]));
        }

        return $this->setDataValue('line', $line);
    }
}