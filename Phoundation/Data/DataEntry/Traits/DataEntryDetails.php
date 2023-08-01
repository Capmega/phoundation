<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Core\Log\Log;
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
     * @return array|string|null
     */
    public function getDetails(): array|string|null
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
        return $this->setSourceValue('details', Json::encode($details));
    }
}