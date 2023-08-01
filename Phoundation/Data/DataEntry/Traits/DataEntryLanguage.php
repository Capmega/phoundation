<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Core\Locale\Language\Language;
use Phoundation\Exception\OutOfBoundsException;


/**
 * Trait DataEntryLanguage
 *
 * This trait contains methods for DataEntry objects that require a language
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryLanguage
{
    /**
     * Returns the languages_id for this object
     *
     * @return int|null
     */
    public function getLanguagesId(): ?int
    {
        return $this->getDataValue('int', 'languages_id');
    }


    /**
     * Sets the languages_id for this object
     *
     * @param int|null $languages_id
     * @return static
     */
    public function setLanguagesId(?int $languages_id): static
    {
        return $this->setDataValue('languages_id', $languages_id);
    }


    /**
     * Returns the languages_id for this user
     *
     * @return Language|null
     */
    public function getLanguage(): ?Language
    {
        $languages_id = $this->getDataValue('int', 'languages_id');

        if ($languages_id) {
            return new Language($languages_id);
        }

        return null;
    }


    /**
     * Returns the languages_name for this user
     *
     * @return string|null
     */
    public function getLanguagesName(): ?string
    {
        return $this->getDataValue('string', 'languages_name');
    }


    /**
     * Sets the languages_name for this user
     *
     * @param string|null $languages_name
     * @return static
     */
    public function setLanguagesName(?string $languages_name): static
    {
        return $this->setDataValue('languages_name', $languages_name);
    }


    /**
     * Returns the languages_code for this user
     *
     * @return string|null
     */
    public function getLanguagesCode(): ?string
    {
        return $this->getDataValue('string', 'languages_code');
    }


    /**
     * Sets the languages_code for this user
     *
     * @param string|null $languages_code
     * @return static
     */
    public function setLanguagesCode(?string $languages_code): static
    {
        return $this->setDataValue('languages_code', $languages_code);
    }
}