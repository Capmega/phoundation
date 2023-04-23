<?php

namespace Phoundation\Developer\Incidents;

use Phoundation\Accounts\Users\Users;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Traits\DataEntryDescription;
use Phoundation\Data\DataEntry\Traits\DataEntryDetails;
use Phoundation\Data\DataEntry\Traits\DataEntryException;
use Phoundation\Data\DataEntry\Traits\DataEntryTitle;
use Phoundation\Data\DataEntry\Traits\DataEntryType;
use Phoundation\Data\DataEntry\Traits\DataEntryUrl;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\PostValidator;


/**
 * Incident class
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataEntry
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Developer
 */
class Incident extends DataEntry
{
    use DataEntryDescription;
    use DataEntryDetails;
    use DataEntryException;
    use DataEntryTitle;
    use DataEntryType;
    use DataEntryUrl;



    /**
     * Validates the provider record with the specified validator object
     *
     * @param ArgvValidator|PostValidator|GetValidator $validator
     * @param bool $no_arguments_left
     * @param bool $modify
     * @return array
     */
    protected function validate(ArgvValidator|PostValidator|GetValidator $validator, bool $no_arguments_left = false, bool $modify = false): array
    {
        $data = $validator
            ->select($this->getAlternateValidationField('type'), true)->hasMaxCharacters(16)->isName()
            ->select($this->getAlternateValidationField('url'), true)->hasMaxCharacters(2048)->isUrl()
            ->select($this->getAlternateValidationField('title'), true)->hasMaxCharacters(255)->isPrintable()
            ->select($this->getAlternateValidationField('description'), true)->isOptional()->hasMaxCharacters(16_777_200)->isPrintable()
            ->select($this->getAlternateValidationField('exception'), true)->isOptional()->hasMaxCharacters(16_777_200)->isPrintable()
            ->select($this->getAlternateValidationField('details'), true)->isOptional()->hasMaxCharacters(16_777_200)->isPrintable()
            ->noArgumentsLeft($no_arguments_left)
            ->validate();

        return $data;
    }


    /**
     * Sets the available data keys for this entry
     *
     * @return array
     */
    protected static function getFieldDefinitions(): array
    {
        return [
            'type' => [
                'readonly' => true,
                'label'    => tr('URL'),
                'size'     => 6,
                'maxlength'=> 255,
            ],
            'title' => [
                'readonly' => true,
                'label'    => tr('Title'),
                'size'     => 6,
                'maxlength'=> 255,
            ],
            'url' => [
                'readonly' => true,
                'label'    => tr('URL'),
                'size'     => 12,
                'maxlength'=> 2048,
            ],
            'description' => [
                'readonly' => true,
                'element'  => 'text',
                'size'     => 12,
                'maxlength'=> 16_777_200,
                'label'    => tr('Description'),
            ],
            'exception' => [
                'readonly' => true,
                'element'  => 'text',
                'size'     => 12,
                'maxlength'=> 16_777_200,
                'label'    => tr('Exception'),
            ],
            'data' => [
                'readonly' => true,
                'element'  => 'text',
                'size'     => 12,
                'maxlength'=> 16_777_200,
                'label'    => tr('Data'),
            ],
        ];
    }
}
