<?php

declare(strict_types=1);

namespace Phoundation\Translator;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\PostValidator;

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
    protected function validate(GetValidator|PostValidator|ArgvValidator $validator, bool $no_arguments_left = false, bool $modify = true): array
    {
        // TODO: Implement validate() method.
    }


    /**
     * Sets the available data keys for this entry
     *
     * @return array
     */
    protected static function getFieldDefinitions(): array
    {
        // TODO: Implement getFieldDefinitions() method.
    }
}