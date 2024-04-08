<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Utils\Json;

/**
 * Trait TraitDataEntryTrace
 *
 * This trait contains methods for DataEntry objects that require a trace
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */
trait TraitDataEntryTrace
{
    /**
     * Returns the trace for this object
     *
     * @return array|null
     */
    public function getTrace(): ?array
    {
        return Json::decode($this->getValueTypesafe('array', 'trace'));
    }


    /**
     * Sets the trace for this object
     *
     * @param array|string|null $trace
     *
     * @return static
     */
    public function setTrace(array|string|null $trace): static
    {
        if (is_array($trace)) {
            $trace = Json::encode($trace);
        }

        return $this->setValue('trace', $trace);
    }
}
