<?php

/**
 * Language class
 *
 *
 *
 * @see       \Phoundation\Data\DataEntry\DataEntry
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Core
 */


declare(strict_types=1);

namespace Phoundation\Core\Locale\Language;

use Phoundation\Core\Locale\Language\Interfaces\LanguageInterface;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryNameDescription;
use Phoundation\Web\Html\Enums\EnumInputType;


class Language extends DataEntry implements LanguageInterface
{
    use TraitDataEntryNameDescription;

    /**
     * Returns the table name used by this object
     *
     * @return string|null
     */
    public static function getTable(): ?string
    {
        return 'core_languages';
    }


    /**
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getEntryName(): string
    {
        return 'language';
    }


    /**
     * Returns the field that is unique for this object
     *
     * @return string|null
     */
    public static function getUniqueColumn(): ?string
    {
        return 'code_639_1';
    }


    /**
     * Returns the code_639_1 for this language
     *
     * @return string|null
     */
    public function getCode6391(): ?string
    {
        return $this->getTypesafe('string', 'code_639_1');
    }


    /**
     * Sets the code_639_1 for this language
     *
     * @param string|null $code_639_1
     *
     * @return static
     */
    public function setCode6391(?string $code_639_1): static
    {
        return $this->set($code_639_1, 'code_639_1');
    }


    /**
     * Returns the code_639_2_b for this language
     *
     * @return string|null
     */
    public function getCode6392B(): ?string
    {
        return $this->getTypesafe('string', 'code_639_2_b');
    }


    /**
     * Sets the code_639_2_b for this language
     *
     * @param string|null $code_639_2_b
     *
     * @return static
     */
    public function setCode6392B(?string $code_639_2_b): static
    {
        return $this->set($code_639_2_b, 'code_639_2_b');
    }


    /**
     * Returns the code_639_2_t for this language
     *
     * @return string|null
     */
    public function getCode6392T(): ?string
    {
        return $this->getTypesafe('string', 'code_639_2_t');
    }


    /**
     * Sets the code_639_2_t for this language
     *
     * @param string|null $code_639_2_t
     *
     * @return static
     */
    public function setCode6392T(?string $code_639_2_t): static
    {
        return $this->set($code_639_2_t, 'code_639_2_t');
    }


    /**
     * Returns the code_639_3 for this language
     *
     * @return string|null
     */
    public function getCode6393(): ?string
    {
        return $this->getTypesafe('string', 'code_639_3');
    }


    /**
     * Sets the code_639_3 for this language
     *
     * @param string|null $code_639_3
     *
     * @return static
     */
    public function setCode6393(?string $code_639_3): static
    {
        return $this->set($code_639_3, 'code_639_3');
    }


    /**
     * Sets the available data keys for this entry
     *
     * @param DefinitionsInterface $definitions
     */
    protected function setDefinitions(DefinitionsInterface $definitions): void
    {
        $definitions->add(DefinitionFactory::newName($this)
                                           ->setDisabled(true)
                                           ->setHelpText(tr('The name for this language')))
                    ->add(DefinitionFactory::newSeoName($this))
                    ->add(Definition::new($this, 'code_639_1')
                                    ->setDisabled(true)
                                    ->setInputType(EnumInputType::code)
                                    ->setLabel(tr('ISO 639-1 code'))
                                    ->setCliColumn(tr('--iso-691-1 CODE'))
                                    ->setSize(12)
                                    ->setMaxlength(2)
                                    ->setHelpText(tr('The ISO 639-1 code for this language')))
                    ->add(Definition::new($this, 'code_639_2_t')
                                    ->setDisabled(true)
                                    ->setInputType(EnumInputType::code)
                                    ->setLabel(tr('ISO 639-2/T code'))
                                    ->setCliColumn(tr('--iso-691-2-t CODE'))
                                    ->setSize(12)
                                    ->setMaxlength(3)
                                    ->setHelpText(tr('The ISO 639-2/T code for this language')))
                    ->add(Definition::new($this, 'code_639_2_b')
                                    ->setDisabled(true)
                                    ->setInputType(EnumInputType::code)
                                    ->setLabel(tr('ISO 639-2/B code'))
                                    ->setCliColumn(tr('--iso-691-2-b CODE'))
                                    ->setSize(12)
                                    ->setMaxlength(3)
                                    ->setHelpText(tr('The ISO 639-2/B code for this language')))
                    ->add(Definition::new($this, 'code_639_3')
                                    ->setDisabled(true)
                                    ->setInputType(EnumInputType::code)
                                    ->setLabel(tr('ISO 639-3 code'))
                                    ->setCliColumn(tr('--iso-691-2-b CODE'))
                                    ->setSize(12)
                                    ->setMaxlength(3)
                                    ->setHelpText(tr('The ISO 639-3 code for this language')))
                    ->add(DefinitionFactory::newDescription($this)
                                           ->setHelpText(tr('The description for this language')));
    }
}
