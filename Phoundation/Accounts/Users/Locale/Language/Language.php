<?php

/**
 * Language class
 *
 *
 *
 * @see       \Phoundation\Data\DataEntries\DataEntry
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

namespace Phoundation\Accounts\Users\Locale\Language;

use Phoundation\Accounts\Users\Locale\Language\Interfaces\LanguageInterface;
use Phoundation\Data\DataEntries\DataEntry;
use Phoundation\Data\DataEntries\Definitions\Definition;
use Phoundation\Data\DataEntries\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryNameDescription;
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
     * @param DefinitionsInterface $_definitions
     *
     * @return static
     */
    protected function setDefinitionsObject(DefinitionsInterface $_definitions): static
    {
        $_definitions->add(DefinitionFactory::newName()
                                             ->setDisabled(true)
                                             ->setHelpText(tr('The name for this language')))

                      ->add(DefinitionFactory::newSeoName())

                      ->add(Definition::new('code_639_1')
                                    ->setDisabled(true)
                                    ->setInputType(EnumInputType::code)
                                    ->setLabel(tr('ISO 639-1 code'))
                                    ->setCliColumn(tr('--iso-691-1 CODE'))
                                    ->setSize(12)
                                    ->setMaxLength(2)
                                    ->setHelpText(tr('The ISO 639-1 code for this language')))

                    ->add(Definition::new('code_639_2_t')
                                    ->setDisabled(true)
                                    ->setInputType(EnumInputType::code)
                                    ->setLabel(tr('ISO 639-2/T code'))
                                    ->setCliColumn(tr('--iso-691-2-t CODE'))
                                    ->setSize(12)
                                    ->setMaxLength(3)
                                    ->setHelpText(tr('The ISO 639-2/T code for this language')))

                    ->add(Definition::new('code_639_2_b')
                                    ->setDisabled(true)
                                    ->setInputType(EnumInputType::code)
                                    ->setLabel(tr('ISO 639-2/B code'))
                                    ->setCliColumn(tr('--iso-691-2-b CODE'))
                                    ->setSize(12)
                                    ->setMaxLength(3)
                                    ->setHelpText(tr('The ISO 639-2/B code for this language')))

                    ->add(Definition::new('code_639_3')
                                    ->setDisabled(true)
                                    ->setInputType(EnumInputType::code)
                                    ->setLabel(tr('ISO 639-3 code'))
                                    ->setCliColumn(tr('--iso-691-2-b CODE'))
                                    ->setSize(12)
                                    ->setMaxLength(3)
                                    ->setHelpText(tr('The ISO 639-3 code for this language')))

                    ->add(DefinitionFactory::newDescription()
                                           ->setHelpText(tr('The description for this language')));

        return $this;
    }
}
