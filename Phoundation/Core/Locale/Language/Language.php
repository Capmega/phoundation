<?php

declare(strict_types=1);

namespace Phoundation\Core\Locale\Language;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Traits\DataEntryNameDescription;
use Phoundation\Data\Interfaces\InterfaceDataEntry;
use Phoundation\Web\Http\Html\Enums\InputTypeExtended;


/**
 * Language class
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataEntry
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Core
 */
class Language extends DataEntry
{
    use DataEntryNameDescription;


    /**
     * Language class constructor
     *
     * @param InterfaceDataEntry|string|int|null $identifier
     */
    public function __construct(InterfaceDataEntry|string|int|null $identifier = null)
    {
        static::$entry_name = 'language';
        $this->unique_field = 'code_639_1';

        parent::__construct($identifier);
    }


    /**
     * Returns the table name used by this object
     *
     * @return string
     */
    public static function getTable(): string
    {
        return 'core_languages';
    }


    /**
     * Returns the code_639_1 for this language
     *
     * @return string|null
     */
    public function getCode_639_1(): ?string
    {
        return $this->getDataValue('string', 'code_639_1');
    }


    /**
     * Sets the code_639_1 for this language
     *
     * @param string|null $code_639_1
     * @return static
     */
    public function setCode_639_1(?string $code_639_1): static
    {
        return $this->setDataValue('code_639_1', $code_639_1);
    }


    /**
     * Returns the code_639_2_b for this language
     *
     * @return string|null
     */
    public function getCode_639_2_b(): ?string
    {
        return $this->getDataValue('string', 'code_639_2_b');
    }


    /**
     * Sets the code_639_2_b for this language
     *
     * @param string|null $code_639_2_b
     * @return static
     */
    public function setCode_639_2_b(?string $code_639_2_b): static
    {
        return $this->setDataValue('code_639_2_b', $code_639_2_b);
    }


    /**
     * Returns the code_639_2_t for this language
     *
     * @return string|null
     */
    public function getCode_639_2_t(): ?string
    {
        return $this->getDataValue('string', 'code_639_2_t');
    }


    /**
     * Sets the code_639_2_t for this language
     *
     * @param string|null $code_639_2_t
     * @return static
     */
    public function setCode_639_2_t(?string $code_639_2_t): static
    {
        return $this->setDataValue('code_639_2_t', $code_639_2_t);
    }


    /**
     * Returns the code_639_3 for this language
     *
     * @return string|null
     */
    public function getCode_639_3(): ?string
    {
        return $this->getDataValue('string', 'code_639_3');
    }


    /**
     * Sets the code_639_3 for this language
     *
     * @param string|null $code_639_3
     * @return static
     */
    public function setCode_639_3(?string $code_639_3): static
    {
        return $this->setDataValue('code_639_3', $code_639_3);
    }


    /**
     * Sets the available data keys for this entry
     *
     * @param DefinitionsInterface $field_definitions
     */
    protected function initFieldDefinitions(DefinitionsInterface $field_definitions): void
    {
        $field_definitions
            ->add(Definition::new('name')
                ->setDisabled(true)
                ->setLabel(tr('Name'))
                ->setInputType(InputTypeExtended::name)
                ->setCliField(tr('-n,--name NAME'))
                ->setAutoComplete(true)
                ->setSize(12)
                ->setMaxlength(64)
                ->setHelpText(tr('The name for this language')))
            ->add(Definition::new('seo_name')
                ->setVisible(false)
                ->setReadonly(true))
            ->add(Definition::new('code_639_1')
                ->setDisabled(true)
                ->setInputType(InputTypeExtended::code)
                ->setLabel(tr('ISO 639-1 code'))
                ->setCliField(tr('--iso-691-1 CODE'))
                ->setSize(12)
                ->setMaxlength(2)
                ->setHelpText(tr('The ISO 639-1 code for this language')))
            ->add(Definition::new('code_639_2_t')
                ->setDisabled(true)
                ->setInputType(InputTypeExtended::code)
                ->setLabel(tr('ISO 639-2/T code'))
                ->setCliField(tr('--iso-691-2-t CODE'))
                ->setSize(12)
                ->setMaxlength(3)
                ->setHelpText(tr('The ISO 639-2/T code for this language')))
            ->add(Definition::new('code_639_2_b')
                ->setDisabled(true)
                ->setInputType(InputTypeExtended::code)
                ->setLabel(tr('ISO 639-2/B code'))
                ->setCliField(tr('--iso-691-2-b CODE'))
                ->setSize(12)
                ->setMaxlength(3)
                ->setHelpText(tr('The ISO 639-2/B code for this language')))
            ->add(Definition::new('code_639_3')
                ->setDisabled(true)
                ->setInputType(InputTypeExtended::code)
                ->setLabel(tr('ISO 639-3 code'))
                ->setCliField(tr('--iso-691-2-b CODE'))
                ->setSize(12)
                ->setMaxlength(3)
                ->setHelpText(tr('The ISO 639-3 code for this language')))
            ->add(Definition::new('description')
                ->setOptional(true)
                ->setInputType(InputTypeExtended::description)
                ->setLabel(tr('Description'))
                ->setSize(12)
                ->setMaxlength(65_535)
                ->setHelpText(tr('The description for this right')));
    }
}