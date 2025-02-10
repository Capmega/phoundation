<?php

/**
 * Trait TraitDataEntryOptions
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Traits;

use Phoundation\Utils\Json;


trait TraitDataEntryOptions
{
    /**
     * Returns the options for this object
     *
     * @return string|null
     */
    public function getOptions(): ?string
    {
        return $this->getTypesafe('string', 'options');
    }


    /**
     * Sets the options for this object
     *
     * @param array|string|null $options
     *
     * @return static
     */
    public function setOptions(array|string|null $options): static
    {
        if ($options) {
            if (is_array($options)) {
                $options = Json::encode($options);
            }
        }

        return $this->set(get_null($options), 'options');
    }
}
