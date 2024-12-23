<?php

declare(strict_types=1);

namespace Phoundation\Core\Locale\Language\Interfaces;

interface LanguageInterface
{
    /**
     * Returns the code_639_1 for this language
     *
     * @return string|null
     */
    public function getCode6391(): ?string;


    /**
     * Sets the code_639_1 for this language
     *
     * @param string|null $code_639_1
     *
     * @return static
     */
    public function setCode6391(?string $code_639_1): static;


    /**
     * Returns the code_639_2_b for this language
     *
     * @return string|null
     */
    public function getCode6392B(): ?string;


    /**
     * Sets the code_639_2_b for this language
     *
     * @param string|null $code_639_2_b
     *
     * @return static
     */
    public function setCode6392B(?string $code_639_2_b): static;


    /**
     * Returns the code_639_2_t for this language
     *
     * @return string|null
     */
    public function getCode6392T(): ?string;


    /**
     * Sets the code_639_2_t for this language
     *
     * @param string|null $code_639_2_t
     *
     * @return static
     */
    public function setCode6392T(?string $code_639_2_t): static;


    /**
     * Returns the code_639_3 for this language
     *
     * @return string|null
     */
    public function getCode6393(): ?string;


    /**
     * Sets the code_639_3 for this language
     *
     * @param string|null $code_639_3
     *
     * @return static
     */
    public function setCode6393(?string $code_639_3): static;
}
