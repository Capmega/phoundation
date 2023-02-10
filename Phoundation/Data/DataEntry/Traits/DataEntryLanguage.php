<?php

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Core\Locale\Language\Language;


/**
 * Trait DataEntryLanguage
 *
 * This trait contains methods for DataEntry objects that require a language
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryLanguage
{
    /**
     * Returns the languages_id for this object
     *
     * @return string|null
     */
    public function getLanguagesId(): ?string
    {
        return $this->getDataValue('languages_id');
    }



    /**
     * Sets the languages_id for this object
     *
     * @param string|null $languages_id
     * @return static
     */
    public function setLanguagesId(?string $languages_id): static
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
        $languages_id = $this->getDataValue('languages_id');

        if ($languages_id) {
            return new Language($languages_id);
        }

        return null;
    }



    /**
     * Sets the languages_id for this user
     *
     * @param Language|string|int|null $languages_id
     * @return static
     */
    public function setLanguage(Language|string|int|null $languages_id): static
    {
        if (!is_numeric($languages_id)) {
            $languages_id = Language::get($languages_id);
        }

        if (is_object($languages_id)) {
            $languages_id = $languages_id->getId();
        }

        return $this->setDataValue('languages_id', $languages_id);
    }
}