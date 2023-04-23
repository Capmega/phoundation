<?php

namespace Phoundation\Utils;


/**
 * Class Words
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Class reference
 * @package Phoundation\Utils
 */
class Dictionary
{
    /**
     * The selected language for this dictionary
     *
     * @var string|null $language
     */
    protected ?string $language = null;


    /**
     * Dictionary class constructor
     *
     * @param string|null $language
     */
    public function __construct(?string $language)
    {

    }


    /**
     * Returns true if the specified word exists in this or other languages
     *
     * @param string $word
     * @return bool
     */
    public function wordExists(string $word, ?string $language = null): bool
    {

    }
}