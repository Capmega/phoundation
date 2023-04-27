<?php

declare(strict_types=1);

namespace Phoundation\Core\Hooks;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Interfaces\DataEntryFieldDefinitionsInterface;
use Phoundation\Data\Validator\Interfaces\DataValidator;


/**
 * Hook class
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataEntry
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Core
 */
class Hook extends DataEntry
{
    /**
     * Sets the available data keys for this entry
     *
     * @return array
     */
    protected static function getFieldDefinitions(): array
    {
        // TODO: Implement getFieldDefinitions() method.
        return [];
    }

    protected function validate(DataValidator $validator, bool $no_arguments_left, bool $modify): array
    {
        // TODO: Implement validate() method.
    }
}