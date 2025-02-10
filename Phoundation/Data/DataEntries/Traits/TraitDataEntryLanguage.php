<?php

/**
 * Trait TraitDataEntryLanguage
 *
 * This trait contains methods for DataEntry objects that require a language
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Traits;

use Phoundation\Core\Locale\Language\Interfaces\LanguageInterface;
use Phoundation\Core\Locale\Language\Language;


trait TraitDataEntryLanguage
{
    /**
     * Setup virtual configuration for Languages
     *
     * @return static
     */
    protected function addVirtualConfigurationLanguages(): static
    {
        return $this->addVirtualConfiguration('languages', Language::class, [
            'id',
            'code',
            'name'
        ]);
    }


    /**
     * Returns the languages_id column
     *
     * @return int|null
     */
    public function getLanguagesId(): ?int
    {
        return $this->getVirtualData('languages', 'int', 'id');
    }


    /**
     * Sets the languages_id column
     *
     * @param int|null $id
     * @return static
     */
    public function setLanguagesId(?int $id): static
    {
        return $this->setVirtualData('languages', $id, 'id');
    }


    /**
     * Returns the languages_code column
     *
     * @return string|null
     */
    public function getLanguagesCode(): ?string
    {
        return $this->getVirtualData('languages', 'string', 'code');
    }


    /**
     * Sets the languages_code column
     *
     * @param string|null $code
     * @return static
     */
    public function setLanguagesCode(?string $code): static
    {
        return $this->setVirtualData('languages', $code, 'code');
    }


    /**
     * Returns the languages_name column
     *
     * @return string|null
     */
    public function getLanguagesName(): ?string
    {
        return $this->getVirtualData('languages', 'string', 'name');
    }


    /**
     * Sets the languages_name column
     *
     * @param string|null $name
     * @return static
     */
    public function setLanguagesName(?string $name): static
    {
        return $this->setVirtualData('languages', $name, 'name');
    }


    /**
     * Returns the Language Object
     *
     * @return LanguageInterface|null
     */
    public function getLanguageObject(): ?LanguageInterface
    {
        return $this->getVirtualObject('languages');
    }


    /**
     * Returns the languages_id for this user
     *
     * @param LanguageInterface|null $o_object
     *
     * @return static
     */
    public function setLanguageObject(?LanguageInterface $o_object): static
    {
        return $this->setVirtualObject('languages', $o_object);
    }
}
