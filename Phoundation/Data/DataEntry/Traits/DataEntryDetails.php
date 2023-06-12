<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Utils\Json;


/**
 * Trait DataEntryDetails
 *
 * This trait contains methods for DataEntry objects that require a details
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryDetails
{
    /**
     * Returns the details for this object
     *
     * @return array|null
     */
    public function getDetails(): ?array
    {
        return Json::decode($this->getDataValue('string', 'details'));
    }


    /**
     * Sets the details for this object
     *
     * @param array|string|null $details
     * @return static
     */
    public function setDetails(array|string|null $details): static
    {
        if (is_array($details)) {
            $details = Json::encode($details);
        }

        return $this->setDataValue('details', $details);
    }
}