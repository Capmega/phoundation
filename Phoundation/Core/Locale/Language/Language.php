<?php

declare(strict_types=1);

namespace Phoundation\Core\Locale\Language;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\DataEntryFieldDefinitions;
use Phoundation\Data\DataEntry\Interfaces\DataEntryFieldDefinitionsInterface;
use Phoundation\Data\DataEntry\Traits\DataEntryNameDescription;
use Phoundation\Data\Interfaces\InterfaceDataEntry;


/**
 * Language class
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataEntry
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
     * @return DataEntryFieldDefinitionsInterface
     */
    protected static function setFieldDefinitions(): DataEntryFieldDefinitionsInterface
    {
        return DataEntryFieldDefinitions::new(static::getTable());

        return [
            'name' => [
                'disabled'  => true,
                'label'     => tr('Name'),
                'size'      => 12,
                'maxlength' => 32,
                'help'      => tr('The name for this language'),
            ],
            'seo_name' => [
                'visible'  => false,
            ],
            'code_639_1' => [
                'disabled'  => true,
                'type'      => 'text',
                'label'     => tr('ISO 639-1 code'),
                'size'      => 3,
                'maxlength' => 2,
                'help'      => tr('The code_639_1 code for this language'),
            ],
            'code_639_2_t' => [
                'disabled'  => true,
                'type'      => 'text',
                'label'     => tr('ISO 639-2/T code'),
                'size'      => 3,
                'maxlength' => 3,
                'help'      => tr('The code_639_2_t code for this language'),
            ],
            'code_639_2_b' => [
                'disabled'  => true,
                'type'      => 'text',
                'label'     => tr('ISO 639-2/B code'),
                'size'      => 3,
                'maxlength' => 3,
                'help'      => tr('The code_639_2_b code for this language'),
            ],
            'code_639_3' => [
                'disabled'  => true,
                'type'      => 'text',
                'label'     => tr('ISO 639-3 code'),
                'size'      => 3,
                'maxlength' => 3,
                'help'      => tr('The name for this language'),
            ],
            'description' => [
                'element'   => 'text',
                'label'     => tr('Description'),
                'size'      => 3,
                'maxlength' => 65535,
                'help'      => tr('The description for this language'),
            ]
        ];


//        $data = $validator
//            ->select($this->getAlternateValidationField('name'), true)->isOptional()->hasMaxCharacters(32)->isName()
//            ->select($this->getAlternateValidationField('code_639_1'), true)->isOptional()->hasCharacters(2)->isCode()
//            ->select($this->getAlternateValidationField('code_639_2_t'), true)->isOptional()->hasCharacters(3)->isCode()
//            ->select($this->getAlternateValidationField('code_639_2_b'), true)->isOptional()->hasCharacters(3)->isCode()
//            ->select($this->getAlternateValidationField('code_639_3'), true)->isOptional()->hasCharacters(3)->isCode()
//            ->select($this->getAlternateValidationField('description'), true)->isOptional()->isPrintable()->hasMaxCharacters(65_530)
//            ->noArgumentsLeft($no_arguments_left)
//            ->validate();
//
//        // Ensure the name doesn't exist yet as it is a unique identifier
//        if ($data['name']) {
//            static::notExists($data['name'], $this->getId(), true);
//        }
//
//        return $data;
    }
}