<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;


/**
 * Trait DataEntryNameDescription
 *
 * This trait contains methods for DataEntry objects that require a name and description
 *
 * @todo Get rid of this trait, its just two use lines
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryNameDescription
{
    use DataEntryName;
    use DataEntryDescription;
}