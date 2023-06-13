<?php

namespace Phoundation\Data\DataEntry\Definitions;

use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Http\Html\Enums\InputTypeExtended;


/**
 * Class DefinitionFactory
 *
 * Definition class factory that contains predefined field definitions
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
class DefinitionFactory
{
    public static function new(string $definition_name): DefinitionInterface
    {
        switch ($definition_name) {
            case 'title':
                return Definition::new('title')
                    ->setReadonly(true)
                    ->setLabel('Title')
                    ->setMaxlength(255)
                    ->addValidationFunction(function ($validator) {
                        $validator->hasMaxCharacters(255)->isPrintable();
                    });

            case 'name':
                return Definition::new('name')
                    ->setInputType(InputTypeExtended::name)
                    ->setLabel(tr('Name'))
                    ->setCliField(tr('-n,--name NAME'))
                    ->setMaxlength(64);

            case 'seo_name':
                return Definition::new('seo_name')
                    ->setVisible(true)
                    ->setReadonly(true);

            case 'description':
                return Definition::new('description')
                    ->setOptional(true)
                    ->setInputType(InputTypeExtended::description)
                    ->setMaxlength(65_535)
                    ->setCliField('-d,--description')
                    ->setAutoComplete(true)
                    ->setLabel(tr('Description'));

            default:
                throw new OutOfBoundsException(tr('Unknown pre-defined definition ":definition" specified', [
                    ':definition' => $definition_name
                ]));
        }
    }
}