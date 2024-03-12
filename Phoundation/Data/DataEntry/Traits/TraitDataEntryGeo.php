<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;


/**
 * Trait TraitDataEntryGeo
 *
 * This trait contains methods for DataEntry objects that require GEO data (country, state and city)
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait TraitDataEntryGeo
{
    use TraitDataEntryCountry;
    use TraitDataEntryState;
    use TraitDataEntryCity;
}