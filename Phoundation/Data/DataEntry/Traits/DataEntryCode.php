<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Exception\OutOfBoundsException;


/**
 * Trait DataEntryCode
 *
 * This trait contains methods for DataEntry objects that require a code
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryCode
{
    /**
     * Returns the code for this object
     *
     * @return string|null
     */
    public function getCode(): ?string
    {
        return $this->getDataValue('string', 'code');
    }


    /**
     * Sets the code for this object
     *
     * @note This method prefixes each code with a "#" symbol to ensure that codes are never seen as numeric, which
     *       would cause issues with $identifier detection, as $identifier can be numeric (ALWAYS id) or non numeric
     *       (The other unique column)
     * @param string|null $code
     * @return static
     */
    public function setCode(?string $code): static
    {
        // Ensure that "code" is never seen as numeric!
        if (is_numeric($code)) {
            throw new OutOfBoundsException(tr('Specified code ":code" is numeric, code column must be non numeric', [
                ':code' => $code
            ]));
        }

        return $this->setDataValue('code', $code);
    }
}