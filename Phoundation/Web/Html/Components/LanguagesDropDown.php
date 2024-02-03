<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components;

use Phoundation\Core\Locale\Language\Languages;
use Phoundation\Web\Http\UrlBuilder;
use Stringable;


/**
 * LanguagesDropDown class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class LanguagesDropDown extends ElementsBlock
{
    /**
     * The list of languages
     *
     * @var Languages|null $languages
     */
    protected ?Languages $languages = null;

    /**
     * Contains the URL for the settings page
     *
     * @var Stringable|string|null $settings_url
     */
    protected Stringable|string|null $settings_url = null;


    /**
     * Returns the languages object
     *
     * @return Languages|null
     */
    public function getLanguages(): ?Languages
    {
        return $this->languages;
    }


    /**
     * Sets the languages object
     *
     * @param Languages|null $languages
     * @return static
     */
    public function setLanguages(?Languages $languages): static
    {
        $this->languages = $languages;
        return $this;
    }


    /**
     * Returns the languages page URL
     *
     * @return Stringable|string|null
     */
    public function getSettingsUrl(): Stringable|string|null
    {
        return $this->settings_url;
    }


    /**
     * Sets the settings page URL
     *
     * @param Stringable|string|null $settings_url
     * @return static
     */
    public function setSettingsUrl(Stringable|string|null $settings_url): static
    {
        $this->settings_url = UrlBuilder::getWww($settings_url);
        return $this;
    }
}