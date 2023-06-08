<?php

declare(strict_types=1);

namespace Phoundation\Translator;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Interfaces\DataEntryFieldDefinitionsInterface;
use Phoundation\Data\Validator\Interfaces\DataValidator;

/**
 * Class Translation
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataEntry
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Translator
 */
class Translation extends DataEntry
{
    /**
     * @inheritDoc
     */
    protected function validate(DataValidator $validator, bool $no_arguments_left, bool $modify): array
    {
        // TODO: Implement validate() method.
    }


    /**
     * Sets the available data keys for this entry
     *
     * @return DataEntryFieldDefinitionsInterface
     */
    protected static function setFieldDefinitions(): DataEntryFieldDefinitionsInterface
    {
        // TODO: Implement getFieldDefinitions() method.
        return [];
    }
}