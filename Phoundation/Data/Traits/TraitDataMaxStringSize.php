<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Core\Log\Log;

/**
 * Trait TraitDataMaxStringSize
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */
trait TraitDataMaxStringSize
{
    /**
     * The max size for strings that we're able to handle
     *
     * @var int $max_string_size
     */
    protected int $max_string_size = 1_073_741_824;


    /**
     * Returns the maximum strings size we're able to handle
     *
     * @param int|null $characters
     *
     * @return int
     */
    public function getMaxStringSize(?int $characters = null): int
    {
        // Ensure we have a valid default value
        $this->max_string_size = ($this->max_string_size ?? 1_073_741_824);
        if ($characters === null) {
            // Return the maximum size
            return $this->max_string_size;
        }
        if ($characters > $this->max_string_size) {
            Log::warning(tr('The specified number of maximum characters ":specified" surpasses the configured maximum number of ":configured". Forcing configured maximum amount instead', [
                ':specified'  => $characters,
                ':configured' => $this->max_string_size,
            ]));

            return $this->max_string_size;
        }

        // Yeah, this is okay
        return $characters;
    }


    /**
     * Sets the maximum strings size we're able to handle
     *
     * @param int|null $max_string_size
     *
     * @return static
     */
    public function setMaxStringSize(?int $max_string_size): static
    {
        $this->max_string_size = ($max_string_size ?? 1_073_741_824);

        return $this;
    }
}
