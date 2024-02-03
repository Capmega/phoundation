<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Core\Log\Log;
use Phoundation\Seo\Seo;
use Phoundation\Utils\Json;


/**
 * Trait DataEntryOptions
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryOptions
{
    /**
     * Returns the options for this object
     *
     * @return string|null
     */
    public function getOptions(): ?string
    {
        return $this->getSourceColumnValue('string', 'options');
    }


    /**
     * Sets the options for this object
     *
     * @param array|string|null $options
     * @return static
     */
    public function setOptions(array|string|null $options): static
    {
        if (is_array($options)) {
            $options = Json::encode($options);
        }

        return $this->setSourceValue('options', $options);
    }
}
