<?php

declare(strict_types=1);

namespace Phoundation\Core\Locale\Language\Interfaces;

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
interface LanguageInterface
{
    /**
     * Returns the code_639_1 for this language
     *
     * @return string|null
     */
    public function getCode_639_1(): ?string;


    /**
     * Sets the code_639_1 for this language
     *
     * @param string|null $code_639_1
     *
     * @return static
     */
    public function setCode_639_1(?string $code_639_1): static;


    /**
     * Returns the code_639_2_b for this language
     *
     * @return string|null
     */
    public function getCode_639_2_b(): ?string;


    /**
     * Sets the code_639_2_b for this language
     *
     * @param string|null $code_639_2_b
     *
     * @return static
     */
    public function setCode_639_2_b(?string $code_639_2_b): static;


    /**
     * Returns the code_639_2_t for this language
     *
     * @return string|null
     */
    public function getCode_639_2_t(): ?string;


    /**
     * Sets the code_639_2_t for this language
     *
     * @param string|null $code_639_2_t
     *
     * @return static
     */
    public function setCode_639_2_t(?string $code_639_2_t): static;


    /**
     * Returns the code_639_3 for this language
     *
     * @return string|null
     */
    public function getCode_639_3(): ?string;


    /**
     * Sets the code_639_3 for this language
     *
     * @param string|null $code_639_3
     *
     * @return static
     */
    public function setCode_639_3(?string $code_639_3): static;
}
