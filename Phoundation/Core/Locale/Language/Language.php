<?php

declare(strict_types=1);

namespace Phoundation\Core\Locale\Language;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntry\Traits\DataEntryNameDescription;
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
     * Returns the table name used by this object
     *
     * @return string
     */
    public static function getTable(): string
    {
        return 'core_languages';
    }


    /**
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getDataEntryName(): string
    {
        return 'language';
    }


    /**
     * Returns the field that is unique for this object
     *
     * @return string|null
     */
    public static function getUniqueField(): ?string
    {
        return 'code_639_1';
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
     * @param DefinitionsInterface $definitions
     */
    protected function initDefinitions(DefinitionsInterface $definitions): void
    {
        $definitions
            ->addDefinition(DefinitionFactory::getName($this)
                ->setDisabled(true)
                ->setHelpText(tr('The name for this language')))
            ->addDefinition(DefinitionFactory::getSeoName($this))
            ->addDefinition(Definition::new($this, 'code_639_1')
                ->setDisabled(true)
                ->setInputType(InputTypeExtended::code)
                ->setLabel(tr('ISO 639-1 code'))
                ->setCliField(tr('--iso-691-1 CODE'))
                ->setSize(12)
                ->setMaxlength(2)
                ->setHelpText(tr('The ISO 639-1 code for this language')))
            ->addDefinition(Definition::new($this, 'code_639_2_t')
                ->setDisabled(true)
                ->setInputType(InputTypeExtended::code)
                ->setLabel(tr('ISO 639-2/T code'))
                ->setCliField(tr('--iso-691-2-t CODE'))
                ->setSize(12)
                ->setMaxlength(3)
                ->setHelpText(tr('The ISO 639-2/T code for this language')))
            ->addDefinition(Definition::new($this, 'code_639_2_b')
                ->setDisabled(true)
                ->setInputType(InputTypeExtended::code)
                ->setLabel(tr('ISO 639-2/B code'))
                ->setCliField(tr('--iso-691-2-b CODE'))
                ->setSize(12)
                ->setMaxlength(3)
                ->setHelpText(tr('The ISO 639-2/B code for this language')))
            ->addDefinition(Definition::new($this, 'code_639_3')
                ->setDisabled(true)
                ->setInputType(InputTypeExtended::code)
                ->setLabel(tr('ISO 639-3 code'))
                ->setCliField(tr('--iso-691-2-b CODE'))
                ->setSize(12)
                ->setMaxlength(3)
                ->setHelpText(tr('The ISO 639-3 code for this language')))
            ->addDefinition(DefinitionFactory::getDescription($this)
                ->setHelpText(tr('The description for this language')));
    }
}